<?php

/**
 * Client Shortcodes and Utilities
 *
 * @package DeepClarity
 */

namespace DeepClarity;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Client Class
 */
class Client
{
    /**
     * Instance
     *
     * @var Client
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Client
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
        // Shortcodes
        add_shortcode('client_fullname', array($this, 'shortcode_fullname'));
        add_shortcode('client_firstname', array($this, 'shortcode_firstname'));
        add_shortcode('client_lastname', array($this, 'shortcode_lastname'));
        add_shortcode('client_birthday_until', array($this, 'shortcode_birthday_until'));
        add_shortcode('client_birthday_until_raw', array($this, 'shortcode_birthday_until_raw'));

        // Fluent Forms integration
        add_action('fluentform/submission_inserted', array($this, 'track_fluent_form_submission'), 10, 3);
    }

    /**
     * Track Fluent Form submission in client's ACF repeater field
     *
     * @param int   $entry_id    The submission entry ID.
     * @param array $form_data   The submitted form data.
     * @param object $form       The form object.
     */
    public function track_fluent_form_submission($entry_id, $form_data, $form)
    {
        // Check if client_id field exists in form data
        if (empty($form_data['client_id'])) {
            return;
        }

        $client_id = intval($form_data['client_id']);

        // Verify client post exists
        $client = get_post($client_id);
        if (! $client || $client->post_type !== 'client') {
            return;
        }

        // Check if ACF is available
        if (! function_exists('get_field') || ! function_exists('update_field')) {
            return;
        }

        // Get form details
        $form_id   = $form->id;
        $form_name = $form->title;

        // Get current repeater data
        $client_forms = get_field('client_forms', $client_id);
        if (! is_array($client_forms)) {
            $client_forms = array();
        }

        // Add new entry
        $client_forms[] = array(
            'form_id'   => $form_id,
            'form_name' => $form_name,
            'entry_id'  => $entry_id,
            'date'      => current_time('Y-m-d H:i:s'),
        );

        // Update the repeater field
        update_field('client_forms', $client_forms, $client_id);
    }

    /**
     * Get client post ID from current context
     *
     * @param array $atts Shortcode attributes
     * @return int|null Post ID or null
     */
    private function get_client_id($atts)
    {
        if (!empty($atts['post_id'])) {
            return intval($atts['post_id']);
        }
        return get_the_ID();
    }

    /**
     * Shortcode: Client full name
     *
     * Usage: [client_fullname]
     *        [client_fullname post_id="123"]
     *
     * @param array $atts Shortcode attributes
     * @return string Full name
     */
    public function shortcode_fullname($atts)
    {
        $atts = shortcode_atts(array(
            'post_id' => null,
        ), $atts, 'client_fullname');

        $post_id = $this->get_client_id($atts);

        if (!function_exists('get_field')) {
            return '';
        }

        $firstname = get_field('client_firstname', $post_id);
        $lastname = get_field('client_lastname', $post_id);

        $fullname = trim($firstname . ' ' . $lastname);

        return esc_html($fullname);
    }

    /**
     * Shortcode: Client first name
     *
     * Usage: [client_firstname]
     *        [client_firstname post_id="123"]
     *
     * @param array $atts Shortcode attributes
     * @return string First name
     */
    public function shortcode_firstname($atts)
    {
        $atts = shortcode_atts(array(
            'post_id' => null,
        ), $atts, 'client_firstname');

        $post_id = $this->get_client_id($atts);

        if (!function_exists('get_field')) {
            return '';
        }

        $firstname = get_field('client_firstname', $post_id);

        return esc_html($firstname);
    }

    /**
     * Shortcode: Client last name
     *
     * Usage: [client_lastname]
     *        [client_lastname post_id="123"]
     *
     * @param array $atts Shortcode attributes
     * @return string Last name
     */
    public function shortcode_lastname($atts)
    {
        $atts = shortcode_atts(array(
            'post_id' => null,
        ), $atts, 'client_lastname');

        $post_id = $this->get_client_id($atts);

        if (!function_exists('get_field')) {
            return '';
        }

        $lastname = get_field('client_lastname', $post_id);

        return esc_html($lastname);
    }

