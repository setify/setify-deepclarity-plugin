<?php
/**
 * Sessions class for custom queries
 *
 * @package DeepClarity
 */

namespace DeepClarity;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Sessions
 *
 * Handles session-related queries and functionality.
 */
class Sessions {

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
    const ACF_CLIENT_FIELD = 'client';

    /**
     * Constructor
     */
    public function __construct() {
        $this->register_query_filters();
    }

    /**
     * Register custom query filters for Unlimited Elements
     */
    private function register_query_filters() {
        // Unlimited Elements custom query filter
        add_filter( 'sessions_current_client', array( $this, 'query_sessions_current_client' ), 10, 2 );
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
    public function query_sessions_current_client( $args, $widget_data ) {
        // Get current post ID
        $current_post_id = get_the_ID();

        if ( ! $current_post_id ) {
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
        if ( isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
            $args['meta_query'][] = $meta_query;
        } else {
            $args['meta_query'] = array( $meta_query );
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
    public static function get_sessions_for_client( $client_id, $args = array() ) {
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

        $query_args = wp_parse_args( $args, $default_args );

        return new \WP_Query( $query_args );
    }
}
