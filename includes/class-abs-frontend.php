<?php
/**
 * Frontend display functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Frontend {

    public function __construct() {
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_bundle_products'));
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_general_personalization'), 20);
        add_filter('woocommerce_get_price_html', array($this, 'display_bundle_pricing'), 10, 2);
        add_filter('woocommerce_get_price_html', array($this, 'add_personalization_to_price'), 15, 2);
    }

    /**
     * Display bundle products on product page
     */
    public function display_bundle_products() {
        global $product;

        if (!$product || 'bundle' !== $product->get_type()) {
            return;
        }

        $bundle_items = get_post_meta($product->get_id(), '_bundle_items', true);

        // Backward compatibility: convert old format to new format if needed
        if (empty($bundle_items) || !is_array($bundle_items)) {
            $old_bundle_products = get_post_meta($product->get_id(), '_bundle_products', true);

            if (empty($old_bundle_products) || !is_array($old_bundle_products)) {
                return; // No bundle data at all
            }

            // Convert old format to new format for display
            $bundle_items = array();
            $product_counts = array_count_values($old_bundle_products);

            foreach ($product_counts as $product_id => $quantity) {
                $bundle_items[] = array(
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'variation_id' => 0,
                    'ask_attributes' => 'no',
                    'enable_personalization' => 'no',
                    'personalization_label' => __('Enter text:', 'advanced-bundle-system'),
                    'max_characters' => 50,
                    'personalization_disclaimer' => ''
                );
            }
        }

        $bundle_heading = ABS_Settings::get_setting('bundle_heading', __('This bundle includes:', 'advanced-bundle-system'));

        echo '<div class="abs-bundle-products">';
        echo '<h3>' . esc_html($bundle_heading) . '</h3>';
        echo '<div class="abs-bundle-items">';

        $item_counter = 0; // Counter for unique IDs when same product appears multiple times

        foreach ($bundle_items as $index => $item) {
            $product_id = $item['product_id'];
            $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
            $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
            $ask_attributes = isset($item['ask_attributes']) && $item['ask_attributes'] === 'yes';
            $enable_personalization = isset($item['enable_personalization']) && $item['enable_personalization'] === 'yes';

            $bundled_product = wc_get_product($product_id);
            if (!$bundled_product) {
                continue;
            }

            // Get the product to display price from (variation if specified)
            $price_product = $bundled_product;
            if ($variation_id > 0) {
                $variation_product = wc_get_product($variation_id);
                if ($variation_product) {
                    $price_product = $variation_product;
                }
            }

            // For products with quantity > 1, create separate entries for attributes/personalization
            for ($q = 0; $q < $quantity; $q++) {
                $unique_id = $item_counter++;
                $display_name = $bundled_product->get_name();

                // Add number if quantity > 1
                if ($quantity > 1) {
                    $display_name .= ' #' . ($q + 1);
                }

                echo '<div class="abs-bundle-item" data-product-id="' . esc_attr($product_id) . '" data-item-index="' . esc_attr($unique_id) . '"';
                if ($variation_id > 0) {
                    echo ' data-variation-id="' . esc_attr($variation_id) . '"';
                }
                echo '>';
                echo '<div class="abs-bundle-item-image">';
                echo $bundled_product->get_image('thumbnail');
                echo '</div>';

                echo '<div class="abs-bundle-item-details">';
                echo '<h4>' . esc_html($display_name) . '</h4>';
                echo '<p class="price">' . $price_product->get_price_html() . '</p>';

                // Add attribute selectors if enabled for this item
                if ($ask_attributes) {
                    $this->display_attribute_fields($bundled_product, $product_id, $unique_id);
                }

                // Add personalization fields if enabled for this item
                if ($enable_personalization) {
                    $personalization_label = isset($item['personalization_label']) ? $item['personalization_label'] : __('Enter text:', 'advanced-bundle-system');
                    $personalization_cost = isset($item['personalization_cost']) ? floatval($item['personalization_cost']) : 0;
                    $max_characters = isset($item['max_characters']) ? intval($item['max_characters']) : 50;
                    $disclaimer_text = isset($item['personalization_disclaimer']) ? $item['personalization_disclaimer'] : '';

                    // Bundle personalization is always optional (with toggle)
                    $this->display_personalization_fields($product_id, $unique_id, $personalization_label, $max_characters, $disclaimer_text, $personalization_cost, 'yes');
                }

                echo '</div>';
                echo '</div>';
            }
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Display attribute fields for products with variations
     */
    private function display_attribute_fields($product, $product_id, $unique_id) {
        $attributes = $product->get_attributes();

        if (empty($attributes)) {
            return;
        }

        // Get default attributes for this product
        $default_attributes = array();
        if ($product->is_type('variable')) {
            $default_attributes = $product->get_default_attributes();
        }

        echo '<div class="abs-attribute-fields">';

        foreach ($attributes as $attribute_name => $attribute) {
            // Handle both taxonomy and custom attributes
            if ($attribute->is_taxonomy()) {
                $taxonomy = $attribute->get_taxonomy_object();
                $attribute_label = $taxonomy ? $taxonomy->attribute_label : $attribute_name;
                $terms = wc_get_product_terms($product_id, $attribute_name, array('fields' => 'all'));
            } else {
                $attribute_label = $attribute->get_name();
                $terms = $attribute->get_options();
            }

            if (empty($terms)) {
                continue;
            }

            // Get default value for this attribute (if set)
            $sanitized_attribute_name = sanitize_title($attribute_name);
            $default_value = isset($default_attributes[$sanitized_attribute_name]) ? $default_attributes[$sanitized_attribute_name] : '';

            echo '<div class="abs-attribute-field">';
            echo '<label for="abs_attribute_' . esc_attr($attribute_name) . '_' . esc_attr($unique_id) . '">';
            echo esc_html($attribute_label) . ':';
            echo '</label>';
            echo '<select name="abs_attributes[' . esc_attr($unique_id) . '][' . esc_attr($attribute_name) . ']" ';
            echo 'id="abs_attribute_' . esc_attr($attribute_name) . '_' . esc_attr($unique_id) . '" ';
            echo 'class="abs-attribute-select" required>';

            // Only show placeholder if no default is set
            if (empty($default_value)) {
                echo '<option value="">' . sprintf(__('Choose %s', 'advanced-bundle-system'), esc_html($attribute_label)) . '</option>';
            }

            if ($attribute->is_taxonomy() && is_array($terms)) {
                foreach ($terms as $term) {
                    $selected = ($default_value === $term->slug) ? ' selected' : '';
                    echo '<option value="' . esc_attr($term->slug) . '"' . $selected . '>' . esc_html($term->name) . '</option>';
                }
            } else {
                foreach ($terms as $term) {
                    $selected = ($default_value === $term) ? ' selected' : '';
                    echo '<option value="' . esc_attr($term) . '"' . $selected . '>' . esc_html($term) . '</option>';
                }
            }

            echo '</select>';
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Display personalization fields
     */
    private function display_personalization_fields($product_id, $unique_id, $label, $max_characters, $disclaimer_text = '', $cost = 0, $optional = 'yes') {
        $placeholder = sprintf(__('Enter text (max %d characters)', 'advanced-bundle-system'), $max_characters);

        // Use global setting if no custom disclaimer provided
        if (empty($disclaimer_text)) {
            $disclaimer_text = ABS_Settings::get_setting('personalization_disclaimer', __('This is an embroidered product - image is for visualization purposes', 'advanced-bundle-system'));
        }

        $is_optional = ($optional === 'yes');
        ?>
        <div class="abs-personalization-fields" data-personalization-id="<?php echo esc_attr($unique_id); ?>">
            <?php if ($is_optional): ?>
            <div class="abs-personalization-toggle-wrapper">
                <label class="abs-personalization-toggle-label">
                    <input type="checkbox"
                           id="abs_personalization_toggle_<?php echo $unique_id; ?>"
                           class="abs-personalization-toggle"
                           data-personalization-id="<?php echo esc_attr($unique_id); ?>" />
                    <span class="abs-toggle-text">
                        <?php _e('Add personalization', 'advanced-bundle-system'); ?>
                        <?php if ($cost > 0): ?>
                            <span class="abs-personalization-cost-label">
                                (<?php printf(__('+ %s', 'advanced-bundle-system'), wc_price($cost)); ?>)
                            </span>
                        <?php endif; ?>
                    </span>
                </label>
            </div>
            <?php endif; ?>

            <div class="abs-personalization-field" style="<?php echo $is_optional ? 'display: none;' : ''; ?>">
                <label for="abs_personalization_text_<?php echo $unique_id; ?>">
                    <?php echo esc_html($label); ?>
                    <?php if (!$is_optional && $cost > 0): ?>
                        <span class="abs-personalization-cost-label">
                            (<?php printf(__('+ %s', 'advanced-bundle-system'), wc_price($cost)); ?>)
                        </span>
                    <?php endif; ?>
                </label>
                <input type="text"
                       id="abs_personalization_text_<?php echo $unique_id; ?>"
                       name="abs_personalization[<?php echo $unique_id; ?>][text]"
                       data-product-id="<?php echo esc_attr($product_id); ?>"
                       data-personalization-cost="<?php echo esc_attr($cost); ?>"
                       class="abs-personalization-input"
                       maxlength="<?php echo esc_attr($max_characters); ?>"
                       placeholder="<?php echo esc_attr($placeholder); ?>"
                       <?php echo $is_optional ? 'disabled' : ''; ?> />
                <input type="hidden"
                       name="abs_personalization[<?php echo $unique_id; ?>][enabled]"
                       class="abs-personalization-enabled"
                       value="<?php echo $is_optional ? '0' : '1'; ?>" />
            </div>

            <?php if (!empty($disclaimer_text)): ?>
            <div class="abs-personalization-disclaimer" style="<?php echo $is_optional ? 'display: none;' : ''; ?>">
                <small><?php echo esc_html($disclaimer_text); ?></small>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Display personalization fields for non-bundle products (simple, variable, grouped, external)
     */
    public function display_general_personalization() {
        global $product;

        // Only display for non-bundle product types
        if (!$product || $product->get_type() === 'bundle') {
            return;
        }

        // Check if personalization is enabled for this product
        $enable_personalization = get_post_meta($product->get_id(), '_abs_enable_personalization', true);
        if ($enable_personalization !== 'yes') {
            return;
        }

        // Get personalization settings
        $personalization_label = get_post_meta($product->get_id(), '_abs_personalization_label', true);
        if (empty($personalization_label)) {
            $personalization_label = __('Enter text:', 'advanced-bundle-system');
        }

        $personalization_cost = floatval(get_post_meta($product->get_id(), '_abs_personalization_cost', true));
        $personalization_optional = get_post_meta($product->get_id(), '_abs_personalization_optional', true);

        $max_characters = get_post_meta($product->get_id(), '_abs_personalization_max_chars', true);
        if (empty($max_characters)) {
            $max_characters = 50;
        }

        $disclaimer_text = get_post_meta($product->get_id(), '_abs_personalization_disclaimer', true);

        // Display personalization heading
        $personalization_heading = ABS_Settings::get_setting('personalization_heading', __('Personalization Options:', 'advanced-bundle-system'));

        echo '<div class="abs-general-personalization">';
        echo '<h3>' . esc_html($personalization_heading) . '</h3>';

        // Use unique_id 0 for non-bundle products (they only have one personalization field)
        // Pass the optional flag to determine if toggle should be shown
        $this->display_personalization_fields($product->get_id(), 0, $personalization_label, $max_characters, $disclaimer_text, $personalization_cost, $personalization_optional);

        echo '</div>';
    }

    /**
     * Display bundle pricing with discount
     */
    public function display_bundle_pricing($price_html, $product) {
        if ('bundle' !== $product->get_type()) {
            return $price_html;
        }

        // Get bundle items (primary source)
        $bundle_items = get_post_meta($product->get_id(), '_bundle_items', true);

        // Fallback to old format for backward compatibility
        if (empty($bundle_items) || !is_array($bundle_items)) {
            $bundle_products = get_post_meta($product->get_id(), '_bundle_products', true);
            if (empty($bundle_products)) {
                return $price_html;
            }
            // Use old format
            $original_total = ABS_Product_Type::get_bundle_products_total($bundle_products);
        } else {
            // Calculate from bundle items (handles quantities correctly)
            $original_total = 0;
            foreach ($bundle_items as $item) {
                $product_id = $item['product_id'];
                $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
                $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;

                // Use variation price if specified, otherwise use base product price
                if ($variation_id > 0) {
                    $bundled_product = wc_get_product($variation_id);
                } else {
                    $bundled_product = wc_get_product($product_id);
                }

                if ($bundled_product) {
                    $original_total += $bundled_product->get_price() * $quantity;
                }
            }
        }

        $bundle_price = floatval(get_post_meta($product->get_id(), '_bundle_price', true));

        // If bundle price is not set, return default price html
        if ($bundle_price <= 0) {
            return $price_html;
        }

        $discount_percent = ABS_Product_Type::calculate_discount($original_total, $bundle_price);

        // Show pricing even if no discount (original price + bundle price)
        $pricing_html = '<div class="abs-bundle-pricing">';

        if ($original_total > $bundle_price) {
            // Show strikethrough original price if there's a discount
            $pricing_html .= '<p class="abs-original-price"><del>' . wc_price($original_total) . '</del></p>';
        }

        $pricing_html .= '<p class="abs-bundle-price"><ins>' . wc_price($bundle_price) . '</ins></p>';

        if ($discount_percent > 0) {
            $pricing_html .= '<p class="abs-discount-badge">' . sprintf(__('Save %s%%', 'advanced-bundle-system'), $discount_percent) . '</p>';
        }

        $pricing_html .= '</div>';

        return $pricing_html;
    }

    /**
     * Add personalization cost to price display for regular products
     */
    public function add_personalization_to_price($price_html, $product) {
        // Skip for bundle products (they have their own pricing display)
        if (!$product || $product->get_type() === 'bundle') {
            return $price_html;
        }

        // Check if personalization is enabled
        $enable_personalization = get_post_meta($product->get_id(), '_abs_enable_personalization', true);
        if ($enable_personalization !== 'yes') {
            return $price_html;
        }

        // If "paid by customer" is enabled, don't show cost in display price
        $paid_by_customer = get_post_meta($product->get_id(), '_abs_personalization_paid_by_customer', true);
        if ($paid_by_customer === 'yes') {
            return $price_html; // Cost will be added at checkout, not shown in price
        }

        // Check if cost should be shown in price
        $show_cost_in_price = get_post_meta($product->get_id(), '_abs_show_cost_in_price', true);
        if ($show_cost_in_price !== 'yes') {
            return $price_html;
        }

        $personalization_cost = floatval(get_post_meta($product->get_id(), '_abs_personalization_cost', true));
        $personalization_optional = get_post_meta($product->get_id(), '_abs_personalization_optional', true);

        if ($personalization_cost <= 0) {
            return $price_html;
        }

        // If personalization is optional (toggle), show "from" price
        if ($personalization_optional === 'yes') {
            // Show original price as starting point
            $price_html .= '<small class="abs-personalization-price-note" style="display: block; margin-top: 5px; font-size: 0.85em; color: #666;">';
            $price_html .= sprintf(__('+ %s for personalization', 'advanced-bundle-system'), wc_price($personalization_cost));
            $price_html .= '</small>';
        } else {
            // Personalization is always included, add cost to display price
            $original_price = $product->get_price();
            if ($original_price) {
                $price_with_personalization = floatval($original_price) + $personalization_cost;
                $price_html = '<span class="price">' . wc_price($price_with_personalization) . '</span>';
                $price_html .= '<small class="abs-personalization-included" style="display: block; margin-top: 5px; font-size: 0.85em; color: #666;">';
                $price_html .= __('(includes personalization)', 'advanced-bundle-system');
                $price_html .= '</small>';
            }
        }

        return $price_html;
    }
}

new ABS_Frontend();
