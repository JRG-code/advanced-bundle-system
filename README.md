# Advanced Bundle System for WooCommerce

A powerful WordPress/WooCommerce plugin that adds advanced product bundling with personalization features, real-time preview overlays, and smart pricing.

## Features

### 🎁 Bundle System

- **Smart Pricing Display**: Shows original total price, custom bundle price, and auto-calculated discount percentage
- **Nested Cart Display**: One bundle line item with individual products shown as indented sub-items
- **Personalization Support**: Each product in the bundle gets its own personalization options
- **Custom Product Type**: Dedicated "Product Bundle" type in WooCommerce
- **Real-time Pricing Calculator**: Auto-calculates discounts as you configure bundles in admin

### ✨ Personalization Features

- **Real-time Preview Overlay**: Interactive preview on product images
- **Text Personalization**: Customers can add custom text (max 50 characters)
- **Visual Preview Modal**: Shows personalized product with overlay text
- **Disclaimer Notice**: "This is an embroidered product - image is for visualization purposes"
- **Works Everywhere**: Compatible with both bundle products and individual products
- **Font Selection**: (Coming soon - 3 font options)

### 🛒 Cart & Checkout

- **Nested Display**: Bundle items shown with proper hierarchy
- **Personalization Display**: Custom text displayed for each personalized product
- **Discount Badges**: Clear savings indicators throughout
- **Order Processing**: Personalization data saved to orders
- **CartFlows Compatible**: Seamless integration with CartFlows checkout pages

### 🔌 Compatibility

- **WooCommerce**: Full compatibility with standard WooCommerce checkout
- **CartFlows**: Dedicated integration layer for CartFlows checkout pages
  - Personalization displays in checkout review
  - Order bumps support with personalization
  - Custom checkout templates fully supported
- **Third-party Themes**: Works with most WooCommerce-compatible themes
- **Page Builders**: Compatible with Elementor, Divi, and other popular builders

### 🎨 Technical Highlights

- **Standalone Plugin**: Hooks into WooCommerce without modifying core files
- **Update Safe**: Works independently, won't break on WordPress/WooCommerce updates
- **jQuery Based**: Uses WordPress's built-in jQuery (no external dependencies)
- **Responsive Design**: Works on all devices
- **Hostinger Compatible**: Works on standard WordPress hosting

## Installation

### Method 1: Upload via WordPress Admin

1. Download the plugin as a ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate Plugin"

### Method 2: Manual Installation

1. Download/clone this repository
2. Upload the `advanced-bundle-system` folder to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "Advanced Bundle System" and click "Activate"

### Requirements

- WordPress 5.8 or higher
- WooCommerce 6.0 or higher
- PHP 7.4 or higher

## Usage Guide

### Creating a Product Bundle

1. Go to **Products → Add New** in WordPress admin
2. Enter product name and details
3. In **Product Data** panel, select **Product Bundle** from the dropdown
4. Go to **Bundle Products** tab:
   - Search and select products to include in the bundle
   - Enable personalization if desired
5. In **General** tab:
   - Enter your custom **Bundle Price**
   - View the pricing summary showing original total, bundle price, and discount %
6. Publish the product

### Enabling Personalization on Simple Products

1. Edit any simple product
2. In **Product Data** → **General** tab
3. Check **Enable Personalization**
4. Save the product

### Customer Experience

**On Product Page:**
- Customers see bundle contents with individual product images and prices
- Discount badge shows percentage saved
- Personalization fields appear for each product (if enabled)
- "Preview Personalization" button opens real-time preview modal

**In Cart:**
- Bundle shown as main line item
- Individual products displayed as nested sub-items
- Personalization text shown for each customized product
- Discount percentage clearly displayed

**At Checkout & Orders:**
- All bundle information preserved
- Personalization data included in order details
- Visible in customer emails and admin order view

## File Structure

```
advanced-bundle-system/
├── advanced-bundle-system.php              # Main plugin file
├── includes/
│   ├── class-wc-product-bundle.php         # Bundle product class
│   ├── class-abs-product-type.php          # Bundle product type
│   ├── class-abs-admin.php                 # Admin functionality
│   ├── class-abs-frontend.php              # Frontend display
│   ├── class-abs-cart.php                  # Cart functionality
│   ├── class-abs-personalization.php       # Personalization system
│   ├── class-abs-general-personalization.php # Personalization for all product types
│   ├── class-abs-order.php                 # Order processing
│   ├── class-abs-stock.php                 # Stock management
│   ├── class-abs-settings.php              # Plugin settings
│   ├── class-abs-inventory.php             # Inventory manager
│   └── class-abs-cartflows-compat.php      # CartFlows compatibility
├── assets/
│   ├── js/
│   │   ├── frontend.js                     # Frontend JavaScript
│   │   └── admin.js                        # Admin JavaScript
│   └── css/
│       ├── frontend.css                    # Frontend styles
│       └── admin.css                       # Admin styles
└── README.md                               # This file
```

## Hooks & Filters

The plugin provides several hooks for customization:

### Filters

- `abs_bundle_discount_calculation` - Modify discount calculation
- `abs_personalization_max_length` - Change max character limit
- `abs_preview_disclaimer_text` - Customize disclaimer text

### Actions

- `abs_before_bundle_items` - Before bundle items display
- `abs_after_bundle_items` - After bundle items display
- `abs_personalization_saved` - When personalization is saved

## Customization

### Changing Personalization Character Limit

```php
add_filter('abs_personalization_max_length', function($limit) {
    return 100; // Change to 100 characters
});
```

### Custom Disclaimer Text

```php
add_filter('abs_preview_disclaimer_text', function($text) {
    return 'Your custom disclaimer text here';
});
```

## Roadmap

- [ ] Font selection (3 options) for personalization
- [ ] Color picker for personalization text
- [ ] Position selector for text placement
- [ ] Multi-line personalization support
- [ ] Image upload for custom graphics
- [ ] Template-based personalization

## Support

For issues, questions, or feature requests, please open an issue on the [GitHub repository](https://github.com/JRG-code/advanced-bundle-system).

## License

GPL v2 or later - see WordPress.org for details

## Credits

Developed by JRG Code

---

**Version**: 1.3.8
**Tested up to**: WordPress 6.4 / WooCommerce 8.0
**Requires**: WordPress 5.8+ / WooCommerce 6.0+ / PHP 7.4+
**Compatible with**: CartFlows 1.0+
