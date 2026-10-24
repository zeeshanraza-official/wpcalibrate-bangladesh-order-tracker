# User Manual: WPCalibrate Bangladesh Order Tracker

## 1. Installation & Quick Start

1. Download `wpcalibrate-bangladesh-order-tracker.zip`.
2. In your WordPress admin, go to **Plugins > Add New Plugin > Upload Plugin**.
3. Choose the ZIP file and click **Install Now**, then **Activate Plugin**.
4. Confirm WooCommerce is installed and active.

---

## 2. Setting Up the Customer Tracking Page

1. The plugin automatically creates a **Track Order** page upon activation with the shortcode `[wpcalibrate_order_tracking]`.
2. To use a custom page:
   - Create a new WordPress page (e.g., "Order Status").
   - Add the shortcode block with `[wpcalibrate_order_tracking]`.
   - Publish the page.
   - Go to **WPCalibrate > BD Order Tracker > Tracking Page** and set **Assigned Tracking Page** to your newly created page.

---

## 3. Managing Shipments on Orders

### 3.1 Adding a Shipment
1. Open any WooCommerce order from **WooCommerce > Orders**.
2. Scroll to the **Bangladesh Shipment Tracking** meta box.
3. Under **Add New Shipment / Parcel**:
   - Select your courier service (e.g., Steadfast Courier, Pathao, Sundarban, etc.).
   - Enter the **Tracking / Consignment #**.
   - Select the initial status (e.g., *Ready for Dispatch* or *Shipped*).
   - (Optional) Set an estimated delivery date.
   - (Optional) Add a customer-visible note (e.g., "Handed to Dhaka hub rider").
   - (Optional) Add an internal staff note (e.g., "Rider contact: 017...").
   - Leave **Send tracking update email to customer** checked if you want an instant notification sent to the customer.
4. Click **Add Shipment**.

### 3.2 Multi-Shipment Orders
If an order contains multiple boxes or partial shipments:
- Simply repeat the **Add Shipment** process. Each shipment appears as an independent package with its own courier, tracking code, and timeline history.

### 3.3 Live Status Sync
For couriers with API credentials configured (Steadfast, Pathao, RedX):
- Click the **Sync Status** button on any shipment card to fetch the real-time status directly from the courier portal.

---

## 4. Customer Experience

- **Guest Lookups:** Customers visit your tracking page and enter their Order ID or Tracking Number along with their billing phone number or email address.
- **Account Dashboard:** Logged-in customers can also see real-time shipment status and event timelines directly inside **My Account > Orders > View Order**.
- **Progress Step Bar:** Clearly indicates progress from *Order Placed* &rarr; *Ready* &rarr; *In Transit* &rarr; *Out for Delivery* &rarr; *Delivered*.

---

## 5. Official Support

If you need any technical assistance:
- **Email:** support@wpcalibrate.com
- **Website:** https://wpcalibrate.com
- **Marketplace:** https://marketplace.wpcalibrate.com/
- **Phone / WhatsApp:** +447474795976
