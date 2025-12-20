<?php
/**
 * Assets class for managing CSS and JavaScript
 *
 * @package DeepClarity
 */

namespace DeepClarity;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Assets
 *
 * Handles the registration and enqueuing of scripts and styles.
 */
class Assets {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        // Priority 100 to load after Elementor (default 10) and Elementor Pro
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 100 );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on our plugin pages
        if ( strpos( $hook, 'deep-clarity' ) === false ) {
            return;
        }

        // Vendor styles
        wp_enqueue_style(
            'sweetalert2',
            DEEP_CLARITY_PLUGIN_URL . 'assets/css/vendor/sweetalert2.min.css',
            array(),
            DEEP_CLARITY_VERSION
        );

        // Plugin admin styles
        wp_enqueue_style(
            'deep-clarity-admin',
            DEEP_CLARITY_PLUGIN_URL . 'assets/css/admin.css',
            array( 'sweetalert2' ),
            DEEP_CLARITY_VERSION
        );

        // Vendor scripts
        wp_enqueue_script(
            'chartjs',
            DEEP_CLARITY_PLUGIN_URL . 'assets/js/vendor/chart.min.js',
            array(),
            DEEP_CLARITY_VERSION,
            true
        );

        wp_enqueue_script(
            'sweetalert2',
            DEEP_CLARITY_PLUGIN_URL . 'assets/js/vendor/sweetalert2.min.js',
            array(),
            DEEP_CLARITY_VERSION,
            true
        );

        wp_enqueue_script(
            'tippy',
            DEEP_CLARITY_PLUGIN_URL . 'assets/js/vendor/tippy-bundle.umd.min.js',
            array(),
            DEEP_CLARITY_VERSION,
            true
        );

        wp_enqueue_script(
            'popper',
            DEEP_CLARITY_PLUGIN_URL . 'assets/js/vendor/popper.min.js',
            array(),
            DEEP_CLARITY_VERSION,
            true
        );

        // Plugin admin script
        wp_enqueue_script(
            'deep-clarity-admin',
            DEEP_CLARITY_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'chartjs', 'sweetalert2', 'tippy' ),
            DEEP_CLARITY_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script(
            'deep-clarity-admin',
            'deepClarityAdmin',
            array(
                'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'deep_clarity_nonce' ),
                'pluginUrl' => DEEP_CLARITY_PLUGIN_URL,
                'i18n'     => array(
                    'confirmDelete' => __( 'Are you sure you want to delete this?', 'deep-clarity' ),
                    'success'       => __( 'Success!', 'deep-clarity' ),
                    'error'         => __( 'Error!', 'deep-clarity' ),
                ),
            )
        );
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_assets() {
        // Build dependencies array - only add if styles are registered
        $style_deps = array();

        if ( wp_style_is( 'elementor-frontend', 'registered' ) ) {
            $style_deps[] = 'elementor-frontend';
        }

        if ( wp_style_is( 'elementor-pro', 'registered' ) ) {
            $style_deps[] = 'elementor-pro';
        }

        // Frontend styles - loads after Elementor
        wp_enqueue_style(
            'deep-clarity-frontend',
            DEEP_CLARITY_PLUGIN_URL . 'assets/css/frontend.css',
            $style_deps,
            DEEP_CLARITY_VERSION
        );

        // Frontend scripts
        wp_enqueue_script(
            'deep-clarity-frontend',
            DEEP_CLARITY_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            DEEP_CLARITY_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'deep-clarity-frontend',
            'deepClarityFrontend',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'deep_clarity_frontend_nonce' ),
            )
        );
    }

    /**
     * Register a custom script
     *
     * @param string $handle    Script handle.
     * @param string $src       Script source URL.
     * @param array  $deps      Dependencies.
     * @param bool   $in_footer Load in footer.
     */
    public function register_script( $handle, $src, $deps = array(), $in_footer = true ) {
        wp_register_script(
            $handle,
            $src,
            $deps,
            DEEP_CLARITY_VERSION,
            $in_footer
        );
    }

    /**
     * Register a custom style
     *
     * @param string $handle Style handle.
     * @param string $src    Style source URL.
     * @param array  $deps   Dependencies.
     */
    public function register_style( $handle, $src, $deps = array() ) {
        wp_register_style(
            $handle,
            $src,
            $deps,
            DEEP_CLARITY_VERSION
        );
    }
}
