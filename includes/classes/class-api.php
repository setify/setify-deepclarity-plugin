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
     * @param string $url  Webhook URL.
     * @param array  $data Data to send.
     * @return bool|\WP_Error True on success, WP_Error on failure.
     */
    private function send_webhook($url, $data)
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
                'timeout'   => 15,
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

        if ($response_code < 200 || $response_code >= 300) {
            error_log('DeepClarity API Webhook Error: HTTP ' . $response_code);
            return new \WP_Error('webhook_failed', 'Webhook returned HTTP ' . $response_code);
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
}
