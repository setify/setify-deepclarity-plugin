<?php

/**
 * Email Signature Generator
 *
 * @package DeepClarity
 */

namespace DeepClarity;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Signature Generator Class
 */
class SignatureGenerator
{
    /**
     * Instance
     *
     * @var SignatureGenerator
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SignatureGenerator
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_shortcode('dc_signature_generator', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets()
    {
        // Assets are inline for simplicity
    }

    /**
     * Render shortcode
     *
     * @return string
     */
    public function render_shortcode()
    {
        ob_start();
        ?>
        <div class="dc-signature-generator">
            <div class="dc-sig-container">
                <!-- Left: Form -->
                <div class="dc-sig-form-panel">
                    <h3>Signatur bearbeiten</h3>

                    <div class="dc-sig-section">
                        <h4>Persönliche Daten</h4>
                        <div class="dc-sig-field">
                            <label for="dc-sig-firstname">Vorname</label>
                            <input type="text" id="dc-sig-firstname" value="Timo">
                        </div>
                        <div class="dc-sig-field">
                            <label for="dc-sig-lastname">Nachname</label>
                            <input type="text" id="dc-sig-lastname" value="Wenzel">
                        </div>
                        <div class="dc-sig-field">
                            <label for="dc-sig-title">Position / Titel</label>
                            <input type="text" id="dc-sig-title" value="Executive Mentor">
                        </div>
                        <div class="dc-sig-field">
                            <label for="dc-sig-company">Unternehmen</label>
                            <input type="text" id="dc-sig-company" value="Deep Clarity">
                        </div>
                    </div>

                    <div class="dc-sig-section">
                        <h4>Kontaktdaten</h4>
                        <div class="dc-sig-field">
                            <label for="dc-sig-phone">Telefon</label>
                            <input type="text" id="dc-sig-phone" value="02241 200 10 99">
                        </div>
                        <div class="dc-sig-field">
                            <label for="dc-sig-email">E-Mail</label>
                            <input type="email" id="dc-sig-email" value="team@deepclarity.de">
                        </div>
                        <div class="dc-sig-field">
                            <label for="dc-sig-website">Website</label>
                            <input type="text" id="dc-sig-website" value="deepclarity.de">
                        </div>
                        <div class="dc-sig-field">
                            <label for="dc-sig-address">Adresse</label>
                            <input type="text" id="dc-sig-address" value="Poststraße 101, 53840 Troisdorf">
                        </div>
                    </div>

                    <div class="dc-sig-section">
                        <h4>Bilder (URLs)</h4>
                        <div class="dc-sig-field">
                            <label for="dc-sig-signature-img">Handschriftliche Signatur</label>
                            <input type="url" id="dc-sig-signature-img" value="https://deepclarity.de/wp-content/uploads/signatur_timo.png">
                        </div>
                        <div class="dc-sig-field">
                            <label for="dc-sig-photo">Profilfoto</label>
                            <input type="url" id="dc-sig-photo" value="https://deepclarity.de/wp-content/uploads/dc_timo_mail.png">
                        </div>
                        <div class="dc-sig-field">
                            <label for="dc-sig-logo">Logo</label>
                            <input type="url" id="dc-sig-logo" value="https://deepclarity.de/wp-content/uploads/deep_clarity_logo_bronze_full_1200px.png">
                        </div>
                    </div>

                    <div class="dc-sig-section">
                        <h4>Social Media</h4>
                        <div class="dc-sig-field">
                            <label for="dc-sig-linkedin">LinkedIn URL</label>
                            <input type="url" id="dc-sig-linkedin" value="https://www.linkedin.com/in/timo-wenzel/">
                        </div>
                        <div class="dc-sig-field">
                            <label for="dc-sig-instagram">Instagram URL</label>
                            <input type="url" id="dc-sig-instagram" value="https://www.instagram.com/deepclarity.de/">
                        </div>
                    </div>
                </div>

                <!-- Right: Preview -->
                <div class="dc-sig-preview-panel">
                    <div class="dc-sig-preview-header">
                        <h3>Vorschau</h3>
                        <button type="button" id="dc-sig-copy-preview" class="dc-sig-btn dc-sig-btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Signatur kopieren
                        </button>
                    </div>
                    <div class="dc-sig-preview-content" id="dc-sig-preview">
                        <!-- Preview will be rendered here -->
                    </div>
                </div>
            </div>

            <!-- HTML Source Code -->
            <div class="dc-sig-source-panel">
                <div class="dc-sig-source-header">
                    <h3>HTML Quellcode</h3>
                    <button type="button" id="dc-sig-copy-html" class="dc-sig-btn dc-sig-btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        HTML kopieren
                    </button>
                </div>
                <textarea id="dc-sig-html-output" readonly></textarea>
            </div>
        </div>

        <style>
            .dc-signature-generator {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                max-width: 1400px;
                margin: 0 auto;
            }

            .dc-sig-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 24px;
                margin-bottom: 24px;
            }

