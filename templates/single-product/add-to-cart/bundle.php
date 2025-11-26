<?php
/**
 * Bundle product add to cart
 *
 * This template matches WooCommerce's simple.php structure
 */

if (!defined('ABSPATH')) {
    exit;
}

error_log('ABS DEBUG: Bundle template loaded!');

global $product;

// DEBUG: Show visible output
echo '<!-- ABS DEBUG: Bundle template is loading -->';
echo '<div style="background: #ffeb3b; padding: 10px; margin: 10px 0; border: 2px solid #f57c00;">';
echo '<strong>DEBUG:</strong> Bundle template loaded successfully!';
echo '<br>Product ID: ' . ($product ? $product->get_id() : 'No product');
echo '<br>Product Type: ' . ($product ? $product->get_type() : 'No product');
echo '<br>Is Purchasable: ' . ($product && $product->is_purchasable() ? 'Yes' : 'No');
echo '<br>In Stock: ' . ($product && $product->is_in_stock() ? 'Yes' : 'No');
echo '</div>';

if (!$product || !$product->is_purchasable()) {
    echo '<div style="background: #ff5252; color: white; padding: 10px;">DEBUG: Exiting - product not purchasable</div>';
    return;
}

echo wc_get_stock_html($product);

if ($product->is_in_stock()) : ?>

    <?php do_action('woocommerce_before_add_to_cart_form'); ?>

    <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
        <?php do_action('woocommerce_before_add_to_cart_button'); ?>

        <?php
        do_action('woocommerce_before_add_to_cart_quantity');

        woocommerce_quantity_input(
            array(
                'min_value'   => apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product),
                'max_value'   => apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product),
                'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
            )
        );

        do_action('woocommerce_after_add_to_cart_quantity');
        ?>

        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>

        <?php do_action('woocommerce_after_add_to_cart_button'); ?>
    </form>

    <?php do_action('woocommerce_after_add_to_cart_form'); ?>

<?php endif; ?>
