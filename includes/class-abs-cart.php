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
        add_filter('woocommerce_add_cart_item', array($this, 'adjust_cart_item_price'), 10, 2);
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_item_meta'), 10, 4);
        add_filter('woocommerce_cart_item_name', array($this, 'display_bundle_items_in_cart'), 10, 3);
        add_filter('woocommerce_cart_item_price', array($this, 'display_bundle_item_price'), 10, 3);
    }

    /**
     * Add personalization and attributes data to cart item
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        // Handle personalization data
        if (isset($_POST['abs_personalization'])) {
            $personalization_data = array();

            foreach ($_POST['abs_personalization'] as $unique_id => $data) {
                // Only add if enabled is set to 1 and text is not empty
                $enabled = isset($data['enabled']) && $data['enabled'] == '1';
                $has_text = isset($data['text']) && !empty($data['text']);

                if ($enabled && $has_text) {
                    $personalization_data[$unique_id] = array(
                        'text' => sanitize_text_field($data['text']),
                        'enabled' => true
                    );
                }
            }

            if (!empty($personalization_data)) {
                $cart_item_data['abs_personalization'] = $personalization_data;
            }
        }

        // Handle attributes data and find matching variations
        if (isset($_POST['abs_attributes'])) {
            $attributes_data = array();
            $variation_ids = array();

            // Get bundle items to match unique_id with product_id
            $bundle_items = get_post_meta($product_id, '_bundle_items', true);
            if (is_array($bundle_items)) {
                $item_counter = 0;
                foreach ($bundle_items as $item) {
                    $bundled_product_id = $item['product_id'];
                    $item_quantity = isset($item['quantity']) ? $item['quantity'] : 1;

                    for ($q = 0; $q < $item_quantity; $q++) {
                        $unique_id = $item_counter++;

                        if (isset($_POST['abs_attributes'][$unique_id]) && is_array($_POST['abs_attributes'][$unique_id])) {
                            $attributes = $_POST['abs_attributes'][$unique_id];
                            $sanitized_attributes = array();

                            foreach ($attributes as $attr_name => $attr_value) {
                                $sanitized_attributes[sanitize_text_field($attr_name)] = sanitize_text_field($attr_value);
                            }

                            $attributes_data[$unique_id] = $sanitized_attributes;

                            // Find matching variation for variable products
                            $bundled_product = wc_get_product($bundled_product_id);
                            if ($bundled_product && $bundled_product->is_type('variable')) {
                                $variation_id = $this->find_matching_variation($bundled_product, $sanitized_attributes);
                                if ($variation_id) {
                                    $variation_ids[$unique_id] = $variation_id;
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($attributes_data)) {
                $cart_item_data['abs_attributes'] = $attributes_data;
            }

            if (!empty($variation_ids)) {
                $cart_item_data['abs_variation_ids'] = $variation_ids;
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
     * Adjust cart item price to include personalization costs
     */
    public function adjust_cart_item_price($cart_item, $cart_item_key) {
        // Only proceed if personalization data exists
        if (!isset($cart_item['abs_personalization']) || empty($cart_item['abs_personalization'])) {
            return $cart_item;
        }

        $product = $cart_item['data'];
        $product_id = $product->get_id();
        $personalization_cost_total = 0;

        // For bundle products
        if ($product->get_type() === 'bundle') {
            $bundle_items = get_post_meta($product_id, '_bundle_items', true);

            if (is_array($bundle_items)) {
                $item_counter = 0;

                foreach ($bundle_items as $item) {
                    $item_quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                    $enable_personalization = isset($item['enable_personalization']) && $item['enable_personalization'] === 'yes';
                    $personalization_cost = isset($item['personalization_cost']) ? floatval($item['personalization_cost']) : 0;

                    // Check each instance of this product in the bundle
                    for ($q = 0; $q < $item_quantity; $q++) {
                        $unique_id = $item_counter++;

                        // If this item has personalization text entered, add the cost
                        if ($enable_personalization && $personalization_cost > 0) {
                            if (isset($cart_item['abs_personalization'][$unique_id]) &&
                                !empty($cart_item['abs_personalization'][$unique_id]['text'])) {
                                $personalization_cost_total += $personalization_cost;
                            }
                        }
                    }
                }
            }
        }
        // For regular products (simple, variable, etc.)
        else {
            $enable_personalization = get_post_meta($product_id, '_abs_enable_personalization', true);
            $personalization_cost = floatval(get_post_meta($product_id, '_abs_personalization_cost', true));

            // Check if personalization text was entered (unique_id 0 for regular products)
            if ($enable_personalization === 'yes' && $personalization_cost > 0) {
                if (isset($cart_item['abs_personalization'][0]) &&
                    !empty($cart_item['abs_personalization'][0]['text'])) {
                    $personalization_cost_total += $personalization_cost;
                }
            }
        }

        // Add personalization cost to product price
        if ($personalization_cost_total > 0) {
            $original_price = $product->get_price();
            $new_price = $original_price + $personalization_cost_total;
            $product->set_price($new_price);

            // Store the personalization cost for later display
            $cart_item['abs_personalization_cost'] = $personalization_cost_total;
        }

        return $cart_item;
    }

    /**
     * Display cart item data
     */
    public function display_cart_item_data($item_data, $cart_item) {
        // Display attributes
        if (isset($cart_item['abs_attributes'])) {
            foreach ($cart_item['abs_attributes'] as $unique_id => $attributes) {
                foreach ($attributes as $attr_name => $attr_value) {
                    $item_data[] = array(
                        'key' => ucfirst(str_replace('_', ' ', $attr_name)),
                        'value' => esc_html($attr_value),
                        'display' => ''
                    );
                }
            }
        }

        // Display personalization
        if (isset($cart_item['abs_personalization'])) {
            foreach ($cart_item['abs_personalization'] as $unique_id => $personalization) {
                $item_data[] = array(
                    'key' => __('Personalization', 'advanced-bundle-system'),
                    'value' => esc_html($personalization['text']),
                    'display' => ''
                );
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
        // Save attributes
        if (isset($values['abs_attributes'])) {
            foreach ($values['abs_attributes'] as $unique_id => $attributes) {
                foreach ($attributes as $attr_name => $attr_value) {
                    $item->add_meta_data(
                        ucfirst(str_replace('_', ' ', $attr_name)),
                        $attr_value,
                        true
                    );
                }
            }
        }

        // Save personalization
        if (isset($values['abs_personalization'])) {
            foreach ($values['abs_personalization'] as $unique_id => $personalization) {
                $item->add_meta_data(
                    __('Personalization', 'advanced-bundle-system'),
                    $personalization['text'],
                    true
                );
            }
        }

        if (isset($values['abs_bundle_products'])) {
            $item->add_meta_data('_abs_bundle_products', $values['abs_bundle_products'], false);
        }

        // Save variation IDs for stock management
        if (isset($values['abs_variation_ids'])) {
            $item->add_meta_data('_abs_variation_ids', $values['abs_variation_ids'], false);
        }
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
}

new ABS_Cart();
