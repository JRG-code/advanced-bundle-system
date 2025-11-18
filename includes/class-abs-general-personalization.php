<?php
/**
 * General Personalization for Simple, Variable, Grouped, and External Products
 */

if (!defined('ABSPATH')) {
    exit;
}

class ABS_General_Personalization {

    public function __construct() {
        // Add personalization fields to the General tab for all non-bundle product types
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_personalization_fields'));

        // Save personalization data
        add_action('woocommerce_process_product_meta', array($this, 'save_personalization_data'));
    }

    /**
     * Add personalization fields to the General tab
     */
    public function add_personalization_fields() {
        global $post;

        $product = wc_get_product($post->ID);

        // Only show for non-bundle product types (simple, variable, grouped, external)
        if (!$product || $product->get_type() === 'bundle') {
            return;
        }

        // Get existing settings
        $enable_personalization = get_post_meta($post->ID, '_enable_personalization', true);
        $personalization_label = get_post_meta($post->ID, '_personalization_label', true);
        if (empty($personalization_label)) {
            $personalization_label = __('Enter text:', 'advanced-bundle-system');
        }
        $max_characters = get_post_meta($post->ID, '_max_characters', true);
        if (empty($max_characters)) {
            $max_characters = 50;
        }

        ?>
        <div class="options_group hide_if_bundle">
            <h4 style="padding: 0 12px; margin-top: 10px;"><?php _e('Product Personalization', 'advanced-bundle-system'); ?></h4>
            <p style="padding: 0 12px; color: #666; margin-bottom: 10px;">
                <?php _e('Allow customers to personalize this product (e.g., add initials on sleeves).', 'advanced-bundle-system'); ?>
            </p>

            <?php
            woocommerce_wp_checkbox(array(
                'id' => '_enable_personalization',
                'label' => __('Enable Personalization', 'advanced-bundle-system'),
                'description' => __('Check this box to allow customers to add custom text to this product.', 'advanced-bundle-system'),
                'desc_tip' => true,
                'value' => $enable_personalization
            ));
            ?>

            <div id="abs_personalization_options" style="<?php echo ($enable_personalization === 'yes') ? '' : 'display: none;'; ?>">
                <?php
                woocommerce_wp_text_input(array(
                    'id' => '_personalization_label',
                    'label' => __('Personalization Label', 'advanced-bundle-system'),
                    'description' => __('This text will be shown to customers. Example: "Enter your initials:"', 'advanced-bundle-system'),
                    'desc_tip' => true,
                    'placeholder' => __('e.g., Enter your initials:', 'advanced-bundle-system'),
                    'value' => $personalization_label
                ));

                woocommerce_wp_text_input(array(
                    'id' => '_max_characters',
                    'label' => __('Maximum Characters', 'advanced-bundle-system'),
                    'description' => __('Maximum number of characters allowed (1-100).', 'advanced-bundle-system'),
                    'desc_tip' => true,
                    'type' => 'number',
                    'custom_attributes' => array(
                        'min' => '1',
                        'max' => '100',
                        'step' => '1'
                    ),
                    'value' => $max_characters
                ));

                $disclaimer_text = get_post_meta($post->ID, '_personalization_disclaimer', true);
                woocommerce_wp_text_input(array(
                    'id' => '_personalization_disclaimer',
                    'label' => __('Disclaimer Text', 'advanced-bundle-system'),
                    'description' => __('Optional disclaimer text shown below the personalization field. Example: "3rd image is representative of the font used"', 'advanced-bundle-system'),
                    'desc_tip' => true,
                    'placeholder' => __('e.g., 3rd image is representative of the font used', 'advanced-bundle-system'),
                    'value' => $disclaimer_text
                ));
                ?>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(function($) {
                // Toggle personalization options when checkbox is changed
                $('#_enable_personalization').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#abs_personalization_options').slideDown();
                    } else {
                        $('#abs_personalization_options').slideUp();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Save personalization data when product is saved
     */
    public function save_personalization_data($post_id) {
        $product = wc_get_product($post_id);

        // Only save for non-bundle product types
        if (!$product || $product->get_type() === 'bundle') {
            return;
        }

        // Save enable personalization checkbox
        $enable_personalization = isset($_POST['_enable_personalization']) ? 'yes' : 'no';
        update_post_meta($post_id, '_enable_personalization', $enable_personalization);

        // Save personalization settings if enabled
        if ($enable_personalization === 'yes') {
            $personalization_label = isset($_POST['_personalization_label']) ? sanitize_text_field($_POST['_personalization_label']) : __('Enter text:', 'advanced-bundle-system');
            update_post_meta($post_id, '_personalization_label', $personalization_label);

            $max_characters = isset($_POST['_max_characters']) ? intval($_POST['_max_characters']) : 50;
            $max_characters = max(1, min(100, $max_characters)); // Ensure it's between 1 and 100
            update_post_meta($post_id, '_max_characters', $max_characters);

            $disclaimer_text = isset($_POST['_personalization_disclaimer']) ? sanitize_text_field($_POST['_personalization_disclaimer']) : '';
            update_post_meta($post_id, '_personalization_disclaimer', $disclaimer_text);
        } else {
            // Clear personalization settings if disabled
            delete_post_meta($post_id, '_personalization_label');
            delete_post_meta($post_id, '_max_characters');
            delete_post_meta($post_id, '_personalization_disclaimer');
        }
    }
}

new ABS_General_Personalization();
