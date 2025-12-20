<?php

/**
 * Sessions class for custom queries
 *
 * @package DeepClarity
 */

namespace DeepClarity;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Sessions
 *
 * Handles session-related queries and functionality.
 */
class Sessions
{

    /**
     * Post type name
     *
     * @var string
     */
    const POST_TYPE = 'session';

    /**
     * ACF relation field name
     *
     * @var string
     */
    const ACF_CLIENT_FIELD = 'session_client';

    /**
     * ACF transcript file field name
     *
     * @var string
     */
    const ACF_TRANSCRIPT_FILE_FIELD = 'session_transcript_file';

    /**
     * ACF transcript textarea field name
     *
     * @var string
     */
    const ACF_TRANSCRIPT_FIELD = 'session_transcript';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->register_query_filters();
        $this->register_save_hooks();
    }

    /**
     * Register save hooks for transcript processing
     */
    private function register_save_hooks()
    {
        // WordPress Backend save
        add_action('acf/save_post', array($this, 'process_transcript_file'), 20);

        // Frontend Admin plugin support
        add_action('frontend_admin/save_post', array($this, 'process_transcript_file'), 20);
    }

    /**
     * Register custom query filters for Unlimited Elements
     */
    private function register_query_filters()
    {
        // Unlimited Elements custom query filter
        add_filter('sessions_current_client', array($this, 'query_sessions_current_client'), 10, 2);
    }

    /**
     * Query sessions for current client (Unlimited Elements)
     *
     * Returns sessions where the ACF relation field contains the current post ID.
     *
     * @param array $args        The query arguments array.
     * @param array $widget_data Widget settings.
     * @return array Modified query arguments.
     */
    public function query_sessions_current_client($args, $widget_data)
    {
        // Get current post ID
        $current_post_id = get_the_ID();

        if (! $current_post_id) {
            return $args;
        }

        // Set post type
        $args['post_type'] = self::POST_TYPE;

        // Build meta query for ACF relation field
        $meta_query = array(
            'relation' => 'OR',
            // For serialized array (multiple values)
            array(
                'key'     => self::ACF_CLIENT_FIELD,
                'value'   => '"' . $current_post_id . '"',
                'compare' => 'LIKE',
            ),
            // For single value (stored as post ID directly)
            array(
                'key'     => self::ACF_CLIENT_FIELD,
                'value'   => $current_post_id,
                'compare' => '=',
            ),
        );

        // Merge with existing meta_query if present
        if (isset($args['meta_query']) && is_array($args['meta_query'])) {
            $args['meta_query'][] = $meta_query;
        } else {
            $args['meta_query'] = array($meta_query);
        }

        return $args;
    }

    /**
     * Get sessions for a specific client
     *
     * @param int   $client_id Client post ID.
     * @param array $args      Additional query arguments.
     * @return \WP_Query
     */
    public static function get_sessions_for_client($client_id, $args = array())
    {
        $default_args = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
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

        $query_args = wp_parse_args($args, $default_args);

        return new \WP_Query($query_args);
    }

    /**
     * Process transcript file on post save
     *
     * Reads the VTT file content and saves it to the transcript textarea field.
     *
     * @param int $post_id The post ID being saved.
     */
    public function process_transcript_file($post_id)
    {
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Skip revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Only process session post type
        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }

        // Get the transcript file
        $file = get_field(self::ACF_TRANSCRIPT_FILE_FIELD, $post_id);

        if (! $file) {
            return;
        }

        // Get file URL or path
        $file_url = is_array($file) ? $file['url'] : $file;

        if (empty($file_url)) {
            return;
        }

        // Convert URL to local path
        $file_path = $this->url_to_path($file_url);

        if (! $file_path || ! file_exists($file_path)) {
            return;
        }

        // Read file content
        $content = file_get_contents($file_path);

        if ($content === false) {
            return;
        }

        // Parse VTT content
        $transcript = $this->parse_vtt_content($content);

        // Update the transcript field
        update_field(self::ACF_TRANSCRIPT_FIELD, $transcript, $post_id);
    }

    /**
     * Convert URL to local file path
     *
     * @param string $url The file URL.
     * @return string|false The local path or false on failure.
     */
    private function url_to_path($url)
    {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_dir = $upload_dir['basedir'];

        // Check if URL is in uploads directory
        if (strpos($url, $base_url) === 0) {
            return str_replace($base_url, $base_dir, $url);
        }

        return false;
    }

    /**
     * Parse VTT file content
     *
     * Extracts text content from VTT format, removing timestamps and metadata.
     *
     * @param string $content The raw VTT file content.
     * @return string The parsed transcript text.
     */
    private function parse_vtt_content($content)
    {
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Split into lines
        $lines = preg_split('/\r\n|\r|\n/', $content);

        $transcript_lines = array();
        $skip_next = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                $skip_next = false;
                continue;
            }

            // Skip WEBVTT header
            if (strpos($line, 'WEBVTT') === 0) {
                continue;
            }

            // Skip NOTE comments
            if (strpos($line, 'NOTE') === 0) {
                $skip_next = true;
                continue;
            }

            // Skip cue identifiers (numeric or alphanumeric)
            if (preg_match('/^[\d\w-]+$/', $line) && strpos($line, ' ') === false) {
                continue;
            }

            // Skip timestamp lines (00:00:00.000 --> 00:00:00.000)
            if (preg_match('/^\d{2}:\d{2}[:\.][\d\.]+\s*-->\s*\d{2}:\d{2}[:\.][\d\.]+/', $line)) {
                continue;
            }

            // Skip if flagged
            if ($skip_next) {
                continue;
            }

            // Remove inline tags like <v Speaker> or <c>
            $line = preg_replace('/<[^>]+>/', '', $line);
            $line = trim($line);

            if (! empty($line)) {
                $transcript_lines[] = $line;
            }
        }

        return implode("\n", $transcript_lines);
    }
}
