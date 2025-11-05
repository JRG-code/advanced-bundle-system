/**
 * Settings Page Live Preview
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Update preview when fields change
        $('#abs_settings_bundle_heading').on('input', function() {
            $('#preview-bundle-heading').text($(this).val());
        });

        $('#abs_settings_preview_button_text').on('input', function() {
            $('#preview-button-text').text($(this).val());
        });

        $('#abs_settings_personalization_disclaimer').on('input', function() {
            $('#preview-disclaimer').text($(this).val());
        });

        $('#abs_settings_discount_format').on('input', function() {
            var format = $(this).val();
            var preview = format.replace('%s', '20');
            $('#preview-discount-badge').text(preview);
        });

        $('#abs_settings_cart_bundle_includes').on('input', function() {
            $('#preview-bundle-includes').text($(this).val());
        });

        $('#abs_settings_cart_personalization_label').on('input', function() {
            $('#preview-personalization-label').text($(this).val());
        });
    });

})(jQuery);
