<?php
/**
 * Order Meta Box View.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables from caller:
 *
 * @var \WC_Order $order
 * @var array<int, array<string, mixed>> $shipments
 * @var array<string, string> $couriers
 * @var array<string, string> $statuses
 */

$courier_manager = \WPCalibrate\BangladeshOrderTracker\Courier_Manager::instance();
?>

<div class="wpcalibrate-metabox-wrap" data-order-id="<?php echo esc_attr( (string) $order->get_id() ); ?>">

	<!-- Existing Shipments List -->
	<div class="wpcalibrate-shipments-list" id="wpcalibrate_shipments_container">
		<?php if ( empty( $shipments ) ) : ?>
			<div class="wpcalibrate-empty-shipments" id="wpcalibrate_no_shipments_msg">
				<p><?php esc_html_e( 'No shipments have been added to this order yet.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
			</div>
		<?php else : ?>
			<?php foreach ( $shipments as $shipment ) : ?>
				<?php
				$s_id       = esc_attr( $shipment['id'] ?? '' );
				$c_code     = esc_attr( $shipment['courier_code'] ?? 'manual' );
				$c_name     = esc_html( $shipment['courier_name'] ?? 'Courier' );
				$tracking   = esc_html( $shipment['tracking_number'] ?? '' );
				$status     = sanitize_key( $shipment['status'] ?? 'pending' );
				$status_lbl = esc_html( $shipment['status_label'] ?? $status );
				$est_date   = ! empty( $shipment['estimated_delivery'] ) ? esc_html( $shipment['estimated_delivery'] ) : '';
				$track_url  = $courier_manager->resolve_tracking_url( $c_code, (string) ( $shipment['tracking_number'] ?? '' ), (string) ( $shipment['custom_tracking_url'] ?? '' ) );
				$provider   = $courier_manager->get_provider( $c_code );
				$events     = is_array( $shipment['events'] ?? null ) ? $shipment['events'] : [];
				?>
				<div class="wpcalibrate-shipment-card" id="wpcalibrate_shipment_<?php echo $s_id; ?>" data-shipment-id="<?php echo $s_id; ?>">
					<div class="wpcalibrate-shipment-header">
						<div class="wpcalibrate-shipment-title-area">
							<span class="wpcalibrate-badge wpcalibrate-badge-<?php echo esc_attr( $status ); ?>">
								<?php echo $status_lbl; ?>
							</span>
							<strong class="wpcalibrate-courier-title"><?php echo $c_name; ?>:</strong>
							<span class="wpcalibrate-tracking-code">
								<?php if ( ! empty( $track_url ) ) : ?>
									<a href="<?php echo esc_url( $track_url ); ?>" target="_blank" rel="noopener noreferrer" class="wpcalibrate-external-track-link">
										<?php echo $tracking; ?>
										<span class="dashicons dashicons-external"></span>
									</a>
								<?php else : ?>
									<code><?php echo $tracking; ?></code>
								<?php endif; ?>
							</span>
						</div>
						<div class="wpcalibrate-shipment-actions">
							<?php if ( $provider && $provider->supports_api() ) : ?>
								<button type="button" class="button button-small wpcalibrate-sync-btn" data-shipment-id="<?php echo $s_id; ?>" title="<?php esc_attr_e( 'Fetch latest status from courier API', 'wpcalibrate-bangladesh-order-tracker' ); ?>">
									<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Sync Status', 'wpcalibrate-bangladesh-order-tracker' ); ?>
								</button>
							<?php endif; ?>
							<button type="button" class="button button-small button-link-delete wpcalibrate-delete-btn" data-shipment-id="<?php echo $s_id; ?>">
								<span class="dashicons dashicons-trash"></span>
							</button>
						</div>
					</div>

					<div class="wpcalibrate-shipment-meta">
						<?php if ( ! empty( $est_date ) ) : ?>
							<span><strong><?php esc_html_e( 'Estimated Delivery:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> <?php echo $est_date; ?></span> &bull;
						<?php endif; ?>
						<span><strong><?php esc_html_e( 'Last Updated:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> <?php echo esc_html( $shipment['updated_at'] ?? '' ); ?></span>
					</div>

					<?php if ( ! empty( $shipment['internal_notes'] ) ) : ?>
						<div class="wpcalibrate-internal-note-box">
							<span class="dashicons dashicons-lock"></span>
							<em><?php esc_html_e( 'Internal Staff Note:', 'wpcalibrate-bangladesh-order-tracker' ); ?></em> <?php echo esc_html( $shipment['internal_notes'] ); ?>
						</div>
					<?php endif; ?>

					<!-- Events Timeline Toggle -->
					<?php if ( ! empty( $events ) ) : ?>
						<details class="wpcalibrate-timeline-details">
							<summary><?php printf( esc_html__( 'Status History (%d events)', 'wpcalibrate-bangladesh-order-tracker' ), count( $events ) ); ?></summary>
							<ul class="wpcalibrate-timeline-list">
								<?php foreach ( array_reverse( $events ) as $evt ) : ?>
									<li>
										<span class="wpcalibrate-timeline-time"><?php echo esc_html( $evt['timestamp'] ?? '' ); ?></span> &mdash;
										<strong><?php echo esc_html( $evt['status_label'] ?? ( $evt['status'] ?? '' ) ); ?></strong>:
										<span><?php echo esc_html( $evt['note'] ?? '' ); ?></span>
										<?php if ( empty( $evt['is_customer_visible'] ) ) : ?>
											<span class="wpcalibrate-private-tag">(<?php esc_html_e( 'Admin Only', 'wpcalibrate-bangladesh-order-tracker' ); ?>)</span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</details>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<!-- Add Shipment Form -->
	<div class="wpcalibrate-add-shipment-panel">
		<h4 class="wpcalibrate-add-title">
			<span class="dashicons dashicons-plus-alt2"></span>
			<?php esc_html_e( 'Add New Shipment / Parcel', 'wpcalibrate-bangladesh-order-tracker' ); ?>
		</h4>

		<div class="wpcalibrate-form-row">
			<div class="wpcalibrate-form-col">
				<label for="wpcalibrate_courier_code"><strong><?php esc_html_e( 'Courier Service', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong></label>
				<select id="wpcalibrate_courier_code" name="wpcalibrate_courier_code" class="widefat">
					<?php foreach ( $couriers as $code => $name ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="wpcalibrate-form-col">
				<label for="wpcalibrate_tracking_number"><strong><?php esc_html_e( 'Tracking / Consignment #', 'wpcalibrate-bangladesh-order-tracker' ); ?> *</strong></label>
				<input type="text" id="wpcalibrate_tracking_number" name="wpcalibrate_tracking_number" class="widefat" placeholder="e.g. 15BAEB8A or DS0407254C48WT" required>
			</div>
			<div class="wpcalibrate-form-col">
				<label for="wpcalibrate_status"><strong><?php esc_html_e( 'Initial Status', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong></label>
				<select id="wpcalibrate_status" name="wpcalibrate_status" class="widefat">
					<?php foreach ( $statuses as $st_key => $st_name ) : ?>
						<option value="<?php echo esc_attr( $st_key ); ?>" <?php selected( $st_key, 'ready_for_dispatch' ); ?>>
							<?php echo esc_html( $st_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="wpcalibrate-form-row">
			<div class="wpcalibrate-form-col">
				<label for="wpcalibrate_est_delivery"><?php esc_html_e( 'Estimated Delivery Date', 'wpcalibrate-bangladesh-order-tracker' ); ?></label>
				<input type="date" id="wpcalibrate_est_delivery" name="wpcalibrate_est_delivery" class="widefat">
			</div>
			<div class="wpcalibrate-form-col">
				<label for="wpcalibrate_custom_url"><?php esc_html_e( 'Custom Tracking URL (Optional Override)', 'wpcalibrate-bangladesh-order-tracker' ); ?></label>
				<input type="url" id="wpcalibrate_custom_url" name="wpcalibrate_custom_url" class="widefat" placeholder="https://...">
			</div>
		</div>

		<div class="wpcalibrate-form-row">
			<div class="wpcalibrate-form-col">
				<label for="wpcalibrate_event_note"><?php esc_html_e( 'Customer Status Note (Visible on tracking page & emails)', 'wpcalibrate-bangladesh-order-tracker' ); ?></label>
				<input type="text" id="wpcalibrate_event_note" name="wpcalibrate_event_note" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Handed over to delivery rider in Dhaka hub', 'wpcalibrate-bangladesh-order-tracker' ); ?>">
			</div>
			<div class="wpcalibrate-form-col">
				<label for="wpcalibrate_internal_notes"><?php esc_html_e( 'Internal Staff Note (Never shown to customer)', 'wpcalibrate-bangladesh-order-tracker' ); ?></label>
				<input type="text" id="wpcalibrate_internal_notes" name="wpcalibrate_internal_notes" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Rider phone: 01700000000', 'wpcalibrate-bangladesh-order-tracker' ); ?>">
			</div>
		</div>

		<div class="wpcalibrate-form-row wpcalibrate-submit-row">
			<label class="wpcalibrate-notify-check">
				<input type="checkbox" id="wpcalibrate_send_email" name="wpcalibrate_send_email" value="1" checked>
				<?php esc_html_e( 'Send tracking update email to customer', 'wpcalibrate-bangladesh-order-tracker' ); ?>
			</label>
			<button type="button" class="button button-primary" id="wpcalibrate_add_shipment_btn">
				<?php esc_html_e( 'Add Shipment', 'wpcalibrate-bangladesh-order-tracker' ); ?>
			</button>
		</div>

		<div class="wpcalibrate-ajax-feedback" id="wpcalibrate_metabox_feedback"></div>
	</div>

</div>
