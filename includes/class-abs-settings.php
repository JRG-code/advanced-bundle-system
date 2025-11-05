<?php
/**
 * Settings Page for Advanced Bundle System
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings page under WooCommerce menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Bundle Settings', 'advanced-bundle-system'),
            __('Bundle Settings', 'advanced-bundle-system'),
            'manage_woocommerce',
            'abs-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('abs_settings_group', 'abs_settings');

        // Display Section
        add_settings_section(
            'abs_display_section',
            __('Display Settings', 'advanced-bundle-system'),
            array($this, 'display_section_callback'),
            'abs-settings'
        );

        // Bundle heading text
        add_settings_field(
            'abs_bundle_heading',
            __('Bundle Items Heading', 'advanced-bundle-system'),
            array($this, 'text_field_callback'),
            'abs-settings',
            'abs_display_section',
            array(
                'id' => 'bundle_heading',
                'default' => __('This bundle includes:', 'advanced-bundle-system'),
                'description' => __('Heading text shown above the list of bundle items', 'advanced-bundle-system')
            )
        );

        // Personalization disclaimer
        add_settings_field(
            'abs_personalization_disclaimer',
            __('Personalization Disclaimer', 'advanced-bundle-system'),
            array($this, 'text_field_callback'),
            'abs-settings',
            'abs_display_section',
            array(
                'id' => 'personalization_disclaimer',
                'default' => __('This is an embroidered product - image is for visualization purposes', 'advanced-bundle-system'),
                'description' => __('Text shown below personalization fields', 'advanced-bundle-system')
            )
        );

        // Preview button text
        add_settings_field(
            'abs_preview_button_text',
            __('Preview Button Text', 'advanced-bundle-system'),
            array($this, 'text_field_callback'),
            'abs-settings',
            'abs_display_section',
            array(
                'id' => 'preview_button_text',
                'default' => __('Preview Personalization', 'advanced-bundle-system'),
                'description' => __('Text for the personalization preview button', 'advanced-bundle-system')
            )
        );

        // Preview modal heading
        add_settings_field(
            'abs_preview_modal_heading',
            __('Preview Modal Heading', 'advanced-bundle-system'),
            array($this, 'text_field_callback'),
            'abs-settings',
            'abs_display_section',
            array(
                'id' => 'preview_modal_heading',
                'default' => __('⚠️ Preview Only - Final Product May Vary', 'advanced-bundle-system'),
                'description' => __('Heading shown in the preview modal', 'advanced-bundle-system')
            )
        );

        // Cart/Order Section
        add_settings_section(
            'abs_cart_section',
            __('Cart & Order Settings', 'advanced-bundle-system'),
            array($this, 'cart_section_callback'),
            'abs-settings'
        );

        // Bundle includes text (cart)
        add_settings_field(
            'abs_cart_bundle_includes',
            __('Bundle Includes Text', 'advanced-bundle-system'),
            array($this, 'text_field_callback'),
            'abs-settings',
            'abs_cart_section',
            array(
                'id' => 'cart_bundle_includes',
                'default' => __('Bundle includes:', 'advanced-bundle-system'),
                'description' => __('Text shown in orders for bundle contents', 'advanced-bundle-system')
            )
        );

        // Personalization label (cart)
        add_settings_field(
            'abs_cart_personalization_label',
            __('Personalization Label', 'advanced-bundle-system'),
            array($this, 'text_field_callback'),
            'abs-settings',
            'abs_cart_section',
            array(
                'id' => 'cart_personalization_label',
                'default' => __('Personalization', 'advanced-bundle-system'),
                'description' => __('Label for personalization in cart and orders', 'advanced-bundle-system')
            )
        );

        // Discount format
        add_settings_field(
            'abs_discount_format',
            __('Discount Badge Format', 'advanced-bundle-system'),
            array($this, 'text_field_callback'),
            'abs-settings',
            'abs_cart_section',
            array(
                'id' => 'discount_format',
                'default' => __('Save %s%%', 'advanced-bundle-system'),
                'description' => __('Format for discount badge. Use %s for the percentage number.', 'advanced-bundle-system')
            )
        );
    }

    /**
     * Display section callback
     */
    public function display_section_callback() {
        echo '<p>' . __('Customize the text displayed on product pages for bundles.', 'advanced-bundle-system') . '</p>';
    }

    /**
     * Cart section callback
     */
    public function cart_section_callback() {
        echo '<p>' . __('Customize the text displayed in cart and order pages.', 'advanced-bundle-system') . '</p>';
    }

    /**
     * Text field callback
     */
    public function text_field_callback($args) {
        $settings = get_option('abs_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : $args['default'];
        ?>
        <input type="text"
               id="abs_settings_<?php echo esc_attr($args['id']); ?>"
               name="abs_settings[<?php echo esc_attr($args['id']); ?>]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text" />
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // Handle form submission
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'abs_messages',
                'abs_message',
                __('Settings Saved', 'advanced-bundle-system'),
                'updated'
            );
        }

        settings_errors('abs_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form action="options.php" method="post">
                <?php
                settings_fields('abs_settings_group');
                do_settings_sections('abs-settings');
                submit_button(__('Save Settings', 'advanced-bundle-system'));
                ?>
            </form>

            <div class="abs-settings-info" style="margin-top: 30px; padding: 20px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
                <h3 style="margin-top: 0;"><?php _e('Preview Your Changes', 'advanced-bundle-system'); ?></h3>
                <p><?php _e('After saving, visit any bundle product page to see your customized text.', 'advanced-bundle-system'); ?></p>
                <p><strong><?php _e('Tip:', 'advanced-bundle-system'); ?></strong> <?php _e('You can use HTML in most fields for additional styling.', 'advanced-bundle-system'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Get a setting value
     */
    public static function get_setting($key, $default = '') {
        $settings = get_option('abs_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
}

new ABS_Settings();
