<?php
/**
 * Admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Admin {

    public function __construct() {
        add_action('admin_footer', array($this, 'add_product_search_script'));
        add_action('wp_ajax_abs_search_products', array($this, 'ajax_search_products'));
        add_action('wp_ajax_abs_calculate_bundle_pricing', array($this, 'ajax_calculate_bundle_pricing'));

        // Override WooCommerce's is_in_stock column rendering
        // Use admin_init to remove WooCommerce's handler and add ours
        add_action('admin_init', array($this, 'setup_stock_column_override'));
    }

    /**
     * Setup our stock column handler
     */
    public function setup_stock_column_override() {
        // Add our handler with priority 10 (same as WooCommerce)
        add_action('manage_product_posts_custom_column', array($this, 'display_inventory_column'), 10, 2);
    }

    /**
     * Add product search script and nonces
     */
    public function add_product_search_script() {
        global $post;
        if (!$post || 'product' !== $post->post_type) {
            return;
        }
        ?>
        <input type="hidden" id="abs_search_nonce" value="<?php echo wp_create_nonce('abs-search-products'); ?>" />
        <input type="hidden" id="abs_pricing_nonce" value="<?php echo wp_create_nonce('abs-calculate-pricing'); ?>" />
        <input type="hidden" id="abs_current_product_id" value="<?php echo esc_attr($post->ID); ?>" />
        <?php
    }

    /**
     * AJAX search products
     */
    public function ajax_search_products() {
        check_ajax_referer('abs-search-products', 'nonce');

        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        $exclude_id = isset($_GET['exclude_id']) ? intval($_GET['exclude_id']) : 0;

        // Use wc_get_products() for better performance
        $args = array(
            'limit' => 20,
            'status' => 'publish',
            'return' => 'objects',
        );

        // Add search parameter if term is provided
        if (!empty($term)) {
            $args['s'] = $term;
        }

        // Exclude the current product being edited
        if ($exclude_id > 0) {
            $args['exclude'] = array($exclude_id);
        }

        $products = wc_get_products($args);
        $results = array();

        foreach ($products as $product_obj) {
            // Exclude bundle products
            if ($product_obj && $product_obj->get_type() !== 'bundle') {
                $results[] = array(
                    'id' => $product_obj->get_id(),
                    'text' => $product_obj->get_name() . ' (#' . $product_obj->get_id() . ') - ' . wc_price($product_obj->get_price())
                );
            }
        }

        wp_send_json($results);
    }

    /**
     * AJAX calculate bundle pricing
     */
    public function ajax_calculate_bundle_pricing() {
        check_ajax_referer('abs-calculate-pricing', 'nonce');

        $bundle_items = isset($_POST['bundle_items']) ? $_POST['bundle_items'] : array();
        $bundle_price = isset($_POST['bundle_price']) ? floatval($_POST['bundle_price']) : 0;

        $original_total = 0;

        // Calculate total based on products and quantities
        foreach ($bundle_items as $item) {
            if (empty($item['product_id'])) {
                continue;
            }

            $product_id = intval($item['product_id']);
            $quantity = isset($item['quantity']) && $item['quantity'] > 0 ? intval($item['quantity']) : 1;
            $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;

            // Use variation if specified, otherwise use main product
            $product = $variation_id > 0 ? wc_get_product($variation_id) : wc_get_product($product_id);

            if ($product) {
                // Use regular price for original total calculation
                $regular = $product->get_regular_price();
                // If no regular price, fall back to current price
                $price = $regular !== '' ? floatval($regular) : floatval($product->get_price());

                if ($price > 0) {
                    $original_total += $price * $quantity;
                }
            }
        }

        $discount_percent = ABS_Product_Type::calculate_discount($original_total, $bundle_price);

        wp_send_json_success(array(
            'original_total' => $original_total,
            'original_total_formatted' => wc_price($original_total),
            'bundle_price' => $bundle_price,
            'bundle_price_formatted' => wc_price($bundle_price),
            'discount_percent' => $discount_percent,
            'debug' => array(
                'items_count' => count($bundle_items),
                'items' => $bundle_items
            )
        ));
    }

    /**
     * Override WooCommerce's stock column display with our custom logic
     */
    public function display_inventory_column($column, $post_id) {
        // Only handle the is_in_stock column
        if ($column !== 'is_in_stock') {
            return;
        }

        $product = wc_get_product($post_id);
        if (!$product) {
            echo '<div class="abs-stock-content"><span style="color: #999;">—</span></div>';
            return;
        }

        $product_type = $product->get_type();

        // Start our custom content wrapper
        echo '<div class="abs-stock-content">';

        // For bundle products
        if ($product_type === 'bundle') {
            $bundle_items = get_post_meta($post_id, '_bundle_items', true);

            if (empty($bundle_items) || !is_array($bundle_items)) {
                echo '<span style="color: #d63638;">⚠ No items</span>';
                echo '</div>'; // Close wrapper
                return;
            }

            $min_stock = null;
            $is_in_stock = true;
            $out_of_stock_items = array();

            foreach ($bundle_items as $item) {
                $bundled_product = wc_get_product($item['product_id']);
                if (!$bundled_product) {
                    continue;
                }

                $quantity = isset($item['quantity']) ? $item['quantity'] : 1;

                if ($bundled_product->managing_stock()) {
                    $stock_quantity = $bundled_product->get_stock_quantity();
                    $available_bundles = floor($stock_quantity / $quantity);

                    if ($min_stock === null || $available_bundles < $min_stock) {
                        $min_stock = $available_bundles;
                    }

                    if ($stock_quantity < $quantity) {
                        $is_in_stock = false;
                        $out_of_stock_items[] = $bundled_product->get_name();
                    }
                } elseif (!$bundled_product->is_in_stock()) {
                    $is_in_stock = false;
                    $out_of_stock_items[] = $bundled_product->get_name();
                }
            }

            // Output with proper structure
            echo '<div style="white-space: nowrap;">';

            if (!$is_in_stock) {
                echo '<span style="color: #d63638; font-weight: 500;">✗ Out of stock</span>';
                if (!empty($out_of_stock_items)) {
                    echo '<br><small style="color: #999; display: block; margin-top: 3px; white-space: normal;">' . esc_html(implode(', ', array_slice($out_of_stock_items, 0, 2))) . '</small>';
                }
            } elseif ($min_stock !== null) {
                if ($min_stock <= 0) {
                    echo '<span style="color: #d63638; font-weight: 500;">✗ Out of stock</span>';
                } elseif ($min_stock <= 5) {
                    echo '<span style="color: #dba617; font-weight: 500;">⚠ Low stock</span>';
                    echo '<br><small style="color: #999; display: block; margin-top: 3px;">' . sprintf(__('%d available', 'advanced-bundle-system'), $min_stock) . '</small>';
                } else {
                    echo '<span style="color: #00a32a; font-weight: 500;">✓ In stock</span>';
                    echo '<br><small style="color: #999; display: block; margin-top: 3px;">' . sprintf(__('%d available', 'advanced-bundle-system'), $min_stock) . '</small>';
                }
            } else {
                echo '<span style="color: #00a32a; font-weight: 500;">✓ In stock</span>';
            }

            echo '</div>';
        }
        // For regular products
        else {
            echo '<div style="white-space: nowrap;">';

            if ($product->managing_stock()) {
                $stock_quantity = $product->get_stock_quantity();
                $stock_status = $product->get_stock_status();

                if ($stock_quantity <= 0 || $stock_status === 'outofstock') {
                    echo '<span style="color: #d63638; font-weight: 500;">✗ Out of stock</span>';
                } elseif ($stock_quantity <= 5) {
                    echo '<span style="color: #dba617; font-weight: 500;">⚠ Low stock</span>';
                    echo '<br><small style="color: #999; display: block; margin-top: 3px;">' . sprintf(__('%d in stock', 'advanced-bundle-system'), $stock_quantity) . '</small>';
                } else {
                    echo '<span style="color: #00a32a; font-weight: 500;">✓ In stock</span>';
                    echo '<br><small style="color: #999; display: block; margin-top: 3px;">' . sprintf(__('%d in stock', 'advanced-bundle-system'), $stock_quantity) . '</small>';
                }
            } else {
                $stock_status = $product->get_stock_status();
                if ($stock_status === 'instock') {
                    echo '<span style="color: #00a32a; font-weight: 500;">✓ In stock</span>';
                } elseif ($stock_status === 'onbackorder') {
                    echo '<span style="color: #dba617; font-weight: 500;">⟳ On backorder</span>';
                } else {
                    echo '<span style="color: #d63638; font-weight: 500;">✗ Out of stock</span>';
                }
            }

            echo '</div>';
        }

        // Close the abs-stock-content wrapper
        echo '</div>';
    }
}

new ABS_Admin();
