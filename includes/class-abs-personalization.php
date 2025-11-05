<?php
/**
 * Personalization functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Personalization {

    public function __construct() {
        add_action('woocommerce_before_add_to_cart_button', array($this, 'add_personalization_to_simple_products'));
        add_action('wp_ajax_abs_generate_preview', array($this, 'ajax_generate_preview'));
        add_action('wp_ajax_nopriv_abs_generate_preview', array($this, 'ajax_generate_preview'));
    }

    /**
     * Add personalization to simple products
     */
    public function add_personalization_to_simple_products() {
        global $product;

        if (!$product || 'simple' !== $product->get_type()) {
            return;
        }

        $enable_personalization = get_post_meta($product->get_id(), '_enable_personalization', true);
        if ($enable_personalization !== 'yes') {
            return;
        }

        ?>
        <div class="abs-single-product-personalization">
            <h3><?php _e('Personalize This Product', 'advanced-bundle-system'); ?></h3>

            <div class="abs-personalization-fields">
                <div class="abs-personalization-field">
                    <label for="abs_personalization_text_single">
                        <?php _e('Personalization Text:', 'advanced-bundle-system'); ?>
                    </label>
                    <input type="text"
                           id="abs_personalization_text_single"
                           name="abs_personalization[<?php echo $product->get_id(); ?>][text]"
                           class="abs-personalization-input"
                           maxlength="50"
                           placeholder="<?php _e('Enter text (max 50 characters)', 'advanced-bundle-system'); ?>" />
                </div>

                <div class="abs-personalization-preview-trigger">
                    <button type="button" class="abs-show-preview" data-product-id="<?php echo $product->get_id(); ?>">
                        <?php _e('Preview Personalization', 'advanced-bundle-system'); ?>
                    </button>
                </div>

                <div class="abs-personalization-disclaimer">
                    <small><?php _e('This is an embroidered product - image is for visualization purposes', 'advanced-bundle-system'); ?></small>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX generate preview
     */
    public function ajax_generate_preview() {
        check_ajax_referer('abs-nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $text = isset($_POST['text']) ? sanitize_text_field($_POST['text']) : '';

        if (!$product_id || empty($text)) {
            wp_send_json_error(array('message' => __('Invalid data', 'advanced-bundle-system')));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found', 'advanced-bundle-system')));
        }

        // Get product image
        $image_id = $product->get_image_id();
        $image_url = wp_get_attachment_image_url($image_id, 'full');

        if (!$image_url) {
            $image_url = wc_placeholder_img_src('full');
        }

        wp_send_json_success(array(
            'image_url' => $image_url,
            'text' => $text,
            'product_name' => $product->get_name()
        ));
    }

    /**
     * Add personalization option to product settings
     */
    public static function add_product_option() {
        add_action('woocommerce_product_options_general_product_data', function() {
            woocommerce_wp_checkbox(array(
                'id' => '_enable_personalization',
                'label' => __('Enable Personalization', 'advanced-bundle-system'),
                'description' => __('Allow customers to add personalized text to this product', 'advanced-bundle-system')
            ));
        });

        add_action('woocommerce_process_product_meta', function($post_id) {
            $enable_personalization = isset($_POST['_enable_personalization']) ? 'yes' : 'no';
            update_post_meta($post_id, '_enable_personalization', $enable_personalization);
        });
    }
}

// Initialize personalization
$abs_personalization = new ABS_Personalization();
ABS_Personalization::add_product_option();
