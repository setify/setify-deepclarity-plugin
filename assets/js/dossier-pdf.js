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
                        DossierPDF.showModal(
                            'success',
                            dcDossierPdf.strings.success,
                            '',
                            response.data.pdf_url
                        );
                    } else {
                        DossierPDF.showModal(
                            'error',
                            dcDossierPdf.strings.error,
                            response.data.message || 'Unbekannter Fehler'
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
         * @param {string} type    Modal type (success/error).
         * @param {string} title   Modal title.
         * @param {string} message Modal message.
         * @param {string} pdfUrl  PDF URL (optional).
         */
        showModal: function(type, title, message, pdfUrl) {
            // Remove existing modal
            this.closeModal();

            var iconSvg = type === 'success'
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';

            var downloadButton = pdfUrl
                ? '<a href="' + pdfUrl + '" target="_blank" class="dc-pdf-modal-download" onclick="event.stopPropagation();">' + dcDossierPdf.strings.download + '</a>'
                : '';

            var modalHtml =
                '<div class="dc-pdf-modal-overlay">' +
                    '<div class="dc-pdf-modal dc-pdf-modal--' + type + '">' +
                        '<div class="dc-pdf-modal-icon">' + iconSvg + '</div>' +
                        '<div class="dc-pdf-modal-content">' +
                            '<h3 class="dc-pdf-modal-title">' + title + '</h3>' +
                            (message ? '<p class="dc-pdf-modal-message">' + message + '</p>' : '') +
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
