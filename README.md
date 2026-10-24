# WPCalibrate Bangladesh Order Tracker

![WPCalibrate Bangladesh Order Tracker Banner](branding/icon-dark.png)

[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-purple.svg)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3%2B-777bb4.svg)](https://www.php.net)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![HPOS Ready](https://img.shields.io/badge/HPOS-Compatible-success.svg)](https://woocommerce.com)

A high-performance, security-hardened WooCommerce order tracking extension built specifically for Bangladeshi e-commerce stores. Connects seamlessly with domestic couriers (**Steadfast**, **Pathao**, **RedX**, **Paperfly**, **Sundarban**, **SA Paribahan**, and merchant delivery staff) and provides a customer-facing self-service tracking portal to eliminate customer support friction and curb Cash-on-Delivery (COD) return rates.

---

## 📖 About

In Bangladesh, Cash on Delivery (COD) represents over 70% of e-commerce transactions. High delivery return (Return to Origin / RTO) rates and customer anxiety ("Where is my parcel?") often strain support teams.

**WPCalibrate Bangladesh Order Tracker** solves this problem by:
- Giving store owners a dedicated **Bangladesh Shipment Tracking** order panel.
- Connecting directly to local courier APIs and webhooks for automated status updates.
- Providing customers with an accessible, mobile-optimized tracking page (`[wpcalibrate_order_tracking]`).
- Keeping customers informed through automated, deduplicated email alerts as their shipment transitions from *Dispatched* to *Out for Delivery* to *Delivered*.

---

## ✨ Features

- **Multi-Shipment Tracking per Order:** Attach multiple packages with independent couriers, tracking codes, and status timelines to a single WooCommerce order.
- **Domestic Bangladesh Logistics Ecosystem:**
  - **Steadfast Courier:** Automated Portal API v1 status checking and webhooks.
  - **Pathao Courier:** OAuth 2.0 Hermes API tracking and webhook listener.
  - **RedX Courier:** OpenAPI v1.0.0-beta Bearer token tracking.
  - **Paperfly:** Wings tracking URL templates.
  - **Manual / Custom Couriers:** Universal support for Sundarban, SA Paribahan, eCourier, Karatoa, and custom delivery staff with customizable URL templates.
- **Customer Tracking Portal (`[wpcalibrate_order_tracking]`):**
  - Responsive visual progress step bar (Order Placed &rarr; Ready &rarr; In Transit &rarr; Out for Delivery &rarr; Delivered).
  - Chronological status event history with customer-visible milestones.
  - Embedded in WooCommerce *My Account > Orders > View Order*.
- **Bangladesh-Specific Phone Normalization:**
  - Tolerant lookup supporting `+8801XXXXXXXXX`, `8801XXXXXXXXX`, `01XXXXXXXXX`, spaces, and dashes.
- **Strict Privacy & Anti-Enumeration Protection:**
  - Guest lookups require matching Order ID / Tracking Number with Billing Phone or Email.
  - Built-in IP-based rate limiting to prevent automated order enumeration or brute-force guessing.
  - Administrative staff notes and private credentials are never exposed to the frontend.
- **WooCommerce HPOS Native:**
  - Declared `custom_order_tables` and `cart_checkout_blocks` compatible.
  - Utilizes WooCommerce Order CRUD metadata abstractions for fast query indexing.
- **Transactional Customer Notifications:**
  - Automatic status change emails with deduplication protection (`last_notified_status`).
  - Embeds tracking cards into standard WooCommerce Completed and Invoice customer emails.
- **Overview & Onboarding Dashboard:**
  - Dedicated Overview tab inside `WPCalibrate > BD Order Tracker` explaining purpose, operational method, and how-to-use workflows.
- **No API Credentials Required to Start:**
  - Works out of the box with manual tracking and custom URL templates from day one.

---

## 🚚 Supported Couriers

| Courier | Integration Type | Tracking URL Template | API Status Check | Webhook Listener |
| :--- | :---: | :--- | :---: | :---: |
| **Steadfast Courier** | API & Webhook | `https://steadfast.com.bd/t/{tracking_number}` | ✅ Supported | ✅ Supported |
| **Pathao Courier** | API & Webhook | `https://merchant.pathao.com/tracking?consignment_id={tracking_number}` | ✅ Supported | ✅ Supported |
| **RedX Courier** | API & Webhook | `https://redx.com.bd/track?trackingId={tracking_number}` | ✅ Supported | ✅ Supported |
| **Paperfly** | URL Template | `https://paperfly.com.bd/tracking.php?tracking_id={tracking_number}` | ℹ️ Portal | ℹ️ Custom |
| **Sundarban Courier** | Manual Template | `https://sundarbancourierltd.com/track/{tracking_number}` | ℹ️ Portal | ℹ️ Manual |
| **SA Paribahan** | Manual Template | `https://saparibahan.com/tracking?cn={tracking_number}` | ℹ️ Portal | ℹ️ Manual |
| **eCourier** | Manual Template | `https://ecourier.com.bd/track-order/?tracking_id={tracking_number}` | ℹ️ Portal | ℹ️ Manual |
| **Merchant Custom** | Custom Template | Administrator Defined (`https://example.com/track?id={tracking_number}`) | ℹ️ Flexible | ℹ️ Flexible |

---

## ⚙️ Installation

1. Download the latest release `wpcalibrate-bangladesh-order-tracker-1.0.0.zip`.
2. In your WordPress admin, navigate to **Plugins > Add New Plugin > Upload Plugin**.
3. Choose the ZIP file and click **Install Now**, then **Activate Plugin**.
4. Confirm WooCommerce is active.
5. Go to **WPCalibrate > BD Order Tracker** in your sidebar menu to configure options and explore the **Overview** tab.

---

## 🚀 Quick Start Guide

### 1. Configure Customer Tracking Page
- The plugin automatically generates a page titled **Track Order** containing `[wpcalibrate_order_tracking]`.
- You can place the shortcode on any custom page and select that page under **WPCalibrate > BD Order Tracker > Tracking Page**.

### 2. Attach Shipments to Orders
1. Open any WooCommerce order under **WooCommerce > Orders**.
2. Scroll to the **Bangladesh Shipment Tracking** panel.
3. Select the courier, enter the tracking/consignment number, set status, and click **Add Shipment**.
4. Check **Send tracking update email to customer** to trigger an instant email alert.

### 3. (Optional) Connect Courier APIs
- Navigate to **WPCalibrate > BD Order Tracker > API Integrations**.
- Enter your **Steadfast**, **Pathao**, or **RedX** merchant credentials.
- Click **Test Connection** to confirm connectivity.
- Use the **Sync Status** button on any order to fetch live courier updates, or enable automated background sync in **General Settings**.

---

## 📚 Documentation

- [Architecture Blueprint](docs/ARCHITECTURE.md) - System architecture, data structures, and security controls.
- [Courier Integration Guide](docs/COURIER_INTEGRATION_GUIDE.md) - Step-by-step API credential setup for Steadfast, Pathao, RedX, and custom adapters.
- [Store Owner User Manual](docs/USER_MANUAL.md) - Complete merchant operational guide.

---

## 📋 Changelog

### 1.0.0 - 2026-10-24
- Initial release.
- Native integration for Steadfast Courier, Pathao, RedX, Paperfly, Sundarban, and SA Paribahan.
- Multi-shipment support per WooCommerce order with chronological event timeline.
- High-Performance Order Storage (HPOS) and Cart/Checkout Blocks full compatibility.
- Customer-facing responsive tracking portal (`[wpcalibrate_order_tracking]`).
- Bangladesh phone number normalization (`01...`, `+8801...`, `8801...`) and anti-enumeration rate limiting.
- Automated WP-Cron background status polling and REST webhook listeners.
- Embedded tracking cards in WooCommerce customer transactional emails.
- Interactive Overview dashboard tab detailing purpose, operational method, and how-to-use guide.
- High-contrast white WPCalibrate branding logo in WordPress admin sidebar with standard 20x20px sizing.

---

## 📄 License & Support

- **License:** Distributed under the [GNU General Public License v2.0 or later](LICENSE).
- **Author:** [WPCalibrate](https://wpcalibrate.com)
- **Marketplace:** [marketplace.wpcalibrate.com](https://marketplace.wpcalibrate.com/)
- **Email Support:** [support@wpcalibrate.com](mailto:support@wpcalibrate.com)
- **WhatsApp / Phone Support:** +447474795976
