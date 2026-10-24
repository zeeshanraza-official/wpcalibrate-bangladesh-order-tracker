<?php
/**
 * REST API Webhook Endpoint Handler.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class REST_API
 *
 * Exposes secure webhook listener routes for Bangladesh courier partners
 * (Steadfast, Pathao, RedX) to push real-time status updates.
 */
class REST_API {

	public const REST_NAMESPACE = 'wpcalibrate-bot/v1';

	protected Settings $settings;

	protected Shipment_Repository $repository;

	public function __construct( Settings $settings, Shipment_Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/webhook/(?P<provider>[a-zA-Z0-9_-]+)',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_webhook' ],
				'permission_callback' => [ $this, 'check_webhook_permissions' ],
				'args'                => [
					'provider' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);
	}

	/**
	 * Webhook security & permission callback.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool|\WP_Error
	 */
	public function check_webhook_permissions( \WP_REST_Request $request ) {
		$provider_id = (string) $request->get_param( 'provider' );
		$provider    = Courier_Manager::instance()->get_provider( $provider_id );

		if ( ! $provider || ! $provider->supports_webhooks() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Webhook provider is not supported or active.', 'wpcalibrate-bangladesh-order-tracker' ),
				[ 'status' => 403 ]
			);
		}

		$credentials = (array) $this->settings->get( 'integrations', $provider_id, [] );
		$is_valid    = $provider->validate_webhook( $request, $credentials );

		if ( ! $is_valid ) {
			return new \WP_Error(
				'rest_unauthorized',
				__( 'Invalid webhook credentials, secret, or signature.', 'wpcalibrate-bangladesh-order-tracker' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Process validated webhook payload.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function handle_webhook( \WP_REST_Request $request ): \WP_REST_Response {
		$provider_id = (string) $request->get_param( 'provider' );
		$provider    = Courier_Manager::instance()->get_provider( $provider_id );

		if ( ! $provider ) {
			return new \WP_REST_Response( [ 'success' => false, 'message' => 'Provider not found' ], 404 );
		}

		$parsed = $provider->parse_webhook( $request );
		if ( ! $parsed['success'] || empty( $parsed['tracking_number'] ) ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => $parsed['error_message'] ?? 'Failed to parse webhook payload',
				],
				400
			);
		}

		$tracking_number = $parsed['tracking_number'];
		$new_status      = $parsed['normalized_status'];
		$note            = $parsed['note'] ?? '';

		$order = $this->repository->find_order_by_tracking_number( $tracking_number );
		if ( ! $order instanceof \WC_Order ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => sprintf( 'No order found with tracking number %s', $tracking_number ),
				],
				404
			);
		}

		$shipments = $this->repository->get_shipments( $order );
		$updated   = false;

		foreach ( $shipments as $s ) {
			if ( isset( $s['tracking_number'] ) && (string) $s['tracking_number'] === (string) $tracking_number ) {
				$shipment_id = (string) $s['id'];
				$this->repository->add_status_event(
					$order,
					$shipment_id,
					$new_status,
					$note,
					$provider_id . '_webhook',
					true
				);
				$updated = true;
				break;
			}
		}

		if ( $updated ) {
			// Check if WooCommerce order status should be updated on delivery.
			if ( 'delivered' === $new_status && 'yes' === $this->settings->get( 'general', 'sync_order_status', 'no' ) ) {
				$target_status = (string) $this->settings->get( 'general', 'delivered_order_status', 'wc-completed' );
				$clean_status  = str_replace( 'wc-', '', $target_status );
				if ( ! $order->has_status( $clean_status ) ) {
					$order->update_status(
						$clean_status,
						sprintf(
							/* translators: %s: courier name */
							__( 'Shipment marked as Delivered by %s webhook.', 'wpcalibrate-bangladesh-order-tracker' ),
							$provider->get_name()
						)
					);
				}
			}

			return new \WP_REST_Response(
				[
					'success' => true,
					'message' => 'Shipment status updated successfully',
					'status'  => $new_status,
				],
				200
			);
		}

		return new \WP_REST_Response(
			[
				'success' => false,
				'message' => 'Shipment could not be matched',
			],
			404
		);
	}
}
