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
        return $price !== '' ? $price : parent::get_price($context);
    }

    /**
     * Get regular price
     */
    public function get_regular_price($context = 'view') {
        // For bundles, regular price is the sum of all bundle items
        $bundle_products = $this->get_bundle_products();
        if (empty($bundle_products)) {
            $bundle_items = $this->get_bundle_items();
            $bundle_products = array();
            foreach ($bundle_items as $item) {
                $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                for ($i = 0; $i < $quantity; $i++) {
                    $bundle_products[] = $item['product_id'];
                }
            }
        }

        $total = 0;
        foreach ($bundle_products as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                // Use regular price, not sale price, for calculating bundle original total
                $regular = $product->get_regular_price();
                // If no regular price, fall back to current price
                $price = $regular !== '' ? floatval($regular) : floatval($product->get_price());
                $total += $price;
            }
        }
        return $total > 0 ? $total : '';
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
        $bundle_price = $this->get_price();

        if ($bundle_price === '' || $regular_price === '') {
            return apply_filters('woocommerce_bundle_empty_price_html', '', $this);
        }

        $regular_price_num = floatval($regular_price);
        $bundle_price_num = floatval($bundle_price);

        if ($bundle_price_num < $regular_price_num) {
            // Show discount
            $discount_percent = (($regular_price_num - $bundle_price_num) / $regular_price_num) * 100;
            $discount_format = ABS_Settings::get_setting('discount_format', __('Save %s%%', 'advanced-bundle-system'));

            $price_html = '<div class="abs-bundle-pricing">';
            $price_html .= '<span class="abs-original-price"><del>' . wc_price($regular_price_num) . '</del></span> ';
            $price_html .= '<span class="abs-bundle-price"><ins>' . wc_price($bundle_price_num) . '</ins></span>';
            $price_html .= ' <span class="abs-discount-badge">' . sprintf($discount_format, number_format($discount_percent, 0)) . '</span>';
            $price_html .= '</div>';
        } else {
            $price_html = '<span class="abs-bundle-price">' . wc_price($bundle_price_num) . '</span>';
        }

        return apply_filters('woocommerce_get_price_html', $price_html, $this);
    }
}
