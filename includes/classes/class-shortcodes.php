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
     * ACF field name for note client relation
     */
    public const ACF_NOTE_CLIENT_FIELD = 'note_client';

    /**
     * Register all shortcodes
     */
    private function register_shortcodes()
    {
        add_shortcode('session_client_name', array($this, 'session_client_name'));
        add_shortcode('notes_client_list', array($this, 'notes_client_list'));
        add_shortcode('check_url_client_id', array($this, 'check_url_client_id'));
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

    /**
     * Shortcode: notes_client_list
     *
     * Outputs a list of notes linked to the current client post.
     *
     * Usage: [notes_client_list]
     *
     * @param array $atts Shortcode attributes.
     * @return string Notes list HTML or empty string.
     */
    public function notes_client_list($atts)
    {
        $atts = shortcode_atts(array(
            'empty_message' => '',
        ), $atts, 'notes_client_list');

        $client_id = get_the_ID();

        if (! $client_id) {
            return '';
        }

        // Query notes for this client
        $args = array(
            'post_type'      => 'note',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => self::ACF_NOTE_CLIENT_FIELD,
                    'value'   => '"' . $client_id . '"',
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => self::ACF_NOTE_CLIENT_FIELD,
                    'value'   => $client_id,
                    'compare' => '=',
                ),
            ),
        );

        $notes = new \WP_Query($args);

        if (! $notes->have_posts()) {
            return $atts['empty_message'] ? '<p>' . esc_html($atts['empty_message']) . '</p>' : '';
        }

        $output = '<div class="dc-notes-list" data-client-id="' . esc_attr($client_id) . '">';

        while ($notes->have_posts()) {
            $notes->the_post();
            $note_id = get_the_ID();
            $content = get_the_content();
            $date    = get_the_date('d.m.Y H:i');

            $output .= '<div class="dc-note" data-note-id="' . esc_attr($note_id) . '">';
            $output .= '<div class="dc-note-actions">';
            $output .= '<button type="button" class="dc-note-edit" data-note-id="' . esc_attr($note_id) . '" title="Bearbeiten">';
            $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.96 4.97l-9.9 9.89c-.16.15-.3.29-.42.46 -.11.14-.19.3-.25.46 -.08.18-.12.38-.16.6l-.9 4.43c-.04.17-.08.35-.09.5 -.02.15-.03.39.07.63 .12.3.37.55.67.67 .24.1.47.09.63.07 .15-.02.32-.06.5-.09l4.43-.9c.22-.05.41-.09.6-.16 .16-.07.31-.15.46-.25 .16-.12.3-.26.46-.42L21.04 8.87c.37-.38.69-.7.92-.98 .24-.3.45-.6.56-.96 .17-.56.17-1.15 0-1.7 -.12-.36-.33-.66-.57-.96 -.24-.28-.56-.6-.93-.98l-.52-.52c-.38-.38-.7-.7-.98-.93 -.3-.25-.6-.46-.96-.57 -.56-.18-1.15-.18-1.7 0 -.36.11-.66.32-.96.56 -.28.23-.6.55-.98.92l-2.09 2.08c-.01 0-.01 0-.01 0 -.01 0-.01 0-.01 0Zm4.48-2.17c.25-.09.52-.09.77 0 .09.03.22.09.44.28 .22.19.49.46.9.86l.47.47c.4.4.67.67.86.9 .18.21.25.34.28.44 .08.25.08.52 0 .77 -.04.09-.1.22-.29.44 -.2.22-.47.49-.87.9l-1.54 1.53 -3.94-3.94 1.53-1.54c.4-.41.67-.68.9-.87 .21-.19.34-.26.44-.29Zm-3.95 3.75l3.93 3.93 -9.34 9.33c-.21.2-.26.24-.3.27 -.05.03-.11.06-.16.08 -.05.01-.11.03-.4.09l-4.45.89 .89-4.45c.05-.29.07-.35.09-.4 .02-.06.04-.11.08-.16 .02-.05.06-.09.27-.29l9.34-9.35Z"></path></svg>';
            $output .= '</button>';
            $output .= '<button type="button" class="dc-note-delete" data-note-id="' . esc_attr($note_id) . '" title="Löschen">';
            $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M10.2 1.24c-.39-.01-.74-.01-1.06.09 -.29.08-.55.22-.78.41 -.27.21-.46.49-.67.82l-.62.92c-.29.42-.35.51-.42.56 -.08.06-.17.1-.26.13 -.09.02-.19.03-.69.03H2.97c-.42 0-.75.33-.75.75 0 .41.33.75.75.75h1.99 0l.79 0c.05 0 .1 0 .15-.01h12.09c.05 0 .1 0 .15 0l.79-.01h0 1.99c.41 0 .75-.34.75-.75 0-.42-.34-.75-.75-.75h-2.73c-.51-.01-.61-.01-.69-.04 -.1-.03-.19-.08-.26-.14 -.07-.06-.13-.14-.42-.57l-.62-.93c-.22-.33-.41-.61-.67-.83 -.23-.19-.5-.33-.78-.42 -.33-.1-.67-.1-1.06-.1h-3.6Zm5.39 3l-.52-.77c-.29-.43-.35-.52-.42-.57 -.08-.07-.17-.11-.26-.14 -.09-.03-.19-.04-.7-.04h-3.44c-.52 0-.62 0-.7.03 -.1.02-.19.07-.26.13 -.07.05-.13.13-.42.56l-.52.76h7.19Z"></path><g><path d="M3.94 8.06c-.04-.42.26-.78.67-.82 .41-.04.77.26.81.67l.9 9.64c.07.78.12 1.32.19 1.74 .07.41.15.64.26.82 .22.38.56.69.96.88 .18.08.43.15.85.18s.97.03 1.75.03h3.25c.78 0 1.32-.01 1.75-.04 .41-.04.66-.1.85-.19 .4-.2.74-.5.96-.89 .1-.19.19-.42.26-.83 .07-.43.12-.97.19-1.75l.9-9.65c.03-.42.4-.72.81-.68 .41.03.71.4.67.81l-.91 9.67c-.07.74-.13 1.34-.21 1.83 -.09.5-.22.93-.46 1.34 -.39.63-.95 1.15-1.62 1.47 -.43.2-.87.28-1.38.32 -.5.03-1.1.03-1.85.03h-3.32c-.75 0-1.35 0-1.85-.04 -.51-.04-.96-.13-1.38-.33 -.68-.32-1.24-.84-1.62-1.48 -.24-.41-.37-.84-.46-1.35 -.09-.49-.15-1.09-.21-1.83l-.91-9.68Z"></path><path d="M13.25 17.5v-7c0-.42.33-.75.75-.75 .41 0 .75.33.75.75v7c0 .41-.34.75-.75.75 -.42 0-.75-.34-.75-.75Z"></path><path d="M9.25 10.5v7c0 .41.33.75.75.75 .41 0 .75-.34.75-.75v-7.01c0-.42-.34-.75-.75-.75 -.42 0-.75.33-.75.75Z"></path></g></svg>';
            $output .= '</button>';
            $output .= '</div>';
            $output .= '<div class="dc-note-content">' . wp_kses_post($content) . '</div>';
            $output .= '<div class="dc-note-date">' . esc_html($date) . '</div>';
            $output .= '</div>';
        }

        wp_reset_postdata();

        $output .= '</div>';

        return $output;
    }

    /**
     * Shortcode: check_url_client_id
     *
     * Checks if URL parameter 'client_id' exists, the client post exists,
     * and ACF field 'client_status' equals 'Aktiv'.
     *
     * Usage: [check_url_client_id]
     *
     * @param array $atts Shortcode attributes.
     * @return string 'true' if all conditions met, empty string otherwise.
     */
    public function check_url_client_id($atts)
    {
        // Check if client_id URL parameter exists
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

        if (! $client_id) {
            return '';
        }

        // Check if client post exists
        $client = get_post($client_id);

        if (! $client || $client->post_type !== 'client') {
            return '';
        }

        // Check if client_status ACF field equals 'Aktiv'
        $status = get_field('client_status', $client_id);

        if ($status === 'Aktiv') {
            return 'true';
        }

        return '';
    }
}
