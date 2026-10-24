<?php
/**
 * Privacy and Personal Data Handler.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Privacy
 *
 * Integrates with WordPress Privacy Exporter, Eraser, and Privacy Policy guide.
 */
class Privacy {

	protected Settings $settings;

	protected Shipment_Repository $repository;

	public function __construct( Settings $settings, Shipment_Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;

		add_action( 'admin_init', [ $this, 'add_privacy_policy_content' ] );
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'register_exporter' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_eraser' ] );
	}

	/**
	 * Suggest privacy policy content.
	 */
	public function add_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			'<h2>%1$s</h2><p>%2$s</p>',
			esc_html__( 'Shipment and Order Tracking Data', 'wpcalibrate-bangladesh-order-tracker' ),
			esc_html__( 'When you place an order on our store, we collect and store tracking numbers, courier partner names (such as Steadfast Courier, Pathao, RedX, or Paperfly), delivery status updates, and estimated delivery dates to fulfill your shipment and allow you to track your parcel. For shipment delivery and status lookups, tracking identifiers are shared securely with the assigned domestic courier provider.', 'wpcalibrate-bangladesh-order-tracker' )
		);

		wp_add_privacy_policy_content(
			__( 'WPCalibrate Bangladesh Order Tracker', 'wpcalibrate-bangladesh-order-tracker' ),
			wp_kses_post( $content )
		);
	}

	/**
	 * Register personal data exporter.
	 *
	 * @param array<string, array<string, mixed>> $exporters
	 * @return array<string, array<string, mixed>>
	 */
	public function register_exporter( array $exporters ): array {
		$exporters['wpcalibrate-bangladesh-order-tracker'] = [
			'exporter_friendly_name' => __( 'Shipment Tracking Data', 'wpcalibrate-bangladesh-order-tracker' ),
			'callback'               => [ $this, 'export_personal_data' ],
		];
		return $exporters;
	}

	/**
	 * Export customer shipment data.
	 *
	 * @param string $email_address
	 * @param int $page
	 * @return array{data: array<int, array<string, mixed>>, done: bool}
	 */
	public function export_personal_data( string $email_address, int $page = 1 ): array {
		$data_to_export = [];

		$orders = wc_get_orders(
			[
				'billing_email' => $email_address,
				'limit'         => 20,
				'page'          => $page,
			]
		);

		if ( ! empty( $orders ) ) {
			foreach ( $orders as $order ) {
				$shipments = $this->repository->get_shipments( $order );
				foreach ( $shipments as $s ) {
					$item_data = [
						[
							'name'  => __( 'Order ID', 'wpcalibrate-bangladesh-order-tracker' ),
							'value' => $order->get_order_number(),
						],
						[
							'name'  => __( 'Courier Name', 'wpcalibrate-bangladesh-order-tracker' ),
							'value' => $s['courier_name'] ?? '',
						],
						[
							'name'  => __( 'Tracking Number', 'wpcalibrate-bangladesh-order-tracker' ),
							'value' => $s['tracking_number'] ?? '',
						],
						[
							'name'  => __( 'Delivery Status', 'wpcalibrate-bangladesh-order-tracker' ),
							'value' => $s['status_label'] ?? ( $s['status'] ?? '' ),
						],
						[
							'name'  => __( 'Last Updated', 'wpcalibrate-bangladesh-order-tracker' ),
							'value' => $s['updated_at'] ?? '',
						],
					];

					$data_to_export[] = [
						'group_id'    => 'wpcalibrate_shipments',
						'group_label' => __( 'Shipment Tracking Records', 'wpcalibrate-bangladesh-order-tracker' ),
						'item_id'     => 'shipment-' . ( $s['id'] ?? '' ),
						'data'        => $item_data,
					];
				}
			}
		}

		$done = count( $orders ) < 20;

		return [
			'data' => $data_to_export,
			'done' => $done,
		];
	}

	/**
	 * Register personal data eraser.
	 *
	 * @param array<string, array<string, mixed>> $erasers
	 * @return array<string, array<string, mixed>>
	 */
	public function register_eraser( array $erasers ): array {
		$erasers['wpcalibrate-bangladesh-order-tracker'] = [
			'eraser_friendly_name' => __( 'Shipment Tracking Data', 'wpcalibrate-bangladesh-order-tracker' ),
			'callback'             => [ $this, 'erase_personal_data' ],
		];
		return $erasers;
	}

	/**
	 * Erase or anonymize personal shipment tracking data.
	 *
	 * @param string $email_address
	 * @param int $page
	 * @return array{items_removed: int, items_retained: int, messages: array<int, string>, done: bool}
	 */
	public function erase_personal_data( string $email_address, int $page = 1 ): array {
		$items_removed  = 0;
		$items_retained = 0;
		$messages       = [];

		$action = $this->settings->get( 'privacy', 'erase_personal_data', 'anonymize' );

		$orders = wc_get_orders(
			[
				'billing_email' => $email_address,
				'limit'         => 20,
				'page'          => $page,
			]
		);

		if ( ! empty( $orders ) ) {
			foreach ( $orders as $order ) {
				$shipments = $this->repository->get_shipments( $order );
				if ( empty( $shipments ) ) {
					continue;
				}

				if ( 'delete' === $action ) {
					$order->delete_meta_data( Shipment_Repository::META_SHIPMENTS );
					$order->delete_meta_data( Shipment_Repository::META_TRACKING_INDEX );
					$order->save();
					$items_removed += count( $shipments );
				} else {
					// Anonymize.
					foreach ( $shipments as &$s ) {
						$s['tracking_number'] = 'ANONYMIZED';
						$s['internal_notes']  = '';
						if ( ! empty( $s['events'] ) && is_array( $s['events'] ) ) {
							foreach ( $s['events'] as &$e ) {
								$e['note'] = __( 'Personal details removed per erasure request.', 'wpcalibrate-bangladesh-order-tracker' );
							}
						}
					}
					unset( $s, $e );

					$order->update_meta_data( Shipment_Repository::META_SHIPMENTS, $shipments );
					$order->delete_meta_data( Shipment_Repository::META_TRACKING_INDEX );
					$order->save();
					$items_removed += count( $shipments );
				}
			}
		}

		$done = count( $orders ) < 20;

		return [
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		];
	}
}
