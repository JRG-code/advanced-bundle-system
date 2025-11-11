/**
 * Centralized Inventory Manager JavaScript
 */

(function($) {
    'use strict';

    var ABS_Inventory = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Stock input change detection
            $(document).on('input', '.abs-stock-input', function() {
                var $input = $(this);
                var $row = $input.closest('tr');
                var $saveBtn = $row.find('.abs-save-stock');
                var original = $input.data('original');
                var current = $input.val();

                if (current != original) {
                    $input.addClass('abs-modified');
                    $saveBtn.show();
                } else {
                    $input.removeClass('abs-modified');
                    $saveBtn.hide();
                }
            });

            // Save stock button
            $(document).on('click', '.abs-save-stock', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $row = $btn.closest('tr');
                var $input = $row.find('.abs-stock-input');
                var $feedback = $row.find('.abs-save-feedback');
                var productId = $row.data('product-id');
                var stockQuantity = $input.val();

                self.saveStock(productId, stockQuantity, $btn, $input, $feedback, $row);
            });

            // Enter key to save
            $(document).on('keypress', '.abs-stock-input', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $(this).closest('tr').find('.abs-save-stock').click();
                }
            });

            // Filter functionality
            $('#abs-inventory-filter').on('change', function() {
                self.filterTable($(this).val());
            });

            // Search functionality
            var searchTimeout;
            $('#abs-inventory-search').on('input', function() {
                clearTimeout(searchTimeout);
                var query = $(this).val();
                searchTimeout = setTimeout(function() {
                    self.searchTable(query);
                }, 300);
            });
        },

        saveStock: function(productId, stockQuantity, $btn, $input, $feedback, $row) {
            // Show saving state
            $btn.prop('disabled', true).text(absInventory.strings.saving);
            $feedback.text(absInventory.strings.saving).removeClass('abs-saved abs-error').addClass('abs-saving');

            $.ajax({
                url: absInventory.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abs_update_stock',
                    nonce: absInventory.nonce,
                    product_id: productId,
                    stock_quantity: stockQuantity
                },
                success: function(response) {
                    if (response.success) {
                        // Success!
                        $feedback.text(absInventory.strings.saved).removeClass('abs-saving abs-error').addClass('abs-saved');
                        $input.removeClass('abs-modified').data('original', stockQuantity);
                        $btn.hide();

                        // Update row stock status
                        var $statusIndicator = $row.find('.abs-stock-status');
                        var qty = parseInt(stockQuantity);

                        $row.removeClass('abs-out-of-stock abs-low-stock');
                        $statusIndicator.removeClass('abs-out-of-stock abs-low-stock abs-in-stock');

                        if (qty === 0) {
                            $row.addClass('abs-out-of-stock');
                            $statusIndicator.addClass('abs-out-of-stock');
                        } else if (qty <= 5) {
                            $row.addClass('abs-low-stock');
                            $statusIndicator.addClass('abs-low-stock');
                        } else {
                            $statusIndicator.addClass('abs-in-stock');
                        }

                        // Clear feedback after 3 seconds
                        setTimeout(function() {
                            $feedback.text('').removeClass('abs-saved');
                        }, 3000);
                    } else {
                        // Error
                        $feedback.text(absInventory.strings.error).removeClass('abs-saving abs-saved').addClass('abs-error');
                    }

                    $btn.prop('disabled', false).text('Save');
                },
                error: function() {
                    $feedback.text(absInventory.strings.error).removeClass('abs-saving abs-saved').addClass('abs-error');
                    $btn.prop('disabled', false).text('Save');
                }
            });
        },

        filterTable: function(filter) {
            var $rows = $('.abs-inventory-row');

            if (filter === 'all') {
                $rows.show();
                return;
            }

            $rows.each(function() {
                var $row = $(this);
                var show = false;

                switch(filter) {
                    case 'low':
                        show = $row.hasClass('abs-low-stock');
                        break;
                    case 'out':
                        show = $row.hasClass('abs-out-of-stock');
                        break;
                    case 'variable':
                        show = $row.find('.abs-col-variation').text().trim() !== '—';
                        break;
                }

                $row.toggle(show);
            });
        },

        searchTable: function(query) {
            var $rows = $('.abs-inventory-row');

            if (!query) {
                $rows.show();
                return;
            }

            query = query.toLowerCase();

            $rows.each(function() {
                var $row = $(this);
                var text = $row.text().toLowerCase();
                var matches = text.indexOf(query) !== -1;
                $row.toggle(matches);
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.abs-inventory-page').length) {
            ABS_Inventory.init();
        }
    });

})(jQuery);
