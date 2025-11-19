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
}

new ABS_Menu_Fix();
