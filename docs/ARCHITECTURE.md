# Architecture & Design: WPCalibrate Bangladesh Order Tracker

## 1. Overview
WPCalibrate Bangladesh Order Tracker is a high-performance, security-focused WooCommerce order tracking solution built specifically for the Bangladeshi e-commerce ecosystem.

The system addresses the unique realities of online retail in Bangladesh:
- Prevalent Cash on Delivery (COD) workflows requiring timely tracking updates to curb delivery returns.
- Multiple local logistics providers (Steadfast, Pathao, RedX, Paperfly, Sundarban, SA Paribahan, eCourier) with varying degrees of API availability.
- Strict data privacy and guest verification to eliminate order enumeration risks without degrading customer UX.
- Direct compatibility with WooCommerce High-Performance Order Storage (HPOS).

---

## 2. Component Architecture

```
                    +------------------------------------+
                    | WooCommerce Core / HPOS Data Store |
                    +-----------------+------------------+
                                      |
                     [wc_get_order / update_meta_data]
                                      |
                                      v
                    +------------------------------------+
                    |       Shipment_Repository          |
                    | (Multi-package, Events, Indexing)  |
                    +---+--------------+--------------+--+
                        |              |              |
         +--------------+              |              +---------------+
         |                             |                              |
         v                             v                              v
+------------------+         +-------------------+         +--------------------+
|  Admin Screens   |         | Customer Tracking |         |    REST Webhook    |
| & Order Metabox  |         |   & Shortcode     |         |     & Sync API     |
+------------------+         +-------------------+         +--------------------+
         |                             |                              |
         +--------------+              |              +---------------+
                        |              |              |
                        v              v              v
                    +------------------------------------+
                    |          Courier_Manager           |
                    |   (Provider registry & routing)    |
                    +------------------+-----------------+
                                       |
         +-----------------+-----------+-----------+------------------+
         |                 |                       |                  |
         v                 v                       v                  v
+----------------+ +----------------+      +----------------+ +----------------+
|   Steadfast    | |     Pathao     |      |      RedX      | |     Manual     |
|    Adapter     | |    Adapter     |      |    Adapter     | |   Templates    |
+----------------+ +----------------+      +----------------+ +----------------+
```

### 2.1 Core Subsystems
1. **Shipment_Repository (`includes/class-shipment-repository.php`)**:
   - Manages package records stored in `_wpcalibrate_bot_shipments`.
   - Maintains an indexed tracking numbers array `_wpcalibrate_bot_tracking_numbers` for efficient reverse lookups without scanning all order records.
   - Fully utilizes WooCommerce Order CRUD APIs (`$order->get_meta()`, `$order->update_meta_data()`, `$order->save()`), guaranteeing compatibility with both HPOS and legacy postmeta.

2. **Courier_Manager (`includes/class-courier-manager.php`)**:
   - Registers default adapters and fires the `wpcalibrate_bot_register_courier_providers` action hook for custom extensions.
   - Resolves safe public tracking URLs using strict scheme filtering (`http` and `https` only).

3. **Tracking_Page (`includes/class-tracking-page.php`)**:
   - Handles the `[wpcalibrate_order_tracking]` shortcode and `woocommerce_view_order` account view.
   - Enforces IP-based anti-enumeration rate limiting.
   - Implements phone normalization tolerant of `+8801...`, `8801...`, and local `01...` formats.
   - Strips administrative and private notes before frontend rendering.

4. **REST_API (`includes/class-rest-api.php`)**:
   - Exposes `/wp-json/wpcalibrate-bot/v1/webhook/{provider}`.
   - Enforces signature and token validation in `permission_callback`.
   - Dispatches idempotent order and shipment updates upon receipt of courier webhooks.

5. **Notifications (`includes/class-notifications.php`)**:
   - Sends transactional tracking emails.
   - Deduplicates emails using `last_notified_status` on the shipment record.
   - Hooks into standard WooCommerce customer order emails (`woocommerce_email_order_meta`).

---

## 3. Security Boundaries & Protections

| Concern | Mitigation Strategy |
| :--- | :--- |
| **Order Enumeration** | Guest queries must match Order Reference AND Billing Phone / Email. Failed attempts trigger IP transient rate limiting. Responses use timing-safe constant-time string comparisons. |
| **XSS & Injection** | Strict sanitization via `sanitize_text_field`, `esc_url_raw`, and output escaping with `esc_html`, `esc_attr`, and `esc_url`. Tracking URL templates reject non-HTTP/HTTPS schemes. |
| **CSRF & Privilege** | All administrative AJAX and form submits require `check_ajax_referer` / `check_admin_referer` and explicit capability checks (`manage_woocommerce` or `edit_shop_orders`). Nonces are never treated as authorizations. |
| **API Secret Exposure** | Passwords and secret keys are stored securely in options, masked with `********` on render, and never logged or included in customer-facing views. |
| **Data Retention** | Defaults to data preservation on deactivation/uninstall. Explicit opt-in required for database cleanup. Integrates with WordPress Personal Data Exporter and Eraser. |
