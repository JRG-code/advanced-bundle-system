<?php
/**
 * Plugin Name: Advanced Bundle System
 * Plugin URI: https://github.com/JRG-code/advanced-bundle-system
 * Description: Advanced bundle system for WooCommerce with personalization features including real-time preview overlay
 * Version: 1.5.5
 * Author: JRG Code
 * Author URI: https://github.com/JRG-code
 * Text Domain: advanced-bundle-system
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ABS_VERSION', '1.5.5');
define('ABS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ABS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
function abs_is_woocommerce_active() {
    return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ||
           (is_multisite() && array_key_exists('woocommerce/woocommerce.php', get_site_option('active_sitewide_plugins', array())));
}

/**
 * Display admin notice if WooCommerce is not active
 */
function abs_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Advanced Bundle System requires WooCommerce to be installed and active.', 'advanced-bundle-system'); ?></p>
    </div>
    <?php
}

/**
 * Initialize the plugin update checker
 */
function abs_init_update_checker() {
    // Load Parsedown library first (required by plugin-update-checker for parsing release notes)
    $parsedown_file = ABS_PLUGIN_DIR . 'plugin-update-checker/Parsedown.php';
    if (file_exists($parsedown_file) && !class_exists('Parsedown')) {
        require_once $parsedown_file;
    }

    // Only load update checker if the library exists
    $update_checker_file = ABS_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';

    if (file_exists($update_checker_file)) {
        try {
            require $update_checker_file;

            if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
                $myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                    'https://github.com/JRG-code/advanced-bundle-system',
                    __FILE__,
                    'advanced-bundle-system'
                );

                // Set the branch that contains the stable release
                $myUpdateChecker->setBranch('main');
            }
        } catch (Exception $e) {
            // Silently fail if update checker has issues
            error_log('Advanced Bundle System: Update checker error - ' . $e->getMessage());
        }
    }
}

/**
 * Main plugin class
 */
class Advanced_Bundle_System {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Check if WooCommerce is active
        if (!abs_is_woocommerce_active()) {
            add_action('admin_notices', 'abs_woocommerce_missing_notice');
            return;
        }

        // Initialize update checker
        add_action('init', 'abs_init_update_checker', 0);

        // Load plugin files
        $this->includes();

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        $includes = array(
            'includes/class-wc-product-bundle.php',
            'includes/class-abs-product-type.php',
            'includes/class-abs-settings.php',
            'includes/class-abs-admin.php',
            'includes/class-abs-frontend.php',
            'includes/class-abs-cart.php',
            'includes/class-abs-stock.php',
            'includes/class-abs-personalization.php',
            'includes/class-abs-order.php',
            'includes/class-abs-inventory.php',
            'includes/class-abs-cartflows-compat.php',
            'includes/class-abs-menu-fix.php',
            'includes/class-abs-promo-banner.php'
        );

        foreach ($includes as $file) {
            $filepath = ABS_PLUGIN_DIR . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            } else {
                error_log('Advanced Bundle System: Missing file - ' . $file);
            }
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('advanced-bundle-system', false, dirname(ABS_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Enqueue frontend assets (optimized - only on relevant pages)
     */
    public function enqueue_frontend_assets() {
        if (!function_exists('is_product') || !function_exists('is_cart') || !function_exists('is_checkout')) {
            return;
        }

        // Only load on product, cart, and checkout pages
        if (is_product() || is_cart() || is_checkout()) {
            wp_enqueue_style('abs-frontend', ABS_PLUGIN_URL . 'assets/css/frontend.css', array(), ABS_VERSION);

            // Register script with defer attribute for better performance
            wp_register_script('abs-frontend', ABS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), ABS_VERSION, true);
            wp_script_add_data('abs-frontend', 'defer', true);
            wp_enqueue_script('abs-frontend');

            wp_localize_script('abs-frontend', 'absData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('abs-nonce'),
                'disclaimerText' => __('This is an embroidered product - image is for visualization purposes', 'advanced-bundle-system')
            ));
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            global $post;
            if ($post && 'product' === $post->post_type) {
                wp_enqueue_style('abs-admin', ABS_PLUGIN_URL . 'assets/css/admin.css', array(), ABS_VERSION);
                wp_enqueue_script('abs-admin', ABS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ABS_VERSION, true);
            }
        }
    }
}

/**
 * Initialize the plugin after plugins are loaded
 */
function abs_init() {
    // Make sure WooCommerce functions are available
    if (!abs_is_woocommerce_active()) {
        return;
    }

    return Advanced_Bundle_System::get_instance();
}

// Initialize plugin on plugins_loaded with priority 20 (after WooCommerce which uses 10)
add_action('plugins_loaded', 'abs_init', 20);

/**
 * Activation hook
 */
function abs_activate() {
    if (!abs_is_woocommerce_active()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Advanced Bundle System requires WooCommerce to be installed and active.', 'advanced-bundle-system'));
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'abs_activate');

/**
 * Deactivation hook
 */
function abs_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'abs_deactivate');

/**
 * Declare HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
