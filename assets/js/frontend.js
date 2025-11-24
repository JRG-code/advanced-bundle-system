/**
 * Frontend JavaScript for Advanced Bundle System
 */

(function($) {
    'use strict';

    var ABS_Frontend = {

        init: function() {
            this.bindEvents();
            this.initPreviewModal();
        },

        bindEvents: function() {
            var self = this;

            // Personalization toggle
            $(document).on('change', '.abs-personalization-toggle', function() {
                self.handlePersonalizationToggle($(this));
            });

            // Preview button click
            $(document).on('click', '.abs-show-preview', this.showPreview);

            // Close preview modal
            $(document).on('click', '.abs-preview-close, .abs-preview-overlay', this.closePreview);

            // Update preview text in real-time
            $(document).on('input', '.abs-personalization-input', this.updatePreviewText);

            // Prevent form submission on preview button click
            $(document).on('click', '.abs-show-preview', function(e) {
                e.preventDefault();
            });
        },

        handlePersonalizationToggle: function($toggle) {
            var personalizationId = $toggle.data('personalization-id');
            var $wrapper = $toggle.closest('.abs-personalization-fields');
            var $field = $wrapper.find('.abs-personalization-field');
            var $disclaimer = $wrapper.find('.abs-personalization-disclaimer');
            var $input = $wrapper.find('.abs-personalization-input');
            var $enabled = $wrapper.find('.abs-personalization-enabled');

            if ($toggle.is(':checked')) {
                // Show the text field and disclaimer
                $field.slideDown(300);
                $disclaimer.slideDown(300);

                // Enable the input and update hidden field
                $input.prop('disabled', false).focus();
                $enabled.val('1');
            } else {
                // Hide the text field and disclaimer
                $field.slideUp(300);
                $disclaimer.slideUp(300);

                // Disable the input, clear it, and update hidden field
                $input.prop('disabled', true).val('');
                $enabled.val('0');
            }
        },

        initPreviewModal: function() {
            // Create modal HTML if it doesn't exist
            if ($('#abs-preview-modal').length === 0) {
                var modalHTML =
                    '<div id="abs-preview-modal" class="abs-preview-modal" style="display: none;">' +
                        '<div class="abs-preview-overlay"></div>' +
                        '<div class="abs-preview-content">' +
                            '<button class="abs-preview-close">&times;</button>' +
                            '<div class="abs-preview-header">' +
                                '<h2>' + absData.disclaimerText + '</h2>' +
                            '</div>' +
                            '<div class="abs-preview-body">' +
                                '<div class="abs-preview-image-container">' +
                                    '<img src="" alt="" class="abs-preview-image" />' +
                                    '<div class="abs-preview-text-overlay"></div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="abs-preview-footer">' +
                                '<p class="abs-preview-product-name"></p>' +
                            '</div>' +
                        '</div>' +
                    '</div>';

                $('body').append(modalHTML);
            }
        },

        showPreview: function(e) {
            e.preventDefault();

            var $button = $(this);
            var productId = $button.data('product-id');
            var uniqueId = $button.data('unique-id');

            // Get the text from the input with unique ID
            var $input = uniqueId !== undefined
                ? $('#abs_personalization_text_' + uniqueId)
                : $('#abs_personalization_text_' + productId);

            var text = $input.val();

            if (!text || text.trim() === '') {
                alert('Please enter some text to preview');
                return;
            }

            // Show loading state
            $button.prop('disabled', true).text('Loading preview...');

            // Make AJAX request
            $.ajax({
                url: absData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abs_generate_preview',
                    nonce: absData.nonce,
                    product_id: productId,
                    text: text
                },
                success: function(response) {
                    if (response.success) {
                        ABS_Frontend.displayPreview(response.data);
                    } else {
                        alert(response.data.message || 'Failed to generate preview');
                    }
                },
                error: function() {
                    alert('An error occurred while generating the preview');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Preview Personalization');
                }
            });
        },

        displayPreview: function(data) {
            var $modal = $('#abs-preview-modal');
            var $image = $modal.find('.abs-preview-image');
            var $textOverlay = $modal.find('.abs-preview-text-overlay');
            var $productName = $modal.find('.abs-preview-product-name');

            // Set image
            $image.attr('src', data.image_url);
            $image.attr('alt', data.product_name);

            // Set text overlay
            $textOverlay.text(data.text);

            // Set product name
            $productName.text(data.product_name);

            // Show modal
            $modal.fadeIn(300);

            // Prevent body scroll
            $('body').addClass('abs-modal-open');
        },

        closePreview: function(e) {
            var $modal = $('#abs-preview-modal');
            $modal.fadeOut(300);

            // Re-enable body scroll
            $('body').removeClass('abs-modal-open');
        },

        updatePreviewText: function() {
            var $input = $(this);
            var text = $input.val();
            var maxLength = parseInt($input.attr('maxlength')) || 50;

            // Enforce max length
            if (text.length > maxLength) {
                $input.val(text.substring(0, maxLength));
            }

            // Update character counter if it exists
            var $counter = $input.siblings('.abs-character-counter');
            if ($counter.length > 0) {
                $counter.text(text.length + ' / ' + maxLength);
            }
        },

        // Helper function to validate personalization before add to cart
        validatePersonalization: function() {
            var isValid = true;
            var messages = [];

            // Only validate enabled personalization fields
            $('.abs-personalization-input:not([disabled])').each(function() {
                var $input = $(this);
                var text = $input.val();
                var maxLength = parseInt($input.attr('maxlength')) || 50;

                if (text.length > maxLength) {
                    isValid = false;
                    messages.push('Personalization text cannot exceed ' + maxLength + ' characters');
                }
            });

            if (!isValid) {
                alert(messages.join('\n'));
            }

            return isValid;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ABS_Frontend.init();
    });

    // Add validation before add to cart
    $(document).on('submit', 'form.cart', function(e) {
        if ($('.abs-personalization-input').length > 0) {
            if (!ABS_Frontend.validatePersonalization()) {
                e.preventDefault();
                return false;
            }
        }
    });

})(jQuery);
