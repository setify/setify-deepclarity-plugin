<?php

/**
 * Shortcodes class
 *
 * @package DeepClarity
 */

namespace DeepClarity;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Shortcodes
 *
 * Handles all plugin shortcodes.
 */
class Shortcodes
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->register_shortcodes();
    }

    /**
     * Register all shortcodes
     */
    private function register_shortcodes()
    {
        add_shortcode('session_client_name', array($this, 'session_client_name'));
    }

    /**
     * Shortcode: session_client_name
     *
     * Outputs the first and last name of the client linked to the current session.
     *
     * Usage: [session_client_name]
     * Options: [session_client_name field_first="first_name" field_last="last_name"]
     *
     * @param array $atts Shortcode attributes.
     * @return string Client name or empty string.
     */
    public function session_client_name($atts)
    {
        $atts = shortcode_atts(array(
            'field_first' => 'first_name',
            'field_last'  => 'last_name',
            'separator'   => ' ',
            'fallback'    => '',
        ), $atts, 'session_client_name');

        // Get current post ID
        $post_id = get_the_ID();

        if (! $post_id) {
            return esc_html($atts['fallback']);
        }

        // Get client from ACF relation field
        $client = get_field(Sessions::ACF_CLIENT_FIELD, $post_id);

        if (! $client) {
            return esc_html($atts['fallback']);
        }

        // Handle both single post object and array of posts
        if (is_array($client)) {
            $client = $client[0];
        }

        // Get client ID
        $client_id = is_object($client) ? $client->ID : $client;

        // Get first and last name from ACF fields
        $first_name = get_field($atts['field_first'], $client_id);
        $last_name  = get_field($atts['field_last'], $client_id);

        // Build full name
        $name_parts = array_filter(array($first_name, $last_name));

        if (empty($name_parts)) {
            // Fallback to post title if no name fields
            return esc_html(get_the_title($client_id));
        }

        return esc_html(implode($atts['separator'], $name_parts));
    }
}
