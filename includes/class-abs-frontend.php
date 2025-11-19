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
        if (empty($bundle_items) || !is_array($bundle_items)) {
            return;
        }

        $bundle_heading = ABS_Settings::get_setting('bundle_heading', __('This bundle includes:', 'advanced-bundle-system'));

        echo '<div class="abs-bundle-products">';
        echo '<h3>' . esc_html($bundle_heading) . '</h3>';
        echo '<div class="abs-bundle-items">';

        $item_counter = 0; // Counter for unique IDs when same product appears multiple times

        foreach ($bundle_items as $index => $item) {
            $product_id = $item['product_id'];
            $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
            $ask_attributes = isset($item['ask_attributes']) && $item['ask_attributes'] === 'yes';
            $enable_personalization = isset($item['enable_personalization']) && $item['enable_personalization'] === 'yes';

            $bundled_product = wc_get_product($product_id);
            if (!$bundled_product) {
                continue;
            }

            // For products with quantity > 1, create separate entries for attributes/personalization
            for ($q = 0; $q < $quantity; $q++) {
                $unique_id = $item_counter++;
                $display_name = $bundled_product->get_name();

                // Add number if quantity > 1
                if ($quantity > 1) {
                    $display_name .= ' #' . ($q + 1);
                }

                echo '<div class="abs-bundle-item" data-product-id="' . esc_attr($product_id) . '" data-item-index="' . esc_attr($unique_id) . '">';
                echo '<div class="abs-bundle-item-image">';
                echo $bundled_product->get_image('thumbnail');
                echo '</div>';

                echo '<div class="abs-bundle-item-details">';
                echo '<h4>' . esc_html($display_name) . '</h4>';
                echo '<p class="price">' . $bundled_product->get_price_html() . '</p>';

                // Add attribute selectors if enabled for this item
                if ($ask_attributes) {
                    $this->display_attribute_fields($bundled_product, $product_id, $unique_id);
                }

                // Add personalization fields if enabled for this item
                if ($enable_personalization) {
                    $personalization_label = isset($item['personalization_label']) ? $item['personalization_label'] : __('Enter text:', 'advanced-bundle-system');
                    $max_characters = isset($item['max_characters']) ? intval($item['max_characters']) : 50;
                    $disclaimer_text = isset($item['personalization_disclaimer']) ? $item['personalization_disclaimer'] : '';

                    $this->display_personalization_fields($product_id, $unique_id, $personalization_label, $max_characters, $disclaimer_text);
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
    private function display_personalization_fields($product_id, $unique_id, $label, $max_characters, $disclaimer_text = '') {
        $placeholder = sprintf(__('Enter text (max %d characters)', 'advanced-bundle-system'), $max_characters);

        // Use global setting if no custom disclaimer provided
        if (empty($disclaimer_text)) {
            $disclaimer_text = ABS_Settings::get_setting('personalization_disclaimer', __('This is an embroidered product - image is for visualization purposes', 'advanced-bundle-system'));
        }
        ?>
        <div class="abs-personalization-fields">
            <div class="abs-personalization-field">
                <label for="abs_personalization_text_<?php echo $unique_id; ?>">
                    <?php echo esc_html($label); ?>
                </label>
                <input type="text"
                       id="abs_personalization_text_<?php echo $unique_id; ?>"
                       name="abs_personalization[<?php echo $unique_id; ?>][text]"
                       data-product-id="<?php echo esc_attr($product_id); ?>"
                       class="abs-personalization-input"
                       maxlength="<?php echo esc_attr($max_characters); ?>"
                       placeholder="<?php echo esc_attr($placeholder); ?>" />
            </div>

            <?php if (!empty($disclaimer_text)): ?>
            <div class="abs-personalization-disclaimer">
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
        $enable_personalization = get_post_meta($product->get_id(), '_enable_personalization', true);
        if ($enable_personalization !== 'yes') {
            return;
        }

        // Get personalization settings
        $personalization_label = get_post_meta($product->get_id(), '_personalization_label', true);
        if (empty($personalization_label)) {
            $personalization_label = __('Enter text:', 'advanced-bundle-system');
        }

        $max_characters = get_post_meta($product->get_id(), '_max_characters', true);
        if (empty($max_characters)) {
            $max_characters = 50;
        }

        $disclaimer_text = get_post_meta($product->get_id(), '_personalization_disclaimer', true);

        // Display personalization heading
        $personalization_heading = ABS_Settings::get_setting('personalization_heading', __('Personalization Options:', 'advanced-bundle-system'));

        echo '<div class="abs-general-personalization">';
        echo '<h3>' . esc_html($personalization_heading) . '</h3>';

        // Use unique_id 0 for non-bundle products (they only have one personalization field)
        $this->display_personalization_fields($product->get_id(), 0, $personalization_label, $max_characters, $disclaimer_text);

        echo '</div>';
    }

    /**
     * Display bundle pricing with discount
     */
    public function display_bundle_pricing($price_html, $product) {
        if ('bundle' !== $product->get_type()) {
            return $price_html;
        }

        $bundle_products = get_post_meta($product->get_id(), '_bundle_products', true);
        if (empty($bundle_products)) {
            return $price_html;
        }

        $original_total = ABS_Product_Type::get_bundle_products_total($bundle_products);
        $bundle_price = floatval(get_post_meta($product->get_id(), '_bundle_price', true));
        $discount_percent = ABS_Product_Type::calculate_discount($original_total, $bundle_price);

        if ($discount_percent <= 0) {
            return $price_html;
        }

        $pricing_html = '<div class="abs-bundle-pricing">';
        $pricing_html .= '<p class="abs-original-price"><del>' . wc_price($original_total) . '</del></p>';
        $pricing_html .= '<p class="abs-bundle-price"><ins>' . wc_price($bundle_price) . '</ins></p>';
        $pricing_html .= '<p class="abs-discount-badge">' . sprintf(__('Save %s%%', 'advanced-bundle-system'), $discount_percent) . '</p>';
        $pricing_html .= '</div>';

        return $pricing_html;
    }
}

new ABS_Frontend();
