<?php
/**
 * Stock Management for Bundle Products
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Stock {

    public function __construct() {
        // Override stock status for bundle products
        add_filter('woocommerce_product_is_in_stock', array($this, 'check_bundle_stock'), 10, 2);
        add_filter('woocommerce_product_get_stock_status', array($this, 'get_bundle_stock_status'), 10, 2);
        add_filter('woocommerce_get_availability', array($this, 'get_bundle_availability'), 10, 2);

        // Validate stock before adding to cart
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_bundle_stock'), 10, 3);

        // Prevent bundle products from reducing their own stock
        add_filter('woocommerce_can_reduce_order_stock', array($this, 'prevent_bundle_stock_reduction'), 10, 2);
    }

    /**
     * Check if bundle has sufficient stock based on individual products
     */
    public function check_bundle_stock($is_in_stock, $product) {
        if ('bundle' !== $product->get_type()) {
            return $is_in_stock;
        }

        $bundle_items = get_post_meta($product->get_id(), '_bundle_items', true);
        if (empty($bundle_items) || !is_array($bundle_items)) {
            return false;
        }

        // Check stock for each bundle item
        foreach ($bundle_items as $item) {
            $product_id = $item['product_id'];
            $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
            $bundled_product = wc_get_product($product_id);

            if (!$bundled_product) {
                return false;
            }

            // If product manages stock, check quantity
            if ($bundled_product->managing_stock()) {
                $stock_quantity = $bundled_product->get_stock_quantity();
                if ($stock_quantity < $quantity) {
                    return false;
                }
            }

            // Check stock status
            if (!$bundled_product->is_in_stock()) {
                return false;
            }

            // For variable products with "ask_attributes", we can't check variation stock
            // until attributes are selected, so we check parent stock status
            if ($bundled_product->is_type('variable')) {
                $ask_attributes = isset($item['ask_attributes']) && $item['ask_attributes'] === 'yes';

                if (!$ask_attributes) {
                    // If not asking for attributes, the bundle might not work with variable products
                    // Check if at least one variation is in stock
                    if (!$this->has_in_stock_variation($bundled_product)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check if a variable product has at least one variation in stock (optimized)
     */
    private function has_in_stock_variation($product) {
        if (!$product->is_type('variable')) {
            return false;
        }

        // Use get_children() instead of get_available_variations() for better performance
        $variation_ids = $product->get_children();
        foreach ($variation_ids as $variation_id) {
            $variation_obj = wc_get_product($variation_id);
            if ($variation_obj && $variation_obj->is_in_stock()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get bundle stock status
     */
    public function get_bundle_stock_status($stock_status, $product) {
        if ('bundle' !== $product->get_type()) {
            return $stock_status;
        }

        $is_in_stock = $this->check_bundle_stock(true, $product);
        return $is_in_stock ? 'instock' : 'outofstock';
    }

    /**
     * Get bundle availability text and class
     */
    public function get_bundle_availability($availability, $product) {
        if ('bundle' !== $product->get_type()) {
            return $availability;
        }

        $bundle_items = get_post_meta($product->get_id(), '_bundle_items', true);
        if (empty($bundle_items) || !is_array($bundle_items)) {
            return array(
                'availability' => __('Bundle configuration error', 'advanced-bundle-system'),
                'class' => 'out-of-stock'
            );
        }

        $is_in_stock = $this->check_bundle_stock(true, $product);
        $min_stock = null;
        $out_of_stock_items = array();

        // Check each item for stock quantity
        foreach ($bundle_items as $item) {
            $product_id = $item['product_id'];
            $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
            $bundled_product = wc_get_product($product_id);

            if (!$bundled_product) {
                continue;
            }

            if ($bundled_product->managing_stock()) {
                $stock_quantity = $bundled_product->get_stock_quantity();
                $available_bundles = floor($stock_quantity / $quantity);

                if ($min_stock === null || $available_bundles < $min_stock) {
                    $min_stock = $available_bundles;
                }

                if ($stock_quantity < $quantity) {
                    $out_of_stock_items[] = $bundled_product->get_name();
                }
            }

            if (!$bundled_product->is_in_stock()) {
                $out_of_stock_items[] = $bundled_product->get_name();
            }
        }

        if (!$is_in_stock) {
            $message = __('Out of stock', 'advanced-bundle-system');
            if (!empty($out_of_stock_items)) {
                $message .= ' (' . implode(', ', $out_of_stock_items) . ')';
            }

            return array(
                'availability' => $message,
                'class' => 'out-of-stock'
            );
        }

        if ($min_stock !== null && $min_stock > 0) {
            return array(
                'availability' => sprintf(__('%d bundles available', 'advanced-bundle-system'), $min_stock),
                'class' => 'in-stock'
            );
        }

        return array(
            'availability' => __('In stock', 'advanced-bundle-system'),
            'class' => 'in-stock'
        );
    }

    /**
     * Validate bundle stock before adding to cart
     */
    public function validate_bundle_stock($passed, $product_id, $quantity) {
        $product = wc_get_product($product_id);

        if (!$product || 'bundle' !== $product->get_type()) {
            return $passed;
        }

        $bundle_items = get_post_meta($product_id, '_bundle_items', true);
        if (empty($bundle_items) || !is_array($bundle_items)) {
            wc_add_notice(__('This bundle has no products configured.', 'advanced-bundle-system'), 'error');
            return false;
        }

        // Check if attributes were provided when required
        $attributes_provided = isset($_POST['abs_attributes']) ? $_POST['abs_attributes'] : array();

        $item_counter = 0;
        foreach ($bundle_items as $index => $item) {
            $bundled_product_id = $item['product_id'];
            $item_quantity = isset($item['quantity']) ? $item['quantity'] : 1;
            $ask_attributes = isset($item['ask_attributes']) && $item['ask_attributes'] === 'yes';

            $bundled_product = wc_get_product($bundled_product_id);

            if (!$bundled_product) {
                wc_add_notice(
                    sprintf(__('Product #%d in bundle is not available.', 'advanced-bundle-system'), $bundled_product_id),
                    'error'
                );
                return false;
            }

            // For products with quantity > 1, check each instance
            for ($q = 0; $q < $item_quantity; $q++) {
                $unique_id = $item_counter++;
                $total_quantity = $item_quantity * $quantity;

                // If this is a variable product and attributes are required
                if ($bundled_product->is_type('variable') && $ask_attributes) {
                    // Check if attributes were provided
                    if (!isset($attributes_provided[$unique_id]) || empty($attributes_provided[$unique_id])) {
                        wc_add_notice(
                            sprintf(__('Please select all options for %s.', 'advanced-bundle-system'), $bundled_product->get_name()),
                            'error'
                        );
                        return false;
                    }

                    // Find the matching variation
                    $variation_id = $this->find_matching_variation($bundled_product, $attributes_provided[$unique_id]);

                    if (!$variation_id) {
                        wc_add_notice(
                            sprintf(__('The selected combination for %s is not available.', 'advanced-bundle-system'), $bundled_product->get_name()),
                            'error'
                        );
                        return false;
                    }

                    // Check variation stock
                    $variation = wc_get_product($variation_id);
                    if (!$variation || !$variation->is_in_stock()) {
                        wc_add_notice(
                            sprintf(__('%s is out of stock for the selected options.', 'advanced-bundle-system'), $bundled_product->get_name()),
                            'error'
                        );
                        return false;
                    }

                    if ($variation->managing_stock()) {
                        $stock_quantity = $variation->get_stock_quantity();
                        if ($stock_quantity < $total_quantity) {
                            wc_add_notice(
                                sprintf(
                                    __('Not enough stock for %s. Available: %d, Required: %d', 'advanced-bundle-system'),
                                    $bundled_product->get_name(),
                                    $stock_quantity,
                                    $total_quantity
                                ),
                                'error'
                            );
                            return false;
                        }
                    }
                } else {
                    // Simple product or variable product without attribute selection
                    if (!$bundled_product->is_in_stock()) {
                        wc_add_notice(
                            sprintf(__('%s is out of stock.', 'advanced-bundle-system'), $bundled_product->get_name()),
                            'error'
                        );
                        return false;
                    }

                    if ($bundled_product->managing_stock()) {
                        $stock_quantity = $bundled_product->get_stock_quantity();
                        if ($stock_quantity < $total_quantity) {
                            wc_add_notice(
                                sprintf(
                                    __('Not enough stock for %s. Available: %d, Required: %d', 'advanced-bundle-system'),
                                    $bundled_product->get_name(),
                                    $stock_quantity,
                                    $total_quantity
                                ),
                                'error'
                            );
                            return false;
                        }
                    }
                }
            }
        }

        return $passed;
    }

    /**
     * Find matching variation based on selected attributes
     */
    private function find_matching_variation($product, $selected_attributes) {
        if (!$product->is_type('variable')) {
            return false;
        }

        $data_store = WC_Data_Store::load('product');

        // Format attributes for matching (add 'attribute_' prefix if not present)
        $formatted_attributes = array();
        foreach ($selected_attributes as $key => $value) {
            // Handle both pa_ taxonomy attributes and custom attributes
            if (strpos($key, 'attribute_') !== 0) {
                $formatted_key = 'attribute_' . $key;
            } else {
                $formatted_key = $key;
            }
            $formatted_attributes[$formatted_key] = $value;
        }

        return $data_store->find_matching_product_variation($product, $formatted_attributes);
    }

    /**
     * Prevent WooCommerce from reducing stock for bundle products
     * (we handle individual product stock reduction separately)
     */
    public function prevent_bundle_stock_reduction($can_reduce, $order) {
        // Ensure we have a valid order object
        if (!is_a($order, 'WC_Order')) {
            return $can_reduce;
        }

        // Check if any item in the order is a bundle
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();

            if ($product && 'bundle' === $product->get_type()) {
                // Don't reduce stock for the bundle itself
                // Individual product stock is handled in ABS_Order class
                return false;
            }
        }

        return $can_reduce;
    }
}

new ABS_Stock();
