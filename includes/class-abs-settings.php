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

        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_style('abs-settings', ABS_PLUGIN_URL . 'assets/css/settings.css', array('wp-color-picker'), ABS_VERSION);
        wp_enqueue_script('abs-settings', ABS_PLUGIN_URL . 'assets/js/settings.js', array('jquery', 'wp-color-picker'), ABS_VERSION, true);
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
        register_setting('abs_settings_group', 'abs_settings', array($this, 'sanitize_settings'));

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

        // Bundle Auto-Suggest Section
        add_settings_section(
            'abs_auto_suggest_section',
            __('Bundle Auto-Suggest', 'advanced-bundle-system'),
            array($this, 'auto_suggest_section_callback'),
            'abs-settings'
        );

        // Enable bundle auto-suggest
        add_settings_field(
            'abs_enable_bundle_auto_suggest',
            __('Enable Bundle Auto-Suggest', 'advanced-bundle-system'),
            array($this, 'checkbox_field_callback'),
            'abs-settings',
            'abs_auto_suggest_section',
            array(
                'id' => 'enable_bundle_auto_suggest',
                'default' => 'no',
                'description' => __('Automatically suggest bundles to customers when they add matching products to their cart', 'advanced-bundle-system')
            )
        );

        // Auto-suggest message
        add_settings_field(
            'abs_bundle_auto_suggest_message',
            __('Suggestion Message', 'advanced-bundle-system'),
            array($this, 'textarea_field_callback'),
            'abs-settings',
            'abs_auto_suggest_section',
            array(
                'id' => 'bundle_auto_suggest_message',
                'default' => __('You added products that are part of a bundle! Save {savings} by switching to our bundle deal!', 'advanced-bundle-system'),
                'description' => __('Message shown to customer when bundle is suggested. Use {savings} to show savings amount, {bundle_name} for bundle title.', 'advanced-bundle-system')
            )
        );

        // Excluded bundles
        add_settings_field(
            'abs_bundle_auto_suggest_excluded',
            __('Exclude Bundles', 'advanced-bundle-system'),
            array($this, 'bundle_exclusion_field_callback'),
            'abs-settings',
            'abs_auto_suggest_section',
            array(
                'id' => 'bundle_auto_suggest_excluded',
                'default' => array(),
                'description' => __('Select bundles that should NOT be auto-suggested to customers. Excluded bundles will never appear in cart suggestions.', 'advanced-bundle-system')
            )
        );

        // Menu Fix Section
        add_settings_section(
            'abs_menu_fix_section',
            __('Menu Fix Settings', 'advanced-bundle-system'),
            array($this, 'menu_fix_section_callback'),
            'abs-settings'
        );

        // Enable menu fix
        add_settings_field(
            'abs_enable_menu_fix',
            __('Enable Menu Fix', 'advanced-bundle-system'),
            array($this, 'checkbox_field_callback'),
            'abs-settings',
            'abs_menu_fix_section',
            array(
                'id' => 'enable_menu_fix',
                'default' => 'no',
                'description' => __('Fixes menu visibility, duplicate menus, excessive header padding, and spacing issues on product pages', 'advanced-bundle-system')
            )
        );

        // Menu background color
        add_settings_field(
            'abs_menu_fix_bg_color',
            __('Menu Background Color', 'advanced-bundle-system'),
            array($this, 'color_field_callback'),
            'abs-settings',
            'abs_menu_fix_section',
            array(
                'id' => 'menu_fix_bg_color',
                'default' => '#ffffff',
                'description' => __('Background color for the navigation menu (default: white)', 'advanced-bundle-system')
            )
        );

        // Promotional Banner Section
        add_settings_section(
            'abs_promo_banner_section',
            __('Promotional Banner Settings', 'advanced-bundle-system'),
            array($this, 'promo_banner_section_callback'),
            'abs-settings'
        );

        // Enable promotional banner
        add_settings_field(
            'abs_enable_promo_banner',
            __('Enable Promotional Banner', 'advanced-bundle-system'),
            array($this, 'checkbox_field_callback'),
            'abs-settings',
            'abs_promo_banner_section',
            array(
                'id' => 'enable_promo_banner',
                'default' => 'no',
                'description' => __('Display a promotional banner above your header for announcements, sales, or marketing messages', 'advanced-bundle-system')
            )
        );

        // Banner text
        add_settings_field(
            'abs_promo_banner_text',
            __('Banner Text', 'advanced-bundle-system'),
            array($this, 'textarea_field_callback'),
            'abs-settings',
            'abs_promo_banner_section',
            array(
                'id' => 'promo_banner_text',
                'default' => __('🎁 Free Shipping on orders over €50', 'advanced-bundle-system'),
                'description' => __('The message to display in the banner. You can use emojis and HTML formatting.', 'advanced-bundle-system')
            )
        );

        // Banner link
        add_settings_field(
            'abs_promo_banner_link',
            __('Banner Link (optional)', 'advanced-bundle-system'),
            array($this, 'text_field_callback'),
            'abs-settings',
            'abs_promo_banner_section',
            array(
                'id' => 'promo_banner_link',
                'default' => '',
                'description' => __('Make the banner clickable. Leave empty for no link.', 'advanced-bundle-system')
            )
        );

        // Banner background color
        add_settings_field(
            'abs_promo_banner_bg_color',
            __('Background Color', 'advanced-bundle-system'),
            array($this, 'color_field_callback'),
            'abs-settings',
            'abs_promo_banner_section',
            array(
                'id' => 'promo_banner_bg_color',
                'default' => '#000000',
                'description' => __('Banner background color (default: black)', 'advanced-bundle-system')
            )
        );

        // Banner text color
        add_settings_field(
            'abs_promo_banner_text_color',
            __('Text Color', 'advanced-bundle-system'),
            array($this, 'color_field_callback'),
            'abs-settings',
            'abs_promo_banner_section',
            array(
                'id' => 'promo_banner_text_color',
                'default' => '#ffffff',
                'description' => __('Banner text color (default: white)', 'advanced-bundle-system')
            )
        );

        // Banner behavior
        add_settings_field(
            'abs_promo_banner_dismissible',
            __('Dismissible Banner', 'advanced-bundle-system'),
            array($this, 'checkbox_field_callback'),
            'abs-settings',
            'abs_promo_banner_section',
            array(
                'id' => 'promo_banner_dismissible',
                'default' => 'yes',
                'description' => __('Allow users to close the banner with an X button', 'advanced-bundle-system')
            )
        );

        // Banner sticky
        add_settings_field(
            'abs_promo_banner_sticky',
            __('Sticky Banner', 'advanced-bundle-system'),
            array($this, 'checkbox_field_callback'),
            'abs-settings',
            'abs_promo_banner_section',
            array(
                'id' => 'promo_banner_sticky',
                'default' => 'no',
                'description' => __('Keep banner visible when scrolling down the page', 'advanced-bundle-system')
            )
        );

        // Display on pages
        add_settings_field(
            'abs_promo_banner_pages',
            __('Display On', 'advanced-bundle-system'),
            array($this, 'multicheck_field_callback'),
            'abs-settings',
            'abs_promo_banner_section',
            array(
                'id' => 'promo_banner_pages',
                'options' => array(
                    'home' => __('Homepage', 'advanced-bundle-system'),
                    'shop' => __('Shop Pages', 'advanced-bundle-system'),
                    'product' => __('Product Pages', 'advanced-bundle-system'),
                    'cart' => __('Cart Page', 'advanced-bundle-system'),
                    'checkout' => __('Checkout Page', 'advanced-bundle-system'),
                ),
                'default' => array('home', 'shop', 'product'),
                'description' => __('Select which pages should display the banner', 'advanced-bundle-system')
            )
        );

        // Hide on mobile
        add_settings_field(
            'abs_promo_banner_hide_mobile',
            __('Hide on Mobile', 'advanced-bundle-system'),
            array($this, 'checkbox_field_callback'),
            'abs-settings',
            'abs_promo_banner_section',
            array(
                'id' => 'promo_banner_hide_mobile',
                'default' => 'no',
                'description' => __('Hide the banner on mobile devices', 'advanced-bundle-system')
            )
        );
    }

    /**
     * Sanitize settings
     *
     * Handles checkbox fields that don't send data when unchecked
     */
    public function sanitize_settings($input) {
        if (!is_array($input)) {
            $input = array();
        }

        // Define all checkbox fields - set to 'no' if not present in input
        $checkbox_fields = array(
            'enable_bundle_auto_suggest',
            'enable_menu_fix',
            'enable_promo_banner',
            'promo_banner_dismissible',
            'promo_banner_sticky',
            'promo_banner_hide_mobile'
        );

        // Ensure all checkboxes have a value (default to 'no' if unchecked)
        foreach ($checkbox_fields as $field) {
            if (!isset($input[$field])) {
                $input[$field] = 'no';
            }
        }

        // Sanitize text fields
        if (isset($input['bundle_heading'])) {
            $input['bundle_heading'] = sanitize_text_field($input['bundle_heading']);
        }
        if (isset($input['bundle_button_text'])) {
            $input['bundle_button_text'] = sanitize_text_field($input['bundle_button_text']);
        }
        if (isset($input['promo_banner_text'])) {
            $input['promo_banner_text'] = wp_kses_post($input['promo_banner_text']);
        }
        if (isset($input['promo_banner_link'])) {
            $input['promo_banner_link'] = esc_url_raw($input['promo_banner_link']);
        }

        // Sanitize color fields
        if (isset($input['menu_fix_bg_color'])) {
            $input['menu_fix_bg_color'] = sanitize_hex_color($input['menu_fix_bg_color']);
        }
        if (isset($input['promo_banner_bg_color'])) {
            $input['promo_banner_bg_color'] = sanitize_hex_color($input['promo_banner_bg_color']);
        }
        if (isset($input['promo_banner_text_color'])) {
            $input['promo_banner_text_color'] = sanitize_hex_color($input['promo_banner_text_color']);
        }

        // Sanitize array fields (multicheck)
        if (isset($input['promo_banner_pages']) && is_array($input['promo_banner_pages'])) {
            $input['promo_banner_pages'] = array_map('sanitize_key', $input['promo_banner_pages']);
        } elseif (!isset($input['promo_banner_pages'])) {
            $input['promo_banner_pages'] = array();
        }

        // Sanitize bundle exclusion array
        if (isset($input['bundle_auto_suggest_excluded']) && is_array($input['bundle_auto_suggest_excluded'])) {
            $input['bundle_auto_suggest_excluded'] = array_map('intval', $input['bundle_auto_suggest_excluded']);
        } elseif (!isset($input['bundle_auto_suggest_excluded'])) {
            $input['bundle_auto_suggest_excluded'] = array();
        }

        return $input;
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
     * Auto-suggest section callback
     */
    public function auto_suggest_section_callback() {
        echo '<p>' . __('Automatically detect when customers add products to their cart that could be purchased as a bundle for savings. A notice will appear on the cart page suggesting the bundle.', 'advanced-bundle-system') . '</p>';
        echo '<p class="description">' . __('When enabled, the system checks all bundle products and suggests them when matching items are in the cart. Customers can switch with one click or dismiss the suggestion.', 'advanced-bundle-system') . '</p>';
    }

    /**
     * Menu fix section callback
     */
    public function menu_fix_section_callback() {
        echo '<p>' . __('Optional menu visibility fix for WooCommerce product pages. Fixes menu disappearing, duplicate menus, and improves spacing/layout.', 'advanced-bundle-system') . '</p>';
        echo '<p class="description">' . __('Includes: Menu visibility fixes, duplicate menu removal, header spacing optimization (removes excessive padding), product page spacing improvements.', 'advanced-bundle-system') . '</p>';
    }

    /**
     * Promo banner section callback
     */
    public function promo_banner_section_callback() {
        echo '<p>' . __('Display a promotional banner above your header for announcements, sales, or special offers.', 'advanced-bundle-system') . '</p>';
        echo '<p class="description">' . __('The banner appears at the top of selected pages and can be customized with your brand colors.', 'advanced-bundle-system') . '</p>';
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
     * Checkbox field callback
     */
    public function checkbox_field_callback($args) {
        $settings = get_option('abs_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : $args['default'];
        $checked = ($value === 'yes') ? 'checked' : '';
        ?>
        <label>
            <input type="checkbox"
                   id="abs_settings_<?php echo esc_attr($args['id']); ?>"
                   name="abs_settings[<?php echo esc_attr($args['id']); ?>]"
                   value="yes"
                   <?php echo $checked; ?> />
            <?php _e('Enable', 'advanced-bundle-system'); ?>
        </label>
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Color field callback
     */
    public function color_field_callback($args) {
        $settings = get_option('abs_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : $args['default'];
        ?>
        <input type="text"
               id="abs_settings_<?php echo esc_attr($args['id']); ?>"
               name="abs_settings[<?php echo esc_attr($args['id']); ?>]"
               value="<?php echo esc_attr($value); ?>"
               class="abs-color-picker" />
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Textarea field callback
     */
    public function textarea_field_callback($args) {
        $settings = get_option('abs_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : $args['default'];
        ?>
        <textarea
            id="abs_settings_<?php echo esc_attr($args['id']); ?>"
            name="abs_settings[<?php echo esc_attr($args['id']); ?>]"
            rows="3"
            class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Multi-checkbox field callback
     */
    public function multicheck_field_callback($args) {
        $settings = get_option('abs_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : $args['default'];

        if (!is_array($value)) {
            $value = $args['default'];
        }

        foreach ($args['options'] as $key => $label) {
            $checked = in_array($key, $value) ? 'checked' : '';
            ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox"
                       name="abs_settings[<?php echo esc_attr($args['id']); ?>][]"
                       value="<?php echo esc_attr($key); ?>"
                       <?php echo $checked; ?> />
                <?php echo esc_html($label); ?>
            </label>
            <?php
        }

        if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }

    /**
     * Bundle exclusion field callback
     */
    public function bundle_exclusion_field_callback($args) {
        $settings = get_option('abs_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : $args['default'];

        if (!is_array($value)) {
            $value = $args['default'];
        }

        // Get all bundle products
        $bundle_args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'bundle'
                )
            )
        );

        $bundles = get_posts($bundle_args);

        if (empty($bundles)) {
            echo '<p style="color: #999; font-style: italic;">' . __('No bundle products found. Create bundle products first.', 'advanced-bundle-system') . '</p>';
            return;
        }

        echo '<div style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">';

        foreach ($bundles as $bundle) {
            $bundle_id = $bundle->ID;
            $checked = in_array($bundle_id, $value) ? 'checked' : '';
            $bundle_product = wc_get_product($bundle_id);
            $price = $bundle_product ? wc_price($bundle_product->get_price()) : '';
            ?>
            <label style="display: block; margin-bottom: 8px; padding: 5px; border-bottom: 1px solid #f0f0f0;">
                <input type="checkbox"
                       name="abs_settings[<?php echo esc_attr($args['id']); ?>][]"
                       value="<?php echo esc_attr($bundle_id); ?>"
                       <?php echo $checked; ?> />
                <strong><?php echo esc_html($bundle->post_title); ?></strong>
                <span style="color: #999; font-size: 12px;">
                    (#<?php echo esc_html($bundle_id); ?><?php echo $price ? ' - ' . $price : ''; ?>)
                </span>
            </label>
            <?php
        }

        echo '</div>';

        if (!empty($args['description'])): ?>
            <p class="description" style="margin-top: 10px;"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
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
