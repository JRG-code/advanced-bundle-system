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

        $bundle_products = get_post_meta($product->get_id(), '_bundle_products', true);
        if (empty($bundle_products)) {
            return;
        }

        $enable_personalization = get_post_meta($product->get_id(), '_bundle_enable_personalization', true);

        echo '<div class="abs-bundle-products">';
        echo '<h3>' . __('This bundle includes:', 'advanced-bundle-system') . '</h3>';
        echo '<div class="abs-bundle-items">';

        foreach ($bundle_products as $product_id) {
            $bundled_product = wc_get_product($product_id);
            if (!$bundled_product) {
                continue;
            }

            echo '<div class="abs-bundle-item" data-product-id="' . esc_attr($product_id) . '">';
            echo '<div class="abs-bundle-item-image">';
            echo $bundled_product->get_image('thumbnail');
            echo '</div>';

            echo '<div class="abs-bundle-item-details">';
            echo '<h4>' . esc_html($bundled_product->get_name()) . '</h4>';
            echo '<p class="price">' . $bundled_product->get_price_html() . '</p>';

            // Add personalization fields if enabled
            if ($enable_personalization === 'yes') {
                $this->display_personalization_fields($product_id);
            }

            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Display personalization fields
     */
    private function display_personalization_fields($product_id) {
        ?>
        <div class="abs-personalization-fields">
            <div class="abs-personalization-field">
                <label for="abs_personalization_text_<?php echo $product_id; ?>">
                    <?php _e('Personalization Text:', 'advanced-bundle-system'); ?>
                </label>
                <input type="text"
                       id="abs_personalization_text_<?php echo $product_id; ?>"
                       name="abs_personalization[<?php echo $product_id; ?>][text]"
                       class="abs-personalization-input"
                       maxlength="50"
                       placeholder="<?php _e('Enter text (max 50 characters)', 'advanced-bundle-system'); ?>" />
            </div>

            <div class="abs-personalization-preview-trigger">
                <button type="button" class="abs-show-preview" data-product-id="<?php echo $product_id; ?>">
                    <?php _e('Preview Personalization', 'advanced-bundle-system'); ?>
                </button>
            </div>

            <div class="abs-personalization-disclaimer">
                <small><?php _e('This is an embroidered product - image is for visualization purposes', 'advanced-bundle-system'); ?></small>
            </div>
        </div>
        <?php
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
