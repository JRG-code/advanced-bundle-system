<?php
/**
 * Bundle Product Type
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Product_Type {

    public function __construct() {
        add_filter('product_type_selector', array($this, 'add_product_type'));
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_product_data_panel'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_data'));
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_bundle_pricing_fields'));
    }

    /**
     * Add bundle product type
     */
    public function add_product_type($types) {
        $types['bundle'] = __('Product Bundle', 'advanced-bundle-system');
        return $types;
    }

    /**
     * Add bundle tab to product data
     */
    public function add_product_data_tab($tabs) {
        $tabs['bundle'] = array(
            'label' => __('Bundle Products', 'advanced-bundle-system'),
            'target' => 'bundle_product_data',
            'class' => array('show_if_bundle'),
            'priority' => 60,
        );
        return $tabs;
    }

    /**
     * Add bundle pricing fields
     */
    public function add_bundle_pricing_fields() {
        global $post;
        ?>
        <div class="options_group show_if_bundle">
            <?php
            woocommerce_wp_text_input(array(
                'id' => '_bundle_price',
                'label' => __('Bundle Price', 'advanced-bundle-system') . ' (' . get_woocommerce_currency_symbol() . ')',
                'desc_tip' => 'true',
                'description' => __('Set the custom bundle price. Discount will be calculated automatically.', 'advanced-bundle-system'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => '0.01',
                    'min' => '0'
                )
            ));
            ?>
        </div>
        <?php
    }

    /**
     * Add bundle product data panel
     */
    public function add_product_data_panel() {
        global $post;
        ?>
        <div id="bundle_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <p class="form-field">
                    <label><?php _e('Bundle Products', 'advanced-bundle-system'); ?></label>
                    <select id="abs_bundle_products" name="abs_bundle_products[]" multiple="multiple" style="width: 50%;" data-placeholder="<?php _e('Search for products', 'advanced-bundle-system'); ?>">
                        <?php
                        $bundle_products = get_post_meta($post->ID, '_bundle_products', true);
                        if (!empty($bundle_products)) {
                            foreach ($bundle_products as $product_id) {
                                $product = wc_get_product($product_id);
                                if ($product) {
                                    echo '<option value="' . esc_attr($product_id) . '" selected="selected">' . esc_html($product->get_name()) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                </p>
            </div>

            <div class="options_group">
                <p class="form-field">
                    <label>
                        <input type="checkbox" name="_bundle_enable_personalization" value="yes" <?php checked(get_post_meta($post->ID, '_bundle_enable_personalization', true), 'yes'); ?> />
                        <?php _e('Enable personalization for bundle products', 'advanced-bundle-system'); ?>
                    </label>
                </p>
            </div>

            <div class="options_group">
                <h4><?php _e('Bundle Pricing Summary', 'advanced-bundle-system'); ?></h4>
                <div id="abs_pricing_summary" style="padding: 10px; background: #f8f8f8; margin: 10px;">
                    <p><strong><?php _e('Original Total:', 'advanced-bundle-system'); ?></strong> <span id="abs_original_total">-</span></p>
                    <p><strong><?php _e('Bundle Price:', 'advanced-bundle-system'); ?></strong> <span id="abs_bundle_price_display">-</span></p>
                    <p><strong><?php _e('Discount:', 'advanced-bundle-system'); ?></strong> <span id="abs_discount_percent">-</span></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save bundle product data
     */
    public function save_product_data($post_id) {
        if (isset($_POST['abs_bundle_products'])) {
            $bundle_products = array_map('intval', $_POST['abs_bundle_products']);
            update_post_meta($post_id, '_bundle_products', $bundle_products);
        } else {
            delete_post_meta($post_id, '_bundle_products');
        }

        if (isset($_POST['_bundle_price'])) {
            update_post_meta($post_id, '_bundle_price', sanitize_text_field($_POST['_bundle_price']));
            update_post_meta($post_id, '_price', sanitize_text_field($_POST['_bundle_price']));
        }

        $enable_personalization = isset($_POST['_bundle_enable_personalization']) ? 'yes' : 'no';
        update_post_meta($post_id, '_bundle_enable_personalization', $enable_personalization);
    }

    /**
     * Calculate bundle discount percentage
     */
    public static function calculate_discount($original_price, $bundle_price) {
        if ($original_price <= 0) {
            return 0;
        }
        $discount = (($original_price - $bundle_price) / $original_price) * 100;
        return round($discount, 2);
    }

    /**
     * Get bundle products total price
     */
    public static function get_bundle_products_total($product_ids) {
        $total = 0;
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $total += $product->get_price();
            }
        }
        return $total;
    }
}

new ABS_Product_Type();
