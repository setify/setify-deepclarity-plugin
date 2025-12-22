/**
 * Deep Clarity - Frontend JavaScript
 *
 * @package DeepClarity
 */

(function($) {
    'use strict';

    /**
     * Deep Clarity Frontend Module
     */
    const DeepClarityFrontend = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Referal link - go back in history or use referrer
            $('.referal-link a').on('click', function(e) {
                e.preventDefault();
                if (document.referrer && document.referrer !== '') {
                    window.location.href = document.referrer;
                } else {
                    window.history.back();
                }
            });
        },

        /**
         * AJAX request helper
         *
         * @param {string} action - AJAX action name
         * @param {object} data - Additional data
         * @param {function} callback - Success callback
         */
        ajax: function(action, data, callback) {
            $.ajax({
                url: deepClarityFrontend.ajaxUrl,
                type: 'POST',
                data: $.extend({
                    action: 'deep_clarity_' + action,
                    nonce: deepClarityFrontend.nonce
                }, data),
                success: function(response) {
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Deep Clarity Error:', error);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DeepClarityFrontend.init();
    });

    // Expose to global scope
    window.DeepClarityFrontend = DeepClarityFrontend;

})(jQuery);
