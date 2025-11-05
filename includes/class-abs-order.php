<?php
/**
 * Order processing functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Order {

    public function __construct() {
        add_action('woocommerce_order_item_meta_end', array($this, 'display_bundle_items_in_order'), 10, 4);
        add_filter('woocommerce_order_item_name', array($this, 'format_bundle_item_name'), 10, 3);
        add_action('woocommerce_admin_order_item_headers', array($this, 'add_admin_order_item_headers'));
        add_action('woocommerce_admin_order_item_values', array($this, 'add_admin_order_item_values'), 10, 3);
    }

    /**
     * Display bundle items in order
     */
    public function display_bundle_items_in_order($item_id, $item, $order, $plain_text) {
        $bundle_products = $item->get_meta('_abs_bundle_products', true);

        if (empty($bundle_products)) {
            return;
        }

        if ($plain_text) {
            echo "\n" . __('Bundle includes:', 'advanced-bundle-system') . "\n";
            foreach ($bundle_products as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    echo "  - " . $product->get_name() . "\n";
                }
            }
        } else {
            echo '<div class="abs-order-bundle-items">';
            echo '<strong>' . __('Bundle includes:', 'advanced-bundle-system') . '</strong>';
            echo '<ul>';
            foreach ($bundle_products as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    echo '<li>' . esc_html($product->get_name()) . '</li>';
                }
            }
            echo '</ul>';
            echo '</div>';
        }
    }

    /**
     * Format bundle item name in order
     */
    public function format_bundle_item_name($product_name, $item, $is_visible) {
        $bundle_products = $item->get_meta('_abs_bundle_products', true);

        if (empty($bundle_products)) {
            return $product_name;
        }

        $output = '<div class="abs-order-bundle-main">' . $product_name;

        // Show discount badge if applicable
        $product = $item->get_product();
        if ($product && 'bundle' === $product->get_type()) {
            $original_total = ABS_Product_Type::get_bundle_products_total($bundle_products);
            $bundle_price = $item->get_total();
            $discount_percent = ABS_Product_Type::calculate_discount($original_total, $bundle_price);

            if ($discount_percent > 0) {
                $output .= ' <span class="abs-order-discount-badge">' . sprintf(__('(-%s%%)', 'advanced-bundle-system'), $discount_percent) . '</span>';
            }
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Add admin order item headers
     */
    public function add_admin_order_item_headers() {
        ?>
        <th class="abs-personalization-column"><?php _e('Personalization', 'advanced-bundle-system'); ?></th>
        <?php
    }

    /**
     * Add admin order item values
     */
    public function add_admin_order_item_values($product, $item, $item_id) {
        echo '<td class="abs-personalization-column">';

        // Get all meta data and look for personalization
        $meta_data = $item->get_meta_data();
        $has_personalization = false;

        foreach ($meta_data as $meta) {
            $key = $meta->key;
            if (strpos($key, 'Personalization for') !== false) {
                if ($has_personalization) {
                    echo '<br>';
                }
                echo '<small>' . esc_html($meta->value) . '</small>';
                $has_personalization = true;
            }
        }

        if (!$has_personalization) {
            echo '<small>' . __('None', 'advanced-bundle-system') . '</small>';
        }

        echo '</td>';
    }
}

new ABS_Order();
