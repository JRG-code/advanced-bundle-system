# advanced-bundle-system
Advanced Bundle System for wordpress/woocommerce integration

Bundle System:

Pricing Display: Show original total price, your custom bundle price, and auto-calculate the % discount
Cart Behavior: One bundle line item, with individual products shown as sub-items underneath (indented/nested)
Personalization: Each product in the bundle gets its own line for personalization details

Personalization:

Real-time preview overlay on product images
Disclaimer text: "This is an embroidered product - image is for visualization purposes"
Font selection (3 options) - implement later
Works on both bundles and individual products

Technical Approach:

Standalone plugin that hooks into WooCommerce (smart choice for update safety)
Vanilla JavaScript or jQuery (already available in WordPress)
Compatible with standard WordPress/Hostinger hosting
