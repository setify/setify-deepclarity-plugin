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
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_filter( 'plugin_action_links_' . DEEP_CLARITY_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
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

        // Settings submenu
        add_submenu_page(
            $this->menu_slug,
            __( 'Settings', 'deep-clarity' ),
            __( 'Settings', 'deep-clarity' ),
            'manage_options',
            $this->menu_slug . '-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'deep_clarity_settings',
            'deep_clarity_options',
            array( $this, 'sanitize_settings' )
        );

        // General settings section
        add_settings_section(
            'deep_clarity_general',
            __( 'General Settings', 'deep-clarity' ),
            array( $this, 'render_general_section' ),
            'deep_clarity_settings'
        );

        // Add settings fields
        add_settings_field(
            'enable_tracking',
            __( 'Enable Tracking', 'deep-clarity' ),
            array( $this, 'render_checkbox_field' ),
            'deep_clarity_settings',
            'deep_clarity_general',
            array(
                'id'          => 'enable_tracking',
                'description' => __( 'Enable data tracking functionality.', 'deep-clarity' ),
            )
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $input The input array.
     * @return array Sanitized array.
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        if ( isset( $input['enable_tracking'] ) ) {
            $sanitized['enable_tracking'] = (bool) $input['enable_tracking'];
        }

        return $sanitized;
    }

    /**
     * Render general section description
     */
    public function render_general_section() {
        echo '<p>' . esc_html__( 'Configure the general settings for Deep Clarity.', 'deep-clarity' ) . '</p>';
    }

    /**
     * Render checkbox field
     *
     * @param array $args Field arguments.
     */
    public function render_checkbox_field( $args ) {
        $options = get_option( 'deep_clarity_options', array() );
        $value   = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : false;

        printf(
            '<label><input type="checkbox" id="%1$s" name="deep_clarity_options[%1$s]" value="1" %2$s /> %3$s</label>',
            esc_attr( $args['id'] ),
            checked( $value, true, false ),
            esc_html( $args['description'] )
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

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        include DEEP_CLARITY_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links.
     * @return array Modified links.
     */
    public function add_plugin_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=' . $this->menu_slug . '-settings' ) . '">' . __( 'Settings', 'deep-clarity' ) . '</a>',
        );

        return array_merge( $plugin_links, $links );
    }

    /**
     * Get option value
     *
     * @param string $key     Option key.
     * @param mixed  $default Default value.
     * @return mixed Option value.
     */
    public function get_option( $key, $default = false ) {
        $options = get_option( 'deep_clarity_options', array() );
        return isset( $options[ $key ] ) ? $options[ $key ] : $default;
    }
}
