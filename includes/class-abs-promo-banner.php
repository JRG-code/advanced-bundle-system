<?php
/**
 * Promotional Banner Display
 *
 * Displays promotional banner above header
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Promo_Banner {

    public function __construct() {
        // Only initialize if promo banner is enabled
        $settings = get_option('abs_settings', array());
        $enabled = isset($settings['enable_promo_banner']) && $settings['enable_promo_banner'] === 'yes';

        if (!$enabled) {
            return;
        }

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_banner_assets'));

        // Display banner
        add_action('wp_body_open', array($this, 'display_banner'), 5);
    }

    /**
     * Check if banner should display on current page
     */
    private function should_display_banner() {
        $settings = get_option('abs_settings', array());
        $pages = isset($settings['promo_banner_pages']) ? $settings['promo_banner_pages'] : array('home', 'shop', 'product');

        if (!is_array($pages)) {
            $pages = array('home', 'shop', 'product');
        }

        // Check each page type
        if (in_array('home', $pages) && is_front_page()) {
            return true;
        }

        if (in_array('shop', $pages) && (is_shop() || is_product_category() || is_product_tag())) {
            return true;
        }

        if (in_array('product', $pages) && is_product()) {
            return true;
        }

        if (in_array('cart', $pages) && is_cart()) {
            return true;
        }

        if (in_array('checkout', $pages) && is_checkout()) {
            return true;
        }

        return false;
    }

    /**
     * Enqueue banner assets
     */
    public function enqueue_banner_assets() {
        if (!$this->should_display_banner()) {
            return;
        }

        wp_enqueue_style(
            'abs-promo-banner',
            ABS_PLUGIN_URL . 'assets/css/promo-banner.css',
            array(),
            ABS_VERSION
        );

        wp_enqueue_script(
            'abs-promo-banner',
            ABS_PLUGIN_URL . 'assets/js/promo-banner.js',
            array('jquery'),
            ABS_VERSION,
            true
        );

        // Pass settings to JavaScript
        $settings = get_option('abs_settings', array());
        wp_localize_script('abs-promo-banner', 'absPromoBanner', array(
            'dismissible' => isset($settings['promo_banner_dismissible']) && $settings['promo_banner_dismissible'] === 'yes',
        ));
    }

    /**
     * Display banner
     */
    public function display_banner() {
        if (!$this->should_display_banner()) {
            return;
        }

        // Check if user dismissed banner
        if (isset($_COOKIE['abs_promo_banner_dismissed']) && $_COOKIE['abs_promo_banner_dismissed'] === '1') {
            return;
        }

        $settings = get_option('abs_settings', array());

        $text = isset($settings['promo_banner_text']) ? $settings['promo_banner_text'] : '🎁 Free Shipping on orders over €50';
        $link = isset($settings['promo_banner_link']) ? $settings['promo_banner_link'] : '';
        $bg_color = isset($settings['promo_banner_bg_color']) ? $settings['promo_banner_bg_color'] : '#000000';
        $text_color = isset($settings['promo_banner_text_color']) ? $settings['promo_banner_text_color'] : '#ffffff';
        $dismissible = isset($settings['promo_banner_dismissible']) && $settings['promo_banner_dismissible'] === 'yes';
        $sticky = isset($settings['promo_banner_sticky']) && $settings['promo_banner_sticky'] === 'yes';
        $hide_mobile = isset($settings['promo_banner_hide_mobile']) && $settings['promo_banner_hide_mobile'] === 'yes';

        // Sanitize
        $bg_color = sanitize_hex_color($bg_color) ?: '#000000';
        $text_color = sanitize_hex_color($text_color) ?: '#ffffff';

        $classes = array('abs-promo-banner');
        if ($sticky) {
            $classes[] = 'abs-promo-banner-sticky';
        }
        if ($hide_mobile) {
            $classes[] = 'abs-promo-banner-hide-mobile';
        }

        $style = sprintf(
            'background-color: %s; color: %s;',
            esc_attr($bg_color),
            esc_attr($text_color)
        );

        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo $style; ?>" id="abs-promo-banner">
            <div class="abs-promo-banner-inner">
                <?php if (!empty($link)) : ?>
                    <a href="<?php echo esc_url($link); ?>" class="abs-promo-banner-link">
                        <?php echo wp_kses_post($text); ?>
                    </a>
                <?php else : ?>
                    <span class="abs-promo-banner-text"><?php echo wp_kses_post($text); ?></span>
                <?php endif; ?>

                <?php if ($dismissible) : ?>
                    <button type="button" class="abs-promo-banner-close" aria-label="<?php esc_attr_e('Close banner', 'advanced-bundle-system'); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

new ABS_Promo_Banner();
