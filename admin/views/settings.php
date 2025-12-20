<?php
/**
 * Settings admin view
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

    <form method="post" action="options.php" class="deep-clarity-settings-form">
        <?php
        settings_fields( 'deep_clarity_settings' );
        do_settings_sections( 'deep_clarity_settings' );
        submit_button();
        ?>
    </form>
</div>
