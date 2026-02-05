<?php
/**
 * Dossier PDF Generator
 *
 * @package DeepClarity
 */

namespace DeepClarity;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class DossierPDF
 *
 * Handles PDF generation for dossiers using mPDF.
 */
class DossierPDF
{

    /**
     * Single instance
     *
     * @var DossierPDF
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return DossierPDF
     */
    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // AJAX handlers
        add_action('wp_ajax_dc_create_dossier_pdf', array($this, 'ajax_create_pdf'));

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Register shortcode
        add_shortcode('dossier_pdf_url', array($this, 'shortcode_pdf_url'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts()
    {
        if (! is_singular('dossier')) {
            return;
        }

        wp_enqueue_script(
            'dc-dossier-pdf',
            DEEP_CLARITY_PLUGIN_URL . 'assets/js/dossier-pdf.js',
            array('jquery'),
            DEEP_CLARITY_VERSION,
            true
        );

        wp_localize_script('dc-dossier-pdf', 'dcDossierPdf', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('dc_create_dossier_pdf'),
            'strings' => array(
                'creating'     => __('PDF wird erstellt...', 'deep-clarity'),
                'success'      => __('PDF erfolgreich erstellt!', 'deep-clarity'),
                'error'        => __('Fehler beim Erstellen der PDF', 'deep-clarity'),
                'download'     => __('PDF herunterladen', 'deep-clarity'),
                'close'        => __('Schließen', 'deep-clarity'),
            ),
        ));
    }

    /**
     * AJAX handler for PDF creation
     */
    public function ajax_create_pdf()
    {
        // Verify nonce
        if (! check_ajax_referer('dc_create_dossier_pdf', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Ungültige Anfrage'));
        }

        // Check if user is logged in
        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Nicht autorisiert'));
        }

        // Get dossier ID
        $dossier_id = isset($_POST['dossier_id']) ? intval($_POST['dossier_id']) : 0;

        if (! $dossier_id) {
            wp_send_json_error(array('message' => 'Keine Dossier-ID angegeben'));
        }

        // Check if post exists and is a dossier
        $dossier = get_post($dossier_id);
        if (! $dossier || $dossier->post_type !== 'dossier') {
            wp_send_json_error(array('message' => 'Dossier nicht gefunden'));
        }

        // Get dossier content
        $content = get_field('dossier_content', $dossier_id);
        if (empty($content)) {
            wp_send_json_error(array('message' => 'Kein Dossier-Inhalt vorhanden'));
        }

        // Get client data for filename
        $client = get_field('dossier_client', $dossier_id);
        $client_id = is_array($client) ? $client[0] : $client;
        $client_id = is_object($client_id) ? $client_id->ID : $client_id;

        $vorname = '';
        $nachname = '';

        if ($client_id) {
            $vorname = get_field('client_firstname', $client_id) ?: '';
            $nachname = get_field('client_lastname', $client_id) ?: '';
        }

        // Fallback to dossier title if no client
        if (empty($vorname) && empty($nachname)) {
            $nachname = sanitize_title($dossier->post_title);
        }

        // Generate filename
        $datum = date('Y-m-d');
        $filename = sprintf(
            'dossier-%s-%s-%s.pdf',
            sanitize_file_name(strtolower($vorname)),
            sanitize_file_name(strtolower($nachname)),
            $datum
        );

        // Delete existing PDF if exists
        $existing_pdf = get_field('dossier_pdf', $dossier_id);
        if ($existing_pdf) {
            $attachment_id = is_array($existing_pdf) ? $existing_pdf['ID'] : $existing_pdf;
            if ($attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }
        }

        // Create PDF
        try {
            $pdf_path = $this->generate_pdf($content, $filename, $dossier_id);

            if (! $pdf_path || ! file_exists($pdf_path)) {
                wp_send_json_error(array('message' => 'PDF konnte nicht erstellt werden'));
            }

            // Upload to media library
            $attachment_id = $this->upload_pdf_to_media($pdf_path, $filename, $dossier_id);

            if (is_wp_error($attachment_id)) {
                wp_send_json_error(array('message' => $attachment_id->get_error_message()));
            }

            // Update ACF field
            update_field('dossier_pdf', $attachment_id, $dossier_id);

            // Get PDF URL
            $pdf_url = wp_get_attachment_url($attachment_id);

            // Clean up temp file
            if (file_exists($pdf_path)) {
                unlink($pdf_path);
            }

            wp_send_json_success(array(
                'message' => 'PDF erfolgreich erstellt',
                'pdf_url' => $pdf_url,
                'attachment_id' => $attachment_id,
            ));

        } catch (\Exception $e) {
            wp_send_json_error(array('message' => 'Fehler: ' . $e->getMessage()));
        }
    }

    /**
     * Generate PDF using mPDF
     *
     * @param string $content HTML content.
     * @param string $filename Filename.
     * @param int    $dossier_id Dossier ID.
     * @return string|false Path to generated PDF or false on failure.
     */
    private function generate_pdf($content, $filename, $dossier_id)
    {
        // Check if mPDF is available
        if (! class_exists('Mpdf\Mpdf')) {
            throw new \Exception('mPDF ist nicht installiert');
        }

        // Create temp directory if not exists
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/dc-temp';

        if (! file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }

        // Get client data for header
        $client = get_field('dossier_client', $dossier_id);
        $client_id = is_array($client) ? $client[0] : $client;
        $client_id = is_object($client_id) ? $client_id->ID : $client_id;

        $client_name = '';
        if ($client_id) {
            $vorname = get_field('client_firstname', $client_id) ?: '';
            $nachname = get_field('client_lastname', $client_id) ?: '';
            $client_name = trim($vorname . ' ' . $nachname);
        }

        // Configure mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10,
            'tempDir' => $temp_dir,
        ]);

