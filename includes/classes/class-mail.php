<?php
/**
 * Mail Class
 *
 * Handles email sending functionality with AJAX support
 *
 * @package DeepClarity
 */

namespace DeepClarity;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Mail
 */
class Mail
{
    /**
     * Instance
     *
     * @var Mail
     */
    private static $instance = null;

    /**
     * Allowed HTML tags for email content
     *
     * @var array
     */
    private $allowed_html = array(
        'h1' => array(),
        'h2' => array(),
        'h3' => array(),
        'p' => array(),
        'br' => array(),
        'strong' => array(),
        'b' => array(),
        'em' => array(),
        'i' => array(),
        'ul' => array(),
        'ol' => array(),
        'li' => array(),
        'a' => array(
            'href' => array(),
            'title' => array(),
            'target' => array(),
        ),
    );

    /**
     * Get instance
     *
     * @return Mail
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->register_post_type();
        $this->register_ajax_handlers();
        $this->register_query_filters();
    }

    /**
     * Register custom query filters for Unlimited Elements
     */
    private function register_query_filters()
    {
        add_filter('mails_current_client', array($this, 'query_mails_current_client'), 10, 2);
    }

    /**
     * Query mails for current client (Unlimited Elements)
     *
     * Returns mails where the ACF relation field contains the current post ID.
     *
     * @param array $args        The query arguments array.
     * @param array $widget_data Widget settings.
     * @return array Modified query arguments.
     */
    public function query_mails_current_client($args, $widget_data)
    {
        $current_post_id = get_the_ID();

        if (!$current_post_id) {
            return $args;
        }

        $args['post_type'] = 'mail';
        $args['post_status'] = 'any';
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';

        $meta_query = array(
            'relation' => 'OR',
            array(
                'key'     => 'mail_client',
                'value'   => '"' . $current_post_id . '"',
                'compare' => 'LIKE',
            ),
            array(
                'key'     => 'mail_client',
                'value'   => $current_post_id,
                'compare' => '=',
            ),
        );

        if (isset($args['meta_query']) && is_array($args['meta_query'])) {
            $args['meta_query'][] = $meta_query;
        } else {
            $args['meta_query'] = array($meta_query);
        }

        return $args;
    }

