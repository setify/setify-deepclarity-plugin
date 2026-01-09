<?php
/**
 * Admin class for managing the WordPress admin interface
 *
 * @package DeepClarity
 */

namespace DeepClarity;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Admin
 *
 * Handles all admin-related functionality.
 */
class Admin {

    /**
     * The menu slug
     *
     * @var string
     */
    private $menu_slug = 'deep-clarity';

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __( 'Deep Clarity', 'deep-clarity' ),
            __( 'Deep Clarity', 'deep-clarity' ),
            'manage_options',
            $this->menu_slug,
            array( $this, 'render_dashboard_page' ),
            'dashicons-chart-area',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            $this->menu_slug,
            __( 'Dashboard', 'deep-clarity' ),
            __( 'Dashboard', 'deep-clarity' ),
            'manage_options',
            $this->menu_slug,
            array( $this, 'render_dashboard_page' )
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        include DEEP_CLARITY_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
}
