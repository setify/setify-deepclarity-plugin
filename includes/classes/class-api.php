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
     * Webhook URL for client updates
     *
     * @var string
     */
    private $webhook_client_update = 'https://n8n.setify.de/webhook/dc_update_client';

    /**
     * API Key for authentication
     *
     * @var string
     */
    private $api_key = 'dc_api_k7Qm9xR4vN2pL8wY3jF6hT1cB5sA0eD';

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

        // Get ACF fields
        $client_firstname = '';
        $client_lastname = '';
        $client_birthday = '';

        if (function_exists('get_field')) {
            $client_firstname = get_field('client_firstname', $post_id) ?: '';
            $client_lastname = get_field('client_lastname', $post_id) ?: '';
            $client_birthday = get_field('client_birthday', $post_id) ?: '';
        }

        // Prepare webhook data
        $data = array(
            'post_id'          => $post_id,
            'client_firstname' => $client_firstname,
            'client_lastname'  => $client_lastname,
            'client_birthday'  => $client_birthday,
            'is_update'        => $update,
        );

        // Send webhook
        $this->send_webhook($this->webhook_client_update, $data);
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

        // Update the post title
        $result = wp_update_post(array(
            'ID'         => $post_id,
            'post_title' => $title,
        ), true);

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
}
