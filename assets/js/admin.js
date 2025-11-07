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
                self.updateBaseProductsList();
            });

            // Update pricing on changes
            $(document).on('change', '.abs-product-search, .abs-item-quantity', function() {
                self.updatePricingSummary();
                self.updateBaseProductsList();
            });

            $(document).on('change keyup', '#_bundle_price', function() {
                self.updatePricingSummary();
            });
        },

        handleProductTypeChange: function() {
            var productType = $('#product-type').val();

            if (productType === 'bundle') {
                // Add class to body for CSS targeting (use specific class to avoid conflicts)
                $('body').addClass('abs-editing-bundle-product');

                $('.show_if_bundle').show();
                $('.hide_if_bundle').hide();

                // Hide fields that don't apply to bundles
                $('.show_if_simple').hide();
                $('.show_if_variable').hide();

                // Hide only stock quantity management fields (not SKU, GTIN, etc.)
                // These will be hidden in General tab but visible in Inventory tab via CSS
                $('#general_product_data ._manage_stock_field').hide();
                $('#general_product_data ._stock_field').hide();
                $('#general_product_data ._backorders_field').hide();
                $('#general_product_data ._low_stock_amount_field').hide();
                $('#general_product_data ._sku_field').hide();
                $('#general_product_data ._gtin_field').hide();
                $('#general_product_data ._upc_field').hide();
                $('#general_product_data ._ean_field').hide();
                $('#general_product_data ._isbn_field').hide();
                $('#general_product_data ._stock_status_field').hide();
                $('#general_product_data ._sold_individually_field').hide();

                // Keep Inventory tab visible
                $('.inventory_options.inventory_tab').show();

                // Show bundle tab
                $('.bundle_options').addClass('active');
                $('#bundle_product_data').show();
            } else {
                // Remove class from body
                $('body').removeClass('abs-editing-bundle-product');

                $('.show_if_bundle').hide();

                // Restore inventory fields for non-bundle products
                $('.inventory_options.inventory_tab').show();
                $('#general_product_data ._sku_field').show();
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
                            nonce: $('#abs_search_nonce').val() || '',
                            exclude_id: $('#abs_current_product_id').val() || 0
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
        },

        updateBaseProductsList: function() {
            var $baseProductsList = $('#abs_base_products_list');
            if ($baseProductsList.length === 0) {
                return; // Not on Linked Products tab
            }

            // Collect all bundle items and group by product
            var productQuantities = {};
            $('#abs_bundle_items_tbody tr').each(function() {
                var $select = $(this).find('.abs-product-search');
                var productId = $select.val();
                var productText = $select.find('option:selected').text();
                var quantity = parseInt($(this).find('.abs-item-quantity').val()) || 1;

                if (productId) {
                    if (productQuantities[productId]) {
                        productQuantities[productId].quantity += quantity;
                    } else {
                        productQuantities[productId] = {
                            quantity: quantity,
                            name: productText
                        };
                    }
                }
            });

            // Build the HTML
            var html = '';
            var hasProducts = false;

            if (Object.keys(productQuantities).length > 0) {
                html = '<ul style="margin: 0; padding-left: 20px;">';
                $.each(productQuantities, function(productId, data) {
                    hasProducts = true;
                    var quantityLabel = data.quantity > 1 ? '<strong>' + data.quantity + 'x </strong>' : '';
                    // Extract product name from the select option text (remove the price part)
                    var productName = data.name.split(' - ')[0];
                    html += '<li style="margin: 5px 0;">' + quantityLabel + productName + ' <span style="color: #999;">(#' + productId + ')</span></li>';
                });
                html += '</ul>';
            }

            if (!hasProducts) {
                html = '<p style="margin: 0; color: #999; font-style: italic;">No products added to bundle yet.</p>';
            }

            $baseProductsList.html(html);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ABS_Admin.init();
    });

})(jQuery);
