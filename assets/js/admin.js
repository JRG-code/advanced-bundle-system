/**
 * Admin JavaScript for Advanced Bundle System
 */

(function($) {
    'use strict';

    var ABS_Admin = {

        rowIndex: 1000, // Start high to avoid conflicts

        init: function() {
            this.bindEvents();
            this.handleProductTypeChange();
            this.initExistingRows();
            this.updatePricingSummary();
        },

        bindEvents: function() {
            var self = this;

            // Handle product type change
            $('#product-type').on('change', this.handleProductTypeChange);

            // Add bundle item
            $(document).on('click', '#abs_add_bundle_item', function(e) {
                e.preventDefault();
                self.addBundleItem();
            });

            // Remove bundle item
            $(document).on('click', '.abs-remove-item', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();
                self.updatePricingSummary();
            });

            // Update pricing on changes
            $(document).on('change', '.abs-product-search, .abs-item-quantity', function() {
                self.updatePricingSummary();
            });

            $(document).on('change keyup', '#_bundle_price', function() {
                self.updatePricingSummary();
            });
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

        initExistingRows: function() {
            var self = this;

            // Initialize Select2 for existing rows
            $('#abs_bundle_items_tbody .abs-product-search').each(function() {
                self.initProductSearch($(this));
            });
        },

        addBundleItem: function() {
            var self = this;
            var template = $('#abs_bundle_item_row_template').html();
            var $row = $(template.replace(/\{\{INDEX\}\}/g, this.rowIndex));

            $('#abs_bundle_items_tbody').append($row);

            // Initialize Select2 for the new row
            this.initProductSearch($row.find('.abs-product-search'));

            this.rowIndex++;
        },

        initProductSearch: function($select) {
            $select.selectWoo({
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'abs_search_products',
                            term: params.term,
                            nonce: $('#abs_search_nonce').val() || ''
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: $select.data('placeholder'),
                allowClear: false
            });
        },

        updatePricingSummary: function() {
            var bundleItems = [];
            var bundlePrice = parseFloat($('#_bundle_price').val()) || 0;

            // Collect all bundle items
            $('#abs_bundle_items_tbody tr').each(function() {
                var productId = $(this).find('.abs-product-search').val();
                var quantity = parseInt($(this).find('.abs-item-quantity').val()) || 1;

                if (productId) {
                    bundleItems.push({
                        product_id: productId,
                        quantity: quantity
                    });
                }
            });

            if (bundleItems.length === 0) {
                $('#abs_original_total').text('-');
                $('#abs_bundle_price_display').text('-');
                $('#abs_discount_percent').text('-');
                return;
            }

            // Show loading state
            $('#abs_original_total').text('Calculating...');
            $('#abs_bundle_price_display').text('Calculating...');
            $('#abs_discount_percent').text('Calculating...');

            // Make AJAX request to calculate pricing
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'abs_calculate_bundle_pricing',
                    bundle_items: bundleItems,
                    bundle_price: bundlePrice,
                    nonce: $('#abs_pricing_nonce').val() || ''
                },
                success: function(response) {
                    if (response.success) {
                        $('#abs_original_total').html(response.data.original_total_formatted);
                        $('#abs_bundle_price_display').html(response.data.bundle_price_formatted);
                        $('#abs_discount_percent').text(response.data.discount_percent + '%');
                    } else {
                        $('#abs_original_total').text('Error');
                        $('#abs_bundle_price_display').text('Error');
                        $('#abs_discount_percent').text('-');
                    }
                },
                error: function() {
                    $('#abs_original_total').text('Error');
                    $('#abs_bundle_price_display').text('Error');
                    $('#abs_discount_percent').text('-');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ABS_Admin.init();
    });

})(jQuery);
