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
    const ACF_CLIENT_FIELD = 'client'; // Adjust field name as needed

    /**
     * Constructor
     */
    public function __construct() {
        $this->register_query_filters();
    }

    /**
     * Register custom query filters for Elementor
     */
    private function register_query_filters() {
        // Custom query: sessions_current_client
        add_filter( 'elementor/query/sessions_current_client', array( $this, 'query_sessions_current_client' ), 10, 2 );
    }

    /**
     * Query sessions for current client
     *
     * Returns sessions where the ACF relation field contains the current post ID.
     *
     * @param \WP_Query $query      The query object.
     * @param array     $widget_data Widget settings.
     * @return \WP_Query Modified query.
     */
    public function query_sessions_current_client( $query, $widget_data ) {
        // Get current post ID
        $current_post_id = get_the_ID();

        if ( ! $current_post_id ) {
            return $query;
        }

        // Modify query arguments
        $query->set( 'post_type', self::POST_TYPE );

        // Get existing meta query or create new array
        $meta_query = $query->get( 'meta_query' );
        if ( ! is_array( $meta_query ) ) {
            $meta_query = array();
        }

        // Add meta query for ACF relation field
        // ACF stores relation field values as serialized arrays
        $meta_query[] = array(
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

        $query->set( 'meta_query', $meta_query );

        return $query;
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
