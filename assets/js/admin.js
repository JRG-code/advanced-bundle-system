/**
 * Admin JavaScript for Advanced Bundle System
 */

(function($) {
    'use strict';

    var ABS_Admin = {

        init: function() {
            this.bindEvents();
            this.handleProductTypeChange();
        },

        bindEvents: function() {
            // Handle product type change
            $('#product-type').on('change', this.handleProductTypeChange);

            // Handle bundle products selection
            $(document).on('change', '#abs_bundle_products', this.updatePricingSummary);
            $(document).on('change keyup', '#_bundle_price', this.updatePricingSummary);
        },

        handleProductTypeChange: function() {
            var productType = $('#product-type').val();

            if (productType === 'bundle') {
                $('.show_if_bundle').show();
                $('.hide_if_bundle').hide();

                // Hide fields that don't apply to bundles
                $('.show_if_simple').hide();
                $('.show_if_variable').hide();

                // Show bundle tab
                $('.bundle_options').addClass('active');
                $('#bundle_product_data').show();
            } else {
                $('.show_if_bundle').hide();
            }
        },

        updatePricingSummary: function() {
            var productIds = $('#abs_bundle_products').val();
            var bundlePrice = parseFloat($('#_bundle_price').val()) || 0;

            if (!productIds || productIds.length === 0) {
                $('#abs_original_total').text('-');
                $('#abs_bundle_price_display').text('-');
                $('#abs_discount_percent').text('-');
                return;
            }

            // This would typically make an AJAX call to calculate
            // For now, we'll just display the entered bundle price
            $('#abs_bundle_price_display').text('Calculating...');
            $('#abs_original_total').text('Calculating...');
            $('#abs_discount_percent').text('Calculating...');
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ABS_Admin.init();
    });

})(jQuery);
