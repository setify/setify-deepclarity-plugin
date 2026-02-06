(function($) {
    'use strict';

    /**
     * Dossier PDF Generator
     */
    var DossierPDF = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $(document).on('click', '.create_dossier_pdf, #create_dossier_pdf', this.handleCreatePDF.bind(this));
            $(document).on('click', '.dc-pdf-modal-close, .dc-pdf-modal-overlay', this.closeModal.bind(this));
        },

        /**
         * Handle create PDF button click
         *
         * @param {Event} e Click event.
         */
        handleCreatePDF: function(e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var dossierId = $button.data('dossier-id');

            if (!dossierId) {
                this.showModal('error', dcDossierPdf.strings.error, 'Keine Dossier-ID gefunden');
                return;
            }

            // Disable button and show loading
            var originalText = $button.text();
            $button
                .prop('disabled', true)
                .addClass('loading')
                .text(dcDossierPdf.strings.creating);

            // Send AJAX request
            $.ajax({
                url: dcDossierPdf.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dc_create_dossier_pdf',
                    nonce: dcDossierPdf.nonce,
                    dossier_id: dossierId
                },
                success: function(response) {
                    if (response.success) {
                        // Determine modal type based on source
                        var source = response.data.source || 'unknown';
                        var isFallback = source !== 'structure';
                        var modalType = isFallback ? 'warning' : 'success';

                        // Build source info message
                        var sourceInfo = '';
                        if (source === 'structure') {
                            sourceInfo = 'Quelle: dossier_structure (Template)';
                        } else if (source === 'html_fallback') {
                            sourceInfo = 'Hinweis: dossier_structure konnte nicht verarbeitet werden. Es wurde dossier_html als Fallback verwendet.';
                        } else {
                            sourceInfo = 'Quelle: ' + source;
                        }

                        DossierPDF.showModal(
                            modalType,
                            dcDossierPdf.strings.success,
                            sourceInfo,
                            response.data.pdf_url
                        );
                    } else {
                        DossierPDF.showModal(
                            'error',
                            dcDossierPdf.strings.error,
                            response.data.message || 'Unbekannter Fehler',
                            null,
                            response.data.debug || null
                        );
                    }
                },
                error: function(xhr, status, error) {
                    DossierPDF.showModal(
                        'error',
                        dcDossierPdf.strings.error,
                        'Serverfehler: ' + error
                    );
                },
                complete: function() {
                    // Re-enable button
                    $button
                        .prop('disabled', false)
                        .removeClass('loading')
                        .text(originalText);
                }
            });
        },

        /**
         * Show modal
         *
         * @param {string} type      Modal type (success/error/warning).
         * @param {string} title     Modal title.
         * @param {string} message   Modal message.
         * @param {string} pdfUrl    PDF URL (optional).
         * @param {object} debugData Debug data object (optional).
         */
        showModal: function(type, title, message, pdfUrl, debugData) {
            // Remove existing modal
            this.closeModal();

            var iconSvg;
            if (type === 'success') {
                iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
            } else if (type === 'warning') {
                iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
            } else {
                iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
            }

            var downloadButton = pdfUrl
                ? '<a href="' + pdfUrl + '" target="_blank" class="dc-pdf-modal-download" onclick="event.stopPropagation();">' + dcDossierPdf.strings.download + '</a>'
                : '';

            // Build debug info HTML if available
            var debugHtml = '';
            if (debugData && type === 'error') {
                debugHtml = '<div class="dc-pdf-modal-debug">' +
                    '<details>' +
                        '<summary>Debug-Informationen anzeigen</summary>' +
                        '<div class="dc-pdf-modal-debug-content">' +
                            '<table>' +
                                '<tr><td>dossier_structure leer:</td><td>' + (debugData.field_empty ? 'Ja' : 'Nein') + '</td></tr>' +
                                '<tr><td>Feldtyp:</td><td>' + (debugData.field_type || 'n/a') + '</td></tr>' +
                                '<tr><td>Feldlänge:</td><td>' + (debugData.field_length || 'n/a') + ' Zeichen</td></tr>' +
                                '<tr><td>JSON-Parsing:</td><td>' + (debugData.json_decode_result || 'nicht getestet') + '</td></tr>' +
                                '<tr><td>JSON-Fehler:</td><td>' + (debugData.json_error || 'keiner') + '</td></tr>' +
                                '<tr><td>dossier_html leer:</td><td>' + (debugData.dossier_html_empty ? 'Ja' : 'Nein') + '</td></tr>' +
                                '<tr><td>dossier_html Länge:</td><td>' + (debugData.dossier_html_length || 'n/a') + ' Zeichen</td></tr>' +
                            '</table>' +
                            (debugData.first_100_chars ? '<div class="dc-pdf-modal-debug-preview"><strong>Erste 100 Zeichen:</strong><pre>' + this.escapeHtml(debugData.first_100_chars) + '</pre></div>' : '') +
                        '</div>' +
                    '</details>' +
                '</div>';
            }

            var modalHtml =
                '<div class="dc-pdf-modal-overlay">' +
                    '<div class="dc-pdf-modal dc-pdf-modal--' + type + '">' +
                        '<div class="dc-pdf-modal-icon">' + iconSvg + '</div>' +
                        '<h3 class="dc-pdf-modal-title">' + title + '</h3>' +
                        (message ? '<p class="dc-pdf-modal-message">' + message + '</p>' : '') +
                        debugHtml +
                        '<div class="dc-pdf-modal-actions">' +
                            downloadButton +
                            '<button type="button" class="dc-pdf-modal-close">' + dcDossierPdf.strings.close + '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            $('body').append(modalHtml);

            // Animate in
            setTimeout(function() {
                $('.dc-pdf-modal-overlay').addClass('active');
            }, 10);
        },

        /**
         * Escape HTML special characters
         *
         * @param {string} str String to escape.
         * @return {string} Escaped string.
         */
        escapeHtml: function(str) {
            if (!str) return '';
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        /**
         * Close modal and reload page
         *
         * @param {Event} e Click event (optional).
         */
        closeModal: function(e) {
            if (e) {
                // Allow download link to work
                if ($(e.target).hasClass('dc-pdf-modal-download') || $(e.target).closest('.dc-pdf-modal-download').length) {
                    return;
                }

                // Don't close if clicking inside modal (except close button)
                if ($(e.target).closest('.dc-pdf-modal').length && !$(e.target).hasClass('dc-pdf-modal-close')) {
                    return;
                }

                e.preventDefault();
            }

            var $modal = $('.dc-pdf-modal-overlay');

            if ($modal.length) {
                $modal.removeClass('active');

                setTimeout(function() {
                    $modal.remove();

                    // Reload page after closing success modal
                    if (e && $(e.target).hasClass('dc-pdf-modal-close')) {
                        window.location.reload();
                    }
                }, 300);
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DossierPDF.init();
    });

})(jQuery);
