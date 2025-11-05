<?php
/**
 * Plugin Name: Advanced Bundle System
 * Plugin URI: https://github.com/JRG-code/advanced-bundle-system
 * Description: Advanced bundle system for WooCommerce with personalization features including real-time preview overlay
 * Version: 1.0.0
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
define('ABS_VERSION', '1.0.0');
define('ABS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ABS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include the update checker
require ABS_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/JRG-code/advanced-bundle-system',
    __FILE__,
    'advanced-bundle-system'
);

// Set the branch that contains the stable release
$myUpdateChecker->setBranch('main');

// Optional: If your repository is private, specify the access token
// $myUpdateChecker->setAuthentication('your-token-here');

/**
 * Check if WooCommerce is active
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'abs_woocommerce_missing_notice');
    return;
}

function abs_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Advanced Bundle System requires WooCommerce to be installed and active.', 'advanced-bundle-system'); ?></p>
    </div>
    <?php
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
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-product-type.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-admin.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-frontend.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-cart.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-personalization.php';
        require_once ABS_PLUGIN_DIR . 'includes/class-abs-order.php';
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
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (is_product() || is_cart() || is_checkout()) {
            wp_enqueue_style('abs-frontend', ABS_PLUGIN_URL . 'assets/css/frontend.css', array(), ABS_VERSION);
            wp_enqueue_script('abs-frontend', ABS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), ABS_VERSION, true);

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
 * Initialize the plugin
 */
function abs_init() {
    return Advanced_Bundle_System::get_instance();
}

// Initialize plugin
add_action('plugins_loaded', 'abs_init');

/**
 * Activation hook
 */
function abs_activate() {
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
