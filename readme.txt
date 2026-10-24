=== WPCalibrate Bangladesh Order Tracker ===
Contributors: wpcalibrate
Donate link: https://wpcalibrate.com
Tags: woocommerce, order tracking, bangladesh, steadfast, pathao
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 8.2
WC requires at least: 8.0
WC tested up to: 10.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce order and shipment tracking for Bangladesh stores with Steadfast, Pathao, RedX, Paperfly, and domestic couriers.

== Description ==

**WPCalibrate Bangladesh Order Tracker** provides automated and manual shipment tracking built specifically for Bangladeshi WooCommerce stores.

Allow your customers to check their parcel delivery status, live status timeline, and courier tracking details on a dedicated tracking page without calling customer service.

### Key Features
* **Multi-Shipment Support**: Track multiple parcels or partial shipments per WooCommerce order.
* **Domestic Courier Integration**: Built-in support for Steadfast Courier, Pathao Courier, RedX, Paperfly, Sundarban, SA Paribahan, and custom couriers.
* **Customer Tracking Portal**: Clean, accessible, responsive tracking page via `[wpcalibrate_order_tracking]` shortcode.
* **Bangladesh Phone Normalization**: Tolerant lookup accepting `01XXXXXXXXX`, `+8801XXXXXXXXX`, or `8801XXXXXXXXX`.
* **Guest Verification & Anti-Enumeration**: Protects customer privacy by requiring billing phone or email matching, with built-in IP rate limiting.
* **Automated Status Synchronization**: Background WP-Cron status sync and real-time webhook listeners for Steadfast, Pathao, and RedX.
* **WooCommerce HPOS Compatible**: Fully declared and tested with High-Performance Order Storage (HPOS) and Cart/Checkout Blocks.
* **Transactional Email Alerts**: Automatic customer email notifications upon shipment status changes.
* **No API Keys Required to Start**: Fully functional with manual courier entry and custom URL templates from day one.

== Installation ==

1. Upload the `wpcalibrate-bangladesh-order-tracker` folder to the `/wp-content/plugins/` directory, or upload the ZIP file directly via Plugins > Add New > Upload Plugin.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure WooCommerce is installed and activated.
4. Navigate to **WPCalibrate > BD Order Tracker** to configure default courier, tracking page, and optional API credentials.
5. Add the `[wpcalibrate_order_tracking]` shortcode to your tracking page.

== Frequently Asked Questions ==

= Do I need courier API credentials to use this plugin? =
No. The plugin includes full manual tracking and custom tracking URL templates for all Bangladesh couriers. You only need API credentials if you wish to use automated remote status sync.

= Is High-Performance Order Storage (HPOS) supported? =
Yes! The plugin fully declares and supports WooCommerce HPOS (`custom_order_tables`) and Cart/Checkout Blocks.

= How do guest customers check their order tracking? =
Guests enter their Order ID or Tracking Number along with their billing phone number or email address. The plugin validates the information before displaying tracking progress, preventing unauthorized order enumeration.

== Screenshots ==

1. WooCommerce Order Admin Meta Box with multi-shipment tracking cards and timeline history.
2. Customer Tracking Portal with responsive visual progress step indicator.
3. WPCalibrate Settings Dashboard with courier configurations and connection diagnostics.

== Changelog ==

= 1.0.0 =
* Initial production release.
* Added support for Steadfast Courier, Pathao, RedX, Paperfly, and custom couriers.
* Added [wpcalibrate_order_tracking] customer shortcode with anti-enumeration rate limiting.
* Added HPOS compatibility declaration and order list status column.
* Added automated webhook listeners and WP-Cron background status polling.
