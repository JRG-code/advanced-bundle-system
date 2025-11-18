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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_settings_assets'));
    }

    /**
     * Enqueue settings page assets
     */
    public function enqueue_settings_assets($hook) {
        if ($hook !== 'woocommerce_page_abs-settings') {
            return;
        }

        wp_enqueue_style('abs-settings', ABS_PLUGIN_URL . 'assets/css/settings.css', array(), ABS_VERSION);
        wp_enqueue_script('abs-settings', ABS_PLUGIN_URL . 'assets/js/settings.js', array('jquery'), ABS_VERSION, true);
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

        // Personalization heading (for non-bundle products)
        add_settings_field(
            'abs_personalization_heading',
            __('Personalization Section Heading', 'advanced-bundle-system'),
            array($this, 'text_field_callback'),
            'abs-settings',
            'abs_display_section',
            array(
                'id' => 'personalization_heading',
                'default' => __('Personalization Options:', 'advanced-bundle-system'),
                'description' => __('Heading shown above personalization fields for non-bundle products', 'advanced-bundle-system')
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
                ?>

                <div class="abs-settings-buttons">
                    <?php submit_button(__('Save Settings', 'advanced-bundle-system'), 'primary', 'submit', false); ?>
                    <button type="button" id="abs-generate-preview" class="button button-secondary">
                        <?php _e('Generate Preview', 'advanced-bundle-system'); ?>
                    </button>
                </div>
            </form>

            <div class="abs-settings-preview" id="abs-settings-preview" style="display: none;">
                <div class="abs-preview-container">
                    <h2><?php _e('Preview', 'advanced-bundle-system'); ?></h2>
                    <p class="description"><?php _e('Preview of your customized text', 'advanced-bundle-system'); ?></p>

                        <!-- Product Page Preview -->
                        <div class="abs-preview-section">
                            <h3><?php _e('Product Page', 'advanced-bundle-system'); ?></h3>
                            <div class="abs-preview-mock">
                                <div class="abs-mock-bundle">
                                    <h4 id="preview-bundle-heading"><?php echo esc_html(self::get_setting('bundle_heading', __('This bundle includes:', 'advanced-bundle-system'))); ?></h4>

                                    <div class="abs-mock-bundle-item">
                                        <div class="abs-mock-item-image"></div>
                                        <div class="abs-mock-item-details">
                                            <strong>Bathrobe</strong>
                                            <p class="abs-mock-price">€45.00</p>

                                            <div class="abs-mock-personalization">
                                                <label>Enter your initials:</label>
                                                <input type="text" placeholder="AB" disabled />
                                                <button type="button" id="preview-button-text" class="abs-mock-button">
                                                    <?php echo esc_html(self::get_setting('preview_button_text', __('Preview Personalization', 'advanced-bundle-system'))); ?>
                                                </button>
                                                <small id="preview-disclaimer"><?php echo esc_html(self::get_setting('personalization_disclaimer', __('This is an embroidered product - image is for visualization purposes', 'advanced-bundle-system'))); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Preview -->
                        <div class="abs-preview-section">
                            <h3><?php _e('Price Display', 'advanced-bundle-system'); ?></h3>
                            <div class="abs-preview-mock">
                                <div class="abs-mock-pricing">
                                    <del>€90.00</del> <ins>€72.00</ins>
                                    <span id="preview-discount-badge" class="abs-mock-discount">
                                        <?php echo sprintf(self::get_setting('discount_format', __('Save %s%%', 'advanced-bundle-system')), '20'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Cart/Order Preview -->
                        <div class="abs-preview-section">
                            <h3><?php _e('Cart & Orders', 'advanced-bundle-system'); ?></h3>
                            <div class="abs-preview-mock">
                                <div class="abs-mock-order">
                                    <strong>Couple's Bathrobe Bundle</strong>
                                    <div class="abs-mock-order-items">
                                        <strong id="preview-bundle-includes"><?php echo esc_html(self::get_setting('cart_bundle_includes', __('Bundle includes:', 'advanced-bundle-system'))); ?></strong>
                                        <ul>
                                            <li>Bathrobe</li>
                                            <li>Towel Set</li>
                                        </ul>
                                    </div>
                                    <small><span id="preview-personalization-label"><?php echo esc_html(self::get_setting('cart_personalization_label', __('Personalization', 'advanced-bundle-system'))); ?></span>: AB</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get a setting value (with static cache for performance)
     */
    public static function get_setting($key, $default = '') {
        static $settings_cache = null;

        // Load settings once and cache in memory
        if ($settings_cache === null) {
            $settings_cache = get_option('abs_settings', array());
        }

        return isset($settings_cache[$key]) ? $settings_cache[$key] : $default;
    }
}

new ABS_Settings();
