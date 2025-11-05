<?php
/**
 * Cart functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Cart {

    public function __construct() {
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_item_meta'), 10, 4);
        add_filter('woocommerce_cart_item_name', array($this, 'display_bundle_items_in_cart'), 10, 3);
        add_filter('woocommerce_cart_item_price', array($this, 'display_bundle_item_price'), 10, 3);
    }

    /**
     * Add personalization data to cart item
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['abs_personalization'])) {
            $personalization_data = array();

            foreach ($_POST['abs_personalization'] as $pid => $data) {
                if (!empty($data['text'])) {
                    $personalization_data[intval($pid)] = array(
                        'text' => sanitize_text_field($data['text'])
                    );
                }
            }

            if (!empty($personalization_data)) {
                $cart_item_data['abs_personalization'] = $personalization_data;
            }
        }

        // Add bundle products info
        $product = wc_get_product($product_id);
        if ($product && 'bundle' === $product->get_type()) {
            $bundle_products = get_post_meta($product_id, '_bundle_products', true);
            if (!empty($bundle_products)) {
                $cart_item_data['abs_bundle_products'] = $bundle_products;
            }
        }

        return $cart_item_data;
    }

    /**
     * Display cart item data
     */
    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['abs_personalization'])) {
            foreach ($cart_item['abs_personalization'] as $product_id => $personalization) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $item_data[] = array(
                        'key' => sprintf(__('Personalization for %s', 'advanced-bundle-system'), $product->get_name()),
                        'value' => esc_html($personalization['text']),
                        'display' => ''
                    );
                }
            }
        }

        return $item_data;
    }

    /**
     * Display bundle items in cart with nested structure
     */
    public function display_bundle_items_in_cart($product_name, $cart_item, $cart_item_key) {
        $product = $cart_item['data'];

        if ('bundle' !== $product->get_type() || !isset($cart_item['abs_bundle_products'])) {
            return $product_name;
        }

        $bundle_products = $cart_item['abs_bundle_products'];

        $output = '<div class="abs-cart-bundle-main">' . $product_name . '</div>';
        $output .= '<div class="abs-cart-bundle-items">';

        foreach ($bundle_products as $bundled_product_id) {
            $bundled_product = wc_get_product($bundled_product_id);
            if (!$bundled_product) {
                continue;
            }

            $output .= '<div class="abs-cart-bundle-item">';
            $output .= '<span class="abs-bundle-item-indicator">└─</span> ';
            $output .= '<span class="abs-bundle-item-name">' . esc_html($bundled_product->get_name()) . '</span>';

            // Show personalization if exists
            if (isset($cart_item['abs_personalization'][$bundled_product_id])) {
                $personalization = $cart_item['abs_personalization'][$bundled_product_id];
                $output .= '<div class="abs-bundle-item-personalization">';
                $output .= '<small>' . sprintf(__('Personalized: "%s"', 'advanced-bundle-system'), esc_html($personalization['text'])) . '</small>';
                $output .= '</div>';
            }

            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Display bundle item price in cart
     */
    public function display_bundle_item_price($price, $cart_item, $cart_item_key) {
        $product = $cart_item['data'];

        if ('bundle' !== $product->get_type() || !isset($cart_item['abs_bundle_products'])) {
            return $price;
        }

        $bundle_products = $cart_item['abs_bundle_products'];
        $original_total = ABS_Product_Type::get_bundle_products_total($bundle_products);
        $bundle_price = $product->get_price();
        $discount_percent = ABS_Product_Type::calculate_discount($original_total, $bundle_price);

        if ($discount_percent > 0) {
            $price = '<del>' . wc_price($original_total) . '</del> <ins>' . wc_price($bundle_price) . '</ins>';
            $price .= ' <span class="abs-cart-discount">(' . sprintf(__('-%s%%', 'advanced-bundle-system'), $discount_percent) . ')</span>';
        }

        return $price;
    }

    /**
     * Add order item meta
     */
    public function add_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['abs_personalization'])) {
            foreach ($values['abs_personalization'] as $product_id => $personalization) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $item->add_meta_data(
                        sprintf(__('Personalization for %s', 'advanced-bundle-system'), $product->get_name()),
                        $personalization['text'],
                        true
                    );
                }
            }
        }

        if (isset($values['abs_bundle_products'])) {
            $item->add_meta_data('_abs_bundle_products', $values['abs_bundle_products'], false);
        }
    }
}

new ABS_Cart();
