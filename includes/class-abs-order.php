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

        // Handle stock reduction for bundle products
        add_action('woocommerce_reduce_order_stock', array($this, 'reduce_bundle_item_stock'));
        add_action('woocommerce_restore_order_stock', array($this, 'restore_bundle_item_stock'));
    }

    /**
     * Display bundle items in order
     */
    public function display_bundle_items_in_order($item_id, $item, $order, $plain_text) {
        $bundle_products = $item->get_meta('_abs_bundle_products', true);

        if (empty($bundle_products)) {
            return;
        }

        $bundle_includes_text = ABS_Settings::get_setting('cart_bundle_includes', __('Bundle includes:', 'advanced-bundle-system'));

        if ($plain_text) {
            echo "\n" . $bundle_includes_text . "\n";
            foreach ($bundle_products as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    echo "  - " . $product->get_name() . "\n";
                }
            }
        } else {
            echo '<div class="abs-order-bundle-items">';
            echo '<strong>' . esc_html($bundle_includes_text) . '</strong>';
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
            // Check for both "Personalization" and "Personalization for" keys
            if ($key === __('Personalization', 'advanced-bundle-system') || strpos($key, 'Personalization for') !== false) {
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

    /**
     * Reduce stock for individual bundle products when order is placed
     */
    public function reduce_bundle_item_stock($order) {
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();

            if (!$product || 'bundle' !== $product->get_type()) {
                continue;
            }

            // Get bundle items
            $bundle_items = get_post_meta($product->get_id(), '_bundle_items', true);
            if (empty($bundle_items) || !is_array($bundle_items)) {
                continue;
            }

            // Get variation IDs if attributes were selected
            $variation_ids = $item->get_meta('_abs_variation_ids', true);
            $order_quantity = $item->get_quantity();

            $item_counter = 0;
            foreach ($bundle_items as $bundle_item) {
                $product_id = $bundle_item['product_id'];
                $item_quantity = isset($bundle_item['quantity']) ? $bundle_item['quantity'] : 1;

                for ($q = 0; $q < $item_quantity; $q++) {
                    $unique_id = $item_counter++;
                    $total_quantity = $order_quantity; // Each bundle ordered reduces stock by item quantity

                    // Check if this item has a variation ID
                    if (is_array($variation_ids) && isset($variation_ids[$unique_id])) {
                        $variation_id = $variation_ids[$unique_id];
                        $variation = wc_get_product($variation_id);

                        if ($variation && $variation->managing_stock()) {
                            $new_stock = wc_update_product_stock($variation, $total_quantity, 'decrease');

                            $item->add_meta_data(
                                '_abs_reduced_stock_' . $unique_id,
                                array(
                                    'product_id' => $variation_id,
                                    'quantity' => $total_quantity,
                                    'new_stock' => $new_stock
                                ),
                                true
                            );

                            $order->add_order_note(
                                sprintf(
                                    __('Stock reduced: %s #%d (variation) by %d. New stock: %d', 'advanced-bundle-system'),
                                    $variation->get_name(),
                                    $variation_id,
                                    $total_quantity,
                                    $new_stock
                                )
                            );
                        }
                    } else {
                        // Simple product or variable product without variation
                        $bundled_product = wc_get_product($product_id);

                        if ($bundled_product && $bundled_product->managing_stock()) {
                            $new_stock = wc_update_product_stock($bundled_product, $total_quantity, 'decrease');

                            $item->add_meta_data(
                                '_abs_reduced_stock_' . $unique_id,
                                array(
                                    'product_id' => $product_id,
                                    'quantity' => $total_quantity,
                                    'new_stock' => $new_stock
                                ),
                                true
                            );

                            $order->add_order_note(
                                sprintf(
                                    __('Stock reduced: %s #%d by %d. New stock: %d', 'advanced-bundle-system'),
                                    $bundled_product->get_name(),
                                    $product_id,
                                    $total_quantity,
                                    $new_stock
                                )
                            );
                        }
                    }
                }
            }

            $item->save();
        }
    }

    /**
     * Restore stock for individual bundle products when order is cancelled/refunded
     */
    public function restore_bundle_item_stock($order) {
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();

            if (!$product || 'bundle' !== $product->get_type()) {
                continue;
            }

            // Get all meta data related to stock reduction
            $meta_data = $item->get_meta_data();

            foreach ($meta_data as $meta) {
                $key = $meta->key;

                if (strpos($key, '_abs_reduced_stock_') === 0) {
                    $stock_data = $meta->value;

                    if (is_array($stock_data) && isset($stock_data['product_id'], $stock_data['quantity'])) {
                        $product_to_restore = wc_get_product($stock_data['product_id']);

                        if ($product_to_restore && $product_to_restore->managing_stock()) {
                            $new_stock = wc_update_product_stock($product_to_restore, $stock_data['quantity'], 'increase');

                            $order->add_order_note(
                                sprintf(
                                    __('Stock restored: %s #%d by %d. New stock: %d', 'advanced-bundle-system'),
                                    $product_to_restore->get_name(),
                                    $stock_data['product_id'],
                                    $stock_data['quantity'],
                                    $new_stock
                                )
                            );
                        }

                        // Remove the meta data after restoring
                        $item->delete_meta_data($key);
                    }
                }
            }

            $item->save();
        }
    }
}

new ABS_Order();
