<?php
/**
 * Access control class for restricting post types to logged-in users
 *
 * @package DeepClarity
 */

namespace DeepClarity;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Access
 *
 * Restricts access to certain post types for non-logged-in users.
 */
class Access
{

    /**
     * Post types that require login
     *
     * @var array
     */
    private $restricted_post_types = array(
        'dossier',
        'client',
        'mail',
        'session',
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('template_redirect', array($this, 'restrict_post_type_access'));
    }

    /**
     * Restrict access to certain post types
     *
     * Redirects non-logged-in users to the login page when trying to access
     * restricted post types.
     *
     * @return void
     */
    public function restrict_post_type_access()
    {
        // Skip if user is logged in
        if (is_user_logged_in()) {
            return;
        }

        // Skip if in admin area
        if (is_admin()) {
            return;
        }

        // Check if we're viewing a singular post of a restricted type
        if (is_singular($this->restricted_post_types)) {
            $this->redirect_to_login();
            return;
        }

        // Check if we're viewing an archive of a restricted type
        foreach ($this->restricted_post_types as $post_type) {
            if (is_post_type_archive($post_type)) {
                $this->redirect_to_login();
                return;
            }
        }

        // Check taxonomy archives related to restricted post types
        $queried_object = get_queried_object();
        if ($queried_object && is_a($queried_object, 'WP_Term')) {
            $taxonomy = get_taxonomy($queried_object->taxonomy);
            if ($taxonomy && is_array($taxonomy->object_type)) {
                foreach ($taxonomy->object_type as $object_type) {
                    if (in_array($object_type, $this->restricted_post_types, true)) {
                        $this->redirect_to_login();
                        return;
                    }
                }
            }
        }
    }

    /**
     * Redirect to login page
     *
     * @return void
     */
    private function redirect_to_login()
    {
        $redirect_url = wp_login_url(get_permalink());
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Get restricted post types
     *
     * @return array
     */
    public function get_restricted_post_types()
    {
        return $this->restricted_post_types;
    }

    /**
     * Check if a post type is restricted
     *
     * @param string $post_type The post type to check.
     * @return bool
     */
    public function is_restricted_post_type($post_type)
    {
        return in_array($post_type, $this->restricted_post_types, true);
    }
}
