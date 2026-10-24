<?php
/**
 * Customer Tracking Results View.
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
 * @var \WPCalibrate\BangladeshOrderTracker\Courier_Manager $courier_manager
 * @var bool $show_timeline
 * @var bool $show_link
 */

$step_keys = [
	'pending'            => 1,
	'ready_for_dispatch' => 2,
	'shipped'            => 3,
	'in_transit'         => 3,
	'out_for_delivery'   => 4,
	'delivered'          => 5,
];
?>

<div class="wpcalibrate-result-card" aria-live="polite">
	<div class="wpcalibrate-result-header">
		<div>
			<span class="wpcalibrate-order-tag">
				<?php
				printf(
					/* translators: %s: Order ID */
					esc_html__( 'Order #%s', 'wpcalibrate-bangladesh-order-tracker' ),
					esc_html( $order->get_order_number() )
				);
				?>
			</span>
			<span class="wpcalibrate-order-date">
				<?php
				$created = $order->get_date_created();
				if ( $created ) {
					echo esc_html( $created->date_i18n( get_option( 'date_format' ) ) );
				}
				?>
			</span>
		</div>
		<div class="wpcalibrate-wc-status">
			<span class="wpcalibrate-badge wpcalibrate-badge-order">
				<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
			</span>
		</div>
	</div>

	<?php if ( empty( $shipments ) ) : ?>
		<div class="wpcalibrate-no-shipments-notice">
			<div class="wpcalibrate-info-icon">&#8505;</div>
			<div>
				<h3><?php esc_html_e( 'Shipment Being Prepared', 'wpcalibrate-bangladesh-order-tracker' ); ?></h3>
				<p><?php esc_html_e( 'Your order has been received and is currently being packed. Tracking details will appear here as soon as your parcel is handed over to our courier partner.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
			</div>
		</div>
	<?php else : ?>
		<div class="wpcalibrate-shipments-grid">
			<?php foreach ( $shipments as $idx => $shipment ) : ?>
				<?php
				$c_code     = (string) ( $shipment['courier_code'] ?? 'manual' );
				$c_name     = esc_html( $shipment['courier_name'] ?? 'Courier' );
				$tracking   = esc_html( $shipment['tracking_number'] ?? '' );
				$status     = sanitize_key( $shipment['status'] ?? 'pending' );
				$status_lbl = esc_html( $shipment['status_label'] ?? $status );
				$est_date   = ! empty( $shipment['estimated_delivery'] ) ? esc_html( $shipment['estimated_delivery'] ) : '';
				$track_url  = $show_link ? $courier_manager->resolve_tracking_url( $c_code, (string) ( $shipment['tracking_number'] ?? '' ), (string) ( $shipment['custom_tracking_url'] ?? '' ) ) : '';
				$events     = is_array( $shipment['events'] ?? null ) ? $shipment['events'] : [];
				$current_step = $step_keys[ $status ] ?? 2;
				?>
				<div class="wpcalibrate-parcel-box">
					<div class="wpcalibrate-parcel-header">
						<div class="wpcalibrate-parcel-title">
							<h3>
								<?php
								if ( count( $shipments ) > 1 ) {
									printf(
										/* translators: 1: package index, 2: courier name */
										esc_html__( 'Package %1$d &mdash; %2$s', 'wpcalibrate-bangladesh-order-tracker' ),
										$idx + 1,
										$c_name
									);
								} else {
									echo $c_name;
								}
								?>
							</h3>
							<div class="wpcalibrate-parcel-meta">
								<span><strong><?php esc_html_e( 'Tracking #:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> <?php echo $tracking; ?></span>
								<?php if ( ! empty( $est_date ) ) : ?>
									&bull; <span><strong><?php esc_html_e( 'Est. Delivery:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> <?php echo $est_date; ?></span>
								<?php endif; ?>
							</div>
						</div>
						<div class="wpcalibrate-parcel-status">
							<span class="wpcalibrate-badge wpcalibrate-badge-<?php echo esc_attr( $status ); ?>">
								<?php echo $status_lbl; ?>
							</span>
							<?php if ( ! empty( $track_url ) ) : ?>
								<a href="<?php echo esc_url( $track_url ); ?>" target="_blank" rel="noopener noreferrer" class="wpcalibrate-courier-btn">
									<?php esc_html_e( 'Open Courier Tracking', 'wpcalibrate-bangladesh-order-tracker' ); ?> &rarr;
								</a>
							<?php endif; ?>
						</div>
					</div>

					<!-- Visual Step Bar for Normal Progression -->
					<?php if ( ! in_array( $status, [ 'delivery_failed', 'returned', 'cancelled' ], true ) ) : ?>
						<div class="wpcalibrate-steps-wrapper" role="progressbar" aria-valuenow="<?php echo esc_attr( (string) $current_step ); ?>" aria-valuemin="1" aria-valuemax="5">
							<div class="wpcalibrate-step <?php echo $current_step >= 1 ? 'is-complete' : ''; ?>">
								<div class="wpcalibrate-step-circle">1</div>
								<span class="wpcalibrate-step-label"><?php esc_html_e( 'Order Placed', 'wpcalibrate-bangladesh-order-tracker' ); ?></span>
							</div>
							<div class="wpcalibrate-step <?php echo $current_step >= 2 ? 'is-complete' : ''; ?>">
								<div class="wpcalibrate-step-circle">2</div>
								<span class="wpcalibrate-step-label"><?php esc_html_e( 'Ready', 'wpcalibrate-bangladesh-order-tracker' ); ?></span>
							</div>
							<div class="wpcalibrate-step <?php echo $current_step >= 3 ? 'is-complete' : ''; ?>">
								<div class="wpcalibrate-step-circle">3</div>
								<span class="wpcalibrate-step-label"><?php esc_html_e( 'In Transit', 'wpcalibrate-bangladesh-order-tracker' ); ?></span>
							</div>
							<div class="wpcalibrate-step <?php echo $current_step >= 4 ? 'is-complete' : ''; ?>">
								<div class="wpcalibrate-step-circle">4</div>
								<span class="wpcalibrate-step-label"><?php esc_html_e( 'Out for Delivery', 'wpcalibrate-bangladesh-order-tracker' ); ?></span>
							</div>
							<div class="wpcalibrate-step <?php echo $current_step >= 5 ? 'is-complete' : ''; ?>">
								<div class="wpcalibrate-step-circle">5</div>
								<span class="wpcalibrate-step-label"><?php esc_html_e( 'Delivered', 'wpcalibrate-bangladesh-order-tracker' ); ?></span>
							</div>
						</div>
					<?php endif; ?>

					<!-- Chronological Customer Event History -->
					<?php if ( $show_timeline && ! empty( $events ) ) : ?>
						<div class="wpcalibrate-history-section">
							<h4 class="wpcalibrate-history-title"><?php esc_html_e( 'Shipment Progress History', 'wpcalibrate-bangladesh-order-tracker' ); ?></h4>
							<ol class="wpcalibrate-timeline-flow">
								<?php
								$filtered_events = array_filter(
									$events,
									function ( $e ) {
										return ! empty( $e['is_customer_visible'] );
									}
								);
								foreach ( array_reverse( $filtered_events ) as $evt ) :
									?>
									<li class="wpcalibrate-timeline-item">
										<div class="wpcalibrate-timeline-marker"></div>
										<div class="wpcalibrate-timeline-content">
											<div class="wpcalibrate-timeline-head">
												<span class="wpcalibrate-timeline-status"><?php echo esc_html( $evt['status_label'] ?? $evt['status'] ); ?></span>
												<time class="wpcalibrate-timeline-date"><?php echo esc_html( $evt['timestamp'] ?? '' ); ?></time>
											</div>
											<?php if ( ! empty( $evt['note'] ) ) : ?>
												<p class="wpcalibrate-timeline-note"><?php echo esc_html( $evt['note'] ); ?></p>
											<?php endif; ?>
										</div>
									</li>
								<?php endforeach; ?>
							</ol>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
