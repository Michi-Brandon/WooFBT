=== YITH Cross Selling Manual for WooCommerce ===
Contributors: yithemes
Tags: woocommerce, cross selling, product accessories, product bundles
Requires at least: 6.7
Tested up to: 6.9
Stable tag: 1.54.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manual cross-selling workflow for WooCommerce single product pages using the shortcode `[fbt_product_check_2]`.

== Description ==

This project is a focused fork of YITH Frequently Bought Together 2, adapted to keep only manual cross-selling features.

The plugin now keeps the product-level configuration and frontend selector behavior required for accessory selling flows (for example: barras, soportes, terminales, anillas, codos).

= What is included =

* Shortcode rendering through `[fbt_product_check_2]` (manual placement in your single product template)
* Product data tab: `Cross Selling Manual`
* Two selectors in product config:
* `Simple` mode (classic set behavior)
* `Categorical` mode (category-based selector with image trigger)
* Product quantity controls with stock-aware limits (`min=1`, `max=stock` when applicable)
* Disabled `+`/`-` buttons at quantity limits
* Main product can be included/excluded and has quantity controls
* Category-level text matching:
* `Match text` input per category
* `Message` input per category
* Frontend notices shown above main product when selected options match
* Multiple matching notices are stacked (unique messages, no overwrite)
* Admin selected product order is preserved and saved in the same order

= What was removed intentionally =

* Automatic insertion into single product page hooks
* Gutenberg block registration for this plugin output
* Settings/Premium panel dependencies for this forked workflow
* Secondary custom shortcode `[fbt_checkbox_list_2]`

== Installation ==

1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Edit a product and configure items in `Product data > Cross Selling Manual`.
4. Add `[fbt_product_check_2]` in your single product template or builder block where you want to render the cross-selling module.

== Usage ==

1. Open a product in admin.
2. In `Cross Selling Manual`, choose `Simple` or `Categorical`.
3. If using `Categorical`, create categories and select products per category.
4. Optional per category:
* Set `Match text` (example: `Doble`)
* Set `Message` to show when a selected product name contains that text
5. Save product and test on frontend.

== Changelog ==

= 1.54.0 = Released on 19 January 2026

* Fork scope reduced to manual cross-selling use case
* Added/kept shortcode-first rendering flow via `[fbt_product_check_2]`
* Added category match/message configuration and dynamic notice rendering
* Added quantity controls and stock-aware limits (including main product row)
* Added stacked multi-message notices for simultaneous matches
* Preserved admin selection order for category products
* Removed auto insert, Gutenberg block flow, settings/premium panel flow, and extra shortcode path

