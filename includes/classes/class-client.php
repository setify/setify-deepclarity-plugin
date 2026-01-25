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
        add_action('fluentform/submission_inserted', array($this, 'track_dcpi_form_submission'), 10, 3);

        // AJAX handler for form entry details
        add_action('wp_ajax_deep_clarity_get_form_entry', array($this, 'ajax_get_form_entry'));
        add_action('wp_ajax_nopriv_deep_clarity_get_form_entry', array($this, 'ajax_get_form_entry'));

        // AJAX handlers for dossier creation
        add_action('wp_ajax_deep_clarity_get_client_sessions', array($this, 'ajax_get_client_sessions'));
        add_action('wp_ajax_deep_clarity_get_client_forms', array($this, 'ajax_get_client_forms'));
        add_action('wp_ajax_deep_clarity_create_dossier', array($this, 'ajax_create_dossier'));
        add_action('wp_ajax_deep_clarity_init_dossier', array($this, 'ajax_init_dossier'));

        // AJAX handler for session analysis
        add_action('wp_ajax_deep_clarity_analyze_session', array($this, 'ajax_analyze_session'));
        add_action('wp_ajax_deep_clarity_check_analysis_status', array($this, 'ajax_check_analysis_status'));

        // REST API endpoint for Bit Flows callback
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Auto-update post title on save
        // Priority 10 for standard save, priority 50 for ACF to ensure fields are saved first
        // Works with both backend and Frontend Admin plugin (which uses acf_form)
        add_action('save_post_client', array($this, 'auto_update_client_title'), 10, 3);
        add_action('acf/save_post', array($this, 'auto_update_client_title_acf'), 50);
    }

    /**
     * Auto-update client post title on save
     *
     * Format: {First Name} {Last Name} - {Email}
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @param bool     $update  Whether this is an existing post being updated.
     */
    public function auto_update_client_title($post_id, $post, $update)
    {
        // Bail if autosave, revision, or not a client post
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        if ($post->post_type !== 'client') {
            return;
        }

        $this->update_client_title($post_id);
    }

    /**
     * Auto-update client post title after ACF save
     *
     * This ensures ACF fields are available when updating title.
     *
     * @param int $post_id Post ID.
     */
    public function auto_update_client_title_acf($post_id)
    {
        // Bail if not a client post
        if (get_post_type($post_id) !== 'client') {
            return;
        }

        $this->update_client_title($post_id);
    }

    /**
     * Update client post title based on ACF fields
     *
     * Format: [post_id] First Name Last Name - Email
     *
     * @param int $post_id Post ID.
     */
    private function update_client_title($post_id)
    {
        // Prevent infinite loop with static flag
        static $updating = array();
        if (isset($updating[$post_id])) {
            return;
        }
        $updating[$post_id] = true;

        // Check if ACF is available
        if (! function_exists('get_field')) {
            unset($updating[$post_id]);
            return;
        }

        // Get ACF field values (correct field names with underscores)
        $firstname = get_field('client_first_name', $post_id);
        $lastname  = get_field('client_last_name', $post_id);
        $email     = get_field('client_email', $post_id);

        // Build title: [post_id] First Name Last Name - Email
        $title_parts = array();

        // Add post_id prefix
        $title_parts[] = '[' . $post_id . ']';

        $name = trim($firstname . ' ' . $lastname);
        if (! empty($name)) {
            $title_parts[] = $name;
        }

        if (! empty($email)) {
            $title_parts[] = '- ' . $email;
        }

        // Build final title
        $new_title = implode(' ', $title_parts);

        // Update the post title
        wp_update_post(array(
            'ID'         => $post_id,
            'post_title' => $new_title,
        ));

        unset($updating[$post_id]);
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
     * Track DCPI Form (ID 23) submission in client's ACF repeater field
     *
     * Saves dimension scores and deep clarity index to client_dcpi repeater.
     *
     * @param int    $entry_id  The submission entry ID.
     * @param array  $form_data The submitted form data.
     * @param object $form      The form object.
     */
    public function track_dcpi_form_submission($entry_id, $form_data, $form)
    {
        // Only process Form ID 23
        if ($form->id != 23) {
            return;
        }

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

        // Get current repeater data
        $client_dcpi = get_field('client_dcpi', $client_id);
        if (! is_array($client_dcpi)) {
            $client_dcpi = array();
        }

        // Build new DCPI entry with mapped fields
        // Dimension scores are rounded to whole numbers, deep_clarity_index keeps decimals
        $new_entry = array(
            'date'               => current_time('Y-m-d H:i:s'),
            'dimension_1_score'  => isset($form_data['dimension_1_score']) ? round(floatval($form_data['dimension_1_score'])) : 0,
            'dimension_2_score'  => isset($form_data['dimension_2_score']) ? round(floatval($form_data['dimension_2_score'])) : 0,
            'dimension_3_score'  => isset($form_data['dimension_3_score']) ? round(floatval($form_data['dimension_3_score'])) : 0,
            'dimension_4_score'  => isset($form_data['dimension_4_score']) ? round(floatval($form_data['dimension_4_score'])) : 0,
            'dimension_5_score'  => isset($form_data['dimension_5_score']) ? round(floatval($form_data['dimension_5_score'])) : 0,
            'deep_clarity_index' => isset($form_data['deep_clarity_index']) ? floatval($form_data['deep_clarity_index']) : 0,
        );

        // Add new entry to repeater
        $client_dcpi[] = $new_entry;

        // Update the repeater field
        update_field('client_dcpi', $client_dcpi, $client_id);
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
     * AJAX handler for getting form entry details
     */
    public function ajax_get_form_entry()
    {
        // Verify nonce
        if (! check_ajax_referer('deep_clarity_frontend', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
        $form_id  = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;

        if (! $entry_id || ! $form_id) {
            wp_send_json_error(array('message' => 'Missing entry or form ID'));
        }

        // Get submission from Fluent Forms
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
            wp_send_json_error(array('message' => 'Entry not found'));
        }

        // Decode the response data
        $response_data = json_decode($submission->response, true);

        if (! is_array($response_data)) {
            wp_send_json_error(array('message' => 'Invalid entry data'));
        }

        // Get form fields to map field names to labels
        $form_table = $wpdb->prefix . 'fluentform_forms';
        $form       = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$form_table} WHERE id = %d",
                $form_id
            )
        );

        $field_labels = array();
        if ($form && ! empty($form->form_fields)) {
            $form_fields = json_decode($form->form_fields, true);
            if (is_array($form_fields) && isset($form_fields['fields'])) {
                $field_labels = $this->extract_field_labels($form_fields['fields']);
            }
        }

        // Build questions and answers array
        $qa_pairs = array();
        foreach ($response_data as $field_name => $value) {
            // Skip internal fields
            if (in_array($field_name, array('_wp_http_referer', '_fluentform_'))) {
                continue;
            }

            // Get label or use field name
            $label = isset($field_labels[$field_name]) ? $field_labels[$field_name] : $field_name;

            // Format value
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $qa_pairs[] = array(
                'question' => $label,
                'answer'   => $value,
            );
        }

        wp_send_json_success(array(
            'form_name'  => $form ? $form->title : '',
            'entry_id'   => $entry_id,
            'created_at' => $submission->created_at,
            'qa_pairs'   => $qa_pairs,
        ));
    }

    /**
     * Extract field labels from Fluent Form fields
     *
     * @param array $fields Form fields array
     * @return array Associative array of field_name => label
     */
    private function extract_field_labels($fields)
    {
        $labels = array();

        foreach ($fields as $field) {
            if (isset($field['attributes']['name']) && isset($field['settings']['label'])) {
                $labels[$field['attributes']['name']] = $field['settings']['label'];
            }

            // Handle container fields with columns
            if (isset($field['columns']) && is_array($field['columns'])) {
                foreach ($field['columns'] as $column) {
                    if (isset($column['fields']) && is_array($column['fields'])) {
                        $labels = array_merge($labels, $this->extract_field_labels($column['fields']));
                    }
                }
            }

            // Handle nested fields
            if (isset($field['fields']) && is_array($field['fields'])) {
                $labels = array_merge($labels, $this->extract_field_labels($field['fields']));
            }
        }

        return $labels;
    }

    /**
     * AJAX handler for getting client sessions
     */
    public function ajax_get_client_sessions()
    {
        // Verify nonce
        if (! check_ajax_referer('deep_clarity_frontend', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;

        if (! $client_id) {
            wp_send_json_error(array('message' => 'Missing client ID'));
        }

        // Get sessions for this client
        $sessions_query = Sessions::get_sessions_for_client($client_id, array(
            'orderby' => 'date',
            'order'   => 'DESC',
        ));

        $sessions = array();

        if ($sessions_query->have_posts()) {
            while ($sessions_query->have_posts()) {
                $sessions_query->the_post();
                $session_id = get_the_ID();

                $sessions[] = array(
                    'id'    => $session_id,
                    'title' => get_the_title(),
                    'date'  => get_the_date('d.m.Y'),
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'sessions' => $sessions,
        ));
    }

    /**
     * AJAX handler for getting client forms
     */
    public function ajax_get_client_forms()
    {
        // Verify nonce
        if (! check_ajax_referer('deep_clarity_frontend', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;

        if (! $client_id) {
            wp_send_json_error(array('message' => 'Missing client ID'));
        }

        // Check if ACF is available
        if (! function_exists('get_field')) {
            wp_send_json_error(array('message' => 'ACF not available'));
        }

        // Get client forms from ACF repeater
        $client_forms = get_field('client_forms', $client_id);

        $forms = array();

        if (is_array($client_forms) && ! empty($client_forms)) {
            // Sort by date descending
            usort($client_forms, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            foreach ($client_forms as $form) {
                $date = isset($form['date']) ? $form['date'] : '';
                $date_formatted = '';
                if ($date) {
                    $timestamp = strtotime($date);
                    $date_formatted = date_i18n('d.m.Y H:i', $timestamp);
                }

                $forms[] = array(
                    'form_id'   => isset($form['form_id']) ? intval($form['form_id']) : 0,
                    'form_name' => isset($form['form_name']) ? $form['form_name'] : '',
                    'entry_id'  => isset($form['entry_id']) ? intval($form['entry_id']) : 0,
                    'date'      => $date_formatted,
                );
            }
        }

        wp_send_json_success(array(
            'forms' => $forms,
        ));
    }

    /**
     * AJAX handler for initializing dossier creation modal
     *
     * Returns all data needed for the dossier creation flow:
     * - Dossier count (to determine if first or subsequent)
     * - Anamnesebogen forms (ID 3)
     * - DCPI forms (ID 23)
     * - All sessions
     */
    public function ajax_init_dossier()
    {
        // Verify nonce
        if (! check_ajax_referer('deep_clarity_frontend', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;

        if (! $client_id) {
            wp_send_json_error(array('message' => 'Missing client ID'));
        }

        // Verify client exists
        $client = get_post($client_id);
        if (! $client || $client->post_type !== 'client') {
            wp_send_json_error(array('message' => 'Invalid client'));
        }

        // Check if ACF is available
        if (! function_exists('get_field')) {
            wp_send_json_error(array('message' => 'ACF not available'));
        }

        // Get dossier count by querying actual dossier posts (post_type: dosi)
        // with ACF field 'dossier_client' matching this client
        $dossier_query = new \WP_Query(array(
            'post_type'      => 'dosi',
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

        // Get client forms from ACF repeater
        $client_forms = get_field('client_forms', $client_id);

        $anamnese_forms = array(); // Form ID 3
        $dcpi_forms = array();     // Form ID 23

        if (is_array($client_forms) && ! empty($client_forms)) {
            // Sort by date descending
            usort($client_forms, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            foreach ($client_forms as $form) {
                $form_id = isset($form['form_id']) ? intval($form['form_id']) : 0;
                $date = isset($form['date']) ? $form['date'] : '';
                $date_formatted = '';
                if ($date) {
                    $timestamp = strtotime($date);
                    $date_formatted = date_i18n('d.m.Y H:i', $timestamp);
                }

                $form_data = array(
                    'form_id'   => $form_id,
                    'form_name' => isset($form['form_name']) ? $form['form_name'] : '',
                    'entry_id'  => isset($form['entry_id']) ? intval($form['entry_id']) : 0,
                    'date'      => $date_formatted,
                );

                if ($form_id === 3) {
                    $anamnese_forms[] = $form_data;
                } elseif ($form_id === 23) {
                    $dcpi_forms[] = $form_data;
                }
            }
        }

        // Get sessions for this client
        $sessions_query = Sessions::get_sessions_for_client($client_id, array(
            'orderby' => 'date',
            'order'   => 'DESC',
        ));

        $sessions = array();

        if ($sessions_query->have_posts()) {
            while ($sessions_query->have_posts()) {
                $sessions_query->the_post();
                $session_id = get_the_ID();

                $sessions[] = array(
                    'id'    => $session_id,
                    'title' => get_the_title(),
                    'date'  => get_the_date('d.m.Y'),
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'dossier_count'  => $dossier_count,
            'anamnese_forms' => $anamnese_forms,
            'dcpi_forms'     => $dcpi_forms,
            'sessions'       => $sessions,
        ));
    }

    /**
     * AJAX handler for creating dossier
     *
     * Expected POST data:
     * - client_id: Client post ID
     * - anamnese_entry_id: Entry ID of selected Anamnesebogen (Form ID 3)
     * - session_id: Selected session ID
     * - dcpi_entry_id: Entry ID of selected DCPI form (Form ID 23, optional)
     * - comparison_session_id: Session ID for comparison (optional, for 2nd+ dossier)
     * - comparison_dcpi_entry_id: Entry ID of comparison DCPI form (optional, for 2nd+ dossier)
     */
    public function ajax_create_dossier()
    {
        // Verify nonce
        if (! check_ajax_referer('deep_clarity_frontend', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $client_id               = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
        $anamnese_entry_id       = isset($_POST['anamnese_entry_id']) ? intval($_POST['anamnese_entry_id']) : 0;
        $session_id              = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
        $dcpi_entry_id           = isset($_POST['dcpi_entry_id']) ? intval($_POST['dcpi_entry_id']) : 0;
        $comparison_session_id   = isset($_POST['comparison_session_id']) ? intval($_POST['comparison_session_id']) : 0;
        $comparison_dcpi_entry_id = isset($_POST['comparison_dcpi_entry_id']) ? intval($_POST['comparison_dcpi_entry_id']) : 0;

        if (! $client_id || ! $anamnese_entry_id || ! $session_id) {
            wp_send_json_error(array('message' => 'Fehlende Pflichtfelder (Client, Anamnesebogen oder Session)'));
        }

        // Verify client exists
        $client = get_post($client_id);
        if (! $client || $client->post_type !== 'client') {
            wp_send_json_error(array('message' => 'Ungültiger Client'));
        }

        // Verify session exists
        $session = get_post($session_id);
        if (! $session || $session->post_type !== 'session') {
            wp_send_json_error(array('message' => 'Ungültige Session'));
        }

        // Build data for do_action with clear keys
        $dossier_data = array(
            'client_id'               => $client_id,
            'anamnese_entry_id'       => $anamnese_entry_id,
            'session_id'              => $session_id,
            'dcpi_entry_id'           => $dcpi_entry_id,
            'comparison_session_id'   => $comparison_session_id,
            'comparison_dcpi_entry_id' => $comparison_dcpi_entry_id,
        );

        // Trigger the do_action
        do_action('bit_pi_do_action', '1-1', $dossier_data);

        wp_send_json_success(array(
            'message' => 'Dossier-Generierung wurde gestartet.',
        ));
    }

    /**
     * AJAX handler for analyzing session
     */
    public function ajax_analyze_session()
    {
        // Verify nonce
        if (! check_ajax_referer('deep_clarity_frontend', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
        $fields     = isset($_POST['fields']) ? array_map('sanitize_text_field', (array) $_POST['fields']) : array();

        if (! $session_id) {
            wp_send_json_error(array('message' => 'Missing session ID'));
        }

        // Verify session exists
        $session = get_post($session_id);
        if (! $session || $session->post_type !== 'session') {
            wp_send_json_error(array('message' => 'Invalid session'));
        }

        // Check if ACF is available
        if (! function_exists('get_field')) {
            wp_send_json_error(array('message' => 'ACF not available'));
        }

        // Generate unique request ID
        $request_id = 'dc_analysis_' . $session_id . '_' . time() . '_' . wp_generate_password(8, false);

        // Allowed fields
        $allowed_fields = array('session_transcript', 'session_diagnosis', 'session_note');

        // Build data array with selected fields
        $data = array(
            'session_id' => $session_id,
            'request_id' => $request_id,
        );

        foreach ($fields as $field) {
            if (in_array($field, $allowed_fields, true)) {
                $data[$field] = get_field($field, $session_id);
            }
        }

        // Store initial status in transient (expires in 30 minutes)
        set_transient($request_id, array(
            'status'     => 'pending',
            'session_id' => $session_id,
            'created_at' => time(),
            'result'     => null,
        ), 30 * MINUTE_IN_SECONDS);

        // Trigger the do_action
        do_action('bit_pi_do_action', '2-1', $data);

        wp_send_json_success(array(
            'message'    => 'Analyse wurde gestartet...',
            'request_id' => $request_id,
        ));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes()
    {
        register_rest_route('deep-clarity/v1', '/analysis-callback', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_analysis_callback'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handle analysis callback from Bit Flows
     *
     * @param \WP_REST_Request $request The REST request.
     * @return \WP_REST_Response
     */
    public function handle_analysis_callback($request)
    {
        $request_id = $request->get_param('request_id');
        $result     = $request->get_param('result');
        $status     = $request->get_param('status') ?: 'complete';

        if (empty($request_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Missing request_id',
            ), 400);
        }

        // Get existing transient data
        $transient_data = get_transient($request_id);

        if (! $transient_data) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Request not found or expired',
            ), 404);
        }

        // Update status and result
        $transient_data['status']       = $status;
        $transient_data['result']       = $result;
        $transient_data['completed_at'] = time();

        // Store updated data (keep for 5 more minutes for polling)
        set_transient($request_id, $transient_data, 5 * MINUTE_IN_SECONDS);

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => 'Status updated',
        ), 200);
    }

    /**
     * AJAX handler for checking analysis status
     */
    public function ajax_check_analysis_status()
    {
        // Verify nonce
        if (! check_ajax_referer('deep_clarity_frontend', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $request_id = isset($_POST['request_id']) ? sanitize_text_field($_POST['request_id']) : '';

        if (empty($request_id)) {
            wp_send_json_error(array('message' => 'Missing request_id'));
        }

        // Get transient data
        $transient_data = get_transient($request_id);

        if (! $transient_data) {
            wp_send_json_error(array('message' => 'Request not found or expired'));
        }

        wp_send_json_success(array(
            'status' => $transient_data['status'],
            'result' => $transient_data['result'],
        ));
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
