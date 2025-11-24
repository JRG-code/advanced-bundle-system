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
        add_filter('woocommerce_product_class', array($this, 'register_bundle_product_class'), 10, 2);
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_bundle_fields_to_general_tab'));
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_personalization_fields_to_general_tab'));
        add_action('woocommerce_product_options_related', array($this, 'add_base_products_to_linked_products'));
        add_action('woocommerce_product_options_attributes', array($this, 'show_bundle_products_attributes'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_data'));
        add_action('woocommerce_process_product_meta', array($this, 'save_personalization_data'));

        // Prevent bundle products from managing their own stock
        add_filter('woocommerce_product_type_options', array($this, 'hide_stock_management_for_bundles'));
    }

    /**
     * Add bundle product type
     */
    public function add_product_type($types) {
        $types['bundle'] = __('Product Bundle', 'advanced-bundle-system');
        return $types;
    }

    /**
     * Register the WC_Product_Bundle class with WooCommerce
     */
    public function register_bundle_product_class($classname, $product_type) {
        if ($product_type === 'bundle') {
            $classname = 'WC_Product_Bundle';
        }
        return $classname;
    }

    /**
     * Add all bundle fields to the General tab
     */
    public function add_bundle_fields_to_general_tab() {
        global $post;

        $bundle_items = get_post_meta($post->ID, '_bundle_items', true);
        if (!is_array($bundle_items)) {
            $bundle_items = array();
        }
        ?>

        <!-- Bundle Price Field -->
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

        <!-- Bundle Items Configuration -->
        <div class="options_group show_if_bundle">
            <h4 style="padding: 0 12px;"><?php _e('Bundle Products', 'advanced-bundle-system'); ?></h4>
            <p style="padding: 0 12px; color: #666;"><?php _e('Add products to this bundle. You can add the same product multiple times with different quantities or attributes.', 'advanced-bundle-system'); ?></p>

                <table id="abs_bundle_items_table" class="wp-list-table widefat fixed striped" style="margin-top: 10px; margin-left: 12px; margin-right: 12px; width: calc(100% - 24px);">
                    <thead>
                        <tr>
                            <th style="width: 28%;"><?php _e('Product', 'advanced-bundle-system'); ?></th>
                            <th style="width: 22%;"><?php _e('Price/Variation', 'advanced-bundle-system'); ?></th>
                            <th style="width: 8%;"><?php _e('Qty', 'advanced-bundle-system'); ?></th>
                            <th style="width: 12%;"><?php _e('Attributes', 'advanced-bundle-system'); ?></th>
                            <th style="width: 12%;"><?php _e('Personalize', 'advanced-bundle-system'); ?></th>
                            <th style="width: 10%;"><?php _e('Cost', 'advanced-bundle-system'); ?></th>
                            <th style="width: 8%;"></th>
                        </tr>
                    </thead>
                    <tbody id="abs_bundle_items_tbody">
                        <?php
                        if (!empty($bundle_items)) {
                            foreach ($bundle_items as $index => $item) {
                                $product = wc_get_product($item['product_id']);
                                $this->render_bundle_item_row($index, $item, $product);
                            }
                        }
                        ?>
                    </tbody>
                </table>

                <p style="margin: 10px 12px;">
                    <button type="button" id="abs_add_bundle_item" class="button">
                        <?php _e('Add Product', 'advanced-bundle-system'); ?>
                    </button>
                </p>
        </div>

        <!-- Bundle Pricing Summary -->
        <div class="options_group show_if_bundle">
            <h4 style="padding: 0 12px;"><?php _e('Bundle Pricing Summary', 'advanced-bundle-system'); ?></h4>
            <div id="abs_pricing_summary" style="padding: 10px; background: #f8f8f8; margin: 10px 12px;">
                <p><strong><?php _e('Original Total:', 'advanced-bundle-system'); ?></strong> <span id="abs_original_total">-</span></p>
                <p><strong><?php _e('Bundle Price:', 'advanced-bundle-system'); ?></strong> <span id="abs_bundle_price_display">-</span></p>
                <p><strong><?php _e('Discount:', 'advanced-bundle-system'); ?></strong> <span id="abs_discount_percent">-</span></p>
            </div>
        </div>

        <!-- Hidden template row -->
        <script type="text/html" id="abs_bundle_item_row_template">
            <?php $this->render_bundle_item_row('{{INDEX}}', array(), null); ?>
        </script>
        <?php
    }

    /**
     * Render a bundle item row (2 lines per product)
     */
    private function render_bundle_item_row($index, $item = array(), $product = null) {
        $product_id = isset($item['product_id']) ? $item['product_id'] : '';
        $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
        $variation_id = isset($item['variation_id']) ? $item['variation_id'] : 0;
        $ask_attributes = isset($item['ask_attributes']) ? $item['ask_attributes'] : 'no';
        $enable_personalization = isset($item['enable_personalization']) ? $item['enable_personalization'] : 'no';
        $personalization_cost = isset($item['personalization_cost']) ? $item['personalization_cost'] : 0;
        $personalization_label = isset($item['personalization_label']) ? $item['personalization_label'] : __('Enter text:', 'advanced-bundle-system');
        $max_characters = isset($item['max_characters']) ? $item['max_characters'] : 50;
        $personalization_disclaimer = isset($item['personalization_disclaimer']) ? $item['personalization_disclaimer'] : '';
        ?>
        <!-- Row 1: Product, Price/Variation, Qty, Attributes, Personalize, Cost, Delete -->
        <tr class="abs-bundle-item-row abs-bundle-item-row-1" data-index="<?php echo esc_attr($index); ?>">
            <td>
                <select name="abs_bundle_items[<?php echo esc_attr($index); ?>][product_id]"
                        class="abs-product-search"
                        style="width: 100%;"
                        data-placeholder="<?php _e('Search for a product', 'advanced-bundle-system'); ?>">
                    <?php if ($product): ?>
                        <option value="<?php echo esc_attr($product_id); ?>" selected="selected">
                            <?php echo esc_html($product->get_name() . ' (#' . $product_id . ') - ' . wc_price($product->get_price())); ?>
                        </option>
                    <?php else: ?>
                        <option value=""><?php _e('Select a product', 'advanced-bundle-system'); ?></option>
                    <?php endif; ?>
                </select>
            </td>
            <td>
                <?php if ($product && $product->is_type('variable')): ?>
                    <select name="abs_bundle_items[<?php echo esc_attr($index); ?>][variation_id]"
                            class="abs-variation-select"
                            style="width: 100%; font-size: 12px;"
                            title="<?php esc_attr_e('Select which variation price to use for bundle calculation', 'advanced-bundle-system'); ?>">
                        <option value="0"><?php _e('Min price', 'advanced-bundle-system'); ?></option>
                        <?php
                        $variations = $product->get_available_variations();
                        foreach ($variations as $variation_data) {
                            $variation = wc_get_product($variation_data['variation_id']);
                            if ($variation) {
                                $attributes_text = wc_get_formatted_variation($variation, true);
                                $selected = ($variation_id == $variation_data['variation_id']) ? 'selected' : '';
                                ?>
                                <option value="<?php echo esc_attr($variation_data['variation_id']); ?>" <?php echo $selected; ?>>
                                    <?php echo esc_html($attributes_text . ' - ' . wc_price($variation->get_price())); ?>
                                </option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                    <span class="dashicons dashicons-info" style="color: #2271b1; cursor: help; font-size: 16px; vertical-align: middle;"
                          title="<?php esc_attr_e('For variable products: select which variation\'s price to use in bundle calculations. This price will be used unless customer changes variation.', 'advanced-bundle-system'); ?>"></span>
                <?php elseif ($product): ?>
                    <span style="font-size: 13px; color: #666;">
                        <?php echo wc_price($product->get_price()); ?>
                    </span>
                    <input type="hidden" name="abs_bundle_items[<?php echo esc_attr($index); ?>][variation_id]" value="0" />
                <?php else: ?>
                    <span style="font-size: 12px; color: #999;">-</span>
                    <input type="hidden" name="abs_bundle_items[<?php echo esc_attr($index); ?>][variation_id]" value="0" />
                <?php endif; ?>
            </td>
            <td style="text-align: center;">
                <input type="number"
                       name="abs_bundle_items[<?php echo esc_attr($index); ?>][quantity]"
                       value="<?php echo esc_attr($quantity); ?>"
                       min="1"
                       step="1"
                       class="abs-item-quantity"
                       style="width: 50px;" />
            </td>
            <td style="text-align: center;">
                <input type="checkbox"
                       name="abs_bundle_items[<?php echo esc_attr($index); ?>][ask_attributes]"
                       value="yes"
                       class="abs-ask-attributes"
                       title="<?php _e('Ask customer to select attributes (size, color, etc.) for each item', 'advanced-bundle-system'); ?>"
                       <?php checked($ask_attributes, 'yes'); ?> />
            </td>
            <td style="text-align: center;">
                <input type="checkbox"
                       name="abs_bundle_items[<?php echo esc_attr($index); ?>][enable_personalization]"
                       value="yes"
                       class="abs-enable-personalization"
                       <?php checked($enable_personalization, 'yes'); ?> />
            </td>
            <td style="text-align: center;">
                <input type="number"
                       name="abs_bundle_items[<?php echo esc_attr($index); ?>][personalization_cost]"
                       value="<?php echo esc_attr($personalization_cost); ?>"
                       min="0"
                       step="0.01"
                       placeholder="0"
                       title="<?php _e('Extra cost for personalization (e.g., 3 for 3€)', 'advanced-bundle-system'); ?>"
                       class="abs-personalization-cost"
                       style="width: 60px;" />
            </td>
            <td rowspan="2" style="text-align: center; vertical-align: middle;">
                <button type="button" class="button abs-remove-item" title="<?php _e('Remove', 'advanced-bundle-system'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </td>
        </tr>

        <!-- Row 2: Label Text, Max Chars, Disclaimer -->
        <tr class="abs-bundle-item-row abs-bundle-item-row-2" data-index="<?php echo esc_attr($index); ?>">
            <td colspan="3">
                <label><?php _e('Label Text:', 'advanced-bundle-system'); ?></label>
                <input type="text"
                       name="abs_bundle_items[<?php echo esc_attr($index); ?>][personalization_label]"
                       value="<?php echo esc_attr($personalization_label); ?>"
                       placeholder="<?php _e('e.g., Enter your initials:', 'advanced-bundle-system'); ?>"
                       class="abs-personalization-label" />
            </td>
            <td>
                <label><?php _e('Max Chars:', 'advanced-bundle-system'); ?></label>
                <input type="number"
                       name="abs_bundle_items[<?php echo esc_attr($index); ?>][max_characters]"
                       value="<?php echo esc_attr($max_characters); ?>"
                       min="1"
                       max="100"
                       step="1"
                       class="abs-max-characters" />
            </td>
            <td colspan="2">
                <label><?php _e('Disclaimer (optional):', 'advanced-bundle-system'); ?></label>
                <input type="text"
                       name="abs_bundle_items[<?php echo esc_attr($index); ?>][personalization_disclaimer]"
                       value="<?php echo esc_attr($personalization_disclaimer); ?>"
                       placeholder="<?php _e('e.g., 3rd image shows font style', 'advanced-bundle-system'); ?>"
                       class="abs-personalization-disclaimer" />
            </td>
        </tr>
        <?php
    }

    /**
     * Show bundle products' attributes in Attributes tab
     */
    public function show_bundle_products_attributes() {
        global $post;

        if (!$post) {
            return;
        }

        // Check using product type selector value
        $product_type = isset($_GET['post']) ? get_post_meta($_GET['post'], '_product_type', true) : '';

        // Also check if it's a new product being created as bundle
        if (isset($_GET['product_type']) && $_GET['product_type'] === 'bundle') {
            $product_type = 'bundle';
        }

        // If still not bundle, check the POST meta
        if ($product_type !== 'bundle') {
            $product_type = get_post_meta($post->ID, '_product_type', true);
        }

        if ($product_type !== 'bundle') {
            return;
        }

        $bundle_items = get_post_meta($post->ID, '_bundle_items', true);

        ?>
        <div class="options_group show_if_bundle" style="padding: 12px; border: 1px solid #ddd; background: #fff;">
            <h4 style="margin: 0 0 15px 0;"><?php _e('Bundle Products Attributes', 'advanced-bundle-system'); ?></h4>

            <?php if (empty($bundle_items) || !is_array($bundle_items)): ?>
                <p style="color: #666; font-style: italic;">
                    <?php _e('No products added to bundle yet. Add products in the General tab to see their attributes here.', 'advanced-bundle-system'); ?>
                </p>
            <?php else: ?>
                <p style="color: #666; margin-bottom: 15px;">
                    <?php _e('These are the attributes available in the products included in this bundle:', 'advanced-bundle-system'); ?>
                </p>

                <?php
                foreach ($bundle_items as $index => $item) {
                    $product = wc_get_product($item['product_id']);
                    if (!$product) {
                        continue;
                    }

                    echo '<div style="margin-bottom: 20px; padding: 12px; background: #f9f9f9; border-left: 3px solid #2271b1; border-radius: 3px;">';
                    echo '<h5 style="margin: 0 0 10px 0; color: #2271b1;">' . esc_html($product->get_name()) . ' (#' . esc_html($item['product_id']) . ')</h5>';

                    if ($product->is_type('variable')) {
                        $attributes = $product->get_attributes();

                        if (!empty($attributes)) {
                            foreach ($attributes as $attribute_name => $attribute) {
                                if ($attribute->is_taxonomy()) {
                                    $terms = wc_get_product_terms($item['product_id'], $attribute_name, array('fields' => 'names'));
                                    $attribute_label = wc_attribute_label($attribute_name);
                                } else {
                                    $terms = $attribute->get_options();
                                    $attribute_label = $attribute->get_name();
                                }

                                if (!empty($terms)) {
                                    echo '<div style="margin-bottom: 8px;">';
                                    echo '<strong style="color: #555;">' . esc_html($attribute_label) . ':</strong> ';
                                    echo '<span style="color: #666;">' . esc_html(is_array($terms) ? implode(', ', $terms) : $terms) . '</span>';
                                    echo '</div>';
                                }
                            }
                        } else {
                            echo '<p style="color: #999; font-style: italic; margin: 0;">' . __('No attributes configured for this product.', 'advanced-bundle-system') . '</p>';
                        }
                    } else {
                        echo '<p style="color: #999; font-style: italic; margin: 0;">' . __('This is a simple product (no variations).', 'advanced-bundle-system') . '</p>';
                    }

                    echo '</div>';
                }
                ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save bundle product data
     */
    public function save_product_data($post_id) {
        // Save bundle items
        if (isset($_POST['abs_bundle_items']) && is_array($_POST['abs_bundle_items'])) {
            $bundle_items = array();

            foreach ($_POST['abs_bundle_items'] as $item) {
                if (!empty($item['product_id'])) {
                    $bundle_items[] = array(
                        'product_id' => intval($item['product_id']),
                        'quantity' => isset($item['quantity']) ? max(1, intval($item['quantity'])) : 1,
                        'variation_id' => isset($item['variation_id']) ? intval($item['variation_id']) : 0,
                        'ask_attributes' => isset($item['ask_attributes']) ? 'yes' : 'no',
                        'enable_personalization' => isset($item['enable_personalization']) ? 'yes' : 'no',
                        'personalization_cost' => isset($item['personalization_cost']) ? max(0, floatval($item['personalization_cost'])) : 0,
                        'personalization_label' => isset($item['personalization_label']) ? sanitize_text_field($item['personalization_label']) : __('Enter text:', 'advanced-bundle-system'),
                        'max_characters' => isset($item['max_characters']) ? max(1, min(100, intval($item['max_characters']))) : 50,
                        'personalization_disclaimer' => isset($item['personalization_disclaimer']) ? sanitize_text_field($item['personalization_disclaimer']) : ''
                    );
                }
            }

            update_post_meta($post_id, '_bundle_items', $bundle_items);

            // Also save as old format for backward compatibility
            $product_ids = array();
            foreach ($bundle_items as $item) {
                for ($i = 0; $i < $item['quantity']; $i++) {
                    $product_ids[] = $item['product_id'];
                }
            }
            update_post_meta($post_id, '_bundle_products', $product_ids);
        } else {
            delete_post_meta($post_id, '_bundle_items');
            delete_post_meta($post_id, '_bundle_products');
        }

        if (isset($_POST['_bundle_price'])) {
            $bundle_price = floatval($_POST['_bundle_price']);
            update_post_meta($post_id, '_bundle_price', $bundle_price);
            update_post_meta($post_id, '_price', $bundle_price);
        }
    }

    /**
     * Add base products section to Linked Products tab
     */
    public function add_base_products_to_linked_products() {
        global $post;

        // Only show for bundle products
        $product = wc_get_product($post->ID);
        if (!$product || 'bundle' !== $product->get_type()) {
            return;
        }

        $bundle_items = get_post_meta($post->ID, '_bundle_items', true);
        if (empty($bundle_items) || !is_array($bundle_items)) {
            return;
        }

        ?>
        <div class="options_group show_if_bundle">
            <p class="form-field">
                <label><?php _e('Base Products', 'advanced-bundle-system'); ?></label>
                <span class="description" style="display: block; margin: 5px 0 10px 0;">
                    <?php _e('Products included in this bundle (automatically populated from bundle configuration)', 'advanced-bundle-system'); ?>
                </span>
                <div id="abs_base_products_list" style="padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <?php
                    // Group products by ID to show quantities
                    $product_quantities = array();
                    foreach ($bundle_items as $item) {
                        $product_id = $item['product_id'];
                        $quantity = isset($item['quantity']) ? $item['quantity'] : 1;

                        if (isset($product_quantities[$product_id])) {
                            $product_quantities[$product_id] += $quantity;
                        } else {
                            $product_quantities[$product_id] = $quantity;
                        }
                    }

                    if (!empty($product_quantities)) {
                        echo '<ul style="margin: 0; padding-left: 20px;">';
                        foreach ($product_quantities as $product_id => $total_quantity) {
                            $bundled_product = wc_get_product($product_id);
                            if ($bundled_product) {
                                $quantity_label = $total_quantity > 1 ? $total_quantity . 'x ' : '';
                                echo '<li style="margin: 5px 0;">';
                                echo '<strong>' . esc_html($quantity_label) . '</strong>';
                                echo '<a href="' . esc_url(get_edit_post_link($product_id)) . '" target="_blank">';
                                echo esc_html($bundled_product->get_name());
                                echo '</a>';
                                echo ' <span style="color: #999;">(#' . esc_html($product_id) . ')</span>';
                                echo '</li>';
                            }
                        }
                        echo '</ul>';
                    } else {
                        echo '<p style="margin: 0; color: #999; font-style: italic;">' . __('No products added to bundle yet.', 'advanced-bundle-system') . '</p>';
                    }
                    ?>
                </div>
            </p>
        </div>
        <?php
    }

    /**
     * Hide stock management options for bundle products
     */
    public function hide_stock_management_for_bundles($options) {
        // Add wrapper class to hide stock management for bundles
        $options['virtual']['wrapper_class'] = isset($options['virtual']['wrapper_class'])
            ? $options['virtual']['wrapper_class'] . ' hide_if_bundle'
            : 'hide_if_bundle';

        $options['downloadable']['wrapper_class'] = isset($options['downloadable']['wrapper_class'])
            ? $options['downloadable']['wrapper_class'] . ' hide_if_bundle'
            : 'hide_if_bundle';

        return $options;
    }

    /**
     * Add personalization fields to general tab for all product types
     */
    public function add_personalization_fields_to_general_tab() {
        global $post;

        // Don't show for bundle products (they have per-item personalization)
        ?>
        <div class="options_group hide_if_bundle">
            <h4 style="padding: 0 12px; margin: 12px 0 8px;"><?php _e('Product Personalization', 'advanced-bundle-system'); ?></h4>

            <?php
            woocommerce_wp_checkbox(array(
                'id' => '_abs_enable_personalization',
                'label' => __('Enable personalization', 'advanced-bundle-system'),
                'description' => __('Allow customers to add personalized text to this product', 'advanced-bundle-system'),
                'desc_tip' => true,
            ));

            woocommerce_wp_text_input(array(
                'id' => '_abs_personalization_cost',
                'label' => __('Personalization cost', 'advanced-bundle-system') . ' (' . get_woocommerce_currency_symbol() . ')',
                'desc_tip' => 'true',
                'description' => __('Extra cost for personalization (e.g., 3 for 3€). Leave 0 for free personalization.', 'advanced-bundle-system'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => '0.01',
                    'min' => '0'
                )
            ));

            woocommerce_wp_checkbox(array(
                'id' => '_abs_personalization_optional',
                'label' => __('Make personalization optional', 'advanced-bundle-system'),
                'description' => __('Show a toggle button so customers can opt-in/out. Cost will be added only when enabled.', 'advanced-bundle-system'),
                'desc_tip' => true,
            ));

            woocommerce_wp_checkbox(array(
                'id' => '_abs_show_cost_in_price',
                'label' => __('Show cost in display price', 'advanced-bundle-system'),
                'description' => __('Add personalization cost to product display price (or final price when toggle is used)', 'advanced-bundle-system'),
                'desc_tip' => true,
            ));

            woocommerce_wp_text_input(array(
                'id' => '_abs_personalization_label',
                'label' => __('Label text', 'advanced-bundle-system'),
                'desc_tip' => 'true',
                'description' => __('Text shown to customer (e.g., "Enter your initials:")', 'advanced-bundle-system'),
                'placeholder' => __('Enter your text:', 'advanced-bundle-system')
            ));

            woocommerce_wp_text_input(array(
                'id' => '_abs_personalization_max_chars',
                'label' => __('Max characters', 'advanced-bundle-system'),
                'desc_tip' => 'true',
                'description' => __('Maximum number of characters allowed', 'advanced-bundle-system'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min' => '1',
                    'max' => '100'
                ),
                'value' => get_post_meta($post->ID, '_abs_personalization_max_chars', true) ?: 50
            ));

            woocommerce_wp_textarea_input(array(
                'id' => '_abs_personalization_disclaimer',
                'label' => __('Disclaimer', 'advanced-bundle-system'),
                'desc_tip' => 'true',
                'description' => __('Optional disclaimer text (e.g., "3rd image shows font style")', 'advanced-bundle-system'),
                'placeholder' => __('Optional disclaimer...', 'advanced-bundle-system')
            ));
            ?>
        </div>
        <?php
    }

    /**
     * Save personalization data for regular products
     */
    public function save_personalization_data($post_id) {
        // Save personalization settings for non-bundle products
        $product = wc_get_product($post_id);
        if ($product && $product->get_type() !== 'bundle') {
            $enable_personalization = isset($_POST['_abs_enable_personalization']) ? 'yes' : 'no';
            update_post_meta($post_id, '_abs_enable_personalization', $enable_personalization);

            $personalization_optional = isset($_POST['_abs_personalization_optional']) ? 'yes' : 'no';
            update_post_meta($post_id, '_abs_personalization_optional', $personalization_optional);

            $show_cost_in_price = isset($_POST['_abs_show_cost_in_price']) ? 'yes' : 'no';
            update_post_meta($post_id, '_abs_show_cost_in_price', $show_cost_in_price);

            if (isset($_POST['_abs_personalization_cost'])) {
                update_post_meta($post_id, '_abs_personalization_cost', max(0, floatval($_POST['_abs_personalization_cost'])));
            }

            if (isset($_POST['_abs_personalization_label'])) {
                update_post_meta($post_id, '_abs_personalization_label', sanitize_text_field($_POST['_abs_personalization_label']));
            }

            if (isset($_POST['_abs_personalization_max_chars'])) {
                update_post_meta($post_id, '_abs_personalization_max_chars', max(1, min(100, intval($_POST['_abs_personalization_max_chars']))));
            }

            if (isset($_POST['_abs_personalization_disclaimer'])) {
                update_post_meta($post_id, '_abs_personalization_disclaimer', sanitize_textarea_field($_POST['_abs_personalization_disclaimer']));
            }
        }
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
