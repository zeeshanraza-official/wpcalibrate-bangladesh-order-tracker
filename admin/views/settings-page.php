<?php
/**
 * Settings Page View.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables available from caller:
 *
 * @var string $current_tab
 * @var array<string, string> $tabs
 * @var array<string, mixed> $all_settings
 * @var array<string, \WPCalibrate\BangladeshOrderTracker\Integrations\Courier_Provider_Interface> $providers
 * @var array{type: string, message: string}|null $notice
 */
?>
<div class="wrap wpcalibrate-wrap">
	<div class="wpcalibrate-header">
		<div class="wpcalibrate-brand-identity">
			<img src="<?php echo esc_url( WPCALIBRATE_BOT_URL . 'branding/icon-dark.png' ); ?>" alt="WPCalibrate" class="wpcalibrate-brand-logo" />
			<div>
				<h1><?php esc_html_e( 'Bangladesh Order Tracker', 'wpcalibrate-bangladesh-order-tracker' ); ?></h1>
				<p class="wpcalibrate-subtitle"><?php esc_html_e( 'Manage courier tracking, automated status synchronization, customer notifications, and tracking portals.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
			</div>
		</div>
		<div class="wpcalibrate-header-badges">
			<span class="wpcalibrate-version-badge">v<?php echo esc_html( WPCALIBRATE_BOT_VERSION ); ?></span>
			<span class="wpcalibrate-hpos-badge">
				<?php
				if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
					esc_html_e( 'HPOS Active', 'wpcalibrate-bangladesh-order-tracker' );
				} else {
					esc_html_e( 'Legacy Post Meta', 'wpcalibrate-bangladesh-order-tracker' );
				}
				?>
			</span>
		</div>
	</div>

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper wpcalibrate-nav-tabs" aria-label="<?php esc_attr_e( 'Settings Navigation', 'wpcalibrate-bangladesh-order-tracker' ); ?>">
		<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker&tab=' . $tab_key ) ); ?>"
			   class="nav-tab <?php echo ( $current_tab === $tab_key ) ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="wpcalibrate-tab-content">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker&tab=' . $current_tab ) ); ?>">
			<?php wp_nonce_field( 'wpcalibrate_bot_save_settings_action', 'wpcalibrate_bot_nonce' ); ?>

			<?php if ( 'overview' === $current_tab ) : ?>
				<div class="wpcalibrate-overview-container">
					<!-- Hero Welcome & Purpose Banner -->
					<div class="wpcalibrate-overview-hero">
						<div class="wpcalibrate-hero-content">
							<span class="wpcalibrate-hero-badge"><?php esc_html_e( 'Bangladesh E-Commerce Logistics', 'wpcalibrate-bangladesh-order-tracker' ); ?></span>
							<h2><?php esc_html_e( 'Purpose & Overview of WPCalibrate Bangladesh Order Tracker', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
							<p>
								<?php esc_html_e( 'Built exclusively for Bangladeshi online merchants, this extension solves the operational friction of Cash-on-Delivery (COD) fulfillment. By providing transparent, automated, and self-service parcel tracking, you eliminate repetitive customer inquiries, increase buyer trust, and significantly reduce delivery returns (RTO).', 'wpcalibrate-bangladesh-order-tracker' ); ?>
							</p>
						</div>
						<div class="wpcalibrate-hero-stats">
							<div class="wpcalibrate-stat-card">
								<span class="dashicons dashicons-location"></span>
								<strong><?php esc_html_e( 'Domestic Couriers', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong>
								<p><?php esc_html_e( 'Steadfast, Pathao, RedX, Paperfly, Sundarban, SA Paribahan & Custom', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</div>
							<div class="wpcalibrate-stat-card">
								<span class="dashicons dashicons-shield"></span>
								<strong><?php esc_html_e( 'Anti-Enumeration', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong>
								<p><?php esc_html_e( 'Secured guest verification with Bangladesh phone normalization & IP rate limiting', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</div>
							<div class="wpcalibrate-stat-card">
								<span class="dashicons dashicons-database"></span>
								<strong><?php esc_html_e( 'HPOS Compatible', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong>
								<p><?php esc_html_e( 'Built with native WooCommerce High-Performance Order Storage support', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Operational Method (How the System Operates) -->
					<div class="wpcalibrate-section-card">
						<div class="wpcalibrate-section-header">
							<span class="dashicons dashicons-randomize"></span>
							<div>
								<h2><?php esc_html_e( 'Operational Method (How It Works)', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
								<p class="description"><?php esc_html_e( 'The complete lifecycle from warehouse packaging to final recipient delivery.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</div>
						</div>

						<div class="wpcalibrate-workflow-grid">
							<div class="wpcalibrate-workflow-step">
								<div class="wpcalibrate-step-number">1</div>
								<h3><?php esc_html_e( '1. Shipment Assignment', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
								<p><?php esc_html_e( 'When staff package an order, they enter the courier and tracking code in the WooCommerce Order screen meta box. Multiple shipments per order are fully supported for partial deliveries.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</div>
							<div class="wpcalibrate-workflow-step">
								<div class="wpcalibrate-step-number">2</div>
								<h3><?php esc_html_e( '2. Real-Time Status Sync', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
								<p><?php esc_html_e( 'The plugin syncs delivery updates from courier APIs (Steadfast, Pathao, RedX) via automated WP-Cron or incoming REST webhooks as packages move from hubs to riders.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</div>
							<div class="wpcalibrate-workflow-step">
								<div class="wpcalibrate-step-number">3</div>
								<h3><?php esc_html_e( '3. Customer Self-Service', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
								<p><?php esc_html_e( 'Customers check real-time progress on your branded tracking portal using their Order ID and phone number. A visual progress step bar displays status from dispatch to doorstep.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</div>
							<div class="wpcalibrate-workflow-step">
								<div class="wpcalibrate-step-number">4</div>
								<h3><?php esc_html_e( '4. Auto-Fulfillment', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
								<p><?php esc_html_e( 'When the courier reports Delivered, the plugin can automatically mark the WooCommerce order as Completed and dispatch closing confirmation emails without manual admin intervention.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</div>
						</div>
					</div>

					<!-- How to Use Guide -->
					<div class="wpcalibrate-section-card">
						<div class="wpcalibrate-section-header">
							<span class="dashicons dashicons-book"></span>
							<div>
								<h2><?php esc_html_e( 'How to Use (Step-by-Step Store Owner Guide)', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
								<p class="description"><?php esc_html_e( 'Quick instructions to configure and operate tracking on your WooCommerce store.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</div>
						</div>

						<div class="wpcalibrate-guide-list">
							<div class="wpcalibrate-guide-item">
								<div class="wpcalibrate-guide-icon"><span class="dashicons dashicons-admin-page"></span></div>
								<div class="wpcalibrate-guide-body">
									<h4><?php esc_html_e( 'Step 1: Setup Customer Tracking Page', 'wpcalibrate-bangladesh-order-tracker' ); ?></h4>
									<p>
										<?php
										printf(
											/* translators: %s: shortcode */
											esc_html__( 'Place the %s shortcode on any WordPress page (e.g., yoursite.com/track-order). Then link that page under the "Tracking Page" tab in these settings.', 'wpcalibrate-bangladesh-order-tracker' ),
											'<code>[wpcalibrate_order_tracking]</code>'
										);
										?>
									</p>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker&tab=tracking_page' ) ); ?>" class="button button-secondary button-small">
										<?php esc_html_e( 'Configure Tracking Page &rarr;', 'wpcalibrate-bangladesh-order-tracker' ); ?>
									</a>
								</div>
							</div>

							<div class="wpcalibrate-guide-item">
								<div class="wpcalibrate-guide-icon"><span class="dashicons dashicons-car"></span></div>
								<div class="wpcalibrate-guide-body">
									<h4><?php esc_html_e( 'Step 2: Choose Your Couriers & Tracking URLs', 'wpcalibrate-bangladesh-order-tracker' ); ?></h4>
									<p><?php esc_html_e( 'Set your default domestic courier (Steadfast, Pathao, RedX, Sundarban, etc.). You can customize tracking URL templates or add manual couriers without requiring any API keys.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker&tab=couriers' ) ); ?>" class="button button-secondary button-small">
										<?php esc_html_e( 'Manage Couriers &rarr;', 'wpcalibrate-bangladesh-order-tracker' ); ?>
									</a>
								</div>
							</div>

							<div class="wpcalibrate-guide-item">
								<div class="wpcalibrate-guide-icon"><span class="dashicons dashicons-rest-api"></span></div>
								<div class="wpcalibrate-guide-body">
									<h4><?php esc_html_e( 'Step 3: (Optional) Connect Courier APIs for Automated Sync', 'wpcalibrate-bangladesh-order-tracker' ); ?></h4>
									<p><?php esc_html_e( 'If you hold merchant accounts with Steadfast, Pathao, or RedX, enter your API keys to enable one-click status syncing and live background tracking updates.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker&tab=integrations' ) ); ?>" class="button button-secondary button-small">
										<?php esc_html_e( 'Setup API Credentials &rarr;', 'wpcalibrate-bangladesh-order-tracker' ); ?>
									</a>
								</div>
							</div>

							<div class="wpcalibrate-guide-item">
								<div class="wpcalibrate-guide-icon"><span class="dashicons dashicons-cart"></span></div>
								<div class="wpcalibrate-guide-body">
									<h4><?php esc_html_e( 'Step 4: Attach Shipments to Orders', 'wpcalibrate-bangladesh-order-tracker' ); ?></h4>
									<p><?php esc_html_e( 'Navigate to WooCommerce > Orders. Open any order and locate the "Bangladesh Shipment Tracking" panel. Enter the tracking number, select status, and click Add Shipment.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
									<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order' ) ); ?>" class="button button-secondary button-small">
										<?php esc_html_e( 'Go to WooCommerce Orders &rarr;', 'wpcalibrate-bangladesh-order-tracker' ); ?>
									</a>
								</div>
							</div>
						</div>
					</div>

					<!-- Quick Jump Navigation Cards -->
					<div class="wpcalibrate-quick-links-grid">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker&tab=general' ) ); ?>" class="wpcalibrate-quick-card">
							<span class="dashicons dashicons-admin-generic"></span>
							<strong><?php esc_html_e( 'General Settings', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong>
							<p><?php esc_html_e( 'Default statuses & order sync rules', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker&tab=notifications' ) ); ?>" class="wpcalibrate-quick-card">
							<span class="dashicons dashicons-email-alt"></span>
							<strong><?php esc_html_e( 'Notifications', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong>
							<p><?php esc_html_e( 'Customer email triggers & templates', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker&tab=diagnostics' ) ); ?>" class="wpcalibrate-quick-card">
							<span class="dashicons dashicons-admin-tools"></span>
							<strong><?php esc_html_e( 'Diagnostics', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong>
							<p><?php esc_html_e( 'System health & webhook endpoints', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker&tab=support' ) ); ?>" class="wpcalibrate-quick-card">
							<span class="dashicons dashicons-sos"></span>
							<strong><?php esc_html_e( 'Help & Support', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong>
							<p><?php esc_html_e( 'WhatsApp, email & marketplace links', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
						</a>
					</div>
				</div>

			<?php elseif ( 'general' === $current_tab ) : ?>
				<div class="wpcalibrate-section-card">
					<h2><?php esc_html_e( 'General Order Tracking Settings', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="wpcalibrate_default_status"><?php esc_html_e( 'Default Shipment Status', 'wpcalibrate-bangladesh-order-tracker' ); ?></label>
							</th>
							<td>
								<select name="wpcalibrate_bot[general][default_status]" id="wpcalibrate_default_status">
									<?php foreach ( \WPCalibrate\BangladeshOrderTracker\Shipment_Repository::get_statuses() as $s_key => $s_label ) : ?>
										<option value="<?php echo esc_attr( $s_key ); ?>" <?php selected( $all_settings['general']['default_status'] ?? '', $s_key ); ?>>
											<?php echo esc_html( $s_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Initial shipment status assigned when a tracking number is added.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Synchronize WooCommerce Order Status', 'wpcalibrate-bangladesh-order-tracker' ); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="wpcalibrate_bot[general][sync_order_status]" value="yes" <?php checked( $all_settings['general']['sync_order_status'] ?? 'no', 'yes' ); ?>>
									<?php esc_html_e( 'Automatically mark WooCommerce order as Completed when shipment status becomes Delivered.', 'wpcalibrate-bangladesh-order-tracker' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpcalibrate_cron_sync"><?php esc_html_e( 'Automated Background Sync', 'wpcalibrate-bangladesh-order-tracker' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" name="wpcalibrate_bot[general][enable_cron_sync]" value="yes" <?php checked( $all_settings['general']['enable_cron_sync'] ?? 'no', 'yes' ); ?>>
									<?php esc_html_e( 'Enable WP-Cron background status polling for active shipments (Steadfast, Pathao, RedX).', 'wpcalibrate-bangladesh-order-tracker' ); ?>
								</label>
								<div style="margin-top: 8px;">
									<select name="wpcalibrate_bot[general][cron_frequency]" id="wpcalibrate_cron_sync">
										<option value="hourly" <?php selected( $all_settings['general']['cron_frequency'] ?? 'hourly', 'hourly' ); ?>><?php esc_html_e( 'Hourly (Recommended)', 'wpcalibrate-bangladesh-order-tracker' ); ?></option>
										<option value="twicedaily" <?php selected( $all_settings['general']['cron_frequency'] ?? 'hourly', 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'wpcalibrate-bangladesh-order-tracker' ); ?></option>
										<option value="daily" <?php selected( $all_settings['general']['cron_frequency'] ?? 'hourly', 'daily' ); ?>><?php esc_html_e( 'Daily', 'wpcalibrate-bangladesh-order-tracker' ); ?></option>
									</select>
								</div>
								<p class="description"><?php esc_html_e( 'Only active shipments (not delivered/returned/cancelled) are synced to conserve server resources.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

			<?php elseif ( 'tracking_page' === $current_tab ) : ?>
				<div class="wpcalibrate-section-card">
					<h2><?php esc_html_e( 'Customer Tracking Page & Security', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<p class="description">
						<?php
						printf(
							/* translators: %s: shortcode */
							esc_html__( 'Display the public customer order tracking form on any WordPress page using the %s shortcode.', 'wpcalibrate-bangladesh-order-tracker' ),
							'<code>[wpcalibrate_order_tracking]</code>'
						);
						?>
					</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="wpcalibrate_tracking_page_id"><?php esc_html_e( 'Assigned Tracking Page', 'wpcalibrate-bangladesh-order-tracker' ); ?></label>
							</th>
							<td>
								<?php
								wp_dropdown_pages(
									[
										'name'              => 'wpcalibrate_bot[tracking_page][tracking_page_id]',
										'id'                => 'wpcalibrate_tracking_page_id',
										'show_option_none'  => __( '&mdash; Select Page &mdash;', 'wpcalibrate-bangladesh-order-tracker' ),
										'selected'          => (int) ( $all_settings['tracking_page']['tracking_page_id'] ?? 0 ),
									]
								);
								?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Guest Lookup Verification', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" name="wpcalibrate_bot[tracking_page][allow_phone_lookup]" value="yes" <?php checked( $all_settings['tracking_page']['allow_phone_lookup'] ?? 'yes', 'yes' ); ?>>
										<?php esc_html_e( 'Allow verification via Billing Phone Number (Bangladesh numbers formatted 01XXXXXXXXX or +8801XXXXXXXXX).', 'wpcalibrate-bangladesh-order-tracker' ); ?>
									</label><br>
									<label>
										<input type="checkbox" name="wpcalibrate_bot[tracking_page][allow_email_lookup]" value="yes" <?php checked( $all_settings['tracking_page']['allow_email_lookup'] ?? 'yes', 'yes' ); ?>>
										<?php esc_html_e( 'Allow verification via Billing Email Address.', 'wpcalibrate-bangladesh-order-tracker' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Require Login', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="wpcalibrate_bot[tracking_page][require_login]" value="yes" <?php checked( $all_settings['tracking_page']['require_login'] ?? 'no', 'yes' ); ?>>
									<?php esc_html_e( 'Restrict tracking page only to logged-in customers (disables guest lookup).', 'wpcalibrate-bangladesh-order-tracker' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Display Options', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" name="wpcalibrate_bot[tracking_page][show_timeline]" value="yes" <?php checked( $all_settings['tracking_page']['show_timeline'] ?? 'yes', 'yes' ); ?>>
										<?php esc_html_e( 'Show visual shipment progress timeline with timestamps.', 'wpcalibrate-bangladesh-order-tracker' ); ?>
									</label><br>
									<label>
										<input type="checkbox" name="wpcalibrate_bot[tracking_page][show_courier_link]" value="yes" <?php checked( $all_settings['tracking_page']['show_courier_link'] ?? 'yes', 'yes' ); ?>>
										<?php esc_html_e( 'Show direct link to external courier tracking portal when available.', 'wpcalibrate-bangladesh-order-tracker' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpcalibrate_rate_limit"><?php esc_html_e( 'Rate Limiting & Anti-Enumeration', 'wpcalibrate-bangladesh-order-tracker' ); ?></label>
							</th>
							<td>
								<input type="number" name="wpcalibrate_bot[tracking_page][rate_limit_max_lookup]" id="wpcalibrate_rate_limit" value="<?php echo esc_attr( $all_settings['tracking_page']['rate_limit_max_lookup'] ?? 15 ); ?>" min="3" max="100" class="small-text">
								<?php esc_html_e( 'max failed lookup attempts per IP address within', 'wpcalibrate-bangladesh-order-tracker' ); ?>
								<input type="number" name="wpcalibrate_bot[tracking_page][rate_limit_window_mins]" value="<?php echo esc_attr( $all_settings['tracking_page']['rate_limit_window_mins'] ?? 15 ); ?>" min="1" max="120" class="small-text">
								<?php esc_html_e( 'minutes before temporary lockout.', 'wpcalibrate-bangladesh-order-tracker' ); ?>
								<p class="description"><?php esc_html_e( 'Protects against order ID guessing and brute-force tracking enumeration.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

			<?php elseif ( 'couriers' === $current_tab ) : ?>
				<div class="wpcalibrate-section-card">
					<h2><?php esc_html_e( 'Configured Couriers & URL Templates', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="wpcalibrate_default_courier"><?php esc_html_e( 'Default Courier', 'wpcalibrate-bangladesh-order-tracker' ); ?></label>
							</th>
							<td>
								<select name="wpcalibrate_bot[couriers][default_courier]" id="wpcalibrate_default_courier">
									<?php foreach ( $providers as $p_id => $provider ) : ?>
										<option value="<?php echo esc_attr( $p_id ); ?>" <?php selected( $all_settings['couriers']['default_courier'] ?? 'steadfast', $p_id ); ?>>
											<?php echo esc_html( $provider->get_name() ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>

					<h3><?php esc_html_e( 'Available Domestic Couriers', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Courier', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
								<th><?php esc_html_e( 'Type', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
								<th><?php esc_html_e( 'Default Tracking URL Pattern', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
								<th><?php esc_html_e( 'API Support', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $providers as $p_id => $provider ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $provider->get_name() ); ?></strong> (<code><?php echo esc_html( $p_id ); ?></code>)</td>
									<td><?php echo $provider->supports_api() ? esc_html__( 'Automated / API', 'wpcalibrate-bangladesh-order-tracker' ) : esc_html__( 'Manual / URL Template', 'wpcalibrate-bangladesh-order-tracker' ); ?></td>
									<td><code><?php echo esc_html( $provider->get_default_tracking_url() ?: __( 'Merchant defined', 'wpcalibrate-bangladesh-order-tracker' ) ); ?></code></td>
									<td>
										<?php if ( $provider->supports_api() ) : ?>
											<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php esc_html_e( 'Supported', 'wpcalibrate-bangladesh-order-tracker' ); ?>
										<?php else : ?>
											<span class="dashicons dashicons-minus" style="color: #999;"></span> <?php esc_html_e( 'Manual Entry', 'wpcalibrate-bangladesh-order-tracker' ); ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			<?php elseif ( 'notifications' === $current_tab ) : ?>
				<div class="wpcalibrate-section-card">
					<h2><?php esc_html_e( 'Customer Notifications', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Customer Email Updates', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="wpcalibrate_bot[notifications][enable_email_notifications]" value="yes" <?php checked( $all_settings['notifications']['enable_email_notifications'] ?? 'yes', 'yes' ); ?>>
									<?php esc_html_e( 'Send dedicated customer email notification when shipment status changes.', 'wpcalibrate-bangladesh-order-tracker' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Notify on Statuses', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
							<td>
								<fieldset>
									<?php
									$selected_notify = (array) ( $all_settings['notifications']['notify_on_statuses'] ?? [] );
									foreach ( \WPCalibrate\BangladeshOrderTracker\Shipment_Repository::get_statuses() as $s_key => $s_label ) :
										?>
										<label style="display: inline-block; margin-right: 15px; margin-bottom: 6px;">
											<input type="checkbox" name="wpcalibrate_bot[notifications][notify_on_statuses][]" value="<?php echo esc_attr( $s_key ); ?>" <?php checked( in_array( $s_key, $selected_notify, true ) ); ?>>
											<?php echo esc_html( $s_label ); ?>
										</label>
									<?php endforeach; ?>
								</fieldset>
								<p class="description"><?php esc_html_e( 'Emails will only be sent for the checked shipment statuses.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'WooCommerce Order Emails', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="wpcalibrate_bot[notifications][attach_to_wc_emails]" value="yes" <?php checked( $all_settings['notifications']['attach_to_wc_emails'] ?? 'yes', 'yes' ); ?>>
									<?php esc_html_e( 'Include shipment tracking details in standard WooCommerce Completed and Invoice emails.', 'wpcalibrate-bangladesh-order-tracker' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpcalibrate_email_subject"><?php esc_html_e( 'Email Subject Template', 'wpcalibrate-bangladesh-order-tracker' ); ?></label>
							</th>
							<td>
								<input type="text" name="wpcalibrate_bot[notifications][email_subject]" id="wpcalibrate_email_subject" value="<?php echo esc_attr( $all_settings['notifications']['email_subject'] ?? '' ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Available tags: {order_number}, {site_title}, {status}, {courier}, {tracking_number}.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

			<?php elseif ( 'integrations' === $current_tab ) : ?>
				<div class="wpcalibrate-section-card">
					<h2><?php esc_html_e( 'Bangladesh Courier API Integrations', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Configure API credentials for automated status synchronization and live tracking lookups.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>

					<!-- Steadfast Courier -->
					<div class="wpcalibrate-provider-box">
						<div class="wpcalibrate-provider-box-header">
							<h3><?php esc_html_e( 'Steadfast Courier', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
							<button type="button" class="button wpcalibrate-test-btn" data-provider="steadfast">
								<?php esc_html_e( 'Test Connection', 'wpcalibrate-bangladesh-order-tracker' ); ?>
							</button>
						</div>
						<div class="wpcalibrate-test-result" id="wpcalibrate_test_result_steadfast"></div>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label><?php esc_html_e( 'API Key', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<input type="text" name="wpcalibrate_bot[integrations][steadfast][api_key]" value="<?php echo esc_attr( $all_settings['integrations']['steadfast']['api_key'] ?? '' ); ?>" class="regular-text" autocomplete="off">
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Secret Key', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<input type="password" name="wpcalibrate_bot[integrations][steadfast][secret_key]" value="<?php echo ! empty( $all_settings['integrations']['steadfast']['secret_key'] ) ? '********' : ''; ?>" class="regular-text" autocomplete="new-password">
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Webhook Callback URL', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<code><?php echo esc_url( rest_url( 'wpcalibrate-bot/v1/webhook/steadfast' ) ); ?></code>
									<p class="description"><?php esc_html_e( 'Enter this URL in your Steadfast merchant portal for instant webhook updates.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
								</td>
							</tr>
						</table>
					</div>

					<!-- Pathao Courier -->
					<div class="wpcalibrate-provider-box">
						<div class="wpcalibrate-provider-box-header">
							<h3><?php esc_html_e( 'Pathao Courier', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
							<button type="button" class="button wpcalibrate-test-btn" data-provider="pathao">
								<?php esc_html_e( 'Test Connection', 'wpcalibrate-bangladesh-order-tracker' ); ?>
							</button>
						</div>
						<div class="wpcalibrate-test-result" id="wpcalibrate_test_result_pathao"></div>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Environment', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<select name="wpcalibrate_bot[integrations][pathao][environment]">
										<option value="live" <?php selected( $all_settings['integrations']['pathao']['environment'] ?? 'live', 'live' ); ?>><?php esc_html_e( 'Live / Production', 'wpcalibrate-bangladesh-order-tracker' ); ?></option>
										<option value="sandbox" <?php selected( $all_settings['integrations']['pathao']['environment'] ?? 'live', 'sandbox' ); ?>><?php esc_html_e( 'Sandbox / Testing', 'wpcalibrate-bangladesh-order-tracker' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Client ID', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<input type="text" name="wpcalibrate_bot[integrations][pathao][client_id]" value="<?php echo esc_attr( $all_settings['integrations']['pathao']['client_id'] ?? '' ); ?>" class="regular-text" autocomplete="off">
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Client Secret', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<input type="password" name="wpcalibrate_bot[integrations][pathao][client_secret]" value="<?php echo ! empty( $all_settings['integrations']['pathao']['client_secret'] ) ? '********' : ''; ?>" class="regular-text" autocomplete="new-password">
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Merchant Email / Username', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<input type="email" name="wpcalibrate_bot[integrations][pathao][username]" value="<?php echo esc_attr( $all_settings['integrations']['pathao']['username'] ?? '' ); ?>" class="regular-text" autocomplete="off">
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Password', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<input type="password" name="wpcalibrate_bot[integrations][pathao][password]" value="<?php echo ! empty( $all_settings['integrations']['pathao']['password'] ) ? '********' : ''; ?>" class="regular-text" autocomplete="new-password">
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Webhook Callback URL', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<code><?php echo esc_url( rest_url( 'wpcalibrate-bot/v1/webhook/pathao' ) ); ?></code>
								</td>
							</tr>
						</table>
					</div>

					<!-- RedX Courier -->
					<div class="wpcalibrate-provider-box">
						<div class="wpcalibrate-provider-box-header">
							<h3><?php esc_html_e( 'RedX Courier', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
							<button type="button" class="button wpcalibrate-test-btn" data-provider="redx">
								<?php esc_html_e( 'Test Connection', 'wpcalibrate-bangladesh-order-tracker' ); ?>
							</button>
						</div>
						<div class="wpcalibrate-test-result" id="wpcalibrate_test_result_redx"></div>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Environment', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<select name="wpcalibrate_bot[integrations][redx][environment]">
										<option value="live" <?php selected( $all_settings['integrations']['redx']['environment'] ?? 'live', 'live' ); ?>><?php esc_html_e( 'Live / Production', 'wpcalibrate-bangladesh-order-tracker' ); ?></option>
										<option value="sandbox" <?php selected( $all_settings['integrations']['redx']['environment'] ?? 'live', 'sandbox' ); ?>><?php esc_html_e( 'Sandbox / Testing', 'wpcalibrate-bangladesh-order-tracker' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Access Token (Bearer)', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<input type="password" name="wpcalibrate_bot[integrations][redx][access_token]" value="<?php echo ! empty( $all_settings['integrations']['redx']['access_token'] ) ? '********' : ''; ?>" class="regular-text" autocomplete="new-password">
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Webhook Callback URL', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<code><?php echo esc_url( rest_url( 'wpcalibrate-bot/v1/webhook/redx' ) ); ?></code>
								</td>
							</tr>
						</table>
					</div>

					<!-- Paperfly -->
					<div class="wpcalibrate-provider-box">
						<div class="wpcalibrate-provider-box-header">
							<h3><?php esc_html_e( 'Paperfly Courier', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
						</div>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Paperfly Wings Username', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<input type="text" name="wpcalibrate_bot[integrations][paperfly][username]" value="<?php echo esc_attr( $all_settings['integrations']['paperfly']['username'] ?? '' ); ?>" class="regular-text" autocomplete="off">
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Password', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
								<td>
									<input type="password" name="wpcalibrate_bot[integrations][paperfly][password]" value="<?php echo ! empty( $all_settings['integrations']['paperfly']['password'] ) ? '********' : ''; ?>" class="regular-text" autocomplete="new-password">
								</td>
							</tr>
						</table>
					</div>
				</div>

			<?php elseif ( 'privacy' === $current_tab ) : ?>
				<div class="wpcalibrate-section-card">
					<h2><?php esc_html_e( 'Privacy, Personal Data & Retention', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Data Removal on Uninstall', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="wpcalibrate_bot[privacy][delete_data_on_uninstall]" value="yes" <?php checked( $all_settings['privacy']['delete_data_on_uninstall'] ?? 'no', 'yes' ); ?>>
									<span style="color: #c00; font-weight: 600;"><?php esc_html_e( 'Permanently delete all tracking settings and order shipment records upon plugin deletion.', 'wpcalibrate-bangladesh-order-tracker' ); ?></span>
								</label>
								<p class="description"><?php esc_html_e( 'Default is disabled. By default, shipment data and settings remain preserved when deactivating or deleting the plugin.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'WordPress Personal Data Eraser', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
							<td>
								<select name="wpcalibrate_bot[privacy][erase_personal_data]">
									<option value="anonymize" <?php selected( $all_settings['privacy']['erase_personal_data'] ?? 'anonymize', 'anonymize' ); ?>><?php esc_html_e( 'Anonymize tracking numbers and internal notes', 'wpcalibrate-bangladesh-order-tracker' ); ?></option>
									<option value="delete" <?php selected( $all_settings['privacy']['erase_personal_data'] ?? 'anonymize', 'delete' ); ?>><?php esc_html_e( 'Completely delete customer shipment tracking data', 'wpcalibrate-bangladesh-order-tracker' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Applied when an administrator runs WordPress core "Erase Personal Data" for a customer.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

			<?php elseif ( 'diagnostics' === $current_tab ) : ?>
				<div class="wpcalibrate-section-card">
					<h2><?php esc_html_e( 'System Status & Diagnostics', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<table class="widefat striped">
						<tbody>
							<tr>
								<td><strong><?php esc_html_e( 'Plugin Version', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong></td>
								<td><?php echo esc_html( WPCALIBRATE_BOT_VERSION ); ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'PHP Version', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong></td>
								<td><?php echo esc_html( PHP_VERSION ); ?> (Targeted: 8.2+)</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'WordPress Version', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong></td>
								<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'WooCommerce Version', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong></td>
								<td><?php echo esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : 'N/A' ); ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'High-Performance Order Storage (HPOS)', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong></td>
								<td>
									<?php if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) : ?>
										<span style="color: #46b450; font-weight: bold;">&#10004; <?php esc_html_e( 'Enabled (Compatible)', 'wpcalibrate-bangladesh-order-tracker' ); ?></span>
									<?php else : ?>
										<span><?php esc_html_e( 'Disabled (Legacy post storage active)', 'wpcalibrate-bangladesh-order-tracker' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'cURL / OpenSSL', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong></td>
								<td>
									<?php echo function_exists( 'curl_version' ) ? esc_html__( 'Available', 'wpcalibrate-bangladesh-order-tracker' ) : esc_html__( 'Unavailable', 'wpcalibrate-bangladesh-order-tracker' ); ?>
								</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Timezone', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong></td>
								<td><?php echo esc_html( wp_timezone_string() ); ?> (UTC: <?php echo esc_html( gmdate( 'Y-m-d H:i:s' ) ); ?>)</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Active Couriers Registered', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong></td>
								<td><?php echo esc_html( (string) count( $providers ) ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>

			<?php elseif ( 'license' === $current_tab ) : ?>
				<div class="wpcalibrate-section-card">
					<h2><?php esc_html_e( 'License Management', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Enter your WPCalibrate license key to activate automated updates and priority customer support.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
					<?php
					$license = $this->settings->get_license();
					?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="wpcalibrate_license_key"><?php esc_html_e( 'License Key', 'wpcalibrate-bangladesh-order-tracker' ); ?></label></th>
							<td>
								<input type="text" name="wpcalibrate_bot_license_key" id="wpcalibrate_license_key" value="<?php echo ! empty( $license['key'] ) ? '********' : ''; ?>" class="regular-text" placeholder="WPCB-XXXX-XXXX-XXXX" autocomplete="off">
								<span class="wpcalibrate-badge <?php echo 'active' === $license['status'] ? 'wpcalibrate-badge-delivered' : 'wpcalibrate-badge-pending'; ?>">
									<?php echo esc_html( ucfirst( $license['status'] ) ); ?>
								</span>
								<p class="description">
									<?php
									printf(
										/* translators: %s: marketplace url */
										esc_html__( 'Obtain or renew your key at %s.', 'wpcalibrate-bangladesh-order-tracker' ),
										'<a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer">marketplace.wpcalibrate.com</a>'
									);
									?>
								</p>
							</td>
						</tr>
					</table>
				</div>

			<?php elseif ( 'support' === $current_tab ) : ?>
				<div class="wpcalibrate-section-card">
					<h2><?php esc_html_e( 'WPCalibrate Support & Assistance', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<p><?php esc_html_e( 'Need help setting up Bangladesh courier APIs, customizing tracking templates, or reporting an issue? Reach out to our engineering support team:', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
					<div class="wpcalibrate-support-grid">
						<div class="wpcalibrate-support-item">
							<span class="dashicons dashicons-email-alt"></span>
							<h3><?php esc_html_e( 'Email Support', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
							<p><a href="mailto:support@wpcalibrate.com">support@wpcalibrate.com</a></p>
						</div>
						<div class="wpcalibrate-support-item">
							<span class="dashicons dashicons-phone"></span>
							<h3><?php esc_html_e( 'WhatsApp / Phone', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
							<p><a href="tel:+447474795976">+447474795976</a></p>
						</div>
						<div class="wpcalibrate-support-item">
							<span class="dashicons dashicons-admin-site-alt3"></span>
							<h3><?php esc_html_e( 'Official Website', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
							<p><a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer">wpcalibrate.com</a></p>
						</div>
						<div class="wpcalibrate-support-item">
							<span class="dashicons dashicons-cart"></span>
							<h3><?php esc_html_e( 'Marketplace & Extensions', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
							<p><a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer">marketplace.wpcalibrate.com</a></p>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( 'support' !== $current_tab && 'diagnostics' !== $current_tab && 'overview' !== $current_tab ) : ?>
				<p class="submit">
					<button type="submit" name="wpcalibrate_bot_save_settings" class="button button-primary button-hero">
						<?php esc_html_e( 'Save Settings', 'wpcalibrate-bangladesh-order-tracker' ); ?>
					</button>
				</p>
			<?php endif; ?>
		</form>
	</div>
</div>
