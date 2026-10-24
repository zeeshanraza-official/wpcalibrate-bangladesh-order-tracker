<?php
/**
 * Cron and Scheduled Tasks Handler.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cron
 *
 * Manages periodic background status synchronization for active shipments.
 */
class Cron {

	public const CRON_HOOK = 'wpcalibrate_bot_sync_active_shipments';

	protected Settings $settings;

	protected Shipment_Repository $repository;

	public function __construct( Settings $settings, Shipment_Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;

		add_action( self::CRON_HOOK, [ $this, 'run_status_sync' ] );
		add_action( 'update_option_' . Settings::OPTION_KEY, [ $this, 'on_settings_update' ], 10, 2 );
	}

	/**
	 * Synchronize cron schedule when settings change.
	 *
	 * @param mixed $old_value
	 * @param mixed $new_value
	 */
	public function on_settings_update( $old_value, $new_value ): void {
		$enabled = 'yes' === ( $new_value['general']['enable_cron_sync'] ?? 'no' );
		$recur   = $new_value['general']['cron_frequency'] ?? 'hourly';

		$timestamp = wp_next_scheduled( self::CRON_HOOK );

		if ( ! $enabled && $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		} elseif ( $enabled ) {
			if ( ! $timestamp ) {
				wp_schedule_event( time() + 300, $recur, self::CRON_HOOK );
			}
		}
	}

	/**
	 * Execute background status polling for active shipments.
	 */
	public function run_status_sync(): void {
		if ( 'yes' !== $this->settings->get( 'general', 'enable_cron_sync', 'no' ) ) {
			return;
		}

		$courier_manager = Courier_Manager::instance();

		// Fetch recent orders that have tracking numbers and are not completed/cancelled.
		$orders = wc_get_orders(
			[
				'limit'        => 20,
				'status'       => [ 'processing', 'on-hold', 'pending' ],
				'meta_key'     => Shipment_Repository::META_TRACKING_INDEX,
				'meta_compare' => 'EXISTS',
				'orderby'      => 'date',
				'order'        => 'DESC',
			]
		);

		if ( empty( $orders ) ) {
			return;
		}

		foreach ( $orders as $order ) {
			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			$shipments = $this->repository->get_shipments( $order );
			foreach ( $shipments as $shipment ) {
				$current_status = sanitize_key( $shipment['status'] ?? 'pending' );

				// Skip terminal statuses (delivered, returned, cancelled).
				if ( Shipment_Repository::is_terminal_status( $current_status ) ) {
					continue;
				}

				$courier_code = (string) ( $shipment['courier_code'] ?? '' );
				$provider     = $courier_manager->get_provider( $courier_code );

				if ( ! $provider || ! $provider->supports_api() ) {
					continue;
				}

				$credentials = (array) $this->settings->get( 'integrations', $courier_code, [] );
				$tracking_no = (string) ( $shipment['tracking_number'] ?? '' );

				if ( empty( $tracking_no ) ) {
					continue;
				}

				$result = $provider->track_shipment( $tracking_no, $credentials );
				if ( $result['success'] ) {
					$new_status = $result['normalized_status'];
					if ( $new_status !== $current_status ) {
						$this->repository->add_status_event(
							$order,
							(string) $shipment['id'],
							$new_status,
							$result['status_message'],
							$courier_code . '_cron_sync',
							true
						);

						// Update WC Order status if delivered.
						if ( 'delivered' === $new_status && 'yes' === $this->settings->get( 'general', 'sync_order_status', 'no' ) ) {
							$target_status = (string) $this->settings->get( 'general', 'delivered_order_status', 'wc-completed' );
							$clean_status  = str_replace( 'wc-', '', $target_status );
							if ( ! $order->has_status( $clean_status ) ) {
								$order->update_status(
									$clean_status,
									sprintf(
										/* translators: %s: courier name */
										__( 'Shipment marked as Delivered by %s background sync.', 'wpcalibrate-bangladesh-order-tracker' ),
										$provider->get_name()
									)
								);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Unschedule all cron events upon plugin deactivation.
	 */
	public static function unschedule_all(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}
