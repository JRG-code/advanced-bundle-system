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
        <?php
    }

    /**
     * AJAX search products
     */
    public function ajax_search_products() {
        check_ajax_referer('abs-search-products', 'nonce');

        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 20,
            's' => $term,
            'post_status' => 'publish'
        );

        $products = get_posts($args);
        $results = array();

        foreach ($products as $product) {
            $product_obj = wc_get_product($product->ID);
            if ($product_obj && $product_obj->get_type() !== 'bundle') {
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
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);

            $product = wc_get_product($product_id);
            if ($product) {
                $original_total += $product->get_price() * $quantity;
            }
        }

        $discount_percent = ABS_Product_Type::calculate_discount($original_total, $bundle_price);

        wp_send_json_success(array(
            'original_total' => $original_total,
            'original_total_formatted' => wc_price($original_total),
            'bundle_price' => $bundle_price,
            'bundle_price_formatted' => wc_price($bundle_price),
            'discount_percent' => $discount_percent
        ));
    }
}

new ABS_Admin();
