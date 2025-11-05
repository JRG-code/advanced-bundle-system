/**
 * Settings Page Preview
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $previewPanel = $('#abs-settings-preview');
        var isPreviewVisible = false;

        // Generate/update preview when button is clicked
        $('#abs-generate-preview').on('click', function() {
            updatePreview();

            if (!isPreviewVisible) {
                $previewPanel.slideDown(300);
                isPreviewVisible = true;
                $(this).text('Hide Preview');
            } else {
                $previewPanel.slideUp(300);
                isPreviewVisible = false;
                $(this).text('Generate Preview');
            }
        });

        function updatePreview() {
            // Update bundle heading
            var bundleHeading = $('#abs_settings_bundle_heading').val();
            if (bundleHeading) {
                $('#preview-bundle-heading').text(bundleHeading);
            }

            // Update preview button text
            var previewButtonText = $('#abs_settings_preview_button_text').val();
            if (previewButtonText) {
                $('#preview-button-text').text(previewButtonText);
            }

            // Update disclaimer
            var disclaimer = $('#abs_settings_personalization_disclaimer').val();
            if (disclaimer) {
                $('#preview-disclaimer').text(disclaimer);
            }

            // Update discount format
            var discountFormat = $('#abs_settings_discount_format').val();
            if (discountFormat) {
                var preview = discountFormat.replace('%s', '20');
                $('#preview-discount-badge').text(preview);
            }

            // Update bundle includes text
            var bundleIncludes = $('#abs_settings_cart_bundle_includes').val();
            if (bundleIncludes) {
                $('#preview-bundle-includes').text(bundleIncludes);
            }

            // Update personalization label
            var personalizationLabel = $('#abs_settings_cart_personalization_label').val();
            if (personalizationLabel) {
                $('#preview-personalization-label').text(personalizationLabel);
            }
        }
    });

})(jQuery);
