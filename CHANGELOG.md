# Changelog

All notable changes to the Advanced Bundle System plugin will be documented in this file.

## [1.3.10] - 2025-11-19

### Added
- Custom background color picker for menu fix
- WordPress color picker integration in Bundle Settings
- Customizable menu background color (default: white) with live color selection

### Changed
- Menu fix now applies custom background color to all header and navigation elements

## [1.3.9] - 2025-11-19

### Added
- Optional menu fix setting to resolve navigation menu disappearing on WooCommerce product pages
- New "Menu Fix Settings" section in Bundle Settings (WooCommerce > Bundle Settings)
- Comprehensive CSS fix for menu visibility across all major WordPress themes
- Support for Astra, OceanWP, Storefront, GeneratePress, Divi, Avada, and Elementor themes
- Mobile menu compatibility fixes

### Changed
- Menu fix is disabled by default (only enable if experiencing menu issues)
- Integrated menu fix directly into plugin settings (no separate plugin needed)
- Menu fix CSS only loads on product pages when enabled

## [1.3.8] - 2025-11-18

### Added
- Custom disclaimer text field for personalization on all product types
- Disclaimer field in bundle items table for per-item custom messages
- Disclaimer field in General tab for simple/variable/grouped/external products
- Support for custom messages like "3rd image is representative of the font used"

### Removed
- Preview Personalization button (simplified user experience)
- Preview modal functionality

### Changed
- Streamlined personalization interface with focus on disclaimer text
- Improved admin UX with clearer field labels

## [1.3.7] - 2025-11-18

### Added
- CartFlows compatibility layer for seamless checkout integration
- Personalization display in CartFlows checkout review
- Support for CartFlows order bumps with personalization
- Checkout-specific CSS styling for better presentation
- WooCommerce checkout review personalization display

### Changed
- Enhanced checkout display with dedicated styling
- Improved compatibility with third-party checkout plugins
- Better visual presentation of personalization in checkout pages

## [1.3.6] - 2025-11-18

### Added
- Personalization system for variable products (add custom text like "initials on sleeves")
- Personalization system for simple products
- Personalization system for grouped products
- Personalization system for external/affiliate products
- New personalization fields in "General" tab for non-bundle product types
- New setting: "Personalization Section Heading" for non-bundle products
- Frontend display of personalization fields for all product types
- Cart and order support for personalization on all product types

### Changed
- Extended personalization functionality from bundles-only to all WooCommerce product types
- Updated order display to properly show personalization for all product types

## [1.3.5] - 2025-11-18

### Changed
- Reorganized Inventory Manager by hierarchy: Bundles → Products in Bundles → Others

## [1.3.4] - 2025-11-18

### Fixed
- Show 'Used In' bundles for product variations in Inventory Manager

## [1.3.3] - 2025-11-18

### Changed
- Performance optimization - Make plugin faster with improved query efficiency

## [1.3.2] - 2025-11-18

### Added
- Inline SKU editing to Inventory Manager for quick updates

## [1.3.1] - 2025-11-18

### Added
- Grouped variable products display in Inventory Manager

## [1.3.0] - 2025-11-18

### Added
- Centralized Inventory Manager for managing all bundle products and inventory
- New admin interface for inventory management

## [1.0.0] - 2025-11-04

### Added
- Initial release of Advanced Bundle System
- Custom "Product Bundle" product type for WooCommerce
- Smart pricing display with original price, bundle price, and auto-calculated discount percentage
- Bundle product configuration interface in WordPress admin
- Real-time pricing calculator in admin
- Product search functionality for selecting bundle products
- Frontend bundle display with product images and prices
- Personalization system for both bundle and individual products
- Real-time preview modal with text overlay on product images
- Text personalization (max 50 characters)
- Disclaimer notice for embroidered/personalized products
- Nested cart display showing bundle as main item with sub-items
- Personalization text display in cart and checkout
- Order processing with bundle and personalization data preservation
- Admin order view showing bundle contents and personalization
- Responsive design for all screen sizes
- jQuery-based frontend interactions
- Complete CSS styling for all components
- AJAX functionality for product search and preview generation
- Filter and action hooks for customization
- Comprehensive documentation and usage guide

### Features
- ✅ Bundle System with smart pricing
- ✅ Nested cart display
- ✅ Real-time preview overlay
- ✅ Text personalization
- ✅ Works on bundles and individual products
- ✅ Auto-calculated discount percentages
- ✅ Responsive design
- ✅ WordPress/Hostinger compatible

### Coming Soon
- Font selection (3 options) for personalization text
- Color picker for personalization text
- Position selector for text placement
- Multi-line personalization support
- Image upload for custom graphics
- Template-based personalization

---

## Version History

- **1.0.0** - Initial release with core bundle and personalization features
