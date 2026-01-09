<?php

/**
 * ACF Shortcodes and Utilities
 *
 * @package DeepClarity
 */

namespace DeepClarity;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACF Class
 */
class ACF
{
    /**
     * Instance
     *
     * @var ACF
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return ACF
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
        add_shortcode('acf', array($this, 'shortcode_acf_field'));
    }

    /**
     * Shortcode to display ACF field value
     *
     * Usage: [acf field="field_name"]
     *        [acf field="field_name" post_id="123"]
     *        [acf field="field_name" post_id="option"]
     *
     * @param array $atts Shortcode attributes
     * @return string Field value or empty string
     */
    public function shortcode_acf_field($atts)
    {
        $atts = shortcode_atts(array(
            'field' => '',
            'post_id' => null,
        ), $atts, 'acf');

        if (empty($atts['field'])) {
            return '';
        }

        if (!function_exists('get_field')) {
            return '';
        }

        // Determine post ID
        $post_id = $atts['post_id'];
        if ($post_id === 'option' || $post_id === 'options') {
            $post_id = 'option';
        } elseif ($post_id === null) {
            $post_id = get_the_ID();
        } else {
            $post_id = intval($post_id);
        }

        $value = get_field($atts['field'], $post_id);

        // Handle different value types
        if (is_array($value)) {
            // For arrays (e.g., repeater, gallery), return JSON or comma-separated
            if (isset($value['url'])) {
                // Image/File field - return URL
                return esc_url($value['url']);
            }
            return esc_html(implode(', ', array_map('strval', $value)));
        }

        if (is_object($value)) {
            // For objects (e.g., post object), return title or ID
            if (isset($value->post_title)) {
                return esc_html($value->post_title);
            }
            return '';
        }

        // For scalar values
        return esc_html($value);
    }
}
