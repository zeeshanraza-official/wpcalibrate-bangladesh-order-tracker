<?php
/**
 * Shipment Repository (HPOS Compatible).
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Shipment_Repository
 *
 * Manages shipment data, multi-package records, and timeline event persistence
 * using WooCommerce Order CRUD APIs for full HPOS and legacy compatibility.
 */
class Shipment_Repository {

	public const META_SHIPMENTS = '_wpcalibrate_bot_shipments';

	public const META_TRACKING_INDEX = '_wpcalibrate_bot_tracking_numbers';

	/**
	 * All supported shipment statuses.
	 *
	 * @return array<string, string>
	 */
	public static function get_statuses(): array {
		return apply_filters(
			'wpcalibrate_bot_shipment_statuses',
			[
				'pending'            => __( 'Shipment Pending', 'wpcalibrate-bangladesh-order-tracker' ),
				'ready_for_dispatch' => __( 'Ready for Dispatch', 'wpcalibrate-bangladesh-order-tracker' ),
				'shipped'            => __( 'Shipped', 'wpcalibrate-bangladesh-order-tracker' ),
				'in_transit'         => __( 'In Transit', 'wpcalibrate-bangladesh-order-tracker' ),
				'out_for_delivery'   => __( 'Out for Delivery', 'wpcalibrate-bangladesh-order-tracker' ),
				'delivered'          => __( 'Delivered', 'wpcalibrate-bangladesh-order-tracker' ),
				'delivery_failed'    => __( 'Delivery Failed', 'wpcalibrate-bangladesh-order-tracker' ),
				'returned'           => __( 'Returned', 'wpcalibrate-bangladesh-order-tracker' ),
				'cancelled'          => __( 'Cancelled', 'wpcalibrate-bangladesh-order-tracker' ),
			]
		);
	}

	/**
	 * Get human readable label for a status key.
	 *
	 * @param string $status
	 * @return string
	 */
	public static function get_status_label( string $status ): string {
		$statuses = self::get_statuses();
		return $statuses[ $status ] ?? ucwords( str_replace( '_', ' ', $status ) );
	}

	/**
	 * Check if status is a final/completed status.
	 *
	 * @param string $status
	 * @return bool
	 */
	public static function is_terminal_status( string $status ): bool {
		return in_array( $status, [ 'delivered', 'returned', 'cancelled' ], true );
	}

	/**
	 * Retrieve all shipments for an order.
	 *
	 * @param int|\WC_Order $order
	 * @return array<int, array<string, mixed>>
	 */
	public function get_shipments( $order ): array {
		$wc_order = is_numeric( $order ) ? wc_get_order( (int) $order ) : $order;
		if ( ! $wc_order instanceof \WC_Order ) {
			return [];
		}

		$raw = $wc_order->get_meta( self::META_SHIPMENTS, true );
		if ( ! is_array( $raw ) ) {
			return [];
		}

		// Ensure array contains valid shipment structures.
		return array_values( array_filter( $raw, 'is_array' ) );
	}

	/**
	 * Retrieve a specific shipment by ID.
	 *
	 * @param int|\WC_Order $order
	 * @param string $shipment_id
	 * @return array<string, mixed>|null
	 */
	public function get_shipment( $order, string $shipment_id ): ?array {
		$shipments = $this->get_shipments( $order );
		foreach ( $shipments as $shipment ) {
			if ( isset( $shipment['id'] ) && $shipment['id'] === $shipment_id ) {
				return $shipment;
			}
		}
		return null;
	}

