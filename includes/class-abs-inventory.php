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
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );

        $products = get_posts($args);

        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if (!$product) continue;

            if ($product->is_type('variable')) {
                // Render parent product header row
                $this->render_parent_product_row($product);

                // Render variation rows (indented)
                $variations = $product->get_available_variations();
                foreach ($variations as $variation_data) {
                    $variation = wc_get_product($variation_data['variation_id']);
                    if ($variation) {
                        $this->render_variation_row($product, $variation, $variation_data);
                    }
                }
            } else {
                $this->render_product_row($product);
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
    private function render_variation_row($product, $variation, $variation_data) {
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

        $variation_name = '';
        if ($variation_data) {
            $attributes = array();
            foreach ($variation_data['attributes'] as $attr_name => $attr_value) {
                $attr_label = wc_attribute_label(str_replace('attribute_', '', $attr_name));
                $attributes[] = ucfirst($attr_value);
            }
            $variation_name = $product->get_name() . ' - ' . implode(', ', $attributes);
        }
        ?>
        <tr class="abs-inventory-row abs-variation-row <?php echo esc_attr($stock_class); ?>" data-product-id="<?php echo esc_attr($item_id); ?>">
            <td class="abs-col-product abs-variation-indent">
                ↳ <?php echo esc_html($variation_name); ?>
            </td>
            <td class="abs-col-variation">
                <?php
                $attrs = array();
                foreach ($variation_data['attributes'] as $attr_name => $attr_value) {
                    $attr_label = wc_attribute_label(str_replace('attribute_', '', $attr_name));
                    $attrs[] = $attr_label . ': ' . ucfirst($attr_value);
                }
                echo esc_html(implode(', ', $attrs));
                ?>
            </td>
            <td class="abs-col-sku">
                <input type="text" class="abs-sku-input" value="<?php echo esc_attr($variation->get_sku()); ?>" placeholder="<?php _e('No SKU', 'advanced-bundle-system'); ?>" data-original="<?php echo esc_attr($variation->get_sku()); ?>" />
                <button class="button abs-save-sku" style="display:none;"><?php _e('Save', 'advanced-bundle-system'); ?></button>
                <span class="abs-sku-feedback"></span>
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
            <td class="abs-col-used">—</td>
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
        ?>
        <tr class="abs-inventory-row <?php echo esc_attr($stock_class); ?>" data-product-id="<?php echo esc_attr($product_id); ?>">
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

    private function get_product_usage($item_id, $product_id) {
        $used_in = array();
        $bundle_args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_product_type',
                    'value' => 'bundle',
                ),
            ),
        );

        $bundles = get_posts($bundle_args);
        foreach ($bundles as $bundle_post) {
            $bundle_items = get_post_meta($bundle_post->ID, '_bundle_items', true);
            if (is_array($bundle_items)) {
                foreach ($bundle_items as $item) {
                    if (isset($item['product_id']) && (int)$item['product_id'] === (int)$product_id) {
                        $used_in[] = array(
                            'id' => $bundle_post->ID,
                            'title' => get_the_title($bundle_post->ID),
                            'type' => 'Bundle',
                        );
                        break;
                    }
                }
            }
        }
        return $used_in;
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
