<?php

/**
 * Notes class
 *
 * @package DeepClarity
 */

namespace DeepClarity;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Notes
 *
 * Handles notes functionality including AJAX operations.
 */
class Notes
{

    /**
     * Single instance of the class
     *
     * @var Notes
     */
    private static $instance = null;

    /**
     * Post type name
     */
    public const POST_TYPE = 'note';

    /**
     * ACF field name for note client relation
     */
    public const ACF_CLIENT_FIELD = 'note_client';

    /**
     * Get single instance of the class
     *
     * @return Notes
     */
    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // AJAX handlers
        add_action('wp_ajax_deep_clarity_get_note', array($this, 'ajax_get_note'));
        add_action('wp_ajax_nopriv_deep_clarity_get_note', array($this, 'ajax_get_note'));

        add_action('wp_ajax_deep_clarity_update_note', array($this, 'ajax_update_note'));
        add_action('wp_ajax_nopriv_deep_clarity_update_note', array($this, 'ajax_update_note'));

        add_action('wp_ajax_deep_clarity_delete_note', array($this, 'ajax_delete_note'));
        add_action('wp_ajax_nopriv_deep_clarity_delete_note', array($this, 'ajax_delete_note'));

        // Auto-set post title to post ID on save
        add_action('save_post_' . self::POST_TYPE, array($this, 'set_post_title_to_id'), 10, 3);
    }

    /**
     * AJAX handler: Get note content
     */
    public function ajax_get_note()
    {
        // Verify nonce
        if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'deep_clarity_frontend_nonce')) {
            wp_send_json_error(array('message' => 'Ungültige Anfrage.'));
        }

        // Get note ID
        $note_id = isset($_POST['note_id']) ? intval($_POST['note_id']) : 0;

        if (! $note_id) {
            wp_send_json_error(array('message' => 'Keine Notiz-ID angegeben.'));
        }

        // Get the note post
        $note = get_post($note_id);

        if (! $note || $note->post_type !== self::POST_TYPE) {
            wp_send_json_error(array('message' => 'Notiz nicht gefunden.'));
        }

        // Return note data (raw content for editing)
        wp_send_json_success(array(
            'id'      => $note->ID,
            'content' => $note->post_content,
        ));
    }

    /**
     * AJAX handler: Update note content
     */
    public function ajax_update_note()
    {
        // Verify nonce
        if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'deep_clarity_frontend_nonce')) {
            wp_send_json_error(array('message' => 'Ungültige Anfrage.'));
        }

        // Get note ID and content
        $note_id = isset($_POST['note_id']) ? intval($_POST['note_id']) : 0;
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';

        if (! $note_id) {
            wp_send_json_error(array('message' => 'Keine Notiz-ID angegeben.'));
        }

        // Get the note post
        $note = get_post($note_id);

        if (! $note || $note->post_type !== self::POST_TYPE) {
            wp_send_json_error(array('message' => 'Notiz nicht gefunden.'));
        }

        // Update the post
        $result = wp_update_post(array(
            'ID'           => $note_id,
            'post_content' => $content,
        ), true);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Fehler beim Speichern: ' . $result->get_error_message()));
        }

        // Return updated content (formatted for display)
        wp_send_json_success(array(
            'id'      => $note_id,
            'content' => wpautop($content),
        ));
    }

    /**
     * AJAX handler: Delete note
     */
    public function ajax_delete_note()
    {
        // Verify nonce
        if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'deep_clarity_frontend_nonce')) {
            wp_send_json_error(array('message' => 'Ungültige Anfrage.'));
        }

        // Get note ID
        $note_id = isset($_POST['note_id']) ? intval($_POST['note_id']) : 0;

        if (! $note_id) {
            wp_send_json_error(array('message' => 'Keine Notiz-ID angegeben.'));
        }

        // Get the note post
        $note = get_post($note_id);

        if (! $note || $note->post_type !== self::POST_TYPE) {
            wp_send_json_error(array('message' => 'Notiz nicht gefunden.'));
        }

        // Delete the post (force delete, no trash)
        $result = wp_delete_post($note_id, true);

        if (! $result) {
            wp_send_json_error(array('message' => 'Notiz konnte nicht gelöscht werden.'));
        }

        wp_send_json_success(array(
            'message' => 'Notiz wurde gelöscht.',
        ));
    }

    /**
     * Set post title to post ID after save
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @param bool     $update  Whether this is an existing post being updated.
     */
    public function set_post_title_to_id($post_id, $post, $update)
    {
        // Avoid infinite loop
        remove_action('save_post_' . self::POST_TYPE, array($this, 'set_post_title_to_id'), 10);

        // Only update title if it doesn't match the ID
        if ($post->post_title !== (string) $post_id) {
            wp_update_post(array(
                'ID'         => $post_id,
                'post_title' => (string) $post_id,
            ));
        }

        // Re-add the action
        add_action('save_post_' . self::POST_TYPE, array($this, 'set_post_title_to_id'), 10, 3);
    }

    /**
     * Get notes for a specific client
     *
     * @param int   $client_id Client post ID.
     * @param array $args      Optional. Additional WP_Query arguments.
     * @return \WP_Query Query results.
     */
    public static function get_notes_for_client($client_id, $args = array())
    {
        $default_args = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => self::ACF_CLIENT_FIELD,
                    'value'   => '"' . $client_id . '"',
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => self::ACF_CLIENT_FIELD,
                    'value'   => $client_id,
                    'compare' => '=',
                ),
            ),
        );

        return new \WP_Query(wp_parse_args($args, $default_args));
    }
}
