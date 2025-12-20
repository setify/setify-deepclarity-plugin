/**
 * Deep Clarity - Admin JavaScript
 *
 * @package DeepClarity
 */

(function($) {
    'use strict';

    /**
     * Deep Clarity Admin Module
     */
    const DeepClarityAdmin = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initChart();
            this.initTooltips();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            $('#deep-clarity-demo-alert').on('click', this.showDemoAlert);
        },

        /**
         * Initialize Chart.js demo
         */
        initChart: function() {
            const canvas = document.getElementById('deep-clarity-chart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Sample Data',
                        data: [12, 19, 3, 5, 2, 3],
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        /**
         * Initialize Tippy.js tooltips
         */
        initTooltips: function() {
            if (typeof tippy === 'undefined') return;

            tippy('[data-tippy-content]', {
                theme: 'light',
                placement: 'top',
                animation: 'fade',
                duration: [200, 150]
            });
        },

        /**
         * Show demo SweetAlert2 alert
         */
        showDemoAlert: function(e) {
            e.preventDefault();

            Swal.fire({
                title: deepClarityAdmin.i18n.success,
                text: 'Deep Clarity is working correctly!',
                icon: 'success',
                confirmButtonText: 'Great!',
                customClass: {
                    popup: 'deep-clarity-popup'
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
                url: deepClarityAdmin.ajaxUrl,
                type: 'POST',
                data: $.extend({
                    action: 'deep_clarity_' + action,
                    nonce: deepClarityAdmin.nonce
                }, data),
                success: function(response) {
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Deep Clarity AJAX Error:', error);
                    Swal.fire({
                        title: deepClarityAdmin.i18n.error,
                        text: error,
                        icon: 'error'
                    });
                }
            });
        },

        /**
         * Show toast notification
         *
         * @param {string} message - Toast message
         * @param {string} type - Toast type (success, error, warning, info)
         */
        toast: function(message, type = 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });

            Toast.fire({
                icon: type,
                title: message
            });
        },

        /**
         * Confirm dialog
         *
         * @param {string} title - Dialog title
         * @param {string} text - Dialog text
         * @param {function} callback - Confirm callback
         */
        confirm: function(title, text, callback) {
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d63638',
                cancelButtonColor: '#2271b1',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'deep-clarity-popup'
                }
            }).then((result) => {
                if (result.isConfirmed && typeof callback === 'function') {
                    callback();
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DeepClarityAdmin.init();
    });

    // Expose to global scope
    window.DeepClarityAdmin = DeepClarityAdmin;

})(jQuery);
