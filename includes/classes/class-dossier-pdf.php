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
        // Load on dossier pages or when shortcode/button might be present
        if (! is_singular('dossier') && ! is_post_type_archive('dossier')) {
            // Also check if we're on a page that might have the button
            global $post;
            if (! $post || (strpos($post->post_content, 'create_dossier_pdf') === false && strpos($post->post_content, 'dossier_pdf') === false)) {
                return;
            }
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
        $fonts_dir = $upload_dir['basedir'] . '/dc-fonts';

        if (! file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }

        if (! file_exists($fonts_dir)) {
            wp_mkdir_p($fonts_dir);
        }

        // Download fonts if not exists
        $this->ensure_fonts_exist($fonts_dir);

        // Get client data for header/footer
        $client = get_field('dossier_client', $dossier_id);
        $client_id = is_array($client) ? $client[0] : $client;
        $client_id = is_object($client_id) ? $client_id->ID : $client_id;

        $vorname = '';
        $nachname = '';
        $client_name = '';
        if ($client_id) {
            $vorname = get_field('client_firstname', $client_id) ?: '';
            $nachname = get_field('client_lastname', $client_id) ?: '';
            $client_name = trim($vorname . ' ' . $nachname);
        }

        // Define custom fonts
        $default_font_config = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $font_dirs = $default_font_config['fontDir'];

        $default_font_data = (new \Mpdf\Config\FontVariables())->getDefaults();
        $font_data = $default_font_data['fontdata'];

        // Configure mPDF with custom fonts
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 20,
            'margin_right' => 20,
            'margin_top' => 25,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
            'tempDir' => $temp_dir,
            'fontDir' => array_merge($font_dirs, [$fonts_dir]),
            'fontdata' => $font_data + [
                'rergian' => [
                    'R' => 'Rergian.woff',
                ],
                'grift' => [
                    'R' => 'Grift-Variable-VF.ttf',
                ],
            ],
            'default_font' => 'grift',
        ]);

        // Set document info
        $mpdf->SetTitle('Deep Clarity Dossier - ' . $client_name);
        $mpdf->SetAuthor('Deep Clarity');
        $mpdf->SetCreator('Deep Clarity WordPress Plugin');

        // Set background image for all pages
        $bg_image = 'https://deepclarity.de/wp-content/uploads/dc-paper_normal.jpg';
        $mpdf->SetDefaultBodyCSS('background', "url('$bg_image')");
        $mpdf->SetDefaultBodyCSS('background-image-resize', 6);

        // Add CSS
        $css = $this->get_pdf_styles();
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        // Add footer (no header)
        $mpdf->SetHTMLFooter($this->get_pdf_footer($vorname, $nachname));

        // Write content
        $mpdf->WriteHTML($content, \Mpdf\HTMLParserMode::HTML_BODY);

        // Output to file
        $pdf_path = $temp_dir . '/' . $filename;
        $mpdf->Output($pdf_path, \Mpdf\Output\Destination::FILE);

        return $pdf_path;
    }

    /**
     * Ensure custom fonts exist in the fonts directory
     *
     * @param string $fonts_dir Path to fonts directory.
     */
    private function ensure_fonts_exist($fonts_dir)
    {
        $fonts = [
            'Rergian.woff' => 'https://deepclarity.de/wp-content/uploads/Rergian.woff',
            'Grift-Variable-VF.ttf' => 'https://deepclarity.de/wp-content/uploads/Grift-Variable-VF.ttf',
        ];

        foreach ($fonts as $filename => $url) {
            $local_path = $fonts_dir . '/' . $filename;
            if (! file_exists($local_path)) {
                $response = wp_remote_get($url, ['timeout' => 30]);
                if (! is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    file_put_contents($local_path, wp_remote_retrieve_body($response));
                }
            }
        }
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
                font-family: grift, sans-serif;
                font-size: 11pt;
                line-height: 1.7;
                color: #27282b;
            }
            h1, h2, h3, h4, h5, h6 {
                font-family: rergian, serif;
                color: #332a22;
                font-weight: normal;
            }
            h1 {
                font-size: 28pt;
                margin-bottom: 24px;
                margin-top: 0;
            }
            h2 {
                font-size: 20pt;
                margin-top: 32px;
                margin-bottom: 16px;
            }
            h3 {
                font-size: 16pt;
                margin-top: 24px;
                margin-bottom: 12px;
            }
            h4 {
                font-size: 13pt;
                margin-top: 18px;
                margin-bottom: 10px;
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
                color: #332a22;
            }
            em {
                font-style: italic;
            }
            mark {
                background-color: rgba(185, 174, 155, 0.4);
                padding: 2px 4px;
            }
            blockquote {
                border-left: 3px solid #7b6e56;
                padding-left: 15px;
                margin-left: 0;
                color: #4a3f36;
                font-style: italic;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #b9ae9b;
                padding: 10px;
                text-align: left;
            }
            th {
                background-color: rgba(185, 174, 155, 0.3);
                font-family: rergian, serif;
                color: #332a22;
            }
            .cover-section {
                text-align: center;
                margin-bottom: 40px;
                padding: 30px;
            }
            .cover-section h2 {
                margin-top: 0;
            }
        </style>
        ';
    }

    /**
     * Get PDF footer
     *
     * @param string $vorname Client first name.
     * @param string $nachname Client last name.
     * @return string HTML footer.
     */
    private function get_pdf_footer($vorname = '', $nachname = '')
    {
        $client_name = trim($vorname . ' ' . $nachname);
        $dossier_text = $client_name ? 'Dossier - ' . esc_html($client_name) : 'Dossier';

        return '
        <div style="font-family: grift, sans-serif; font-size: 9pt; color: #7b6e56;">
            <table width="100%">
                <tr>
                    <td style="border: none; padding: 0;">' . $dossier_text . '</td>
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
