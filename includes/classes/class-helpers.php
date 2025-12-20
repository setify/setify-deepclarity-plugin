<?php

/**
 * Helpers class
 *
 * @package DeepClarity
 */

namespace DeepClarity;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Helpers
 *
 * Provides helper shortcodes and utility functions.
 */
class Helpers
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->register_shortcodes();
    }

    /**
     * Register all helper shortcodes
     */
    private function register_shortcodes()
    {
        add_shortcode('edit_url', array($this, 'edit_url_shortcode'));
    }

    /**
     * Shortcode: edit_url
     *
     * Returns the permalink of a page with the current post ID as edit parameter.
     *
     * Usage: [edit_url page_id="123"]
     *
     * @param array $atts Shortcode attributes.
     * @return string The edit URL.
     */
    public function edit_url_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'page_id' => '',
            'param'   => 'edit',
        ), $atts, 'edit_url');

        // Get page ID
        $page_id = absint($atts['page_id']);

        if (! $page_id) {
            return '';
        }

        // Get current post ID
        $current_post_id = get_the_ID();

        if (! $current_post_id) {
            return '';
        }

        // Get permalink of target page
        $permalink = get_permalink($page_id);

        if (! $permalink) {
            return '';
        }

        // Add edit parameter with current post ID
        $edit_url = add_query_arg($atts['param'], $current_post_id, $permalink);

        return esc_url($edit_url);
    }
}
