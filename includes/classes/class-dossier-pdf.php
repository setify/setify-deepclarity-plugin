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

        // Build HTML from structure and template
        $content = $this->build_html_from_structure($dossier_id);
        if (empty($content)) {
            // Get debug info about the dossier_structure field
            $structure_json = get_field('dossier_structure', $dossier_id);
            $debug_info = array(
                'field_empty' => empty($structure_json),
                'field_type' => gettype($structure_json),
                'field_length' => is_string($structure_json) ? strlen($structure_json) : 'n/a',
                'first_100_chars' => is_string($structure_json) ? substr($structure_json, 0, 100) : 'n/a',
            );

            // Try to decode and get error
            if (is_string($structure_json) && !empty($structure_json)) {
                $test = json_decode($structure_json, true);
                $debug_info['json_decode_result'] = ($test === null) ? 'failed' : 'success';
                $debug_info['json_error'] = json_last_error_msg();
            }

            wp_send_json_error(array(
                'message' => 'Kein Dossier-Inhalt vorhanden. Bitte prüfen Sie, ob dossier_structure gefüllt ist.',
                'debug' => $debug_info
            ));
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
                'regina' => [
                    'R' => 'Regina.ttf',
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

        // Background images
        $bg_front = 'https://deepclarity.de/wp-content/uploads/dc-paper_front.jpg';
        $bg_normal = 'https://deepclarity.de/wp-content/uploads/dc-paper_normal.jpg';

        // Add CSS
        $css = $this->get_pdf_styles();
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        // === FIRST PAGE: Empty cover with front background ===
        $mpdf->SetDefaultBodyCSS('background', "url('$bg_front')");
        $mpdf->SetDefaultBodyCSS('background-image-resize', 6);

        // No footer on first page
        $mpdf->SetHTMLFooter('');

        // Write empty content for first page (just a space to ensure the page exists)
        $mpdf->WriteHTML('<div style="height: 100%;"></div>', \Mpdf\HTMLParserMode::HTML_BODY);

        // === SUBSEQUENT PAGES: Content with normal background ===
        $mpdf->AddPage();

        // Set normal background for all following pages
        $mpdf->SetDefaultBodyCSS('background', "url('$bg_normal')");
        $mpdf->SetDefaultBodyCSS('background-image-resize', 6);

        // Add footer from second page onwards
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
            'Regina.ttf' => 'https://deepclarity.de/wp-content/uploads/Regina.ttf',
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
                font-family: regina, serif;
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
                font-family: regina, serif;
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

    /**
     * Attempt to repair malformed JSON with unescaped quotes in HTML content.
     *
     * This specifically handles the case where n8n sends JSON like:
     * "html":"<div class="cover-page">" instead of "html":"<div class=\"cover-page\">"
     *
     * @param string $json The potentially malformed JSON string.
     * @return string The repaired JSON string.
     */
    private function repair_json($json)
    {
        // If it's already valid JSON, return as-is
        $test = json_decode($json, true);
        if ($test !== null || json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        error_log('Deep Clarity PDF: Attempting JSON repair...');

        // Try wp_unslash first (WordPress double-encoding)
        $unslashed = wp_unslash($json);
        $test = json_decode($unslashed, true);
        if ($test !== null) {
            error_log('Deep Clarity PDF: JSON repair via wp_unslash successful');
            return $unslashed;
        }

        // Try stripslashes (double-escaped JSON)
        $stripped = stripslashes($json);
        $test = json_decode($stripped, true);
        if ($test !== null) {
            error_log('Deep Clarity PDF: JSON repair via stripslashes successful');
            return $stripped;
        }

        // Main repair: Fix unescaped quotes in HTML content
        // Strategy: Find "html":" and "title":" patterns and escape quotes inside them
        $repaired = $this->fix_unescaped_html_quotes($json);
        $test = json_decode($repaired, true);
        if ($test !== null) {
            error_log('Deep Clarity PDF: JSON repair via quote escaping successful');
            return $repaired;
        }

        error_log('Deep Clarity PDF: All repair attempts failed');
        return $json;
    }

    /**
     * Fix unescaped quotes in JSON string values.
     *
     * This handles malformed JSON where quotes inside string values are not escaped.
     * It processes character by character, escaping ALL quotes within string values
     * except the opening and closing quotes.
     *
     * @param string $json The malformed JSON string.
     * @return string The repaired JSON string.
     */
    private function fix_unescaped_html_quotes($json)
    {
        $result = '';
        $len = strlen($json);
        $i = 0;

        while ($i < $len) {
            $char = $json[$i];

            // Look for start of a JSON string value (": pattern)
            if ($char === '"' && $i > 0) {
                $prev_char = $json[$i - 1];

                // Check if this is the start of a string value (after : or after [ for array items)
                if ($prev_char === ':' || $prev_char === '[' || $prev_char === ',') {
                    // This quote starts a string value
                    $result .= '"';
                    $i++;

                    // Process the string value content
                    $value_content = '';

                    while ($i < $len) {
                        $c = $json[$i];
                        $next = ($i + 1 < $len) ? $json[$i + 1] : '';

                        // Check for end of JSON string value
                        // End markers: " followed by , or } or ] or end of string
                        if ($c === '"') {
                            if ($next === ',' || $next === '}' || $next === ']' || $next === '') {
                                // This is the closing quote of the JSON value
                                $result .= $value_content . '"';
                                $i++;
                                break;
                            } else {
                                // This is an unescaped quote inside the value - escape it
                                $value_content .= '\\"';
                                $i++;
                                continue;
                            }
                        }

                        // Check for already escaped quotes (don't double-escape)
                        if ($c === '\\' && $next === '"') {
                            $value_content .= '\\"';
                            $i += 2;
                            continue;
                        }

                        $value_content .= $c;
                        $i++;
                    }

                    continue;
                }
            }

            $result .= $char;
            $i++;
        }

        return $result;
    }

    /**
     * Build HTML content from dossier_structure and template
     *
     * @param int $dossier_id Dossier ID.
     * @return string Generated HTML content.
     */
    private function build_html_from_structure($dossier_id)
    {
        // Get dossier_structure from ACF field
        $structure_json = get_field('dossier_structure', $dossier_id);
        if (empty($structure_json)) {
            error_log('Deep Clarity PDF: dossier_structure is empty for dossier ' . $dossier_id);
            return '';
        }

        // Parse JSON if needed
        $segments = null;
        if (is_string($structure_json)) {
            // First try direct decode
            $segments = json_decode($structure_json, true);

            // If JSON decode failed, try to repair the JSON
            if ($segments === null && json_last_error() !== JSON_ERROR_NONE) {
                $original_error = json_last_error_msg();
                error_log('Deep Clarity PDF: Initial JSON decode error for dossier ' . $dossier_id . ': ' . $original_error);
                error_log('Deep Clarity PDF: First 200 chars of original JSON: ' . substr($structure_json, 0, 200));

                // Try to repair the JSON
                $repaired_json = $this->repair_json($structure_json);
                $segments = json_decode($repaired_json, true);

                // Log the result
                if ($segments !== null) {
                    error_log('Deep Clarity PDF: JSON repair successful for dossier ' . $dossier_id . ', found ' . count($segments) . ' segments');
                } else {
                    // If still failing, log detailed error
                    error_log('Deep Clarity PDF: JSON repair failed for dossier ' . $dossier_id . ': ' . json_last_error_msg());
                    error_log('Deep Clarity PDF: First 500 chars of repaired JSON: ' . substr($repaired_json, 0, 500));

                    // Try one more approach: maybe ACF already decoded it but as an object
                    if (is_object($structure_json)) {
                        $segments = json_decode(json_encode($structure_json), true);
                    }

                    if ($segments === null) {
                        return '';
                    }
                }
            }
        } else if (is_array($structure_json)) {
            // ACF might have already decoded it
            $segments = $structure_json;
        } else if (is_object($structure_json)) {
            // Convert object to array
            $segments = json_decode(json_encode($structure_json), true);
        } else {
            $segments = $structure_json;
        }

        if (empty($segments) || ! is_array($segments)) {
            error_log('Deep Clarity PDF: segments is empty or not an array for dossier ' . $dossier_id);
            return '';
        }

        // Get template from ACF Options, fallback to local file
        $template = get_field('settings_template_dossier', 'option');
        if (empty($template)) {
            $local_template_path = DEEP_CLARITY_PLUGIN_DIR . 'prompts/DOSSIER_HTML_TEMPLATE.html';
            if (file_exists($local_template_path)) {
                $template = file_get_contents($local_template_path);
            }
        }

        if (empty($template)) {
            return '';
        }

        // Build placeholders array
        $placeholders = array();

        // Extract cover variables and chapter data from segments
        foreach ($segments as $segment) {
            if ($segment['type'] === 'cover' && isset($segment['variables'])) {
                $placeholders['CLIENT_NAME'] = $segment['variables']['CLIENT_NAME'] ?? '';
                $placeholders['DOSSIER_NUMBER'] = $segment['variables']['DOSSIER_NUMBER'] ?? '';
            }

            if ($segment['type'] === 'chapter' && isset($segment['chapter_number'])) {
                $chapter_num = $segment['chapter_number'];

                // Chapter title
                $placeholders["chapter_{$chapter_num}_title"] = $segment['title'] ?? '';

                // Sections
                if (isset($segment['sections']) && is_array($segment['sections'])) {
                    foreach ($segment['sections'] as $index => $section) {
                        $section_num = $index + 1;
                        $placeholders["chapter_{$chapter_num}_{$section_num}_title"] = $section['title'] ?? '';
                        $placeholders["chapter_{$chapter_num}_{$section_num}"] = $section['html'] ?? '';
                    }
                }
            }
        }

        // Add date placeholders
        $months_de = array(
            'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
        );
        $placeholders['CREATION_MONTH'] = $months_de[intval(date('n')) - 1];
        $placeholders['CREATION_YEAR'] = date('Y');

        // Get DCPI scores from dossier ACF fields
        $placeholders['DCPI_INDEX'] = get_field('dossier_deep_clarity_index', $dossier_id) ?: '';
        $placeholders['DCPI_DIM_1_SCORE'] = get_field('dossier_dimension_1_score', $dossier_id) ?: '';
        $placeholders['DCPI_DIM_2_SCORE'] = get_field('dossier_dimension_2_score', $dossier_id) ?: '';
        $placeholders['DCPI_DIM_3_SCORE'] = get_field('dossier_dimension_3_score', $dossier_id) ?: '';
        $placeholders['DCPI_DIM_4_SCORE'] = get_field('dossier_dimension_4_score', $dossier_id) ?: '';
        $placeholders['DCPI_DIM_5_SCORE'] = get_field('dossier_dimension_5_score', $dossier_id) ?: '';

        // Fallback for CLIENT_NAME from linked client if not in cover
        if (empty($placeholders['CLIENT_NAME'])) {
            $client = get_field('dossier_client', $dossier_id);
            $client_id = is_array($client) ? $client[0] : $client;
            $client_id = is_object($client_id) ? $client_id->ID : $client_id;

            if ($client_id) {
                $vorname = get_field('client_firstname', $client_id) ?: '';
                $nachname = get_field('client_lastname', $client_id) ?: '';
                $placeholders['CLIENT_NAME'] = trim($vorname . ' ' . $nachname);
            }
        }

        // Replace all placeholders in template
        $html = $template;
        foreach ($placeholders as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }

        // Save generated HTML to dossier_html ACF field
        if (function_exists('update_field')) {
            update_field('dossier_html', $html, $dossier_id);
        }

        return $html;
    }
}
