<?php
/**
 * Personalization functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Personalization {

    public function __construct() {
        add_action('wp_ajax_abs_generate_preview', array($this, 'ajax_generate_preview'));
        add_action('wp_ajax_nopriv_abs_generate_preview', array($this, 'ajax_generate_preview'));
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

}

// Initialize personalization
new ABS_Personalization();
