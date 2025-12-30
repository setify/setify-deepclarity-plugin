<?php

/**
 * Claude API Integration
 *
 * @package DeepClarity
 */

namespace DeepClarity;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Claude API Class
 */
class Claude
{
    /**
     * Instance
     *
     * @var Claude
     */
    private static $instance = null;

    /**
     * API Endpoint
     *
     * @var string
     */
    private $api_endpoint = 'https://api.anthropic.com/v1/messages';

    /**
     * Files API Endpoint
     *
     * @var string
     */
    private $files_endpoint = 'https://api.anthropic.com/v1/files';

    /**
     * API Version
     *
     * @var string
     */
    private $api_version = '2023-06-01';

    /**
     * Files API Beta Version
     *
     * @var string
     */
    private $files_beta_version = 'files-api-2025-04-14';

    /**
     * Get instance
     *
     * @return Claude
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_dc_test_claude_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_dc_upload_claude_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_dc_list_claude_files', array($this, 'ajax_list_files'));
        add_action('wp_ajax_dc_delete_claude_file', array($this, 'ajax_delete_file'));
    }

    /**
     * Get API Key from ACF options
     *
     * @return string|null
     */
    private function get_api_key()
    {
        if (function_exists('get_field')) {
            return get_field('claude_api_key', 'option');
        }
        return null;
    }

    /**
     * Get Model from ACF options
     *
     * @return string
     */
    private function get_model()
    {
        if (function_exists('get_field')) {
            $model = get_field('claude_model', 'option');
            if (!empty($model)) {
                return $model;
            }
        }
        return 'claude-sonnet-4-20250514';
    }

    /**
     * Get System Prompt from ACF options
     *
     * @return string|null
     */
    private function get_system_prompt()
    {
        if (function_exists('get_field')) {
            return get_field('claude_system_prompt', 'option');
        }
        return null;
    }

    /**
     * Get Max Tokens from ACF options
     *
     * @return int
     */
    private function get_max_tokens()
    {
        if (function_exists('get_field')) {
            $max_tokens = get_field('claude_max_tokens', 'option');
            if (!empty($max_tokens) && is_numeric($max_tokens)) {
                return (int) $max_tokens;
            }
        }
        return 4096;
    }

    /**
     * Send prompt to Claude API
     *
     * @param string $prompt User prompt
     * @param array  $options Optional parameters
     * @return array|WP_Error Response array or error
     */
    public function send_prompt($prompt, $options = array())
    {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new \WP_Error('no_api_key', __('Claude API Key ist nicht konfiguriert.', 'deep-clarity'));
        }

        $model = isset($options['model']) ? $options['model'] : $this->get_model();
        $max_tokens = isset($options['max_tokens']) ? $options['max_tokens'] : $this->get_max_tokens();
        $system_prompt = isset($options['system']) ? $options['system'] : $this->get_system_prompt();

        $body = array(
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
        );

        // Add system prompt if set
        if (!empty($system_prompt)) {
            $body['system'] = $system_prompt;
        }

