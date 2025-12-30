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
     * API Version
     *
     * @var string
     */
    private $api_version = '2023-06-01';

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
}
