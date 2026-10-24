# Bangladesh Courier Integration Guide

This guide details the integration requirements, endpoint configurations, and credentials needed to connect Bangladesh courier providers to **WPCalibrate Bangladesh Order Tracker**.

---

## 1. Steadfast Courier

Steadfast Courier provides automated tracking and delivery status checking via their merchant portal REST API.

### 1.1 Credentials Setup
1. Log in to your Steadfast Merchant Portal: `https://portal.steadfast.com.bd`
2. Navigate to **Settings > API Settings**.
3. Copy your **Api-Key** and **Secret-Key**.
4. In your WordPress admin, go to **WPCalibrate > BD Order Tracker > API Integrations**.
5. Paste your API Key and Secret Key under **Steadfast Courier**.
6. Click **Test Connection** to verify connectivity.

### 1.2 Webhook Configuration
To receive instant real-time delivery status updates:
- **Webhook URL:** `https://yourdomain.com/wp-json/wpcalibrate-bot/v1/webhook/steadfast`
- Enter this URL under your Steadfast merchant webhook callback settings.

### 1.3 Public Tracking Link
The plugin automatically generates customer tracking links using:
`https://steadfast.com.bd/t/{tracking_number}`

---

## 2. Pathao Courier

Pathao Courier uses an OAuth 2.0 password grant flow to issue temporary Bearer tokens for API access.

### 2.1 Credentials Setup
1. Obtain your API credentials from your Pathao account representative or Developer portal.
2. In your WordPress admin, navigate to **WPCalibrate > BD Order Tracker > API Integrations > Pathao Courier**.
3. Select Environment: **Live / Production** or **Sandbox / Testing**.
4. Fill in:
   - **Client ID**
   - **Client Secret**
   - **Merchant Email / Username**
   - **Account Password**
5. Click **Test Connection**. The plugin will request an OAuth access token and confirm success.

### 2.2 Webhook Configuration
- **Webhook URL:** `https://yourdomain.com/wp-json/wpcalibrate-bot/v1/webhook/pathao`
- If you configure a Webhook Secret in Pathao, enter the same secret in the plugin settings.

---

## 3. RedX Courier

RedX offers tracking and logistics endpoints authenticated via an API Bearer token.

### 3.1 Credentials Setup
1. Log in to your RedX Merchant Dashboard.
2. Navigate to Developer / API settings and generate your **API-ACCESS-TOKEN**.
3. In WordPress admin, go to **API Integrations > RedX Courier**.
4. Paste the Bearer token into the **Access Token** field.
5. Click **Test Connection**.

### 3.2 Webhook Configuration
- **Webhook URL:** `https://yourdomain.com/wp-json/wpcalibrate-bot/v1/webhook/redx`

---

## 4. Manual and Custom Couriers

For logistics providers without public APIs (such as Sundarban, SA Paribahan, Karatoa, or local merchant delivery staff):
1. Navigate to **WPCalibrate > BD Order Tracker > Couriers & URLs**.
2. Select or enter the courier name.
3. Use tracking URL templates with `{tracking_number}`:
   - **Sundarban:** `https://sundarbancourierltd.com/track/{tracking_number}`
   - **SA Paribahan:** `https://saparibahan.com/tracking?cn={tracking_number}`
   - **eCourier:** `https://ecourier.com.bd/track-order/?tracking_id={tracking_number}`
   - **Paperfly:** `https://paperfly.com.bd/tracking.php?tracking_id={tracking_number}`

---

## 5. Developer API: Adding a Custom Courier Provider

You can register third-party courier adapters by implementing `WPCalibrate\BangladeshOrderTracker\Integrations\Courier_Provider_Interface` and hooking into `wpcalibrate_bot_register_courier_providers`:

```php
add_action( 'wpcalibrate_bot_register_courier_providers', function( $manager ) {
    class My_Custom_Courier implements \WPCalibrate\BangladeshOrderTracker\Integrations\Courier_Provider_Interface {
        public function get_id(): string { return 'my_courier'; }
        public function get_name(): string { return 'My Custom Courier'; }
        public function get_default_tracking_url(): string { return 'https://mycourier.com/t/{tracking_number}'; }
        public function build_tracking_url( string $num, string $custom = '' ): string {
            return esc_url_raw( 'https://mycourier.com/t/' . rawurlencode( $num ) );
        }
        public function supports_api(): bool { return false; }
        public function supports_webhooks(): bool { return false; }
        public function get_settings_fields(): array { return []; }
        public function test_connection( array $credentials ): array { return ['success' => true, 'message' => 'OK']; }
        public function track_shipment( string $num, array $creds = [] ): array { return ['success' => false, 'normalized_status' => 'pending', 'provider_status' => 'manual', 'status_message' => 'Manual', 'timestamp' => current_time('mysql')]; }
        public function normalize_status( string $raw ): string { return 'in_transit'; }
        public function validate_webhook( $req, array $creds = [] ): bool { return false; }
        public function parse_webhook( $req ): array { return ['success' => false, 'tracking_number' => '', 'normalized_status' => '', 'note' => '']; }
    }

    $manager->register_provider( new My_Custom_Courier() );
} );
```
