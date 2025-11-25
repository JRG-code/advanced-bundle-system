/**
 * Bundle Auto-Suggest Frontend JavaScript
 */

(function($) {
    'use strict';

    var BundleSuggest = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Handle "Switch to Bundle" button click
            $(document).on('click', '.abs-swap-to-bundle', this.swapToBundle.bind(this));

            // Handle "No Thanks" button click
            $(document).on('click', '.abs-dismiss-suggestion', this.dismissSuggestion.bind(this));
        },

        swapToBundle: function(e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var bundleId = $button.data('bundle-id');
            var $notice = $button.closest('.abs-bundle-suggestion');

            // Disable buttons during processing
            $notice.find('button').prop('disabled', true);
            $button.text(absBundleSuggest.strings.processing || 'Processing...');

            $.ajax({
                url: absBundleSuggest.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abs_swap_to_bundle',
                    nonce: absBundleSuggest.nonce,
                    bundle_id: bundleId
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $notice.html('<div class="woocommerce-message">' + response.data.message + '</div>');

                        // Reload cart after a short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    } else {
                        alert(response.data.message || absBundleSuggest.strings.error);
                        $notice.find('button').prop('disabled', false);
                        $button.text(absBundleSuggest.strings.switchToBundle);
                    }
                },
                error: function() {
                    alert(absBundleSuggest.strings.error || 'An error occurred');
                    $notice.find('button').prop('disabled', false);
                    $button.text(absBundleSuggest.strings.switchToBundle);
                }
            });
        },

        dismissSuggestion: function(e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var $notice = $button.closest('.abs-bundle-suggestion');

            $.ajax({
                url: absBundleSuggest.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abs_dismiss_bundle_suggestion',
                    nonce: absBundleSuggest.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Fade out and remove the notice
                        $notice.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        BundleSuggest.init();
    });

})(jQuery);
