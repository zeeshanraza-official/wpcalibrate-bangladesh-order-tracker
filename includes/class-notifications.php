<?php
/**
 * Notifications Management Class.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Notifications
 *
 * Handles customer tracking emails, status change alerts, and embedding
 * shipment information in WooCommerce order emails without duplicates.
 */
class Notifications {

	protected Settings $settings;

	protected Shipment_Repository $repository;

	public function __construct( Settings $settings, Shipment_Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;

		$this->init_hooks();
	}

	protected function init_hooks(): void {
		// Hook into shipment saved event.
		add_action( 'wpcalibrate_bot_shipment_saved', [ $this, 'maybe_dispatch_status_change_email' ], 10, 3 );

		// Embed tracking details in standard WooCommerce transactional emails.
		add_action( 'woocommerce_email_order_meta', [ $this, 'render_email_tracking_meta' ], 20, 3 );
	}

	/**
	 * Check settings and dispatch customer email if shipment status has changed.
	 *
	 * @param \WC_Order $order
	 * @param array<string, mixed> $shipment
	 * @param bool $status_changed
	 */
	public function maybe_dispatch_status_change_email( \WC_Order $order, array $shipment, bool $status_changed ): void {
		if ( ! $status_changed ) {
			return;
		}

		$enabled = 'yes' === $this->settings->get( 'notifications', 'enable_email_notifications', 'yes' );
		if ( ! $enabled ) {
			return;
		}

		$notify_statuses = (array) $this->settings->get( 'notifications', 'notify_on_statuses', [] );
		$current_status  = sanitize_key( $shipment['status'] ?? 'pending' );

		if ( ! in_array( $current_status, $notify_statuses, true ) ) {
			return;
		}

		// Prevent duplicate email dispatch for the same status.
		if ( isset( $shipment['last_notified_status'] ) && $shipment['last_notified_status'] === $current_status ) {
			return;
		}

		$this->dispatch_shipment_status_email( $order, $shipment );
	}

	/**
	 * Build and send customer tracking update email.
	 *
	 * @param \WC_Order $order
	 * @param array<string, mixed> $shipment
	 * @return bool
	 */
	public function dispatch_shipment_status_email( \WC_Order $order, array $shipment ): bool {
		$customer_email = $order->get_billing_email();
		if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
			return false;
		}

		$courier_manager = Courier_Manager::instance();
		$courier_name    = esc_html( $shipment['courier_name'] ?? 'Courier' );
		$tracking_number = esc_html( $shipment['tracking_number'] ?? '' );
		$status_label    = esc_html( $shipment['status_label'] ?? $shipment['status'] );
		$tracking_url    = $courier_manager->resolve_tracking_url(
			(string) ( $shipment['courier_code'] ?? '' ),
			(string) ( $shipment['tracking_number'] ?? '' ),
			(string) ( $shipment['custom_tracking_url'] ?? '' )
		);

		// Subject template replacement.
		$subject_template = (string) $this->settings->get( 'notifications', 'email_subject', '' );
		if ( empty( $subject_template ) ) {
			$subject_template = __( 'Tracking update for your order #{order_number}', 'wpcalibrate-bangladesh-order-tracker' );
		}

		$subject = str_replace(
			[ '{order_number}', '{site_title}', '{status}', '{courier}', '{tracking_number}' ],
			[ $order->get_order_number(), get_bloginfo( 'name' ), $status_label, $courier_name, $tracking_number ],
			$subject_template
		);

		$heading = (string) $this->settings->get( 'notifications', 'email_heading', __( 'Shipment Update', 'wpcalibrate-bangladesh-order-tracker' ) );

		// Build email HTML content.
		ob_start();
		?>
		<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #333333; line-height: 1.5; padding: 10px 0;">
			<h2 style="color: #111111; margin-top: 0; font-size: 20px;"><?php echo esc_html( $heading ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: 1: customer name, 2: order number */
					esc_html__( 'Hi %1$s, here is the latest tracking update for your order #%2$s:', 'wpcalibrate-bangladesh-order-tracker' ),
					esc_html( $order->get_billing_first_name() ?: __( 'Customer', 'wpcalibrate-bangladesh-order-tracker' ) ),
					esc_html( $order->get_order_number() )
				);
				?>
			</p>

