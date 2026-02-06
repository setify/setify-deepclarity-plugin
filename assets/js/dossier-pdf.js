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
                        // Build source info message
                        var sourceInfo = '';
                        if (response.data.source) {
                            sourceInfo = response.data.source === 'structure'
                                ? 'Quelle: dossier_structure (Template)'
                                : 'Quelle: dossier_html (Fallback)';
                        }

                        DossierPDF.showModal(
                            'success',
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
         * @param {string} type      Modal type (success/error).
         * @param {string} title     Modal title.
         * @param {string} message   Modal message.
         * @param {string} pdfUrl    PDF URL (optional).
         * @param {object} debugData Debug data object (optional).
         */
        showModal: function(type, title, message, pdfUrl, debugData) {
            // Remove existing modal
            this.closeModal();

            var iconSvg = type === 'success'
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';

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
                                '<tr><td><strong>dossier_structure leer:</strong></td><td>' + (debugData.field_empty ? 'Ja' : 'Nein') + '</td></tr>' +
                                '<tr><td><strong>Feldtyp:</strong></td><td>' + (debugData.field_type || 'n/a') + '</td></tr>' +
                                '<tr><td><strong>Feldlänge:</strong></td><td>' + (debugData.field_length || 'n/a') + ' Zeichen</td></tr>' +
                                '<tr><td><strong>JSON-Parsing:</strong></td><td>' + (debugData.json_decode_result || 'nicht getestet') + '</td></tr>' +
                                '<tr><td><strong>JSON-Fehler:</strong></td><td>' + (debugData.json_error || 'keiner') + '</td></tr>' +
                                '<tr><td><strong>dossier_html leer:</strong></td><td>' + (debugData.dossier_html_empty ? 'Ja' : 'Nein') + '</td></tr>' +
                                '<tr><td><strong>dossier_html Länge:</strong></td><td>' + (debugData.dossier_html_length || 'n/a') + ' Zeichen</td></tr>' +
                            '</table>' +
                            (debugData.first_100_chars ? '<div class="dc-pdf-modal-debug-preview"><strong>Erste 100 Zeichen:</strong><pre>' + this.escapeHtml(debugData.first_100_chars) + '</pre></div>' : '') +
                        '</div>' +
                    '</details>' +
                '</div>';
            }

            var modalHtml =
                '<div class="dc-pdf-modal-overlay">' +
                    '<div class="dc-pdf-modal dc-pdf-modal--' + type + (debugData ? ' dc-pdf-modal--with-debug' : '') + '">' +
                        '<div class="dc-pdf-modal-icon">' + iconSvg + '</div>' +
                        '<div class="dc-pdf-modal-content">' +
                            '<h3 class="dc-pdf-modal-title">' + title + '</h3>' +
                            (message ? '<p class="dc-pdf-modal-message">' + message + '</p>' : '') +
                            debugHtml +
                        '</div>' +
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
