<?php
/**
 * Dashboard admin view
 *
 * @package DeepClarity
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap deep-clarity-wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div class="deep-clarity-dashboard">
        <div class="deep-clarity-card">
            <h2><?php esc_html_e( 'Welcome to Deep Clarity', 'deep-clarity' ); ?></h2>
            <p><?php esc_html_e( 'Your analytics dashboard is ready.', 'deep-clarity' ); ?></p>
        </div>

        <div class="deep-clarity-card">
            <h3><?php esc_html_e( 'Statistics', 'deep-clarity' ); ?></h3>
            <canvas id="deep-clarity-chart" width="400" height="200"></canvas>
        </div>

        <div class="deep-clarity-card">
            <h3><?php esc_html_e( 'Quick Actions', 'deep-clarity' ); ?></h3>
            <button type="button" class="button button-primary" id="deep-clarity-demo-alert">
                <?php esc_html_e( 'Show Demo Alert', 'deep-clarity' ); ?>
            </button>
            <button type="button" class="button" id="deep-clarity-demo-tooltip" data-tippy-content="<?php esc_attr_e( 'This is a tooltip!', 'deep-clarity' ); ?>">
                <?php esc_html_e( 'Hover for Tooltip', 'deep-clarity' ); ?>
            </button>
        </div>
    </div>
</div>
