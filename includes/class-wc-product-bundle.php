<?php
/**
 * WooCommerce Product Bundle Class
 *
 * Extends WC_Product to create a custom bundle product type
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Product_Bundle extends WC_Product {

    /**
     * Initialize bundle product
     */
    public function __construct($product = 0) {
        $this->product_type = 'bundle';
        parent::__construct($product);
    }

    /**
     * Get the product type
     */
    public function get_type() {
        return 'bundle';
    }

    /**
     * Check if product is purchasable
     */
    public function is_purchasable() {
        return apply_filters('woocommerce_is_purchasable', $this->get_id() > 0 && 'publish' === $this->get_status(), $this);
    }

    /**
     * Bundle products are always virtual (no shipping needed for the bundle itself)
     */
    public function is_virtual() {
        return apply_filters('woocommerce_product_is_virtual', false, $this);
    }

    /**
     * Check if product is sold individually
     */
    public function is_sold_individually() {
        return apply_filters('woocommerce_product_is_sold_individually', false, $this);
    }

    /**
     * Get the add to cart button text
     */
    public function add_to_cart_text() {
        return apply_filters('woocommerce_product_add_to_cart_text', __('Select options', 'advanced-bundle-system'), $this);
    }

    /**
     * Get the add to cart button text for single product page
     */
    public function single_add_to_cart_text() {
        return apply_filters('woocommerce_product_single_add_to_cart_text', __('Add to cart', 'advanced-bundle-system'), $this);
    }

    /**
     * Bundle products don't manage their own stock
     */
    public function managing_stock() {
        return false;
    }

    /**
     * Get bundle items
     */
    public function get_bundle_items() {
        $bundle_items = get_post_meta($this->get_id(), '_bundle_items', true);
        return !empty($bundle_items) && is_array($bundle_items) ? $bundle_items : array();
    }

    /**
     * Get bundle products (for backward compatibility)
     */
    public function get_bundle_products() {
        $bundle_products = get_post_meta($this->get_id(), '_bundle_products', true);
        return !empty($bundle_products) && is_array($bundle_products) ? $bundle_products : array();
    }

    /**
     * Get bundle price
     */
    public function get_price($context = 'view') {
        $price = get_post_meta($this->get_id(), '_bundle_price', true);

        // If bundle price is set, use it
        if ($price !== '' && $price !== null) {
            return $price;
        }

        // Otherwise, return the regular price (sum of items)
        // This ensures the price is always shown in product lists
        $regular = $this->get_regular_price($context);
        return $regular !== '' ? $regular : parent::get_price($context);
    }

    /**
     * Get regular price
     */
    public function get_regular_price($context = 'view') {
        // For bundles, regular price is the sum of all bundle items
        $bundle_items = $this->get_bundle_items();

        if (empty($bundle_items)) {
            // Fallback to old format
            $bundle_products = $this->get_bundle_products();
            $total = 0;
            foreach ($bundle_products as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $regular = $product->get_regular_price();
                    $price = $regular !== '' ? floatval($regular) : floatval($product->get_price());
                    $total += $price;
                }
            }
            return $total > 0 ? $total : '';
        }

        // Calculate from bundle items with variations and personalization costs
        $total = 0;
        $personalization_total = 0;

        foreach ($bundle_items as $item) {
            $product_id = $item['product_id'];
            $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
            $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
            $enable_personalization = isset($item['enable_personalization']) && $item['enable_personalization'] === 'yes';
            $personalization_cost = isset($item['personalization_cost']) ? floatval($item['personalization_cost']) : 0;

            // Use variation if specified, otherwise use main product
            if ($variation_id > 0) {
                $product = wc_get_product($variation_id);
            } else {
                $product = wc_get_product($product_id);
            }

            if ($product) {
                // Use regular price, not sale price
                $regular = $product->get_regular_price();
                $price = $regular !== '' ? floatval($regular) : floatval($product->get_price());
                $total += $price * $quantity;
            }

            // Add personalization cost if enabled
            if ($enable_personalization && $personalization_cost > 0) {
                $personalization_total += $personalization_cost * $quantity;
            }
        }

        return ($total + $personalization_total) > 0 ? ($total + $personalization_total) : '';
    }

    /**
     * Get sale price
     */
    public function get_sale_price($context = 'view') {
        $bundle_price = get_post_meta($this->get_id(), '_bundle_price', true);
        $regular_price = $this->get_regular_price();

        if ($bundle_price !== '' && $regular_price && floatval($bundle_price) < floatval($regular_price)) {
            return $bundle_price;
        }

        return '';
    }

    /**
     * Check if product is on sale
     */
    public function is_on_sale($context = 'view') {
        return $this->get_sale_price($context) !== '';
    }

    /**
     * Returns the price in html format
     */
    public function get_price_html($deprecated = '') {
        $regular_price = $this->get_regular_price();
        $bundle_price = get_post_meta($this->get_id(), '_bundle_price', true);

        // If no items in bundle, return empty
        if ($regular_price === '' || $regular_price === null) {
            return apply_filters('woocommerce_bundle_empty_price_html', '', $this);
        }

        $regular_price_num = floatval($regular_price);

        // If no custom bundle price is set, show only regular price
        if ($bundle_price === '' || $bundle_price === null) {
            $price_html = '<span class="abs-bundle-price">' . wc_price($regular_price_num) . '</span>';
            return apply_filters('woocommerce_get_price_html', $price_html, $this);
        }

        $bundle_price_num = floatval($bundle_price);

        // If bundle price is less than regular, show discount
        if ($bundle_price_num < $regular_price_num && $bundle_price_num > 0) {
            $discount_percent = (($regular_price_num - $bundle_price_num) / $regular_price_num) * 100;
            $discount_format = ABS_Settings::get_setting('discount_format', __('Save %s%%', 'advanced-bundle-system'));

            $price_html = '<div class="abs-bundle-pricing">';
            $price_html .= '<span class="abs-original-price"><del>' . wc_price($regular_price_num) . '</del></span> ';
            $price_html .= '<span class="abs-bundle-price"><ins>' . wc_price($bundle_price_num) . '</ins></span>';
            $price_html .= ' <span class="abs-discount-badge">' . sprintf($discount_format, number_format($discount_percent, 0)) . '</span>';
            $price_html .= '</div>';
        } else {
            // Show bundle price (or regular if bundle is higher/equal)
            $display_price = $bundle_price_num > 0 ? $bundle_price_num : $regular_price_num;
            $price_html = '<span class="abs-bundle-price">' . wc_price($display_price) . '</span>';
        }

        return apply_filters('woocommerce_get_price_html', $price_html, $this);
    }
}
