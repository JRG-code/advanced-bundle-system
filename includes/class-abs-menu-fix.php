<?php
/**
 * Menu Fix for WooCommerce Product Pages
 *
 * Optional fix for navigation menus disappearing on product pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Menu_Fix {

    public function __construct() {
        // Only initialize if menu fix is enabled
        $settings = get_option('abs_settings', array());
        $enabled = isset($settings['enable_menu_fix']) && $settings['enable_menu_fix'] === 'yes';

        if (!$enabled) {
            return;
        }

        // Enqueue menu fix CSS and JS
        add_action('wp_enqueue_scripts', array($this, 'enqueue_menu_fix_assets'));
        add_action('wp_head', array($this, 'output_custom_menu_styles'), 100);
    }

    /**
     * Enqueue menu fix assets
     */
    public function enqueue_menu_fix_assets() {
        // Only load on WooCommerce product pages
        if (!is_product()) {
            return;
        }

        // Enqueue menu fix CSS
        wp_enqueue_style(
            'abs-menu-fix',
            ABS_PLUGIN_URL . 'assets/css/menu-fix.css',
            array(),
            ABS_VERSION
        );
    }

    /**
     * Output custom menu styles
     */
    public function output_custom_menu_styles() {
        // Only load on WooCommerce product pages
        if (!is_product()) {
            return;
        }

        // Get the custom background color
        $settings = get_option('abs_settings', array());
        $bg_color = isset($settings['menu_fix_bg_color']) ? $settings['menu_fix_bg_color'] : '#ffffff';

        // Sanitize color
        $bg_color = sanitize_hex_color($bg_color);

        if (!$bg_color) {
            $bg_color = '#ffffff';
        }

        // Output custom CSS
        ?>
        <style type="text/css" id="abs-menu-fix-custom">
            /* Custom menu background color */
            .site-header,
            header.site-header,
            #masthead,
            .main-header,
            #site-header,
            header,
            .primary-navigation,
            .main-navigation,
            #site-navigation,
            .site-navigation,
            nav.site-navigation {
                background-color: <?php echo esc_attr($bg_color); ?> !important;
            }
        </style>
        <?php
    }
}

new ABS_Menu_Fix();
