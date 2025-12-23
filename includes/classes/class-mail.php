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
        $this->register_ajax_handlers();
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
     * @return bool
     */
    public function send($to, $subject, $message, $attachments = array(), $headers = array())
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

        return wp_mail($to, $subject, $html_message, $headers, $attachments);
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
        $template = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($subject) . '</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #111;
            margin-top: 1.5em;
            margin-bottom: 0.5em;
        }
        h1 { font-size: 24px; }
        h2 { font-size: 20px; }
        h3 { font-size: 18px; }
        p { margin: 1em 0; }
        ul, ol { margin: 1em 0; padding-left: 2em; }
        li { margin: 0.5em 0; }
        a { color: #0066cc; }
    </style>
</head>
<body>
    ' . $message . '
</body>
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
}