	/**
	 * Save or update a shipment on an order.
	 *
	 * @param int|\WC_Order $order
	 * @param array<string, mixed> $data
	 * @param string $source
	 * @return array<string, mixed> Saved shipment array.
	 * @throws \InvalidArgumentException
	 */
	public function save_shipment( $order, array $data, string $source = 'manual' ): array {
		$wc_order = is_numeric( $order ) ? wc_get_order( (int) $order ) : $order;
		if ( ! $wc_order instanceof \WC_Order ) {
			throw new \InvalidArgumentException( __( 'Invalid WooCommerce order.', 'wpcalibrate-bangladesh-order-tracker' ) );
		}

		$shipments   = $this->get_shipments( $wc_order );
		$shipment_id = ! empty( $data['id'] ) ? sanitize_key( $data['id'] ) : 'shp_' . wp_generate_password( 10, false );
		$now         = current_time( 'mysql' );

		// Find existing record index if updating.
		$existing_index = null;
		$existing       = null;
		foreach ( $shipments as $idx => $s ) {
			if ( isset( $s['id'] ) && $s['id'] === $shipment_id ) {
				$existing_index = $idx;
				$existing       = $s;
				break;
			}
		}

		$status   = sanitize_key( $data['status'] ?? 'pending' );
		$statuses = self::get_statuses();
		if ( ! isset( $statuses[ $status ] ) ) {
			$status = 'pending';
		}

		$courier_code    = sanitize_key( $data['courier_code'] ?? 'custom' );
		$courier_manager = Courier_Manager::instance();
		$provider        = $courier_manager->get_provider( $courier_code );
		$courier_name    = $provider ? $provider->get_name() : sanitize_text_field( $data['courier_name'] ?? 'Custom Courier' );
		$tracking_number = sanitize_text_field( trim( (string) ( $data['tracking_number'] ?? '' ) ) );

		$custom_url = ! empty( $data['custom_tracking_url'] ) ? esc_url_raw( trim( (string) $data['custom_tracking_url'] ) ) : '';
		$est_date   = ! empty( $data['estimated_delivery'] ) ? sanitize_text_field( (string) $data['estimated_delivery'] ) : '';
		$int_notes  = sanitize_textarea_field( (string) ( $data['internal_notes'] ?? '' ) );

		// Manage timeline events.
		$events = $existing['events'] ?? [];
		if ( ! is_array( $events ) ) {
			$events = [];
		}

		$status_changed = ( null === $existing ) || ( $existing['status'] !== $status );
		$event_note     = sanitize_text_field( (string) ( $data['event_note'] ?? '' ) );

		if ( $status_changed || ! empty( $event_note ) ) {
			$events[] = [
				'id'                  => 'evt_' . wp_generate_password( 8, false ),
				'status'              => $status,
				'status_label'        => self::get_status_label( $status ),
				'timestamp'           => $now,
				'source'              => sanitize_text_field( $source ),
				'note'                => ! empty( $event_note ) ? $event_note : sprintf(
					/* translators: %s: status label */
					__( 'Shipment status updated to %s.', 'wpcalibrate-bangladesh-order-tracker' ),
					self::get_status_label( $status )
				),
				'is_customer_visible' => ! empty( $data['is_customer_visible'] ?? true ),
			];
		}

		$shipment_record = [
			'id'                   => $shipment_id,
			'courier_code'         => $courier_code,
			'courier_name'         => $courier_name,
			'tracking_number'      => $tracking_number,
			'custom_tracking_url'  => $custom_url,
			'status'               => $status,
			'status_label'         => self::get_status_label( $status ),
			'created_at'           => $existing['created_at'] ?? $now,
			'updated_at'           => $now,
			'estimated_delivery'   => $est_date,
			'internal_notes'       => $int_notes,
			'last_notified_status' => $existing['last_notified_status'] ?? '',
			'events'               => $events,
		];

		if ( null !== $existing_index ) {
			$shipments[ $existing_index ] = $shipment_record;
		} else {
			$shipments[] = $shipment_record;
		}

		$this->persist_shipments( $wc_order, $shipments );

		/**
		 * Hook after a shipment has been saved.
		 *
		 * @param \WC_Order $wc_order
		 * @param array $shipment_record
		 * @param bool $status_changed
		 */
		do_action( 'wpcalibrate_bot_shipment_saved', $wc_order, $shipment_record, $status_changed );

		return $shipment_record;
	}

