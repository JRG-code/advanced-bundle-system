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
     * Add product search script for Select2
     */
    public function add_product_search_script() {
        global $post;
        if (!$post || 'product' !== $post->post_type) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize Select2 for product search
            $('#abs_bundle_products').selectWoo({
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'abs_search_products',
                            term: params.term,
                            nonce: '<?php echo wp_create_nonce('abs-search-products'); ?>'
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: '<?php _e('Search for products', 'advanced-bundle-system'); ?>'
            });

            // Update pricing summary when products or bundle price changes
            function updatePricingSummary() {
                var productIds = $('#abs_bundle_products').val();
                var bundlePrice = $('#_bundle_price').val();

                if (!productIds || productIds.length === 0) {
                    $('#abs_original_total').text('-');
                    $('#abs_bundle_price_display').text('-');
                    $('#abs_discount_percent').text('-');
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'abs_calculate_bundle_pricing',
                        product_ids: productIds,
                        bundle_price: bundlePrice,
                        nonce: '<?php echo wp_create_nonce('abs-calculate-pricing'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#abs_original_total').text(response.data.original_total_formatted);
                            $('#abs_bundle_price_display').text(response.data.bundle_price_formatted);
                            $('#abs_discount_percent').text(response.data.discount_percent + '%');
                        }
                    }
                });
            }

            $('#abs_bundle_products, #_bundle_price').on('change', updatePricingSummary);

            // Initial calculation
            updatePricingSummary();
        });
        </script>
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

        $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();
        $bundle_price = isset($_POST['bundle_price']) ? floatval($_POST['bundle_price']) : 0;

        $original_total = ABS_Product_Type::get_bundle_products_total($product_ids);
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