            @media (max-width: 1024px) {
                .dc-sig-container {
                    grid-template-columns: 1fr;
                }
            }

            .dc-sig-form-panel,
            .dc-sig-preview-panel,
            .dc-sig-source-panel {
                background: #fff;
                border: 1px solid var(--dc-color-platinum, #e9ebec);
                border-radius: var(--dc-radius-medium, 8px);
                padding: 24px;
            }

            .dc-sig-form-panel h3,
            .dc-sig-preview-panel h3,
            .dc-sig-source-panel h3 {
                margin: 0 0 20px 0;
                font-size: 1.1rem;
                font-weight: 600;
                color: var(--dc-color-carbon-black, #1c1d1f);
            }

            .dc-sig-section {
                margin-bottom: 24px;
                padding-bottom: 24px;
                border-bottom: 1px solid var(--dc-color-platinum, #e9ebec);
            }

            .dc-sig-section:last-child {
                margin-bottom: 0;
                padding-bottom: 0;
                border-bottom: none;
            }

            .dc-sig-section h4 {
                margin: 0 0 12px 0;
                font-size: 0.85rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: var(--dc-color-cool-steel, #a0a3a7);
            }

            .dc-sig-field {
                margin-bottom: 12px;
            }

            .dc-sig-field:last-child {
                margin-bottom: 0;
            }

            .dc-sig-field label {
                display: block;
                margin-bottom: 4px;
                font-size: 0.85rem;
                font-weight: 500;
                color: var(--dc-color-shadow-grey, #27282b);
            }

            .dc-sig-field input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid var(--dc-color-pale-slate, #ccced2);
                border-radius: var(--dc-radius-small, 6px);
                font-size: 0.9rem;
                transition: border-color 0.15s;
            }

            .dc-sig-field input:focus {
                outline: none;
                border-color: var(--dc-color-khaki-beige, #b9ae9b);
            }

            .dc-sig-preview-header,
            .dc-sig-source-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }

            .dc-sig-preview-header h3,
            .dc-sig-source-header h3 {
                margin: 0;
            }

            .dc-sig-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 16px;
                border: none;
                border-radius: var(--dc-radius-small, 6px);
                font-size: 0.85rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.15s;
            }

            .dc-sig-btn svg {
                flex-shrink: 0;
            }

            .dc-sig-btn-primary {
                background: var(--dc-color-royal-plum, #5c272e);
                color: #fff;
            }

            .dc-sig-btn-primary:hover {
                background: var(--dc-color-crimson-violet, #74313b);
            }

            .dc-sig-btn-secondary {
                background: var(--dc-color-khaki-beige, #b9ae9b);
                color: #fff;
            }

            .dc-sig-btn-secondary:hover {
                background: var(--dc-color-olive-wood, #7e6d54);
            }

            .dc-sig-preview-content {
                padding: 24px;
                background: #fafafa;
                border: 1px solid var(--dc-color-platinum, #e9ebec);
                border-radius: var(--dc-radius-small, 6px);
                overflow-x: auto;
            }

            .dc-sig-source-panel textarea {
                width: 100%;
                min-height: 200px;
                padding: 12px;
                border: 1px solid var(--dc-color-pale-slate, #ccced2);
                border-radius: var(--dc-radius-small, 6px);
                font-family: "SF Mono", Monaco, "Cascadia Code", monospace;
                font-size: 0.8rem;
                line-height: 1.5;
                resize: vertical;
                background: #f8f8f8;
            }

            .dc-sig-source-panel textarea:focus {
                outline: none;
                border-color: var(--dc-color-khaki-beige, #b9ae9b);
            }

            /* Toast notification */
            .dc-sig-toast {
                position: fixed;
                bottom: 24px;
                right: 24px;
                padding: 12px 20px;
                background: var(--dc-color-shadow-grey, #27282b);
                color: #fff;
                border-radius: var(--dc-radius-small, 6px);
                font-size: 0.9rem;
                z-index: 9999;
                opacity: 0;
                transform: translateY(10px);
                transition: all 0.3s ease;
            }

            .dc-sig-toast.show {
                opacity: 1;
                transform: translateY(0);
            }
        </style>

        <script>
        (function() {
            'use strict';

            // Field IDs
            const fields = {
                firstname: 'dc-sig-firstname',
                lastname: 'dc-sig-lastname',
                title: 'dc-sig-title',
                company: 'dc-sig-company',
                phone: 'dc-sig-phone',
                email: 'dc-sig-email',
                website: 'dc-sig-website',
                address: 'dc-sig-address',
                signatureImg: 'dc-sig-signature-img',
                photo: 'dc-sig-photo',
                logo: 'dc-sig-logo',
                linkedin: 'dc-sig-linkedin',
                instagram: 'dc-sig-instagram'
            };

            // Get field value
            function getVal(id) {
                const el = document.getElementById(id);
                return el ? el.value : '';
            }

            // Generate signature HTML
            function generateSignature() {
                const firstname = getVal(fields.firstname);
                const lastname = getVal(fields.lastname);
                const title = getVal(fields.title);
                const company = getVal(fields.company);
                const phone = getVal(fields.phone);
                const email = getVal(fields.email);
                const website = getVal(fields.website);
                const address = getVal(fields.address);
                const signatureImg = getVal(fields.signatureImg);
                const photo = getVal(fields.photo);
                const logo = getVal(fields.logo);
                const linkedin = getVal(fields.linkedin);
                const instagram = getVal(fields.instagram);

                return `<div>
  <table cellpadding="0" cellspacing="0" border="0" style="vertical-align: -webkit-baseline-middle; font-size: medium; font-family: Arial;">
    <tbody>
      <tr>
        <td style="padding-bottom: 16px;">
          <img alt="Handwritten Signature" role="presentation" src="${signatureImg}" width="125" data-cy="handwritten-signature-image" style="display: block; width: 125px;">
          <br>
        </td>
      </tr>
      <tr>
        <td>
          <table cellpadding="0" cellspacing="0" border="0" style="vertical-align: -webkit-baseline-middle; font-size: medium; font-family: Arial;">
            <tbody>
              <tr>
                <td style="vertical-align: top;">
                  <h2 style="margin: 0px; font-size: 18px; font-family: Arial; color: rgb(39, 40, 43); font-weight: 600; line-height: 28px;">
                    <span>${firstname}</span>
                    <span>${lastname}</span>
                  </h2>
                  <p style="margin: 0px; color: rgb(39, 40, 43); font-size: 14px; line-height: 22px;">
                    <span>${title}</span>
                  </p>
                  <div style="margin: 0px; font-weight: 500; color: rgb(39, 40, 43); font-size: 14px; line-height: 22px;">
                    <span>${company}</span>
                  </div>
                  <table cellpadding="0" cellspacing="0" border="0" style="vertical-align: -webkit-baseline-middle; font-size: medium; font-family: Arial; width: 100%;">
                    <tbody>
                      <tr>
                        <td height="24" aria-label="Horizontal Spacer">
                          <br>
                        </td>
                      </tr>
                      <tr>
                        <td width="auto" aria-label="Divider" style="width: 100%; height: 1px; border-bottom: 1px solid rgb(123, 110, 86); border-left: none; display: block;">
                          <br>
                        </td>
                      </tr>
                      <tr>
                        <td height="24" aria-label="Horizontal Spacer">
                          <br>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <table cellpadding="0" cellspacing="0" border="0" style="vertical-align: -webkit-baseline-middle; font-size: medium; font-family: Arial; line-height: 1;">
                    <tbody>
                      <tr style="vertical-align: middle; height: 28px;">
                        <td width="26" style="vertical-align: middle;">
                          <table cellpadding="0" cellspacing="0" border="0" style="vertical-align: -webkit-baseline-middle; font-size: medium; font-family: Arial; width: 26px;">
                            <tbody>
                              <tr>
                                <td style="vertical-align: bottom;">
                                  <span style="display: inline-block; background-color: rgb(123, 110, 86);">
                                    <img src="https://cdn2.hubspot.net/hubfs/53/tools/email-signature-generator/icons/phone-icon-dark-2x.png" alt="mobilePhone" width="18" style="display: block; background-image: linear-gradient(rgb(123, 110, 86), rgb(123, 110, 86));">
                                  </span>
                                  <br>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </td>
                        <td style="padding: 0px; color: rgb(39, 40, 43);">
                          <a href="tel:${phone}" style="text-decoration: none; color: rgb(39, 40, 43); font-size: 14px;">
                            <span>${phone}</span>
                          </a>
                        </td>
                      </tr>
                      <tr style="vertical-align: middle; height: 28px;">
                        <td width="26" style="vertical-align: middle;">
                          <table cellpadding="0" cellspacing="0" border="0" style="vertical-align: -webkit-baseline-middle; font-size: medium; font-family: Arial; width: 26px;">
                            <tbody>
                              <tr>
                                <td style="vertical-align: bottom;">
                                  <span style="display: inline-block; background-color: rgb(123, 110, 86);">
                                    <img src="https://cdn2.hubspot.net/hubfs/53/tools/email-signature-generator/icons/email-icon-dark-2x.png" alt="emailAddress" width="18" style="display: block; background-image: linear-gradient(rgb(123, 110, 86), rgb(123, 110, 86));">
                                  </span>
                                  <br>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </td>
                        <td style="padding: 0px; color: rgb(39, 40, 43);">
                          <a href="mailto:${email}" style="text-decoration: none; color: rgb(39, 40, 43); font-size: 14px;">
                            <span>${email}</span>
                          </a>
                        </td>
                      </tr>
                      <tr style="vertical-align: middle; height: 28px;">
                        <td width="26" style="vertical-align: middle;">
                          <table cellpadding="0" cellspacing="0" border="0" style="vertical-align: -webkit-baseline-middle; font-size: medium; font-family: Arial; width: 26px;">
                            <tbody>
                              <tr>
                                <td style="vertical-align: bottom;">
                                  <span style="display: inline-block; background-color: rgb(123, 110, 86);">
                                    <img src="https://cdn2.hubspot.net/hubfs/53/tools/email-signature-generator/icons/link-icon-dark-2x.png" alt="website" width="18" style="display: block; background-image: linear-gradient(rgb(123, 110, 86), rgb(123, 110, 86));">
                                  </span>
                                  <br>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </td>
                        <td style="padding: 0px; color: rgb(39, 40, 43);">
                          <a href="https://${website}/" style="text-decoration: none; color: rgb(39, 40, 43); font-size: 14px;">
                            <span>${website}</span>
                          </a>
                        </td>
                      </tr>
                      <tr style="vertical-align: middle; height: 28px;">
                        <td width="26" style="vertical-align: middle;">
                          <table cellpadding="0" cellspacing="0" border="0" style="vertical-align: -webkit-baseline-middle; font-size: medium; font-family: Arial; width: 26px;">
                            <tbody>
                              <tr>
                                <td style="vertical-align: bottom;">
                                  <span style="display: inline-block; background-color: rgb(123, 110, 86);">
                                    <img src="https://cdn2.hubspot.net/hubfs/53/tools/email-signature-generator/icons/address-icon-dark-2x.png" alt="address" width="18" style="display: block; background-image: linear-gradient(rgb(123, 110, 86), rgb(123, 110, 86));">
                                  </span>
                                  <br>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </td>
                        <td style="padding: 0px; color: rgb(39, 40, 43);">
                          <span style="color: rgb(39, 40, 43);">
                            <span class="size" style="font-size:14px">
                              <span>${address}</span>
                            </span>
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </td>
                <td width="45" aria-label="Vertical Spacer">
                  <div style="width: 45px;">
                    <br>
                  </div>
                </td>
                <td style="vertical-align: top;">
                  <table cellpadding="0" cellspacing="0" border="0" style="vertical-align: -webkit-baseline-middle; font-size: medium; font-family: Arial;">
                    <tbody>
                      <tr>
                        <td align="right">
                          <img src="${photo}" role="presentation" width="130" style="max-width: 130px; display: block;">
                          <br>
                        </td>
                      </tr>
                      <tr>
                        <td height="24" aria-label="Horizontal Spacer">
                          <br>
                        </td>
                      </tr>
                      <tr>
                        <td align="right">
                          <img src="${logo}" role="presentation" width="130" style="max-width: 130px; display: block;">
                          <br>
                        </td>
                      </tr>
                      <tr>
                        <td height="24" aria-label="Horizontal Spacer">
                          <br>
                        </td>
                      </tr>
                      <tr>
                        <td style="text-align: right;">
                          <div>
                            <a href="${linkedin}" style="display: inline-block; padding: 0px; background-color: rgb(123, 110, 86); border-radius: 50%;">
                              <img src="https://cdn2.hubspot.net/hubfs/53/tools/email-signature-generator/icons/linkedin-icon-dark-2x.png" alt="linkedin" width="24" loading="lazy" style="background-color: rgb(123, 110, 86); max-width: 135px; display: block; border-radius: inherit;">
                            </a>
                            <a href="${instagram}" style="display: inline-block; padding: 0px; background-color: rgb(123, 110, 86); border-radius: 50%;">
                              <img src="https://cdn2.hubspot.net/hubfs/53/tools/email-signature-generator/icons/instagram-icon-dark-2x.png" alt="instagram" width="24" loading="lazy" style="background-color: rgb(123, 110, 86); max-width: 135px; display: block; border-radius: inherit;">
                            </a>
                            <br>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>
      <tr>
        <td height="24" aria-label="Horizontal Spacer">
          <br>
        </td>
      </tr>
      <tr>
        <td colspan="3" style="max-width: 300px; font-size: 12px; padding-top: 1rem; text-align: left;">
          <div class="legal-content">
            <p style="font-size: inherit; margin: 0px;">Vertraulichkeit:</p>
            <p style="font-size: inherit; margin: 0px;">Diese E-Mail und alle Anhänge enthalten vertrauliche, möglicherweise rechtlich geschützte Informationen. Sie sind ausschließlich für den bezeichneten Empfänger bestimmt. Sollten Sie nicht der vorgesehene Empfänger sein oder diese E-Mail irrtümlich erhalten haben, bitten wir Sie, die E-Mail unverzüglich zu löschen und den Absender zu informieren. Jegliche unbefugte Verwendung, Weitergabe oder Vervielfältigung ist untersagt.</p>
            <p style="font-size: inherit; margin: 0px;">
              <br>
            </p>
            <p style="font-size: inherit; margin: 0px;">
              <a href="https://deepclarity.de/datenschutz/">Datenschutz</a>
            </p>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
  <div>
    <br>
  </div>
</div>`;
            }

            // Update preview and HTML output
            function updateSignature() {
                const html = generateSignature();
                document.getElementById('dc-sig-preview').innerHTML = html;
                document.getElementById('dc-sig-html-output').value = html;
            }

            // Show toast notification
            function showToast(message) {
                // Remove existing toast
                const existing = document.querySelector('.dc-sig-toast');
                if (existing) existing.remove();

                const toast = document.createElement('div');
                toast.className = 'dc-sig-toast';
                toast.textContent = message;
                document.body.appendChild(toast);

                setTimeout(() => toast.classList.add('show'), 10);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 2500);
            }

            // Copy to clipboard (rich text for preview, plain text for HTML)
            async function copySignature() {
                const preview = document.getElementById('dc-sig-preview');

                try {
                    // Create a selection and copy as rich text
                    const range = document.createRange();
                    range.selectNodeContents(preview);
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(range);

                    document.execCommand('copy');
                    selection.removeAllRanges();

                    showToast('Signatur in Zwischenablage kopiert!');
                } catch (err) {
                    console.error('Copy failed:', err);
                    showToast('Kopieren fehlgeschlagen');
                }
            }

            // Copy HTML source
            async function copyHTML() {
                const textarea = document.getElementById('dc-sig-html-output');

                try {
                    await navigator.clipboard.writeText(textarea.value);
                    showToast('HTML-Code in Zwischenablage kopiert!');
                } catch (err) {
                    // Fallback
                    textarea.select();
                    document.execCommand('copy');
                    showToast('HTML-Code in Zwischenablage kopiert!');
                }
            }

            // Initialize
            document.addEventListener('DOMContentLoaded', function() {
                // Initial render
                updateSignature();

                // Add event listeners to all inputs
                Object.values(fields).forEach(function(id) {
                    const input = document.getElementById(id);
                    if (input) {
                        input.addEventListener('input', updateSignature);
                    }
                });

                // Copy buttons
                document.getElementById('dc-sig-copy-preview').addEventListener('click', copySignature);
                document.getElementById('dc-sig-copy-html').addEventListener('click', copyHTML);
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}