	/**
	 * Append an explicit status event to an existing shipment.
	 *
	 * @param int|\WC_Order $order
	 * @param string $shipment_id
	 * @param string $status
	 * @param string $note
	 * @param string $source
	 * @param bool $customer_visible
	 * @return bool
	 */
	public function add_status_event(
		$order,
		string $shipment_id,
		string $status,
		string $note = '',
		string $source = 'manual',
		bool $customer_visible = true
	): bool {
		$wc_order = is_numeric( $order ) ? wc_get_order( (int) $order ) : $order;
		if ( ! $wc_order instanceof \WC_Order ) {
			return false;
		}

		$shipments = $this->get_shipments( $wc_order );
		$found     = false;

		foreach ( $shipments as &$shipment ) {
			if ( isset( $shipment['id'] ) && $shipment['id'] === $shipment_id ) {
				$now                   = current_time( 'mysql' );
				$status_changed        = ( $shipment['status'] !== $status );
				$shipment['status']    = $status;
				$shipment['status_label'] = self::get_status_label( $status );
				$shipment['updated_at'] = $now;

				if ( ! isset( $shipment['events'] ) || ! is_array( $shipment['events'] ) ) {
					$shipment['events'] = [];
				}

				$shipment['events'][] = [
					'id'                  => 'evt_' . wp_generate_password( 8, false ),
					'status'              => $status,
					'status_label'        => self::get_status_label( $status ),
					'timestamp'           => $now,
					'source'              => sanitize_text_field( $source ),
					'note'                => ! empty( $note ) ? sanitize_text_field( $note ) : sprintf(
						/* translators: %s: status label */
						__( 'Status updated to %s.', 'wpcalibrate-bangladesh-order-tracker' ),
						self::get_status_label( $status )
					),
					'is_customer_visible' => (bool) $customer_visible,
				];

				$found = true;
				break;
			}
		}
		unset( $shipment );

		if ( $found ) {
			$this->persist_shipments( $wc_order, $shipments );
			return true;
		}

		return false;
	}

	/**
	 * Delete a shipment from an order.
	 *
	 * @param int|\WC_Order $order
	 * @param string $shipment_id
	 * @return bool
	 */
	public function delete_shipment( $order, string $shipment_id ): bool {
		$wc_order = is_numeric( $order ) ? wc_get_order( (int) $order ) : $order;
		if ( ! $wc_order instanceof \WC_Order ) {
			return false;
		}

		$shipments = $this->get_shipments( $wc_order );
		$filtered  = [];
		$deleted   = false;

		foreach ( $shipments as $s ) {
			if ( isset( $s['id'] ) && $s['id'] === $shipment_id ) {
				$deleted = true;
				continue;
			}
			$filtered[] = $s;
		}

		if ( $deleted ) {
			$this->persist_shipments( $wc_order, $filtered );
			return true;
		}

		return false;
	}

	/**
	 * Save shipments list and update search index.
	 *
	 * @param \WC_Order $order
	 * @param array<int, array<string, mixed>> $shipments
	 */
	protected function persist_shipments( \WC_Order $order, array $shipments ): void {
		$order->update_meta_data( self::META_SHIPMENTS, array_values( $shipments ) );

		// Maintain tracking number array index for fast lookup.
		$tracking_numbers = [];
		foreach ( $shipments as $s ) {
			if ( ! empty( $s['tracking_number'] ) ) {
				$tracking_numbers[] = sanitize_text_field( (string) $s['tracking_number'] );
			}
		}
		$order->update_meta_data( self::META_TRACKING_INDEX, array_unique( $tracking_numbers ) );

		$order->save();
	}

	/**
	 * Find a WooCommerce order by tracking number (HPOS and legacy safe).
	 *
	 * @param string $tracking_number
	 * @return \WC_Order|null
	 */
	public function find_order_by_tracking_number( string $tracking_number ): ?\WC_Order {
		$clean = trim( $tracking_number );
		if ( empty( $clean ) ) {
			return null;
		}

		// Use wc_get_orders with meta_query to maintain full HPOS compatibility.
		$orders = wc_get_orders(
			[
				'limit'        => 1,
				'meta_key'     => self::META_TRACKING_INDEX,
				'meta_value'   => $clean,
				'meta_compare' => 'LIKE',
			]
		);

		if ( ! empty( $orders ) && $orders[0] instanceof \WC_Order ) {
			return $orders[0];
		}

		return null;
	}
}