    /**
     * Shortcode: Time until next birthday
     *
     * Usage: [client_birthday_until]
     *        [client_birthday_until post_id="123"]
     *
     * @param array $atts Shortcode attributes
     * @return string Relative time (e.g., "6 Monate 18 Tage")
     */
    public function shortcode_birthday_until($atts)
    {
        $atts = shortcode_atts(array(
            'post_id' => null,
        ), $atts, 'client_birthday_until');

        $post_id = $this->get_client_id($atts);

        if (!function_exists('get_field')) {
            return '';
        }

        $birthday = get_field('client_birthday', $post_id);

        if (empty($birthday)) {
            return '';
        }

        return $this->get_time_until_birthday($birthday);
    }

    /**
     * Calculate time until next birthday
     *
     * @param string $birthday Birthday date (Y-m-d or d/m/Y format)
     * @return string Relative time string
     */
    private function get_time_until_birthday($birthday)
    {
        // Parse birthday - try different formats
        $birthday_date = \DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$birthday_date) {
            $birthday_date = \DateTime::createFromFormat('d/m/Y', $birthday);
        }
        if (!$birthday_date) {
            $birthday_date = \DateTime::createFromFormat('Ymd', $birthday);
        }
        if (!$birthday_date) {
            return '';
        }

        $today = new \DateTime('today');
        $current_year = (int) $today->format('Y');

        // Create next birthday date in current year
        $next_birthday = \DateTime::createFromFormat(
            'Y-m-d',
            $current_year . '-' . $birthday_date->format('m-d')
        );

        // If birthday already passed this year, use next year
        if ($next_birthday < $today) {
            $next_birthday->modify('+1 year');
        }

        // Calculate difference
        $diff = $today->diff($next_birthday);

        // Build output string
        $parts = array();

        if ($diff->y > 0) {
            $parts[] = $diff->y . ' ' . ($diff->y === 1 ? 'Jahr' : 'Jahre');
        }

        if ($diff->m > 0) {
            $parts[] = $diff->m . ' ' . ($diff->m === 1 ? 'Monat' : 'Monate');
        }

        if ($diff->d > 0) {
            $parts[] = $diff->d . ' ' . ($diff->d === 1 ? 'Tag' : 'Tage');
        }

        // If birthday is today
        if (empty($parts)) {
            return 'Heute!';
        }

        return implode(' ', $parts);
    }

    /**
     * Shortcode: Days until next birthday (raw number)
     *
     * Usage: [client_birthday_until_raw]
     *        [client_birthday_until_raw post_id="123"]
     *
     * @param array $atts Shortcode attributes
     * @return string Number of days
     */
    public function shortcode_birthday_until_raw($atts)
    {
        $atts = shortcode_atts(array(
            'post_id' => null,
        ), $atts, 'client_birthday_until_raw');

        $post_id = $this->get_client_id($atts);

        if (!function_exists('get_field')) {
            return '';
        }

        $birthday = get_field('client_birthday', $post_id);

        if (empty($birthday)) {
            return '';
        }

        return $this->get_days_until_birthday($birthday);
    }

    /**
     * Calculate total days until next birthday
     *
     * @param string $birthday Birthday date (Y-m-d or d/m/Y format)
     * @return int Number of days
     */
    private function get_days_until_birthday($birthday)
    {
        // Parse birthday - try different formats
        $birthday_date = \DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$birthday_date) {
            $birthday_date = \DateTime::createFromFormat('d/m/Y', $birthday);
        }
        if (!$birthday_date) {
            $birthday_date = \DateTime::createFromFormat('Ymd', $birthday);
        }
        if (!$birthday_date) {
            return '';
        }

        $today = new \DateTime('today');
        $current_year = (int) $today->format('Y');

        // Create next birthday date in current year
        $next_birthday = \DateTime::createFromFormat(
            'Y-m-d',
            $current_year . '-' . $birthday_date->format('m-d')
        );

        // If birthday already passed this year, use next year
        if ($next_birthday < $today) {
            $next_birthday->modify('+1 year');
        }

        // Calculate total days
        $diff = $today->diff($next_birthday);

        return $diff->days;
    }
}