			<div style="background-color: #f8f9fa; border: 1px solid #e9ecef; border-left: 4px solid #1062fe; border-radius: 4px; padding: 16px; margin: 20px 0;">
				<p style="margin: 0 0 8px;"><strong><?php esc_html_e( 'Status:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> <span style="display: inline-block; padding: 2px 8px; background: #e0f2fe; color: #0369a1; font-weight: 600; border-radius: 4px;"><?php echo $status_label; ?></span></p>
				<p style="margin: 0 0 8px;"><strong><?php esc_html_e( 'Courier:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> <?php echo $courier_name; ?></p>
				<p style="margin: 0 0 8px;"><strong><?php esc_html_e( 'Tracking / Consignment #:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> <code><?php echo $tracking_number; ?></code></p>
				<?php if ( ! empty( $shipment['estimated_delivery'] ) ) : ?>
					<p style="margin: 0 0 8px;"><strong><?php esc_html_e( 'Estimated Delivery:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> <?php echo esc_html( $shipment['estimated_delivery'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $tracking_url ) ) : ?>
					<p style="margin: 12px 0 0;">
						<a href="<?php echo esc_url( $tracking_url ); ?>" style="display: inline-block; background-color: #1062fe; color: #ffffff; text-decoration: none; padding: 8px 16px; border-radius: 4px; font-weight: 600; font-size: 14px;" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Track on Courier Website', 'wpcalibrate-bangladesh-order-tracker' ); ?> &rarr;
						</a>
					</p>
				<?php endif; ?>
			</div>

			<?php
			$page_id = (int) $this->settings->get( 'tracking_page', 'tracking_page_id', 0 );
			if ( $page_id > 0 ) :
				?>
				<p>
					<?php esc_html_e( 'You can also check real-time progress on our website tracking portal:', 'wpcalibrate-bangladesh-order-tracker' ); ?>
					<br>
					<a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" style="color: #1062fe;">
						<?php echo esc_url( get_permalink( $page_id ) ); ?>
					</a>
				</p>
			<?php endif; ?>

			<p style="color: #6c757d; font-size: 13px; margin-top: 24px;">
				<?php
				printf(
					/* translators: %s: site name */
					esc_html__( 'Thank you for shopping with %s!', 'wpcalibrate-bangladesh-order-tracker' ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>
		</div>
		<?php
		$message = (string) ob_get_clean();

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		// Wrap in standard WooCommerce email template if WC_Emails is available.
		if ( function_exists( 'WC' ) && isset( WC()->mailer ) ) {
			$wrapped_message = WC()->mailer()->wrap_message( $heading, $message );
			$sent            = wc_mail( $customer_email, $subject, $wrapped_message, $headers );
		} else {
			$sent = wp_mail( $customer_email, $subject, $message, $headers );
		}

		if ( $sent ) {
			// Update last_notified_status on shipment to prevent duplicate emails.
			$this->update_last_notified( $order, (string) ( $shipment['id'] ?? '' ), (string) ( $shipment['status'] ?? '' ) );
		}

		return (bool) $sent;
	}

	/**
	 * Update the last notified status on a shipment record.
	 *
	 * @param \WC_Order $order
	 * @param string $shipment_id
	 * @param string $status
	 */
	protected function update_last_notified( \WC_Order $order, string $shipment_id, string $status ): void {
		$shipments = $this->repository->get_shipments( $order );
		$updated   = false;

		foreach ( $shipments as &$s ) {
			if ( isset( $s['id'] ) && $s['id'] === $shipment_id ) {
				$s['last_notified_status'] = $status;
				$updated                   = true;
				break;
			}
		}
		unset( $s );

		if ( $updated ) {
			$order->update_meta_data( Shipment_Repository::META_SHIPMENTS, $shipments );
			$order->save();
		}
	}

	/**
	 * Append shipment tracking details to standard WooCommerce customer emails.
	 *
	 * @param \WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 */
	public function render_email_tracking_meta( $order, $sent_to_admin, $plain_text = false ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$attach = 'yes' === $this->settings->get( 'notifications', 'attach_to_wc_emails', 'yes' );
		if ( ! $attach ) {
			return;
		}

		$shipments = $this->repository->get_shipments( $order );
		if ( empty( $shipments ) ) {
			return;
		}

		$courier_manager = Courier_Manager::instance();

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'SHIPMENT TRACKING:', 'wpcalibrate-bangladesh-order-tracker' ) . "\n";
			foreach ( $shipments as $s ) {
				$track_url = $courier_manager->resolve_tracking_url( (string) $s['courier_code'], (string) $s['tracking_number'], (string) ( $s['custom_tracking_url'] ?? '' ) );
				echo esc_html( $s['courier_name'] ) . ': ' . esc_html( $s['tracking_number'] ) . ' (' . esc_html( $s['status_label'] ?? $s['status'] ) . ")\n";
				if ( ! empty( $track_url ) ) {
					echo esc_url( $track_url ) . "\n";
				}
			}
			echo "\n";
			return;
		}

		?>
		<div style="margin: 20px 0; padding: 14px 18px; background-color: #fdfdfd; border: 1px solid #e5e5e5; border-radius: 4px;">
			<h3 style="margin-top: 0; color: #111; font-size: 16px;"><?php esc_html_e( 'Shipment Tracking', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="border-bottom: 2px solid #eee; text-align: left;">
						<th style="padding: 6px 0;"><?php esc_html_e( 'Courier', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
						<th style="padding: 6px 0;"><?php esc_html_e( 'Tracking #', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
						<th style="padding: 6px 0;"><?php esc_html_e( 'Status', 'wpcalibrate-bangladesh-order-tracker' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $shipments as $s ) : ?>
						<?php
						$track_url = $courier_manager->resolve_tracking_url(
							(string) ( $s['courier_code'] ?? '' ),
							(string) ( $s['tracking_number'] ?? '' ),
							(string) ( $s['custom_tracking_url'] ?? '' )
						);
						?>
						<tr style="border-bottom: 1px solid #f0f0f0;">
							<td style="padding: 8px 0;"><strong><?php echo esc_html( $s['courier_name'] ?? '' ); ?></strong></td>
							<td style="padding: 8px 0;">
								<?php if ( ! empty( $track_url ) ) : ?>
									<a href="<?php echo esc_url( $track_url ); ?>" target="_blank" rel="noopener noreferrer" style="color: #1062fe; text-decoration: underline;">
										<?php echo esc_html( $s['tracking_number'] ?? '' ); ?>
									</a>
								<?php else : ?>
									<code><?php echo esc_html( $s['tracking_number'] ?? '' ); ?></code>
								<?php endif; ?>
							</td>
							<td style="padding: 8px 0;"><?php echo esc_html( $s['status_label'] ?? ( $s['status'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
