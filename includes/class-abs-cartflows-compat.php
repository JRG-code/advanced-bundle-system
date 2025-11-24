<?php
/**
 * CartFlows Compatibility
 * Ensures personalization works properly with CartFlows checkout pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_CartFlows_Compat {

    public function __construct() {
        // Check if CartFlows is active
        if (!$this->is_cartflows_active()) {
            return;
        }

        // Ensure personalization displays in CartFlows checkout
        add_filter('woocommerce_checkout_cart_item_quantity', array($this, 'add_personalization_to_checkout_review'), 10, 3);

        // Support CartFlows order bumps
        add_filter('cartflows_order_bump_product_data', array($this, 'add_personalization_to_order_bump'), 10, 2);

        // Ensure personalization meta is saved with CartFlows orders
        add_action('cartflows_checkout_order_processed', array($this, 'ensure_personalization_saved'), 10, 2);
    }

    /**
     * Check if CartFlows is active
     */
    private function is_cartflows_active() {
        return class_exists('Cartflows_Loader') || defined('CARTFLOWS_VER');
    }

    /**
     * Add personalization to checkout review
     * This ensures personalization displays in CartFlows checkout templates
     */
    public function add_personalization_to_checkout_review($product_quantity, $cart_item, $cart_item_key) {
        // Add personalization info if exists
        if (isset($cart_item['abs_personalization']) && !empty($cart_item['abs_personalization'])) {
            $personalization_html = '<div class="abs-checkout-personalization">';

            foreach ($cart_item['abs_personalization'] as $unique_id => $personalization) {
                if (!empty($personalization['text'])) {
                    $personalization_html .= '<div class="abs-personalization-item">';
                    $personalization_html .= '<small><strong>' . __('Personalization:', 'advanced-bundle-system') . '</strong> ';
                    $personalization_html .= esc_html($personalization['text']) . '</small>';
                    $personalization_html .= '</div>';
                }
            }

            $personalization_html .= '</div>';

            $product_quantity .= $personalization_html;
        }

        return $product_quantity;
    }

    /**
     * Add personalization to CartFlows order bumps
     * This ensures personalization works with order bump products
     */
    public function add_personalization_to_order_bump($product_data, $product_id) {
        // Check if product has personalization enabled
        $enable_personalization = get_post_meta($product_id, '_abs_enable_personalization', true);

        if ($enable_personalization === 'yes') {
            $product_data['has_personalization'] = true;
            $product_data['personalization_label'] = get_post_meta($product_id, '_abs_personalization_label', true);
            $product_data['max_characters'] = get_post_meta($product_id, '_abs_personalization_max_chars', true);
        }

        return $product_data;
    }

    /**
     * Ensure personalization is saved with CartFlows orders
     * CartFlows sometimes bypasses standard WooCommerce order creation hooks
     */
    public function ensure_personalization_saved($order_id, $post_data) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Verify personalization was saved - if not, add it now
        foreach ($order->get_items() as $item_id => $item) {
            $cart_item_key = $item->get_meta('_cartflows_cart_item_key', true);

            if ($cart_item_key) {
                $cart = WC()->cart;
                $cart_contents = $cart->get_cart_contents();

                if (isset($cart_contents[$cart_item_key])) {
                    $cart_item = $cart_contents[$cart_item_key];

                    // Add personalization if it exists in cart but not in order
                    if (isset($cart_item['abs_personalization']) && !$item->get_meta('Personalization', true)) {
                        foreach ($cart_item['abs_personalization'] as $unique_id => $personalization) {
                            $item->add_meta_data(
                                __('Personalization', 'advanced-bundle-system'),
                                $personalization['text'],
                                true
                            );
                        }
                        $item->save();
                    }
                }
            }
        }
    }
}

new ABS_CartFlows_Compat();