        // Add temperature if set
        if (isset($options['temperature'])) {
            $body['temperature'] = floatval($options['temperature']);
        }

        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => $this->api_version,
        );

        // Add beta header for extended features if needed
        if (isset($options['beta'])) {
            $headers['anthropic-beta'] = $options['beta'];
        }

        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
            return new \WP_Error('api_error', $error_message, array('status' => $response_code));
        }

        return $data;
    }

    /**
     * Get text content from API response
     *
     * @param array $response API response
     * @return string|null Text content or null
     */
    public function get_response_text($response)
    {
        if (is_wp_error($response)) {
            return null;
        }

        if (isset($response['content']) && is_array($response['content'])) {
            foreach ($response['content'] as $block) {
                if ($block['type'] === 'text') {
                    return $block['text'];
                }
            }
        }

        return null;
    }

    /**
     * Simple method to send prompt and get text response
     *
     * @param string $prompt User prompt
     * @param array  $options Optional parameters
     * @return string|WP_Error Text response or error
     */
    public function ask($prompt, $options = array())
    {
        $response = $this->send_prompt($prompt, $options);

        if (is_wp_error($response)) {
            return $response;
        }

        $text = $this->get_response_text($response);

        if ($text === null) {
            return new \WP_Error('no_response', __('Keine Antwort von Claude erhalten.', 'deep-clarity'));
        }

        return $text;
    }

    /**
     * Upload file to Claude Files API
     *
     * @param string $file_path Path to the file
     * @param string $original_filename Optional original filename (useful for temp files)
     * @return array|WP_Error Response array or error
     */
    public function upload_file($file_path, $original_filename = null)
    {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new \WP_Error('no_api_key', __('Claude API Key ist nicht konfiguriert.', 'deep-clarity'));
        }

        if (!file_exists($file_path)) {
            return new \WP_Error('file_not_found', __('Datei nicht gefunden.', 'deep-clarity'));
        }

        // Use original filename if provided, otherwise use basename
        $file_name = $original_filename ? $original_filename : basename($file_path);
        $file_type = mime_content_type($file_path);

        // Use cURL directly for proper multipart file upload
        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->files_endpoint,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => array(
                'x-api-key: ' . $api_key,
                'anthropic-version: ' . $this->api_version,
                'anthropic-beta: ' . $this->files_beta_version,
            ),
            CURLOPT_POSTFIELDS => array(
                'file' => new \CURLFile($file_path, $file_type, $file_name),
            ),
        ));

        $response_body = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return new \WP_Error('curl_error', $curl_error);
        }

        $data = json_decode($response_body, true);

        if ($response_code !== 200 && $response_code !== 201) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error (HTTP ' . $response_code . ')';
            return new \WP_Error('api_error', $error_message, array('status' => $response_code));
        }

        return $data;
    }

    /**
     * List files from Claude Files API
     *
     * @return array|WP_Error Response array or error
     */
    public function list_files()
    {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new \WP_Error('no_api_key', __('Claude API Key ist nicht konfiguriert.', 'deep-clarity'));
        }

        $response = wp_remote_get($this->files_endpoint, array(
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => $this->api_version,
                'anthropic-beta' => $this->files_beta_version,
            ),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
            return new \WP_Error('api_error', $error_message, array('status' => $response_code));
        }

        return $data;
    }

    /**
     * Delete file from Claude Files API
     *
     * @param string $file_id File ID to delete
     * @return array|WP_Error Response array or error
     */
    public function delete_file($file_id)
    {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new \WP_Error('no_api_key', __('Claude API Key ist nicht konfiguriert.', 'deep-clarity'));
        }

        $response = wp_remote_request($this->files_endpoint . '/' . $file_id, array(
            'method' => 'DELETE',
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => $this->api_version,
                'anthropic-beta' => $this->files_beta_version,
            ),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200 && $response_code !== 204) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
            return new \WP_Error('api_error', $error_message, array('status' => $response_code));
        }

        return $data;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'deep-clarity',
            __('Claude API Test', 'deep-clarity'),
            __('Claude API', 'deep-clarity'),
            'manage_options',
            'deep-clarity-claude',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        $api_key = $this->get_api_key();
        $model = $this->get_model();
        $has_api_key = !empty($api_key);
        ?>
        <div class="wrap">
            <h1><?php _e('Claude API Test', 'deep-clarity'); ?></h1>

            <?php if (!$has_api_key) : ?>
                <div class="notice notice-error">
                    <p><?php _e('Claude API Key ist nicht konfiguriert. Bitte in den ACF Optionen hinterlegen.', 'deep-clarity'); ?></p>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 800px; padding: 20px;">
                <h2><?php _e('API Verbindung testen', 'deep-clarity'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Status', 'deep-clarity'); ?></th>
                        <td>
                            <?php if ($has_api_key) : ?>
                                <span style="color: green;">✓ <?php _e('API Key konfiguriert', 'deep-clarity'); ?></span>
                            <?php else : ?>
                                <span style="color: red;">✗ <?php _e('API Key fehlt', 'deep-clarity'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Modell', 'deep-clarity'); ?></th>
                        <td><code><?php echo esc_html($model); ?></code></td>
                    </tr>
                </table>

                <h3><?php _e('Prompt senden', 'deep-clarity'); ?></h3>

                <form id="dc-claude-test-form">
                    <p>
                        <label for="dc-claude-prompt"><strong><?php _e('Prompt:', 'deep-clarity'); ?></strong></label>
                    </p>
                    <p>
                        <textarea
                            id="dc-claude-prompt"
                            name="prompt"
                            rows="5"
                            style="width: 100%; max-width: 100%;"
                            placeholder="<?php esc_attr_e('Gib hier deinen Prompt ein...', 'deep-clarity'); ?>"
                        ></textarea>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary" <?php echo !$has_api_key ? 'disabled' : ''; ?>>
                            <?php _e('Absenden', 'deep-clarity'); ?>
                        </button>
                        <span id="dc-claude-loading" style="display: none; margin-left: 10px;">
                            <span class="spinner is-active" style="float: none;"></span>
                            <?php _e('Warte auf Antwort...', 'deep-clarity'); ?>
                        </span>
                    </p>
                </form>

                <div id="dc-claude-response" style="display: none; margin-top: 20px;">
                    <h3><?php _e('Antwort:', 'deep-clarity'); ?></h3>
                    <div id="dc-claude-response-content" style="background: #f5f5f5; padding: 15px; border-radius: 4px; white-space: pre-wrap; font-family: monospace;"></div>

                    <div id="dc-claude-meta" style="margin-top: 10px; color: #666; font-size: 12px;"></div>
                </div>

                <div id="dc-claude-error" style="display: none; margin-top: 20px;">
                    <div class="notice notice-error" style="margin: 0;">
                        <p id="dc-claude-error-message"></p>
                    </div>
                </div>
            </div>

            <!-- File Upload Section -->
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Dateien verwalten', 'deep-clarity'); ?></h2>
                <p class="description"><?php _e('Lade Dateien in deinen Claude Workspace hoch (PDFs, Textdateien, etc.)', 'deep-clarity'); ?></p>

                <h3><?php _e('Datei hochladen', 'deep-clarity'); ?></h3>
                <form id="dc-claude-upload-form" enctype="multipart/form-data">
                    <p>
                        <input type="file" id="dc-claude-file" name="file" accept=".pdf,.txt,.md,.csv,.json,.xml,.html,.css,.js,.php,.py" <?php echo !$has_api_key ? 'disabled' : ''; ?> />
                    </p>
                    <p>
                        <button type="submit" class="button button-secondary" <?php echo !$has_api_key ? 'disabled' : ''; ?>>
                            <?php _e('Hochladen', 'deep-clarity'); ?>
                        </button>
                        <span id="dc-claude-upload-loading" style="display: none; margin-left: 10px;">
                            <span class="spinner is-active" style="float: none;"></span>
                            <?php _e('Wird hochgeladen...', 'deep-clarity'); ?>
                        </span>
                    </p>
                </form>

                <div id="dc-claude-upload-success" style="display: none; margin-top: 10px;">
                    <div class="notice notice-success" style="margin: 0;">
                        <p id="dc-claude-upload-success-message"></p>
                    </div>
                </div>

                <div id="dc-claude-upload-error" style="display: none; margin-top: 10px;">
                    <div class="notice notice-error" style="margin: 0;">
                        <p id="dc-claude-upload-error-message"></p>
                    </div>
                </div>

                <hr style="margin: 20px 0;">

                <h3><?php _e('Hochgeladene Dateien', 'deep-clarity'); ?></h3>
                <p>
                    <button type="button" id="dc-claude-refresh-files" class="button" <?php echo !$has_api_key ? 'disabled' : ''; ?>>
                        <?php _e('Dateien laden', 'deep-clarity'); ?>
                    </button>
                    <span id="dc-claude-files-loading" style="display: none; margin-left: 10px;">
                        <span class="spinner is-active" style="float: none;"></span>
                    </span>
                </p>

                <div id="dc-claude-files-list" style="margin-top: 15px;">
                    <p class="description"><?php _e('Klicke auf "Dateien laden" um die Liste anzuzeigen.', 'deep-clarity'); ?></p>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#dc-claude-test-form').on('submit', function(e) {
                e.preventDefault();

                var prompt = $('#dc-claude-prompt').val().trim();
                if (!prompt) {
                    alert('<?php echo esc_js(__('Bitte einen Prompt eingeben.', 'deep-clarity')); ?>');
                    return;
                }

                $('#dc-claude-loading').show();
                $('#dc-claude-response').hide();
                $('#dc-claude-error').hide();
                $('button[type="submit"]').prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dc_test_claude_api',
                        prompt: prompt,
                        nonce: '<?php echo wp_create_nonce('dc_claude_test'); ?>'
                    },
                    success: function(response) {
                        $('#dc-claude-loading').hide();
                        $('button[type="submit"]').prop('disabled', false);

                        if (response.success) {
                            $('#dc-claude-response-content').text(response.data.text);
                            $('#dc-claude-meta').html(
                                'Model: <code>' + response.data.model + '</code> | ' +
                                'Input Tokens: ' + response.data.input_tokens + ' | ' +
                                'Output Tokens: ' + response.data.output_tokens
                            );
                            $('#dc-claude-response').show();
                        } else {
                            $('#dc-claude-error-message').text(response.data.message || 'Unbekannter Fehler');
                            $('#dc-claude-error').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#dc-claude-loading').hide();
                        $('button[type="submit"]').prop('disabled', false);
                        $('#dc-claude-error-message').text('AJAX Fehler: ' + error);
                        $('#dc-claude-error').show();
                    }
                });
            });

            // File Upload
            $('#dc-claude-upload-form').on('submit', function(e) {
                e.preventDefault();

                var fileInput = $('#dc-claude-file')[0];
                if (!fileInput.files.length) {
                    alert('<?php echo esc_js(__('Bitte eine Datei auswählen.', 'deep-clarity')); ?>');
                    return;
                }

                var formData = new FormData();
                formData.append('action', 'dc_upload_claude_file');
                formData.append('nonce', '<?php echo wp_create_nonce('dc_claude_upload'); ?>');
                formData.append('file', fileInput.files[0]);

                $('#dc-claude-upload-loading').show();
                $('#dc-claude-upload-success').hide();
                $('#dc-claude-upload-error').hide();
                $('#dc-claude-upload-form button').prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#dc-claude-upload-loading').hide();
                        $('#dc-claude-upload-form button').prop('disabled', false);

                        if (response.success) {
                            $('#dc-claude-upload-success-message').text('Datei erfolgreich hochgeladen: ' + response.data.filename + ' (ID: ' + response.data.id + ')');
                            $('#dc-claude-upload-success').show();
                            $('#dc-claude-file').val('');
                            // Refresh file list
                            $('#dc-claude-refresh-files').click();
                        } else {
                            $('#dc-claude-upload-error-message').text(response.data.message || 'Unbekannter Fehler');
                            $('#dc-claude-upload-error').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#dc-claude-upload-loading').hide();
                        $('#dc-claude-upload-form button').prop('disabled', false);
                        $('#dc-claude-upload-error-message').text('AJAX Fehler: ' + error);
                        $('#dc-claude-upload-error').show();
                    }
                });
            });

            // Refresh Files List
            $('#dc-claude-refresh-files').on('click', function() {
                $('#dc-claude-files-loading').show();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dc_list_claude_files',
                        nonce: '<?php echo wp_create_nonce('dc_claude_files'); ?>'
                    },
                    success: function(response) {
                        $('#dc-claude-files-loading').hide();

                        if (response.success && response.data.files) {
                            var files = response.data.files;
                            if (files.length === 0) {
                                $('#dc-claude-files-list').html('<p class="description"><?php echo esc_js(__('Keine Dateien vorhanden.', 'deep-clarity')); ?></p>');
                            } else {
                                var html = '<table class="widefat striped"><thead><tr><th>Dateiname</th><th>Typ</th><th>Größe</th><th>ID</th><th>Aktion</th></tr></thead><tbody>';
                                files.forEach(function(file) {
                                    var size = file.size ? (file.size / 1024).toFixed(2) + ' KB' : '-';
                                    html += '<tr>';
                                    html += '<td>' + (file.filename || '-') + '</td>';
                                    html += '<td>' + (file.mime_type || '-') + '</td>';
                                    html += '<td>' + size + '</td>';
                                    html += '<td><code style="font-size: 11px;">' + file.id + '</code></td>';
                                    html += '<td><button type="button" class="button button-small dc-delete-file" data-id="' + file.id + '">Löschen</button></td>';
                                    html += '</tr>';
                                });
                                html += '</tbody></table>';
                                $('#dc-claude-files-list').html(html);
                            }
                        } else {
                            $('#dc-claude-files-list').html('<p class="description" style="color: red;">' + (response.data.message || 'Fehler beim Laden') + '</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#dc-claude-files-loading').hide();
                        $('#dc-claude-files-list').html('<p class="description" style="color: red;">AJAX Fehler: ' + error + '</p>');
                    }
                });
            });

            // Delete File
            $(document).on('click', '.dc-delete-file', function() {
                var fileId = $(this).data('id');
                if (!confirm('<?php echo esc_js(__('Datei wirklich löschen?', 'deep-clarity')); ?>')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dc_delete_claude_file',
                        nonce: '<?php echo wp_create_nonce('dc_claude_delete'); ?>',
                        file_id: fileId
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.closest('tr').fadeOut(function() { $(this).remove(); });
                        } else {
                            alert(response.data.message || 'Fehler beim Löschen');
                            $btn.prop('disabled', false).text('Löschen');
                        }
                    },
                    error: function() {
                        alert('AJAX Fehler');
                        $btn.prop('disabled', false).text('Löschen');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for testing API
     */
    public function ajax_test_api()
    {
        check_ajax_referer('dc_claude_test', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'deep-clarity')));
        }

        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';

        if (empty($prompt)) {
            wp_send_json_error(array('message' => __('Kein Prompt angegeben.', 'deep-clarity')));
        }

        $response = $this->send_prompt($prompt);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        $text = $this->get_response_text($response);

        wp_send_json_success(array(
            'text' => $text,
            'model' => isset($response['model']) ? $response['model'] : '',
            'input_tokens' => isset($response['usage']['input_tokens']) ? $response['usage']['input_tokens'] : 0,
            'output_tokens' => isset($response['usage']['output_tokens']) ? $response['usage']['output_tokens'] : 0,
        ));
    }

    /**
     * AJAX handler for uploading file
     */
    public function ajax_upload_file()
    {
        check_ajax_referer('dc_claude_upload', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'deep-clarity')));
        }

        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => __('Keine Datei hochgeladen.', 'deep-clarity')));
        }

        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Fehler beim Datei-Upload.', 'deep-clarity')));
        }

        // Pass original filename to preserve it in Claude
        $response = $this->upload_file($file['tmp_name'], $file['name']);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        wp_send_json_success(array(
            'id' => isset($response['id']) ? $response['id'] : '',
            'filename' => isset($response['filename']) ? $response['filename'] : $file['name'],
        ));
    }

    /**
     * AJAX handler for listing files
     */
    public function ajax_list_files()
    {
        check_ajax_referer('dc_claude_files', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'deep-clarity')));
        }

        $response = $this->list_files();

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        $files = isset($response['data']) ? $response['data'] : array();

        wp_send_json_success(array('files' => $files));
    }

    /**
     * AJAX handler for deleting file
     */
    public function ajax_delete_file()
    {
        check_ajax_referer('dc_claude_delete', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'deep-clarity')));
        }

        $file_id = isset($_POST['file_id']) ? sanitize_text_field($_POST['file_id']) : '';

        if (empty($file_id)) {
            wp_send_json_error(array('message' => __('Keine Datei-ID angegeben.', 'deep-clarity')));
        }

        $response = $this->delete_file($file_id);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        wp_send_json_success(array('deleted' => true));
    }
}
