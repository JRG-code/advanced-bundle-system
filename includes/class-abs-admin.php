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

        // Add inventory column to products list
        add_filter('manage_edit-product_columns', array($this, 'add_inventory_column'));
        add_action('manage_product_posts_custom_column', array($this, 'display_inventory_column'), 10, 2);
        add_filter('manage_edit-product_sortable_columns', array($this, 'make_inventory_column_sortable'));
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

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 20,
            's' => $term,
            'post_status' => 'publish'
        );

        if ($exclude_id > 0) {
            $args['post__not_in'] = array($exclude_id);
        }

        $products = get_posts($args);
        $results = array();

        foreach ($products as $product) {
            $product_obj = wc_get_product($product->ID);
            // Exclude bundle products and the current product being edited
            if ($product_obj && $product_obj->get_type() !== 'bundle' && $product->ID !== $exclude_id) {
                $results[] = array(
                    'id' => $product->ID,
                    'text' => $product->post_title . ' (#' . $product->ID . ') - ' . wc_price($product_obj->get_price())
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

            $product = wc_get_product($product_id);
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
     * Add inventory column to products list
     */
    public function add_inventory_column($columns) {
        // Insert inventory column after the price column
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'price') {
                $new_columns['abs_inventory'] = __('Inventory', 'advanced-bundle-system');
            }
        }
        return $new_columns;
    }

    /**
     * Display inventory column content
     */
    public function display_inventory_column($column, $post_id) {
        if ($column !== 'abs_inventory') {
            return;
        }

        $product = wc_get_product($post_id);
        if (!$product) {
            echo '<span style="color: #999;">—</span>';
            return;
        }

        $product_type = $product->get_type();

        // For bundle products
        if ($product_type === 'bundle') {
            $bundle_items = get_post_meta($post_id, '_bundle_items', true);

            if (empty($bundle_items) || !is_array($bundle_items)) {
                echo '<span style="color: #d63638;">⚠ No items</span>';
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

            if (!$is_in_stock) {
                echo '<span style="color: #d63638; font-weight: 500;">✗ Out of stock</span>';
                if (!empty($out_of_stock_items)) {
                    echo '<br><small style="color: #999;">' . esc_html(implode(', ', array_slice($out_of_stock_items, 0, 2))) . '</small>';
                }
            } elseif ($min_stock !== null) {
                if ($min_stock <= 0) {
                    echo '<span style="color: #d63638; font-weight: 500;">✗ Out of stock</span>';
                } elseif ($min_stock <= 5) {
                    echo '<span style="color: #dba617; font-weight: 500;">⚠ Low stock</span>';
                    echo '<br><small style="color: #999;">' . sprintf(__('%d available', 'advanced-bundle-system'), $min_stock) . '</small>';
                } else {
                    echo '<span style="color: #00a32a; font-weight: 500;">✓ In stock</span>';
                    echo '<br><small style="color: #999;">' . sprintf(__('%d available', 'advanced-bundle-system'), $min_stock) . '</small>';
                }
            } else {
                echo '<span style="color: #00a32a; font-weight: 500;">✓ In stock</span>';
            }
        }
        // For regular products
        else {
            if ($product->managing_stock()) {
                $stock_quantity = $product->get_stock_quantity();
                $stock_status = $product->get_stock_status();

                if ($stock_quantity <= 0 || $stock_status === 'outofstock') {
                    echo '<span style="color: #d63638; font-weight: 500;">✗ Out of stock</span>';
                } elseif ($stock_quantity <= 5) {
                    echo '<span style="color: #dba617; font-weight: 500;">⚠ Low stock</span>';
                    echo '<br><small style="color: #999;">' . sprintf(__('%d in stock', 'advanced-bundle-system'), $stock_quantity) . '</small>';
                } else {
                    echo '<span style="color: #00a32a; font-weight: 500;">✓ In stock</span>';
                    echo '<br><small style="color: #999;">' . sprintf(__('%d in stock', 'advanced-bundle-system'), $stock_quantity) . '</small>';
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
        }
    }

    /**
     * Make inventory column sortable
     */
    public function make_inventory_column_sortable($columns) {
        $columns['abs_inventory'] = 'stock_status';
        return $columns;
    }
}

new ABS_Admin();
