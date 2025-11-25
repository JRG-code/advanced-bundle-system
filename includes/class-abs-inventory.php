<?php
/**
 * Centralized Inventory Management
 *
 * @package Advanced_Bundle_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Inventory {

    private static $instance = null;
    private $bundles_cache = null; // Cache for bundle lookups

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_inventory_menu'), 60);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_inventory_assets'));
        add_action('wp_ajax_abs_update_stock', array($this, 'ajax_update_stock'));
        add_action('wp_ajax_abs_update_sku', array($this, 'ajax_update_sku'));
        add_action('wp_ajax_abs_update_price', array($this, 'ajax_update_price'));
        add_action('woocommerce_product_options_inventory_product_data', array($this, 'add_inventory_notice'));
    }

    public function add_inventory_menu() {
        // Add submenu under Products
        add_submenu_page(
            'edit.php?post_type=product',                        // Parent slug
            __('Inventory Manager', 'advanced-bundle-system'),  // Page title
            __('Inventory', 'advanced-bundle-system'),          // Menu title
            'manage_woocommerce',                                // Capability
            'abs-inventory',                                     // Menu slug
            array($this, 'render_inventory_page')               // Callback
        );
    }

    public function enqueue_inventory_assets($hook) {
        if ('product_page_abs-inventory' !== $hook) {
            return;
        }

        wp_enqueue_style('abs-inventory', ABS_PLUGIN_URL . 'assets/css/inventory.css', array(), ABS_VERSION);
        wp_enqueue_script('abs-inventory', ABS_PLUGIN_URL . 'assets/js/inventory.js', array('jquery'), ABS_VERSION, true);

        wp_localize_script('abs-inventory', 'absInventory', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('abs_inventory_nonce'),
            'strings' => array(
                'saving' => __('Saving...', 'advanced-bundle-system'),
                'saved' => __('Saved!', 'advanced-bundle-system'),
                'error' => __('Error saving', 'advanced-bundle-system'),
            ),
        ));
    }

    public function render_inventory_page() {
        ?>
        <div class="wrap abs-inventory-page">
            <h1><?php _e('Inventory Manager', 'advanced-bundle-system'); ?></h1>
            <p class="description">
                <?php _e('Manage all your product inventory in one place. Changes here update automatically across all products and bundles.', 'advanced-bundle-system'); ?>
            </p>

            <div class="abs-inventory-filters">
                <label>
                    <?php _e('Filter:', 'advanced-bundle-system'); ?>
                    <select id="abs-inventory-filter">
                        <option value="all"><?php _e('All Products', 'advanced-bundle-system'); ?></option>
                        <option value="low"><?php _e('Low Stock (≤ 5)', 'advanced-bundle-system'); ?></option>
                        <option value="out"><?php _e('Out of Stock', 'advanced-bundle-system'); ?></option>
                        <option value="variable"><?php _e('Variable Products Only', 'advanced-bundle-system'); ?></option>
                    </select>
                </label>

                <label>
                    <?php _e('Search:', 'advanced-bundle-system'); ?>
                    <input type="text" id="abs-inventory-search" placeholder="<?php _e('Search products...', 'advanced-bundle-system'); ?>">
                </label>
            </div>

            <table class="wp-list-table widefat fixed striped abs-inventory-table">
                <thead>
                    <tr>
                        <th class="abs-col-product"><?php _e('Product', 'advanced-bundle-system'); ?></th>
                        <th class="abs-col-variation"><?php _e('Variation', 'advanced-bundle-system'); ?></th>
                        <th class="abs-col-sku"><?php _e('SKU', 'advanced-bundle-system'); ?></th>
                        <th class="abs-col-price"><?php _e('Price', 'advanced-bundle-system'); ?></th>
                        <th class="abs-col-stock"><?php _e('Stock', 'advanced-bundle-system'); ?></th>
                        <th class="abs-col-used"><?php _e('Used In', 'advanced-bundle-system'); ?></th>
                    </tr>
                </thead>
                <tbody id="abs-inventory-tbody">
                    <?php $this->render_inventory_rows(); ?>
                </tbody>
            </table>

            <div class="abs-inventory-legend">
                <h3><?php _e('Legend:', 'advanced-bundle-system'); ?></h3>
                <ul>
                    <li><span class="abs-stock-status abs-in-stock">●</span> <?php _e('In Stock', 'advanced-bundle-system'); ?></li>
                    <li><span class="abs-stock-status abs-low-stock">●</span> <?php _e('Low Stock (≤ 5)', 'advanced-bundle-system'); ?></li>
                    <li><span class="abs-stock-status abs-out-of-stock">●</span> <?php _e('Out of Stock', 'advanced-bundle-system'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    private function render_inventory_rows() {
        // Pre-load all bundles once to avoid N+1 queries
        $this->preload_bundles_cache();

        // Use wc_get_products() instead of get_posts() for better performance
        $products = wc_get_products(array(
            'limit' => -1,
            'status' => 'publish',
            'return' => 'objects',
        ));

        // Separate products into categories
        $bundles = array();
        $products_in_bundles = array();
        $other_products = array();

        foreach ($products as $product) {
            if (!$product) continue;

            if ($product->is_type('bundle')) {
                $bundles[] = $product;
            } else {
                // Check if product is used in any bundle
                $used_in = $this->get_product_usage($product->get_id(), $product->get_id());
                if (!empty($used_in)) {
                    $products_in_bundles[] = $product;
                } else {
                    $other_products[] = $product;
                }
            }
        }

        // Sort products in bundles: variable products first, then simple
        usort($products_in_bundles, function($a, $b) {
            $a_is_variable = $a->is_type('variable') ? 0 : 1;
            $b_is_variable = $b->is_type('variable') ? 0 : 1;
            return $a_is_variable - $b_is_variable;
        });

        // 1. RENDER BUNDLES SECTION
        if (!empty($bundles)) {
            $this->render_section_header(__('Bundles', 'advanced-bundle-system'), 'bundles');
            foreach ($bundles as $product) {
                $this->render_product_row($product);
            }
        }

        // 2. RENDER PRODUCTS IN BUNDLES SECTION
        if (!empty($products_in_bundles)) {
            $this->render_section_header(__('Products Used in Bundles', 'advanced-bundle-system'), 'in-bundles');
            foreach ($products_in_bundles as $product) {
                if ($product->is_type('variable')) {
                    $this->render_parent_product_row($product);
                    $variation_ids = $product->get_children();
                    foreach ($variation_ids as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        if ($variation && $variation->is_type('variation')) {
                            $this->render_variation_row($product, $variation);
                        }
                    }
                } else {
                    $this->render_product_row($product);
                }
            }
        }

        // 3. RENDER OTHER PRODUCTS SECTION
        if (!empty($other_products)) {
            $this->render_section_header(__('Other Products', 'advanced-bundle-system'), 'other');
            foreach ($other_products as $product) {
                if ($product->is_type('variable')) {
                    $this->render_parent_product_row($product);
                    $variation_ids = $product->get_children();
                    foreach ($variation_ids as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        if ($variation && $variation->is_type('variation')) {
                            $this->render_variation_row($product, $variation);
                        }
                    }
                } else {
                    $this->render_product_row($product);
                }
            }
        }
    }

    /**
     * Render section header for inventory groups
     */
    private function render_section_header($title, $section_class) {
        ?>
        <tr class="abs-section-header abs-section-<?php echo esc_attr($section_class); ?>">
            <td colspan="6">
                <strong><?php echo esc_html($title); ?></strong>
            </td>
        </tr>
        <?php
    }

    /**
     * Pre-load all bundles to avoid N+1 queries
     */
    private function preload_bundles_cache() {
        if ($this->bundles_cache !== null) {
            return; // Already loaded
        }

        $this->bundles_cache = array();

        // Get all bundle products at once
        $bundles = wc_get_products(array(
            'type' => 'bundle',
            'limit' => -1,
            'return' => 'objects',
        ));

        foreach ($bundles as $bundle) {
            $bundle_items = get_post_meta($bundle->get_id(), '_bundle_items', true);
            if (is_array($bundle_items)) {
                foreach ($bundle_items as $item) {
                    if (isset($item['product_id'])) {
                        $product_id = (int)$item['product_id'];
                        if (!isset($this->bundles_cache[$product_id])) {
                            $this->bundles_cache[$product_id] = array();
                        }
                        $this->bundles_cache[$product_id][] = array(
                            'id' => $bundle->get_id(),
                            'title' => $bundle->get_name(),
                            'type' => 'Bundle',
                        );
                    }
                }
            }
        }
    }

    /**
     * Render parent product header row (for variable products)
     */
    private function render_parent_product_row($product) {
        $product_id = $product->get_id();
        $used_in = $this->get_product_usage($product_id, $product_id);
        ?>
        <tr class="abs-inventory-row abs-parent-row" data-product-id="<?php echo esc_attr($product_id); ?>">
            <td class="abs-col-product" colspan="2">
                <strong>📦 <?php echo esc_html($product->get_name()); ?></strong>
                <span class="abs-product-type">(<?php _e('Variable Product', 'advanced-bundle-system'); ?>)</span>
                <div class="row-actions">
                    <span><a href="<?php echo get_edit_post_link($product_id); ?>" target="_blank"><?php _e('Edit Product', 'advanced-bundle-system'); ?></a></span>
                </div>
            </td>
            <td class="abs-col-sku">—</td>
            <td class="abs-col-price">—</td>
            <td class="abs-col-stock">
                <em><?php _e('See variations below', 'advanced-bundle-system'); ?></em>
            </td>
            <td class="abs-col-used">
                <?php if (!empty($used_in)): ?>
                    <ul class="abs-used-in-list">
                        <?php foreach ($used_in as $usage): ?>
                            <li>
                                <a href="<?php echo get_edit_post_link($usage['id']); ?>" target="_blank">
                                    <?php echo esc_html($usage['title']); ?>
                                </a>
                                <span class="abs-usage-type">(<?php echo esc_html($usage['type']); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <em><?php _e('Not used in bundles', 'advanced-bundle-system'); ?></em>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Render variation row (indented under parent)
     */
    private function render_variation_row($product, $variation) {
        $item_id = $variation->get_id();

        $managing_stock = $variation->managing_stock();
        $stock_quantity = $managing_stock ? $variation->get_stock_quantity() : '';
        $stock_status = $variation->get_stock_status();

        $stock_class = 'abs-in-stock';
        if ($stock_status === 'outofstock' || ($managing_stock && $stock_quantity <= 0)) {
            $stock_class = 'abs-out-of-stock';
        } elseif ($managing_stock && $stock_quantity <= 5) {
            $stock_class = 'abs-low-stock';
        }

        // Get variation attributes directly from the variation object
        $attributes_display = array();
        $attributes_short = array();
        $variation_attributes = $variation->get_attributes();

        foreach ($variation_attributes as $attr_name => $attr_value) {
            $attr_label = wc_attribute_label($attr_name);
            $attributes_display[] = $attr_label . ': ' . ucfirst($attr_value);
            $attributes_short[] = ucfirst($attr_value);
        }

        $variation_name = $product->get_name() . ' - ' . implode(', ', $attributes_short);
        ?>
        <tr class="abs-inventory-row abs-variation-row <?php echo esc_attr($stock_class); ?>" data-product-id="<?php echo esc_attr($item_id); ?>">
            <td class="abs-col-product abs-variation-indent">
                ↳ <?php echo esc_html($variation_name); ?>
            </td>
            <td class="abs-col-variation">
                <?php echo esc_html(implode(', ', $attributes_display)); ?>
            </td>
            <td class="abs-col-sku">
                <input type="text" class="abs-sku-input" value="<?php echo esc_attr($variation->get_sku()); ?>" placeholder="<?php _e('No SKU', 'advanced-bundle-system'); ?>" data-original="<?php echo esc_attr($variation->get_sku()); ?>" />
                <button class="button abs-save-sku" style="display:none;"><?php _e('Save', 'advanced-bundle-system'); ?></button>
                <span class="abs-sku-feedback"></span>
            </td>
            <td class="abs-col-price">
                <input type="number" class="abs-price-input" value="<?php echo esc_attr($variation->get_regular_price()); ?>" min="0" step="0.01" placeholder="0.00" data-original="<?php echo esc_attr($variation->get_regular_price()); ?>" />
                <button class="button abs-save-price" style="display:none;"><?php _e('Save', 'advanced-bundle-system'); ?></button>
                <span class="abs-price-feedback"></span>
            </td>
            <td class="abs-col-stock">
                <span class="abs-stock-status <?php echo esc_attr($stock_class); ?>">●</span>
                <?php if ($managing_stock): ?>
                    <input type="number" class="abs-stock-input" value="<?php echo esc_attr($stock_quantity); ?>" min="0" step="1" data-original="<?php echo esc_attr($stock_quantity); ?>" />
                    <button class="button abs-save-stock" style="display:none;"><?php _e('Save', 'advanced-bundle-system'); ?></button>
                    <span class="abs-save-feedback"></span>
                <?php else: ?>
                    <em><?php _e('Not managed', 'advanced-bundle-system'); ?></em>
                <?php endif; ?>
            </td>
            <td class="abs-col-used">
                <?php
                // Get "Used In" from parent product (variations inherit from parent)
                $used_in = $this->get_product_usage($item_id, $product->get_id());
                if (!empty($used_in)):
                ?>
                    <ul class="abs-used-in-list">
                        <?php foreach ($used_in as $usage): ?>
                            <li>
                                <a href="<?php echo get_edit_post_link($usage['id']); ?>" target="_blank">
                                    <?php echo esc_html($usage['title']); ?>
                                </a>
                                <span class="abs-usage-type">(<?php echo esc_html($usage['type']); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <em><?php _e('Not used in bundles', 'advanced-bundle-system'); ?></em>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Render simple product row
     */
    private function render_product_row($product) {
        $product_id = $product->get_id();

        $managing_stock = $product->managing_stock();
        $stock_quantity = $managing_stock ? $product->get_stock_quantity() : '';
        $stock_status = $product->get_stock_status();

        $stock_class = 'abs-in-stock';
        if ($stock_status === 'outofstock' || ($managing_stock && $stock_quantity <= 0)) {
            $stock_class = 'abs-out-of-stock';
        } elseif ($managing_stock && $stock_quantity <= 5) {
            $stock_class = 'abs-low-stock';
        }

        $used_in = $this->get_product_usage($product_id, $product_id);

        // Get the appropriate price based on product type
        $is_bundle = $product->get_type() === 'bundle';
        if ($is_bundle) {
            $price = get_post_meta($product_id, '_bundle_price', true);
        } else {
            $price = $product->get_regular_price();
        }
        ?>
        <tr class="abs-inventory-row <?php echo esc_attr($stock_class); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" data-product-type="<?php echo esc_attr($product->get_type()); ?>">
            <td class="abs-col-product">
                <strong><?php echo esc_html($product->get_name()); ?></strong>
                <div class="row-actions">
                    <span><a href="<?php echo get_edit_post_link($product_id); ?>" target="_blank"><?php _e('Edit Product', 'advanced-bundle-system'); ?></a></span>
                </div>
            </td>
            <td class="abs-col-variation">—</td>
            <td class="abs-col-sku">
                <input type="text" class="abs-sku-input" value="<?php echo esc_attr($product->get_sku()); ?>" placeholder="<?php _e('No SKU', 'advanced-bundle-system'); ?>" data-original="<?php echo esc_attr($product->get_sku()); ?>" />
                <button class="button abs-save-sku" style="display:none;"><?php _e('Save', 'advanced-bundle-system'); ?></button>
                <span class="abs-sku-feedback"></span>
            </td>
            <td class="abs-col-price">
                <input type="number" class="abs-price-input" value="<?php echo esc_attr($price); ?>" min="0" step="0.01" placeholder="0.00" data-original="<?php echo esc_attr($price); ?>" />
                <button class="button abs-save-price" style="display:none;"><?php _e('Save', 'advanced-bundle-system'); ?></button>
                <span class="abs-price-feedback"></span>
            </td>
            <td class="abs-col-stock">
                <span class="abs-stock-status <?php echo esc_attr($stock_class); ?>">●</span>
                <?php if ($managing_stock): ?>
                    <input type="number" class="abs-stock-input" value="<?php echo esc_attr($stock_quantity); ?>" min="0" step="1" data-original="<?php echo esc_attr($stock_quantity); ?>" />
                    <button class="button abs-save-stock" style="display:none;"><?php _e('Save', 'advanced-bundle-system'); ?></button>
                    <span class="abs-save-feedback"></span>
                <?php else: ?>
                    <em><?php _e('Not managed', 'advanced-bundle-system'); ?></em>
                <?php endif; ?>
            </td>
            <td class="abs-col-used">
                <?php if (!empty($used_in)): ?>
                    <ul class="abs-used-in-list">
                        <?php foreach ($used_in as $usage): ?>
                            <li>
                                <a href="<?php echo get_edit_post_link($usage['id']); ?>" target="_blank">
                                    <?php echo esc_html($usage['title']); ?>
                                </a>
                                <span class="abs-usage-type">(<?php echo esc_html($usage['type']); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <em><?php _e('Not used in bundles', 'advanced-bundle-system'); ?></em>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Get product usage from cache (optimized - no queries)
     */
    private function get_product_usage($item_id, $product_id) {
        // Use the pre-loaded bundles cache
        if (isset($this->bundles_cache[$product_id])) {
            return $this->bundles_cache[$product_id];
        }
        return array();
    }

    public function ajax_update_stock() {
        check_ajax_referer('abs_inventory_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'advanced-bundle-system')));
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $stock_quantity = isset($_POST['stock_quantity']) ? absint($_POST['stock_quantity']) : 0;

        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'advanced-bundle-system')));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found', 'advanced-bundle-system')));
        }

        $product->set_stock_quantity($stock_quantity);
        $product->set_manage_stock(true);

        if ($stock_quantity > 0) {
            $product->set_stock_status('instock');
        } else {
            $product->set_stock_status('outofstock');
        }

        $product->save();

        wp_send_json_success(array(
            'message' => __('Stock updated successfully', 'advanced-bundle-system'),
            'stock_quantity' => $stock_quantity,
        ));
    }

    public function ajax_update_sku() {
        check_ajax_referer('abs_inventory_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'advanced-bundle-system')));
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $sku = isset($_POST['sku']) ? sanitize_text_field($_POST['sku']) : '';

        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'advanced-bundle-system')));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found', 'advanced-bundle-system')));
        }

        // Check if SKU is unique (if not empty)
        if (!empty($sku)) {
            $existing_id = wc_get_product_id_by_sku($sku);
            if ($existing_id && $existing_id !== $product_id) {
                wp_send_json_error(array('message' => __('SKU already exists on another product', 'advanced-bundle-system')));
            }
        }

        $product->set_sku($sku);
        $product->save();

        wp_send_json_success(array(
            'message' => __('SKU updated successfully', 'advanced-bundle-system'),
            'sku' => $sku,
        ));
    }

    public function ajax_update_price() {
        check_ajax_referer('abs_inventory_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'advanced-bundle-system')));
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $price = isset($_POST['price']) ? sanitize_text_field($_POST['price']) : '';
        $product_type = isset($_POST['product_type']) ? sanitize_text_field($_POST['product_type']) : '';

        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'advanced-bundle-system')));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found', 'advanced-bundle-system')));
        }

        // Validate price
        $price_float = floatval($price);
        if ($price_float < 0) {
            wp_send_json_error(array('message' => __('Price cannot be negative', 'advanced-bundle-system')));
        }

        // Update price based on product type
        if ($product_type === 'bundle') {
            // For bundles, update the bundle price
            update_post_meta($product_id, '_bundle_price', $price_float);
            update_post_meta($product_id, '_price', $price_float);
        } else {
            // For regular products and variations, update regular price
            $product->set_regular_price($price_float);
            $product->set_price($price_float);
            $product->save();
        }

        wp_send_json_success(array(
            'message' => __('Price updated successfully', 'advanced-bundle-system'),
            'price' => $price_float,
        ));
    }

    public function add_inventory_notice() {
        global $post;
        if (!$post) return;

        $product = wc_get_product($post->ID);
        if (!$product) return;
        ?>
        <div class="abs-inventory-notice" style="padding: 12px; background: #e3f2fd; border-left: 4px solid #2196f3; margin: 12px 0;">
            <p style="margin: 0;">
                <strong><?php _e('💡 Tip:', 'advanced-bundle-system'); ?></strong>
                <?php _e('For easier inventory management, use the', 'advanced-bundle-system'); ?>
                <a href="<?php echo admin_url('edit.php?post_type=product&page=abs-inventory'); ?>">
                    <?php _e('Centralized Inventory Manager', 'advanced-bundle-system'); ?>
                </a>
                <?php _e('to see all products, their variations, and where they\'re used in bundles.', 'advanced-bundle-system'); ?>
            </p>
        </div>
        <?php
    }
}

ABS_Inventory::get_instance();
