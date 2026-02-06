<?php

/**
 * API class for external integrations (n8n, webhooks)
 *
 * @package DeepClarity
 */

namespace DeepClarity;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Class
 */
class API
{
    /**
     * Instance
     *
     * @var API
     */
    private static $instance = null;

    /**
     * Webhook URLs for client updates (production and test)
     *
     * @var array
     */
    private $webhook_client_update_urls = array(
        'https://n8n.setify.de/webhook/dc_update_client',
        'https://n8n.setify.de/webhook-test/dc_update_client',
    );

    /**
     * Webhook URL for session analysis
     *
     * @var string
     */
    private $webhook_session_analysis_url = 'https://n8n.setify.de/webhook-test/dc_session_analysis';

    /**
     * Webhook URLs for dossier analysis (production and test)
     *
     * @var array
     */
    private $webhook_dossier_urls = array(
        // 'https://n8n.setify.de/webhook/dc_dossier_analysis',
        'https://n8n.setify.de/webhook-test/dc_dossier_analysis',
    );

    /**
     * API Key for authentication
     *
     * @var string
     */
    private $api_key = 'dc_api_k7Qm9xR4vN2pL8wY3jF6hT1cB5sA0eD';

    /**
     * Flag to skip webhook when update comes from REST API
     *
     * @var bool
     */
    private static $skip_webhook = false;

    /**
     * Get instance
     *
     * @return API
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
        // Hook into client post save (create and update)
        add_action('save_post_client', array($this, 'on_client_save'), 20, 3);

        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Triggered when a client post is created or updated
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @param bool     $update  Whether this is an existing post being updated.
     */
    public function on_client_save($post_id, $post, $update)
    {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Skip revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Skip if not published
        if ($post->post_status !== 'publish') {
            return;
        }

        // Prevent infinite loops
        if (did_action('save_post_client') > 1) {
            return;
        }

        // Skip webhook if update comes from REST API
        if (self::$skip_webhook) {
            return;
        }

        // Get ACF fields
        $client_firstname = '';
        $client_lastname = '';
        $client_email = '';

        if (function_exists('get_field')) {
            $client_firstname = get_field('client_firstname', $post_id) ?: '';
            $client_lastname = get_field('client_lastname', $post_id) ?: '';
            $client_email = get_field('client_email', $post_id) ?: '';
        }

        // Prepare webhook data
        $data = array(
            'post_id'          => $post_id,
            'client_firstname' => $client_firstname,
            'client_lastname'  => $client_lastname,
            'client_email'     => $client_email,
            'is_update'        => $update,
        );

        // Send webhook to all configured URLs (production + test)
        foreach ($this->webhook_client_update_urls as $url) {
            $this->send_webhook($url, $data);
        }
    }