        // Set document info
        $mpdf->SetTitle('Deep Clarity Dossier - ' . $client_name);
        $mpdf->SetAuthor('Deep Clarity');
        $mpdf->SetCreator('Deep Clarity WordPress Plugin');

        // Add CSS
        $css = $this->get_pdf_styles();
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        // Add header
        $mpdf->SetHTMLHeader($this->get_pdf_header($client_name));

        // Add footer
        $mpdf->SetHTMLFooter($this->get_pdf_footer());

        // Write content
        $mpdf->WriteHTML($content, \Mpdf\HTMLParserMode::HTML_BODY);

        // Output to file
        $pdf_path = $temp_dir . '/' . $filename;
        $mpdf->Output($pdf_path, \Mpdf\Output\Destination::FILE);

        return $pdf_path;
    }

    /**
     * Get PDF styles
     *
     * @return string CSS styles.
     */
    private function get_pdf_styles()
    {
        return '
        <style>
            body {
                font-family: "DejaVu Sans", sans-serif;
                font-size: 11pt;
                line-height: 1.6;
                color: #333;
            }
            h1 {
                font-size: 24pt;
                color: #1a1a2e;
                margin-bottom: 20px;
                border-bottom: 2px solid #4a90d9;
                padding-bottom: 10px;
            }
            h2 {
                font-size: 18pt;
                color: #1a1a2e;
                margin-top: 30px;
                margin-bottom: 15px;
                border-bottom: 1px solid #ddd;
                padding-bottom: 8px;
            }
            h3 {
                font-size: 14pt;
                color: #333;
                margin-top: 20px;
                margin-bottom: 10px;
            }
            h4 {
                font-size: 12pt;
                color: #555;
                margin-top: 15px;
                margin-bottom: 8px;
            }
            p {
                margin-bottom: 12px;
                text-align: justify;
            }
            ul, ol {
                margin-bottom: 15px;
                padding-left: 25px;
            }
            li {
                margin-bottom: 8px;
            }
            strong {
                color: #1a1a2e;
            }
            em {
                font-style: italic;
            }
            mark {
                background-color: #fff3cd;
                padding: 2px 4px;
            }
            blockquote {
                border-left: 4px solid #4a90d9;
                padding-left: 15px;
                margin-left: 0;
                color: #555;
                font-style: italic;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
            }
            th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .cover-section {
                text-align: center;
                margin-bottom: 40px;
                padding: 30px;
                background-color: #f8f9fa;
                border-radius: 8px;
            }
            .cover-section h2 {
                border: none;
                margin-top: 0;
            }
        </style>
        ';
    }

    /**
     * Get PDF header
     *
     * @param string $client_name Client name.
     * @return string HTML header.
     */
    private function get_pdf_header($client_name)
    {
        $logo_url = DEEP_CLARITY_PLUGIN_URL . 'assets/images/logo.png';

        return '
        <div style="border-bottom: 1px solid #ddd; padding-bottom: 5px; font-size: 9pt; color: #666;">
            <table width="100%">
                <tr>
                    <td style="border: none; padding: 0;">Deep Clarity Executive Leadership Dossier</td>
                    <td style="border: none; padding: 0; text-align: right;">' . esc_html($client_name) . '</td>
                </tr>
            </table>
        </div>
        ';
    }

    /**
     * Get PDF footer
     *
     * @return string HTML footer.
     */
    private function get_pdf_footer()
    {
        return '
        <div style="border-top: 1px solid #ddd; padding-top: 5px; font-size: 9pt; color: #666;">
            <table width="100%">
                <tr>
                    <td style="border: none; padding: 0;">Vertraulich</td>
                    <td style="border: none; padding: 0; text-align: center;">© Deep Clarity ' . date('Y') . '</td>
                    <td style="border: none; padding: 0; text-align: right;">Seite {PAGENO} von {nbpg}</td>
                </tr>
            </table>
        </div>
        ';
    }

    /**
     * Upload PDF to media library
     *
     * @param string $pdf_path Path to PDF file.
     * @param string $filename Filename.
     * @param int    $dossier_id Dossier ID.
     * @return int|WP_Error Attachment ID or error.
     */
    private function upload_pdf_to_media($pdf_path, $filename, $dossier_id)
    {
        // Read file content
        $file_content = file_get_contents($pdf_path);

        // Get upload directory
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'] . '/' . $filename;

        // Write file to uploads directory
        file_put_contents($upload_path, $file_content);

        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => 'application/pdf',
            'post_title'     => sanitize_file_name($filename),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_parent'    => $dossier_id,
        );

        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $upload_path, $dossier_id);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Generate attachment metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return $attachment_id;
    }

    /**
     * Shortcode: dossier_pdf_url
     *
     * Outputs the URL of the dossier PDF if it exists.
     *
     * Usage: [dossier_pdf_url]
     * Options: [dossier_pdf_url post_id="123"]
     *
     * @param array $atts Shortcode attributes.
     * @return string PDF URL or empty string.
     */
    public function shortcode_pdf_url($atts)
    {
        $atts = shortcode_atts(array(
            'post_id' => 0,
        ), $atts, 'dossier_pdf_url');

        // Get post ID
        $post_id = intval($atts['post_id']) ?: get_the_ID();

        if (! $post_id) {
            return '';
        }

        // Get PDF attachment
        $pdf = get_field('dossier_pdf', $post_id);

        if (! $pdf) {
            return '';
        }

        // Get URL
        if (is_array($pdf)) {
            return esc_url($pdf['url']);
        }

        return esc_url(wp_get_attachment_url($pdf));
    }
}