    /**
     * Register mail post type
     */
    private function register_post_type()
    {
        add_action('init', function () {
            register_post_type('mail', array(
                'labels' => array(
                    'name'               => __('E-Mails', 'deep-clarity'),
                    'singular_name'      => __('E-Mail', 'deep-clarity'),
                    'add_new'            => __('Neue E-Mail', 'deep-clarity'),
                    'add_new_item'       => __('Neue E-Mail erstellen', 'deep-clarity'),
                    'edit_item'          => __('E-Mail bearbeiten', 'deep-clarity'),
                    'new_item'           => __('Neue E-Mail', 'deep-clarity'),
                    'view_item'          => __('E-Mail ansehen', 'deep-clarity'),
                    'search_items'       => __('E-Mails suchen', 'deep-clarity'),
                    'not_found'          => __('Keine E-Mails gefunden', 'deep-clarity'),
                    'not_found_in_trash' => __('Keine E-Mails im Papierkorb', 'deep-clarity'),
                ),
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'menu_icon'           => 'dashicons-email',
                'supports'            => array('title', 'editor', 'author'),
                'capability_type'     => 'post',
                'has_archive'         => false,
                'exclude_from_search' => true,
            ));
        });
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers()
    {
        add_action('wp_ajax_deep_clarity_send_mail', array($this, 'ajax_send_mail'));
        add_action('wp_ajax_nopriv_deep_clarity_send_mail', array($this, 'ajax_send_mail'));
    }

    /**
     * AJAX handler for sending mail
     */
    public function ajax_send_mail()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'deep_clarity_frontend')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitsprüfung fehlgeschlagen.', 'deep-clarity'),
            ));
        }

        // Get and sanitize data
        $to = isset($_POST['to']) ? sanitize_email($_POST['to']) : '';
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $message = isset($_POST['message']) ? wp_kses($_POST['message'], $this->allowed_html) : '';

        // Validate required fields
        if (empty($to) || !is_email($to)) {
            wp_send_json_error(array(
                'message' => __('Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'deep-clarity'),
            ));
        }

        if (empty($subject)) {
            wp_send_json_error(array(
                'message' => __('Bitte geben Sie einen Betreff ein.', 'deep-clarity'),
            ));
        }

        if (empty($message)) {
            wp_send_json_error(array(
                'message' => __('Bitte geben Sie eine Nachricht ein.', 'deep-clarity'),
            ));
        }

        // Handle attachments
        $attachments = array();
        if (!empty($_FILES['attachments'])) {
            $attachments = $this->handle_attachments($_FILES['attachments']);
            if (is_wp_error($attachments)) {
                wp_send_json_error(array(
                    'message' => $attachments->get_error_message(),
                ));
            }
        }

        // Send email
        $result = $this->send($to, $subject, $message, $attachments);

        // Clean up temporary attachment files
        $this->cleanup_attachments($attachments);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('E-Mail wurde erfolgreich gesendet.', 'deep-clarity'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('E-Mail konnte nicht gesendet werden.', 'deep-clarity'),
            ));
        }
    }

    /**
     * Handle file attachments
     *
     * @param array $files $_FILES array
     * @return array|WP_Error Array of file paths or error
     */
    private function handle_attachments($files)
    {
        $attachments = array();
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/deep-clarity-temp/';

        // Create temp directory if not exists
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }

        // Handle multiple files
        if (is_array($files['name'])) {
            $file_count = count($files['name']);
            for ($i = 0; $i < $file_count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $filename = sanitize_file_name($files['name'][$i]);
                    $temp_file = $temp_dir . uniqid() . '_' . $filename;

                    if (move_uploaded_file($files['tmp_name'][$i], $temp_file)) {
                        $attachments[] = $temp_file;
                    }
                }
            }
        } else {
            if ($files['error'] === UPLOAD_ERR_OK) {
                $filename = sanitize_file_name($files['name']);
                $temp_file = $temp_dir . uniqid() . '_' . $filename;

                if (move_uploaded_file($files['tmp_name'], $temp_file)) {
                    $attachments[] = $temp_file;
                }
            }
        }

        return $attachments;
    }

    /**
     * Cleanup temporary attachment files
     *
     * @param array $attachments Array of file paths
     */
    private function cleanup_attachments($attachments)
    {
        foreach ($attachments as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Send email
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message (HTML)
     * @param array $attachments Optional attachments
     * @param array $headers Optional headers
     * @param int|null $author_id Author for mail post (null = current user, 0 = system)
     * @return bool
     */
    public function send($to, $subject, $message, $attachments = array(), $headers = array(), $author_id = null)
    {
        // Default headers for HTML email
        if (empty($headers)) {
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
            );

            // Add from header if configured
            $from_email = get_option('admin_email');
            $from_name = get_option('blogname');
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        // Wrap message in HTML template
        $html_message = $this->wrap_html_template($message, $subject);

        // Apply filter for custom modifications
        $html_message = apply_filters('deep_clarity_mail_message', $html_message, $to, $subject);
        $headers = apply_filters('deep_clarity_mail_headers', $headers, $to, $subject);
        $attachments = apply_filters('deep_clarity_mail_attachments', $attachments, $to, $subject);

        $result = wp_mail($to, $subject, $html_message, $headers, $attachments);

        // Create mail post if email was sent successfully
        if ($result) {
            $this->create_mail_post($to, $subject, $message, $author_id, $attachments);
        }

        return $result;
    }

    /**
     * Wrap message in HTML template
     *
     * @param string $message Message content
     * @param string $subject Email subject
     * @return string
     */
    private function wrap_html_template($message, $subject)
    {
        $template = '<!--
* This email was built using Tabular.
* For more information, visit https://tabular.email
-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="de">
<head>
<title>' . esc_html($subject) . '</title>
<meta charset="UTF-8" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!--[if !mso]>-->
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<!--<![endif]-->
<meta name="x-apple-disable-message-reformatting" content="" />
<meta content="target-densitydpi=device-dpi" name="viewport" />
<meta content="true" name="HandheldFriendly" />
<meta content="width=device-width" name="viewport" />
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no" />
<style type="text/css">
table {
border-collapse: separate;
table-layout: fixed;
mso-table-lspace: 0pt;
mso-table-rspace: 0pt
}
table td {
border-collapse: collapse
}
.ExternalClass {
width: 100%
}
.ExternalClass,
.ExternalClass p,
.ExternalClass span,
.ExternalClass font,
.ExternalClass td,
.ExternalClass div {
line-height: 100%
}
body, a, li, p, h1, h2, h3 {
-ms-text-size-adjust: 100%;
-webkit-text-size-adjust: 100%;
}
html {
-webkit-text-size-adjust: none !important
}
body {
min-width: 100%;
Margin: 0px;
padding: 0px;
}
body, #innerTable {
-webkit-font-smoothing: antialiased;
-moz-osx-font-smoothing: grayscale
}
#innerTable img+div {
display: none;
display: none !important
}
img {
Margin: 0;
padding: 0;
-ms-interpolation-mode: bicubic
}
h1, h2, h3, p, a {
overflow-wrap: normal;
white-space: normal;
word-break: break-word
}
a {
text-decoration: none
}
h1, h2, h3, p {
min-width: 100%!important;
width: 100%!important;
max-width: 100%!important;
display: inline-block!important;
border: 0;
padding: 0;
margin: 0
}
a[x-apple-data-detectors] {
color: inherit !important;
text-decoration: none !important;
font-size: inherit !important;
font-family: inherit !important;
font-weight: inherit !important;
line-height: inherit !important
}
u + #body a {
color: inherit;
text-decoration: none;
font-size: inherit;
font-family: inherit;
font-weight: inherit;
line-height: inherit;
}
a[href^="mailto"],
a[href^="tel"],
a[href^="sms"] {
color: inherit;
text-decoration: none
}
</style>
<style type="text/css">
@media (min-width: 481px) {
.hd { display: none!important }
}
</style>
<style type="text/css">
@media (max-width: 480px) {
.hm { display: none!important }
}
</style>
<style type="text/css">
@media (max-width: 480px) {
.t44{text-align:left!important}.t35,.t43{vertical-align:top!important;width:600px!important}
}
</style>
<!--[if !mso]>-->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&amp;display=swap" rel="stylesheet" type="text/css" />
<!--<![endif]-->
<!--[if mso]>
<xml>
<o:OfficeDocumentSettings>
<o:AllowPNG/>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml>
<![endif]-->
</head>
<body id="body" class="t54" style="min-width:100%;Margin:0px;padding:0px;background-color:#F1EDE9;"><div class="t53" style="background-color:#F1EDE9;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" align="center"><tr><td class="t52" style="font-size:0;line-height:0;mso-line-height-rule:exactly;background-color:#F1EDE9;" valign="top" align="center">
<!--[if mso]>
<v:background xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false">
<v:fill color="#F1EDE9"/>
</v:background>
<![endif]-->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" align="center" id="innerTable"><tr><td align="center">
<table class="t8" role="presentation" cellpadding="0" cellspacing="0" style="Margin-left:auto;Margin-right:auto;"><tr><td width="600" class="t7" style="width:600px;">
<table class="t6" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="width:100%;"><tr><td class="t5" style="overflow:hidden;padding:32px 32px 32px 32px;border-radius:0 0 8px 8px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100% !important;"><tr><td align="center">
<table class="t4" role="presentation" cellpadding="0" cellspacing="0" style="Margin-left:auto;Margin-right:auto;"><tr><td width="280" class="t3" style="width:280px;">
<table class="t2" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="width:100%;"><tr><td class="t1"><div style="font-size:0px;"><img class="t0" style="display:block;border:0;height:auto;width:100%;Margin:0;max-width:100%;" width="280" height="28.4375" alt="" src="https://deepclarity.de/wp-content/uploads/text_deep_clarity_small_brown_2500px.png"/></div></td></tr></table>
</td></tr></table>
</td></tr></table></td></tr></table>
</td></tr></table>
</td></tr><tr><td align="center">
<table class="t27" role="presentation" cellpadding="0" cellspacing="0" style="Margin-left:auto;Margin-right:auto;"><tr><td width="600" class="t26" style="width:600px;">
<table class="t25" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="width:100%;"><tr><td class="t24" style="overflow:hidden;border-radius:8px 8px 8px 8px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100% !important;"><tr><td align="center">
<table class="t23" role="presentation" cellpadding="0" cellspacing="0" style="Margin-left:auto;Margin-right:auto;"><tr><td width="600" class="t22" style="width:600px;">
<table class="t21" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="width:100%;"><tr><td class="t20" style="border:1px solid #E0D9D0;overflow:hidden;background-color:#FFFFFF;padding:32px 32px 32px 32px;border-radius:8px 8px 8px 8px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100% !important;"><tr><td align="center">
<table class="t13" role="presentation" cellpadding="0" cellspacing="0" style="Margin-left:auto;Margin-right:auto;"><tr><td width="534" class="t12" style="width:600px;">
<table class="t11" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="width:100%;"><tr><td class="t10"><p class="t9" style="margin:0;Margin:0;font-family:Inter,BlinkMacSystemFont,Segoe UI,Helvetica Neue,Arial,sans-serif;line-height:28px;font-weight:400;font-style:normal;font-size:14px;text-decoration:none;text-transform:none;direction:ltr;color:#494C51;text-align:left;mso-line-height-rule:exactly;mso-text-raise:4px;">' . $message . '</p></td></tr></table>
</td></tr></table>
</td></tr><tr><td><div class="t15" style="mso-line-height-rule:exactly;mso-line-height-alt:32px;line-height:32px;font-size:1px;display:block;">&nbsp;&nbsp;</div></td></tr><tr><td align="left">
<table class="t19" role="presentation" cellpadding="0" cellspacing="0" style="Margin-right:auto;"><tr><td width="160" class="t18" style="width:160px;">
<table class="t17" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="width:100%;"><tr><td class="t16"><div style="font-size:0px;"><img class="t14" style="display:block;border:0;height:auto;width:100%;Margin:0;max-width:100%;" width="160" height="56" alt="" src="https://deepclarity.de/wp-content/uploads/signatur_timo.png"/></div></td></tr></table>
</td></tr></table>
</td></tr></table></td></tr></table>
</td></tr></table>
</td></tr></table></td></tr></table>
</td></tr></table>
</td></tr><tr><td align="center">
<table class="t51" role="presentation" cellpadding="0" cellspacing="0" style="Margin-left:auto;Margin-right:auto;"><tr><td width="600" class="t50" style="width:600px;">
<table class="t49" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="width:100%;"><tr><td class="t48"><div class="t47" style="width:100%;text-align:left;"><div class="t46" style="display:inline-block;"><table class="t45" role="presentation" cellpadding="0" cellspacing="0" align="left" valign="top">
<tr class="t44"><td></td><td class="t35" width="300" valign="top">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="t34" style="width:100%;"><tr><td class="t33"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100% !important;"><tr><td align="center">
<table class="t32" role="presentation" cellpadding="0" cellspacing="0" style="Margin-left:auto;Margin-right:auto;"><tr><td width="300" class="t31" style="width:600px;">
<table class="t30" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="width:100%;"><tr><td class="t29" style="padding:32px 32px 32px 32px;"><p class="t28" style="margin:0;Margin:0;font-family:Inter,BlinkMacSystemFont,Segoe UI,Helvetica Neue,Arial,sans-serif;line-height:22px;font-weight:500;font-style:normal;font-size:11px;text-decoration:none;text-transform:none;direction:ltr;color:#7E6D54;text-align:left;mso-line-height-rule:exactly;mso-text-raise:3px;">&copy; Copyright Deep Clarity<br/>Alle Rechte vorbehalten <br/><br/>www.deepclarity.de<br/>mail@deepclarity.de&nbsp; <br/><br/>Gesch&auml;ftsf&uuml;hrer:&nbsp; Timo Wenzel Registergericht: Amtsgericht K&ouml;ln Registernummer: HRB XXX<br/>Umsatzsteuer-IdNr: DE XXX</p></td></tr></table>
</td></tr></table>
</td></tr></table></td></tr></table>
</td><td class="t43" width="300" valign="top">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="t42" style="width:100%;"><tr><td class="t41"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100% !important;"><tr><td align="center">
<table class="t40" role="presentation" cellpadding="0" cellspacing="0" style="Margin-left:auto;Margin-right:auto;"><tr><td width="300" class="t39" style="width:600px;">
<table class="t38" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="width:100%;"><tr><td class="t37" style="padding:32px 32px 32px 32px;"><p class="t36" style="margin:0;Margin:0;font-family:Inter,BlinkMacSystemFont,Segoe UI,Helvetica Neue,Arial,sans-serif;line-height:22px;font-weight:500;font-style:normal;font-size:11px;text-decoration:none;text-transform:none;direction:ltr;color:#7E6D54;text-align:left;mso-line-height-rule:exactly;mso-text-raise:3px;">Folg mir auf:<br/><br/>Hinweis: Die Inhalte und Produkte stellen keinen medizinischen Rat dar und richten sich an k&ouml;rperlich und psychisch gesunde Menschen. <br/><br/>Impressum | Datenschutz</p></td></tr></table>
</td></tr></table>
</td></tr></table></td></tr></table>
</td>
<td></td></tr>
</table></div></div></td></tr></table>
</td></tr></table>
</td></tr></table></td></tr></table></div><div class="gmail-fix" style="display: none; white-space: nowrap; font: 15px courier; line-height: 0;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div></body>
</html>';

        return $template;
    }

    /**
     * Send email with template
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $template_name Template name
     * @param array $data Template data
     * @param array $attachments Optional attachments
     * @return bool
     */
    public function send_template($to, $subject, $template_name, $data = array(), $attachments = array())
    {
        $template_path = DEEP_CLARITY_PATH . 'templates/emails/' . $template_name . '.php';

        if (!file_exists($template_path)) {
            return false;
        }

        ob_start();
        extract($data);
        include $template_path;
        $message = ob_get_clean();

        return $this->send($to, $subject, $message, $attachments);
    }

    /**
     * Create mail post after sending
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message (HTML)
     * @param int|null $author_id Author user ID (null = current user, 0 = system/user 1)
     * @param array $attachments Array of file paths that were attached to the email
     * @return int|false Post ID on success, false on failure
     */
    public function create_mail_post($to, $subject, $message, $author_id = null, $attachments = array())
    {
        // Determine author
        if ($author_id === null) {
            $author_id = get_current_user_id();
        }

        // If no user is logged in or system mail, use user 1
        if (empty($author_id) || $author_id === 0) {
            $author_id = 1;
        }

        // Create the mail post
        $post_data = array(
            'post_type'    => 'mail',
            'post_title'   => $subject,
            'post_content' => $message,
            'post_status'  => 'publish',
            'post_author'  => $author_id,
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id) || empty($post_id)) {
            return false;
        }

        // Find client by email and set ACF relation field
        $client_id = $this->find_client_by_email($to);
        if ($client_id) {
            update_field('mail_client', $client_id, $post_id);
        }

        // Store recipient email as meta (useful for reference)
        update_post_meta($post_id, '_mail_recipient', $to);

        // Upload attachments to media library and save to ACF field
        if (!empty($attachments)) {
            $attachment_ids = $this->upload_attachments_to_media($attachments, $post_id);
            if (!empty($attachment_ids)) {
                update_field('mail_attachments', $attachment_ids, $post_id);
            }
        }

        return $post_id;
    }

    /**
     * Upload attachment files to WordPress media library
     *
     * @param array $file_paths Array of file paths
     * @param int $parent_post_id Parent post ID for attachments
     * @return array Array of attachment IDs
     */
    private function upload_attachments_to_media($file_paths, $parent_post_id)
    {
        $attachment_ids = array();

        // Require WordPress file handling functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        foreach ($file_paths as $file_path) {
            if (!file_exists($file_path)) {
                continue;
            }

            // Get file info
            $filename = basename($file_path);
            $filetype = wp_check_filetype($filename);

            // Prepare upload directory
            $upload_dir = wp_upload_dir();
            $target_path = $upload_dir['path'] . '/' . $filename;

            // Copy file to uploads directory (original will be cleaned up separately)
            if (copy($file_path, $target_path)) {
                // Prepare attachment data
                $attachment_data = array(
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                );

                // Insert attachment
                $attachment_id = wp_insert_attachment($attachment_data, $target_path, $parent_post_id);

                if (!is_wp_error($attachment_id)) {
                    // Generate metadata
                    $attach_data = wp_generate_attachment_metadata($attachment_id, $target_path);
                    wp_update_attachment_metadata($attachment_id, $attach_data);

                    $attachment_ids[] = $attachment_id;
                }
            }
        }

        return $attachment_ids;
    }

    /**
     * Find client post by email address
     *
     * @param string $email Email address to search for
     * @return int|false Client post ID or false if not found
     */
    public function find_client_by_email($email)
    {
        if (empty($email) || !is_email($email)) {
            return false;
        }

        // Query for client posts with matching email in ACF field
        $args = array(
            'post_type'      => 'client',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'meta_query'     => array(
                array(
                    'key'     => 'client_email',
                    'value'   => $email,
                    'compare' => '=',
                ),
            ),
        );

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            return $query->posts[0]->ID;
        }

        return false;
    }

    /**
     * Get mails for a specific client
     *
     * @param int   $client_id Client post ID.
     * @param array $args      Additional query arguments.
     * @return \WP_Query
     */
    public static function get_mails_for_client($client_id, $args = array())
    {
        $default_args = array(
            'post_type'      => 'mail',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => 'mail_client',
                    'value'   => '"' . $client_id . '"',
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => 'mail_client',
                    'value'   => $client_id,
                    'compare' => '=',
                ),
            ),
        );

        $query_args = wp_parse_args($args, $default_args);

        return new \WP_Query($query_args);
    }
}