    /**
     * Send webhook request via POST with API key authentication
     *
     * @param string $url         Webhook URL.
     * @param array  $data        Data to send.
     * @param bool   $return_body Whether to return the response body (for synchronous n8n responses).
     * @return bool|array|\WP_Error True on success (or response array if $return_body), WP_Error on failure.
     */
    private function send_webhook($url, $data, $return_body = false)
    {
        // Use hardcoded API key
        $api_key = $this->api_key;

        $headers = array(
            'Content-Type' => 'application/json',
        );

        // Add API key header if configured
        if (!empty($api_key)) {
            $headers['X-API-Key'] = $api_key;
        }

        $response = wp_remote_post(
            $url,
            array(
                'timeout'   => 60, // Increased timeout for synchronous n8n workflows
                'sslverify' => true,
                'headers'   => $headers,
                'body'      => wp_json_encode($data),
            )
        );

        if (is_wp_error($response)) {
            error_log('DeepClarity API Webhook Error: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Parse JSON response body
        $parsed_body = json_decode($response_body, true);

        if ($response_code < 200 || $response_code >= 300) {
            error_log('DeepClarity API Webhook Error: HTTP ' . $response_code);
            // If n8n returns validation errors, pass them through
            if ($parsed_body && isset($parsed_body['success']) && $parsed_body['success'] === false) {
                return $parsed_body;
            }
            return new \WP_Error('webhook_failed', 'Webhook returned HTTP ' . $response_code);
        }

        // Return full response body if requested
        if ($return_body && $parsed_body) {
            return $parsed_body;
        }

        return true;
    }

    /**
     * Send session analysis request to n8n webhook
     *
     * @param int    $session_id Session post ID.
     * @param int    $client_id  Client post ID.
     * @param string $request_id Unique request ID for tracking.
     * @param array  $fields     Selected fields to analyze.
     * @return bool|\WP_Error True on success, WP_Error on failure.
     */
    public function send_session_analysis_webhook($session_id, $client_id, $request_id, $fields = array())
    {
        if (! function_exists('get_field')) {
            return new \WP_Error('acf_missing', 'ACF not available');
        }

        // Get session ACF fields
        $session_transcript = '';
        $session_diagnosis  = '';
        $session_note       = '';

        if (in_array('session_transcript', $fields, true)) {
            $session_transcript = get_field('session_transcript', $session_id) ?: '';
        }
        if (in_array('session_diagnosis', $fields, true)) {
            $session_diagnosis = get_field('session_diagnosis', $session_id) ?: '';
        }
        if (in_array('session_note', $fields, true)) {
            $session_note = get_field('session_note', $session_id) ?: '';
        }

        // Get ACF option fields for settings/prompts
        $setting_kb_copywriting_skills = get_field('setting_kb_copywriting_skills', 'option') ?: '';
        $setting_kb_structure_skills   = get_field('setting_kb_structure_skills', 'option') ?: '';
        $setting_prompt_system         = get_field('setting_prompt_system', 'option') ?: '';
        $setting_prompt_analysis       = get_field('setting_prompt_analysis', 'option') ?: '';

        // Prepare webhook data
        $data = array(
            'request_id'                    => $request_id,
            'client_id'                     => $client_id,
            'session_id'                    => $session_id,
            'session_transcript'            => $session_transcript,
            'session_diagnosis'             => $session_diagnosis,
            'session_note'                  => $session_note,
            'setting_kb_copywriting_skills' => $setting_kb_copywriting_skills,
            'setting_kb_structure_skills'   => $setting_kb_structure_skills,
            'setting_prompt_system'         => $setting_prompt_system,
            'setting_prompt_analysis'       => $setting_prompt_analysis,
        );

        return $this->send_webhook($this->webhook_session_analysis_url, $data);
    }

    /**
     * Get form entry data as Q&A text format
     *
     * @param int $entry_id Entry ID.
     * @param int $form_id  Form ID.
     * @return string Q&A formatted text or empty string.
     */
    private function get_form_entry_as_text($entry_id, $form_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fluentform_submissions';

        $submission = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d AND form_id = %d",
                $entry_id,
                $form_id
            )
        );

        if (! $submission) {
            return '';
        }

        $response_data = json_decode($submission->response, true);
        if (! is_array($response_data)) {
            return '';
        }

        // Get form fields for labels
        $form_table = $wpdb->prefix . 'fluentform_forms';
        $form = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$form_table} WHERE id = %d",
                $form_id
            )
        );

        $field_labels = array();
        if ($form && ! empty($form->form_fields)) {
            $form_fields = json_decode($form->form_fields, true);
            if (is_array($form_fields) && isset($form_fields['fields'])) {
                $field_labels = $this->extract_field_labels_recursive($form_fields['fields']);
            }
        }

        // Build Q&A text
        $text_parts = array();
        foreach ($response_data as $field_name => $value) {
            // Skip internal fields
            if (strpos($field_name, '_') === 0 || strpos($field_name, 'fluentform') !== false) {
                continue;
            }

            $label = isset($field_labels[$field_name]) ? $field_labels[$field_name] : $field_name;

            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            if (! empty($value)) {
                $text_parts[] = "Frage: {$label}\nAntwort: {$value}";
            }
        }

        return implode("\n\n", $text_parts);
    }

    /**
     * Extract field labels recursively from form fields
     *
     * @param array $fields Form fields array.
     * @return array Associative array of field_name => label.
     */
    private function extract_field_labels_recursive($fields)
    {
        $labels = array();

        foreach ($fields as $field) {
            if (isset($field['attributes']['name']) && isset($field['settings']['label'])) {
                $labels[$field['attributes']['name']] = $field['settings']['label'];
            }

            if (isset($field['columns']) && is_array($field['columns'])) {
                foreach ($field['columns'] as $column) {
                    if (isset($column['fields']) && is_array($column['fields'])) {
                        $labels = array_merge($labels, $this->extract_field_labels_recursive($column['fields']));
                    }
                }
            }

            if (isset($field['fields']) && is_array($field['fields'])) {
                $labels = array_merge($labels, $this->extract_field_labels_recursive($field['fields']));
            }
        }

        return $labels;
    }

    /**
     * Strip HTML tags and convert to plain text
     *
     * @param string $html HTML content.
     * @return string Plain text.
     */
    private function html_to_text($html)
    {
        if (empty($html)) {
            return '';
        }

        // Convert common HTML elements to text equivalents
        $text = $html;

        // Replace <br> and <br/> with newlines
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

        // Replace </p> with double newlines
        $text = preg_replace('/<\/p>/i', "\n\n", $text);

        // Replace </li> with newlines
        $text = preg_replace('/<\/li>/i', "\n", $text);

        // Strip all remaining HTML tags
        $text = wp_strip_all_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Get DCPI scores from form submission
     *
     * @param int $entry_id Entry ID.
     * @return array DCPI scores.
     */
    private function get_dcpi_scores($entry_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fluentform_submissions';

        $submission = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT response FROM {$table_name} WHERE id = %d",
                $entry_id
            )
        );

        if (! $submission) {
            return array();
        }

        $data = json_decode($submission->response, true);
        if (! is_array($data)) {
            return array();
        }

        return array(
            'dimension_1_score'  => isset($data['dimension_1_score']) ? round(floatval($data['dimension_1_score'])) : 0,
            'dimension_2_score'  => isset($data['dimension_2_score']) ? round(floatval($data['dimension_2_score'])) : 0,
            'dimension_3_score'  => isset($data['dimension_3_score']) ? round(floatval($data['dimension_3_score'])) : 0,
            'dimension_4_score'  => isset($data['dimension_4_score']) ? round(floatval($data['dimension_4_score'])) : 0,
            'dimension_5_score'  => isset($data['dimension_5_score']) ? round(floatval($data['dimension_5_score'])) : 0,
            'deep_clarity_index' => isset($data['deep_clarity_index']) ? round(floatval($data['deep_clarity_index'])) : 0,
        );
    }

    /**
     * Send dossier analysis request to n8n webhook
     *
     * @param array  $dossier_data Dossier data from ajax_create_dossier.
     * @param string $request_id   Unique request ID for tracking.
     * @return array|\WP_Error Response array from n8n (with success, error, errors, etc.), or WP_Error on failure.
     */
    public function send_dossier_webhook($dossier_data, $request_id)
    {
        $client_id               = $dossier_data['client_id'];
        $anamnese_entry_id       = $dossier_data['anamnese_entry_id'];
        $session_id              = $dossier_data['session_id'];
        $dcpi_entry_id           = $dossier_data['dcpi_entry_id'];
        $comparison_session_id   = $dossier_data['comparison_session_id'];
        $comparison_dcpi_entry_id = $dossier_data['comparison_dcpi_entry_id'];

        // Form IDs
        $anamnese_form_id = 3;
        $dcpi_form_id     = 23;

        // Get client data
        $client_firstname = '';
        $client_lastname  = '';
        $client_email     = '';

        if (function_exists('get_field')) {
            $client_firstname = get_field('client_firstname', $client_id) ?: '';
            $client_lastname  = get_field('client_lastname', $client_id) ?: '';
            $client_email     = get_field('client_email', $client_id) ?: '';
        }

        // Build client object
        $client_data = array(
            'client_id'  => $client_id,
            'firstname'  => $client_firstname,
            'lastname'   => $client_lastname,
            'email'      => $client_email,
        );

        // Build anamnese object (only for first dossier)
        $anamnese_data = null;
        if ($anamnese_entry_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'fluentform_submissions';
            $anamnese_submission = $wpdb->get_row(
                $wpdb->prepare("SELECT created_at FROM {$table_name} WHERE id = %d", $anamnese_entry_id)
            );

            $anamnese_data = array(
                'anamnese_id'   => $anamnese_entry_id,
                'anamnese_date' => $anamnese_submission ? $anamnese_submission->created_at : '',
                'anamnese_data' => $this->get_form_entry_as_text($anamnese_entry_id, $anamnese_form_id),
            );
        }

        // Get dossier count for this client
        $dossier_query = new \WP_Query(array(
            'post_type'      => 'dossier',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => 'dossier_client',
                    'value'   => $client_id,
                    'compare' => '=',
                ),
            ),
        ));
        $dossier_count = $dossier_query->found_posts;

        // Get session data
        $session = get_post($session_id);
        $session_date = $session ? $session->post_date : '';

        // Get DCPI submission date
        global $wpdb;
        $table_name = $wpdb->prefix . 'fluentform_submissions';
        $dcpi_submission = $wpdb->get_row(
            $wpdb->prepare("SELECT created_at FROM {$table_name} WHERE id = %d", $dcpi_entry_id)
        );

        // Get DCPI scores
        $dcpi_scores = $this->get_dcpi_scores($dcpi_entry_id);

        // Determine if follow-up (not first dossier)
        $is_followup = ! empty($comparison_session_id) && ! empty($comparison_dcpi_entry_id);

        // Calculate dossier number
        $dossier_number = $dossier_count + 1;

        // Build current session object with separate fields (no HTML)
        $session_transcript = '';
        $session_diagnosis  = '';
        $session_notes      = '';

        if (function_exists('get_field')) {
            $session_transcript = $this->html_to_text(get_field('session_transcript', $session_id) ?: '');
            $session_diagnosis  = $this->html_to_text(get_field('session_diagnosis', $session_id) ?: '');
            $session_notes      = $this->html_to_text(get_field('session_note', $session_id) ?: '');
        }

        $current_session = array(
            'session_id'         => $session_id,
            'session_date'       => $session_date,
            'session_transcript' => $session_transcript,
            'session_diagnosis'  => $session_diagnosis,
            'session_notes'      => $session_notes,
        );

        // Count existing DCPIs to determine DCPI number
        $dcpi_number = 1;
        if (function_exists('get_field')) {
            $client_dcpi = get_field('client_dcpi', $client_id);
            if (is_array($client_dcpi)) {
                $dcpi_number = count($client_dcpi);
            }
        }

        // Build current DCPI object
        $current_dcpi = array(
            'dcpi_id'                 => $dcpi_entry_id,
            'dcpi_number'             => $dcpi_number,
            'dcpi_date'               => $dcpi_submission ? $dcpi_submission->created_at : '',
            'dcpi_dimension_1_score'  => $dcpi_scores['dimension_1_score'] ?? 0,
            'dcpi_dimension_2_score'  => $dcpi_scores['dimension_2_score'] ?? 0,
            'dcpi_dimension_3_score'  => $dcpi_scores['dimension_3_score'] ?? 0,
            'dcpi_dimension_4_score'  => $dcpi_scores['dimension_4_score'] ?? 0,
            'dcpi_dimension_5_score'  => $dcpi_scores['dimension_5_score'] ?? 0,
            'dcpi_deep_clarity_index' => $dcpi_scores['deep_clarity_index'] ?? 0,
            'dcpi_data'               => $this->get_form_entry_as_text($dcpi_entry_id, $dcpi_form_id),
        );

        // Build previous data (for follow-up dossiers)
        $previous_session = null;
        $previous_dcpi = null;

        if ($is_followup) {
            // Previous session with separate fields (no HTML)
            $prev_session = get_post($comparison_session_id);

            $prev_transcript = '';
            $prev_diagnosis  = '';
            $prev_notes      = '';

            if (function_exists('get_field')) {
                $prev_transcript = $this->html_to_text(get_field('session_transcript', $comparison_session_id) ?: '');
                $prev_diagnosis  = $this->html_to_text(get_field('session_diagnosis', $comparison_session_id) ?: '');
                $prev_notes      = $this->html_to_text(get_field('session_note', $comparison_session_id) ?: '');
            }

            $previous_session = array(
                'session_id'         => $comparison_session_id,
                'session_date'       => $prev_session ? $prev_session->post_date : '',
                'session_transcript' => $prev_transcript,
                'session_diagnosis'  => $prev_diagnosis,
                'session_notes'      => $prev_notes,
            );

            // Previous DCPI
            $prev_dcpi_submission = $wpdb->get_row(
                $wpdb->prepare("SELECT created_at FROM {$table_name} WHERE id = %d", $comparison_dcpi_entry_id)
            );
            $prev_dcpi_scores = $this->get_dcpi_scores($comparison_dcpi_entry_id);

            $previous_dcpi = array(
                'dcpi_id'                 => $comparison_dcpi_entry_id,
                'dcpi_number'             => $dcpi_number - 1,
                'dcpi_date'               => $prev_dcpi_submission ? $prev_dcpi_submission->created_at : '',
                'dcpi_dimension_1_score'  => $prev_dcpi_scores['dimension_1_score'] ?? 0,
                'dcpi_dimension_2_score'  => $prev_dcpi_scores['dimension_2_score'] ?? 0,
                'dcpi_dimension_3_score'  => $prev_dcpi_scores['dimension_3_score'] ?? 0,
                'dcpi_dimension_4_score'  => $prev_dcpi_scores['dimension_4_score'] ?? 0,
                'dcpi_dimension_5_score'  => $prev_dcpi_scores['dimension_5_score'] ?? 0,
                'dcpi_deep_clarity_index' => $prev_dcpi_scores['deep_clarity_index'] ?? 0,
                'dcpi_data'               => $this->get_form_entry_as_text($comparison_dcpi_entry_id, $dcpi_form_id),
            );
        }

        // Build skills object from ACF options
        $skills_data = array(
            'setting_skill_structure_analysis'  => get_field('setting_skill_structure_analysis', 'option') ?: '',
            'setting_skill_copywriting_dossier' => get_field('setting_skill_copywirting_dossier', 'option') ?: '',
            'setting_skill_dossier_creation'    => get_field('setting_skill_dossier_creation', 'option') ?: '',
            'settings_template_dossier'         => get_field('settings_template_dossier', 'option') ?: '',
        );

        // Build final webhook data
        $webhook_data = array(
            'request_id'       => $request_id,
            'dossier_number'   => $dossier_number,
            'dossier_followup' => $is_followup,
            'client'           => $client_data,
            'anamnese'         => $anamnese_data,
            'current_session'  => $current_session,
            'current_dcpi'     => $current_dcpi,
            'previous_session' => $previous_session,
            'previous_dcpi'    => $previous_dcpi,
            'skills'           => $skills_data,
        );

        // Send to all webhook URLs (async, fire and forget)
        $last_result = true;
        foreach ($this->webhook_dossier_urls as $url) {
            $result = $this->send_webhook($url, $webhook_data);
            if (is_wp_error($result)) {
                $last_result = $result;
            }
        }

        return $last_result;
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes()
    {
        // Endpoint: Update client title
        register_rest_route('deep-clarity/v1', '/client/update-title', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_client_update_title'),
            'permission_callback' => array($this, 'verify_api_request'),
            'args'                => array(
                'post_id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'description'       => 'The client post ID',
                ),
                'title' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => 'The new post title',
                ),
            ),
        ));

        // Endpoint: Receive session analysis result from n8n
        register_rest_route('deep-clarity/v1', '/session/analysis-result', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_session_analysis_result'),
            'permission_callback' => array($this, 'verify_api_request'),
            'args'                => array(
                'request_id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => 'The unique request ID',
                ),
                'client_id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'description'       => 'The client post ID',
                ),
                'session_id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'description'       => 'The session post ID',
                ),
                'analysis' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => 'The analysis result text',
                ),
            ),
        ));

        // Endpoint: Receive dossier analysis result from n8n
        register_rest_route('deep-clarity/v1', '/dossier/analysis-result', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_dossier_analysis_result'),
            'permission_callback' => array($this, 'verify_api_request'),
            'args'                => array(
                'request_id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => 'The unique request ID',
                ),
                'client_id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'description'       => 'The client post ID',
                ),
                'structural_analysis' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'description'       => 'The structural analysis result (step 1)',
                ),
                'dossier_content' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'description'       => 'The generated dossier content (step 2)',
                ),
                'dossier_html' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'description'       => 'The generated dossier HTML content',
                ),
                'dossier_segments' => array(
                    'required'          => false,
                    'type'              => 'array',
                    'description'       => 'The dossier segments array for template rendering',
                ),
            ),
        ));

        // Endpoint: Receive dossier analysis error from n8n
        register_rest_route('deep-clarity/v1', '/dossier/analysis-error', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_dossier_analysis_error'),
            'permission_callback' => array($this, 'verify_api_request'),
            'args'                => array(
                'request_id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => 'The unique request ID',
                ),
                'error_type' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => 'Type of error (validation, processing, etc.)',
                ),
                'error_message' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => 'The main error message',
                ),
                'errors' => array(
                    'required'          => false,
                    'type'              => 'array',
                    'description'       => 'Array of validation errors',
                ),
                'warnings' => array(
                    'required'          => false,
                    'type'              => 'array',
                    'description'       => 'Array of validation warnings',
                ),
                'metadata' => array(
                    'required'          => false,
                    'type'              => 'object',
                    'description'       => 'Additional metadata (timestamp, n8n_execution_id, validation_node)',
                ),
                'original_payload_preview' => array(
                    'required'          => false,
                    'type'              => 'object',
                    'description'       => 'Preview of original payload for debugging',
                ),
            ),
        ));
    }

    /**
     * Verify API request via API key
     *
     * @param \WP_REST_Request $request The REST request.
     * @return bool|\WP_Error True if authorized, WP_Error otherwise.
     */
    public function verify_api_request($request)
    {
        // Check for API key in header
        $api_key = $request->get_header('X-API-Key');

        if ($api_key && hash_equals($this->api_key, $api_key)) {
            return true;
        }

        return new \WP_Error(
            'rest_forbidden',
            'Unauthorized access - API key required',
            array('status' => 401)
        );
    }

    /**
     * Handle client title update from external API
     *
     * @param \WP_REST_Request $request The REST request.
     * @return \WP_REST_Response
     */
    public function handle_client_update_title($request)
    {
        $post_id = $request->get_param('post_id');
        $title   = $request->get_param('title');

        // Verify post exists and is a client
        $post = get_post($post_id);

        if (!$post) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Post not found',
            ), 404);
        }

        if ($post->post_type !== 'client') {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Post is not a client',
            ), 400);
        }

        // Set flag to prevent webhook from being triggered
        self::$skip_webhook = true;

        // Update the post title
        $result = wp_update_post(array(
            'ID'         => $post_id,
            'post_title' => $title,
        ), true);

        // Reset the flag
        self::$skip_webhook = false;

        if (is_wp_error($result)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message(),
            ), 500);
        }

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => 'Client title updated successfully',
            'post_id' => $post_id,
            'title'   => $title,
        ), 200);
    }

    /**
     * Handle session analysis result from n8n
     *
     * @param \WP_REST_Request $request The REST request.
     * @return \WP_REST_Response
     */
    public function handle_session_analysis_result($request)
    {
        $request_id = $request->get_param('request_id');
        $client_id  = $request->get_param('client_id');
        $session_id = $request->get_param('session_id');
        $analysis   = $request->get_param('analysis');

        // Verify session exists
        $session = get_post($session_id);

        if (! $session) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Session not found',
            ), 404);
        }

        if ($session->post_type !== 'session') {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Post is not a session',
            ), 400);
        }

        // Save analysis to ACF field
        if (function_exists('update_field')) {
            update_field('session_analysis', $analysis, $session_id);
        } else {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'ACF not available',
            ), 500);
        }

        // Update transient status for polling
        $transient_data = get_transient($request_id);

        if ($transient_data) {
            $transient_data['status']       = 'complete';
            $transient_data['result']       = $analysis;
            $transient_data['completed_at'] = time();

            // Keep for 5 more minutes for polling
            set_transient($request_id, $transient_data, 5 * MINUTE_IN_SECONDS);
        }

        return new \WP_REST_Response(array(
            'success'    => true,
            'message'    => 'Analysis saved successfully',
            'session_id' => $session_id,
            'client_id'  => $client_id,
        ), 200);
    }

    /**
     * Handle dossier analysis result from n8n
     *
     * Supports two-step processing:
     * Step 1: structural_analysis only - creates dossier, saves analysis, status = "processing"
     * Step 2: dossier_content - updates dossier with content, status = "complete"
     *
     * @param \WP_REST_Request $request The REST request.
     * @return \WP_REST_Response
     */
    public function handle_dossier_analysis_result($request)
    {
        $request_id          = $request->get_param('request_id');
        $client_id           = $request->get_param('client_id');
        $structural_analysis = $request->get_param('structural_analysis');
        $dossier_content     = $request->get_param('dossier_content');
        $dossier_html        = $request->get_param('dossier_html');
        $dossier_segments    = $request->get_param('dossier_segments');

        // At least one of structural_analysis, dossier_content, or dossier_html must be provided
        if (empty($structural_analysis) && empty($dossier_content) && empty($dossier_html)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Either structural_analysis, dossier_content, or dossier_html must be provided',
            ), 400);
        }

        // Verify client exists
        $client = get_post($client_id);

        if (! $client) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Client not found',
            ), 404);
        }

        if ($client->post_type !== 'client') {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Post is not a client',
            ), 400);
        }

        // Get transient data
        $transient_data = get_transient($request_id);

        // Check if dossier already exists (from step 1)
        $dossier_id = ($transient_data && isset($transient_data['dossier_id'])) ? $transient_data['dossier_id'] : null;

        // If no dossier exists yet, create one
        if (! $dossier_id) {
            // Get client name for dossier title
            $client_firstname = '';
            $client_lastname  = '';
            if (function_exists('get_field')) {
                $client_firstname = get_field('client_firstname', $client_id) ?: '';
                $client_lastname  = get_field('client_lastname', $client_id) ?: '';
            }
            $client_name = trim("{$client_firstname} {$client_lastname}");
            if (empty($client_name)) {
                $client_name = $client->post_title;
            }

            // Create dossier post (empty content for now if only structural_analysis)
            $dossier_title = sprintf('Dossier: %s - %s', $client_name, current_time('d.m.Y'));

            $dossier_id = wp_insert_post(array(
                'post_type'    => 'dossier',
                'post_title'   => $dossier_title,
                'post_content' => $dossier_content ?: '',
                'post_status'  => 'publish',
            ), true);

            if (is_wp_error($dossier_id)) {
                return new \WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Failed to create dossier: ' . $dossier_id->get_error_message(),
                ), 500);
            }

            // Link dossier to client via ACF field
            if (function_exists('update_field')) {
                update_field('dossier_client', $client_id, $dossier_id);
            }
        } elseif (! empty($dossier_content)) {
            // Update existing dossier with content
            wp_update_post(array(
                'ID'           => $dossier_id,
                'post_content' => $dossier_content,
            ));
        }

        // Save structural analysis to ACF field if provided
        if (! empty($structural_analysis) && function_exists('update_field')) {
            update_field('dossier_structural_analysis', $structural_analysis, $dossier_id);
        }

        // Save DCPI dimension scores if provided (from structural analysis step)
        // Accept both dcpi_* and dossier_* prefixes for flexibility
        if (function_exists('update_field')) {
            $dcpi_fields = array(
                'dossier_dimension_1_score'   => $request->get_param('dcpi_dimension_1_score') ?? $request->get_param('dossier_dimension_1_score'),
                'dossier_dimension_2_score'   => $request->get_param('dcpi_dimension_2_score') ?? $request->get_param('dossier_dimension_2_score'),
                'dossier_dimension_3_score'   => $request->get_param('dcpi_dimension_3_score') ?? $request->get_param('dossier_dimension_3_score'),
                'dossier_dimension_4_score'   => $request->get_param('dcpi_dimension_4_score') ?? $request->get_param('dossier_dimension_4_score'),
                'dossier_dimension_5_score'   => $request->get_param('dcpi_dimension_5_score') ?? $request->get_param('dossier_dimension_5_score'),
                'dossier_deep_clarity_index'  => $request->get_param('dcpi_deep_clarity_index') ?? $request->get_param('dossier_deep_clarity_index'),
            );

            foreach ($dcpi_fields as $field_name => $value) {
                if ($value !== null) {
                    update_field($field_name, round(floatval($value)), $dossier_id);
                }
            }
        }

        // Save dossier_html to ACF field if provided
        if (! empty($dossier_html) && function_exists('update_field')) {
            update_field('dossier_html', $dossier_html, $dossier_id);
        }

        // Save dossier_segments to ACF field 'dossier_structure' if provided
        if (! empty($dossier_segments) && function_exists('update_field')) {
            // Ensure proper JSON encoding
            $segments_array = null;

            if (is_array($dossier_segments)) {
                $segments_array = $dossier_segments;
            } elseif (is_string($dossier_segments)) {
                // Try to decode JSON string
                $decoded = json_decode($dossier_segments, true);
                if ($decoded !== null && is_array($decoded)) {
                    $segments_array = $decoded;
                } else {
                    // Try with stripslashes (in case of double-encoding)
                    $decoded = json_decode(stripslashes($dossier_segments), true);
                    if ($decoded !== null && is_array($decoded)) {
                        $segments_array = $decoded;
                    }
                }
            }

            if ($segments_array !== null) {
                // Re-encode with proper escaping using wp_json_encode
                $segments_to_save = wp_json_encode($segments_array, JSON_UNESCAPED_UNICODE);
                update_field('dossier_structure', $segments_to_save, $dossier_id);
            } else {
                // Fallback: save as-is but log a warning
                error_log('Deep Clarity API: Could not parse dossier_segments as JSON for dossier ' . $dossier_id);
                $segments_to_save = is_string($dossier_segments) ? $dossier_segments : wp_json_encode($dossier_segments);
                update_field('dossier_structure', $segments_to_save, $dossier_id);
            }
        }

        // Determine status: "processing" if only structural_analysis, "complete" if dossier_content or dossier_html provided
        $is_complete = ! empty($dossier_content) || ! empty($dossier_html);

        // Update transient status for polling
        if (! $transient_data) {
            $transient_data = array();
        }

        $transient_data['dossier_id'] = $dossier_id;

        if ($is_complete) {
            $transient_data['status']       = 'complete';
            $transient_data['completed_at'] = time();
        } else {
            // Still processing - structural analysis done, waiting for dossier content
            $transient_data['status'] = 'processing';
        }

        // Keep for 30 more minutes for polling
        set_transient($request_id, $transient_data, 30 * MINUTE_IN_SECONDS);

        $message = $is_complete ? 'Dossier created successfully' : 'Structural analysis saved, awaiting dossier content';

        return new \WP_REST_Response(array(
            'success'    => true,
            'message'    => $message,
            'dossier_id' => $dossier_id,
            'client_id'  => $client_id,
            'step'       => $is_complete ? 'complete' : 'structural_analysis',
        ), 200);
    }

    /**
     * Handle dossier analysis error from n8n
     *
     * @param \WP_REST_Request $request The REST request.
     * @return \WP_REST_Response
     */
    public function handle_dossier_analysis_error($request)
    {
        // Get body parameters
        $request_id    = $request->get_param('request_id');
        $error_type    = $request->get_param('error_type') ?: 'unknown';
        $error_message = $request->get_param('error_message') ?: 'Ein Fehler ist aufgetreten';
        $errors        = $request->get_param('errors') ?: array();
        $warnings      = $request->get_param('warnings') ?: array();
        $metadata      = $request->get_param('metadata') ?: array();

        // Extract metadata fields
        $timestamp         = isset($metadata['timestamp']) ? $metadata['timestamp'] : current_time('c');
        $n8n_execution_id  = isset($metadata['n8n_execution_id']) ? $metadata['n8n_execution_id'] : '';
        $validation_node   = isset($metadata['validation_node']) ? $metadata['validation_node'] : '';

        // Log the error with details
        error_log(sprintf(
            'DeepClarity Dossier Error [%s]: request_id=%s, error="%s", errors_count=%d, warnings_count=%d, timestamp=%s, n8n_execution=%s, node=%s',
            $error_type,
            $request_id,
            $error_message,
            count($errors),
            count($warnings),
            $timestamp,
            $n8n_execution_id,
            $validation_node
        ));

        // Log individual errors if present
        if (! empty($errors)) {
            error_log('DeepClarity Dossier Errors: ' . wp_json_encode($errors));
        }

        // Log individual warnings if present
        if (! empty($warnings)) {
            error_log('DeepClarity Dossier Warnings: ' . wp_json_encode($warnings));
        }

        // Update transient status with error
        $transient_data = get_transient($request_id);

        if (! $transient_data) {
            error_log(sprintf('DeepClarity Dossier Error: Transient not found for request_id=%s', $request_id));
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Request not found or expired',
            ), 404);
        }

        $transient_data['status']       = 'error';
        $transient_data['error']        = $error_message;
        $transient_data['errors']       = $errors;
        $transient_data['warnings']     = $warnings;
        $transient_data['error_type']   = $error_type;
        $transient_data['completed_at'] = time();

        // Keep for 5 more minutes for polling
        set_transient($request_id, $transient_data, 5 * MINUTE_IN_SECONDS);

        return new \WP_REST_Response(array(
            'success'    => true,
            'message'    => 'Error status recorded',
            'request_id' => $request_id,
        ), 200);
    }
}
