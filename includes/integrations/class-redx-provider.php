<?php
/**
 * RedX Courier Provider Integration.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RedX_Provider
 *
 * Official RedX Courier Bangladesh integration based on RedX OpenAPI v1.0.0-beta.
 */
class RedX_Provider implements Courier_Provider_Interface {

	public const PROVIDER_ID = 'redx';

	public const SANDBOX_BASE_URL = 'https://sandbox.redx.com.bd/v1.0.0-beta';

	public const LIVE_BASE_URL = 'https://openapi.redx.com.bd/v1.0.0-beta';

	public const DEFAULT_TRACKING_URL = 'https://redx.com.bd/track?trackingId={tracking_number}';

	public function get_id(): string {
		return self::PROVIDER_ID;
	}

	public function get_name(): string {
		return __( 'RedX Courier', 'wpcalibrate-bangladesh-order-tracker' );
	}

	public function get_default_tracking_url(): string {
		return self::DEFAULT_TRACKING_URL;
	}

	public function build_tracking_url( string $tracking_number, string $custom_template = '' ): string {
		$template = ! empty( $custom_template ) ? $custom_template : self::DEFAULT_TRACKING_URL;
		if ( empty( $tracking_number ) ) {
			return '';
		}

		$encoded = rawurlencode( trim( $tracking_number ) );
		$url     = str_replace( [ '{tracking_number}', '{tracking_code}', '{tracking_id}' ], $encoded, $template );

		$parsed = wp_parse_url( $url );
		if ( empty( $parsed['scheme'] ) || ! in_array( strtolower( $parsed['scheme'] ), [ 'http', 'https' ], true ) ) {
			return '';
		}

		return esc_url_raw( $url );
	}

	public function supports_api(): bool {
		return true;
	}

	public function supports_webhooks(): bool {
		return true;
	}

	public function get_settings_fields(): array {
		return [
			'environment' => [
				'label'   => __( 'Environment', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'    => 'select',
				'options' => [
					'live'    => __( 'Live / Production', 'wpcalibrate-bangladesh-order-tracker' ),
					'sandbox' => __( 'Sandbox / Testing', 'wpcalibrate-bangladesh-order-tracker' ),
				],
				'default' => 'live',
			],
			'access_token' => [
				'label'       => __( 'RedX Access Token', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'password',
				'placeholder' => __( 'Enter RedX Bearer Token', 'wpcalibrate-bangladesh-order-tracker' ),
				'description' => __( 'Obtained from RedX Merchant Dashboard under Developer / API settings.', 'wpcalibrate-bangladesh-order-tracker' ),
			],
			'webhook_secret' => [
				'label'       => __( 'Webhook Secret (Optional)', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'text',
				'placeholder' => __( 'Webhook verification token', 'wpcalibrate-bangladesh-order-tracker' ),
			],
		];
	}

	protected function get_base_url( array $credentials ): string {
		$env = (string) ( $credentials['environment'] ?? 'live' );
		return 'sandbox' === $env ? self::SANDBOX_BASE_URL : self::LIVE_BASE_URL;
	}

	public function test_connection( array $credentials ): array {
		$token = trim( (string) ( $credentials['access_token'] ?? '' ) );
		if ( empty( $token ) ) {
			return [
				'success' => false,
				'message' => __( 'RedX Access Token is missing.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$base_url = $this->get_base_url( $credentials );
		// Query with a dummy ID to verify authorization header response.
		$response = wp_remote_get(
			$base_url . '/parcel/track/TEST_CONNECTION',
			[
				'headers' => [
					'API-ACCESS-TOKEN' => 'Bearer ' . $token,
					'Accept'           => 'application/json',
				],
				'timeout' => 12,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 401 === $status_code || 403 === $status_code ) {
			return [
				'success' => false,
				'message' => __( 'Authentication failed. Please verify your RedX Access Token.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		// 200 or 404/400 (parcel not found) confirms token authorization succeeded.
		if ( in_array( $status_code, [ 200, 400, 404 ], true ) ) {
			return [
				'success' => true,
				'message' => __( 'RedX API authentication verified successfully.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		return [
			'success' => false,
			'message' => sprintf(
				/* translators: %d: HTTP status code */
				__( 'Unexpected response from RedX API (HTTP %d).', 'wpcalibrate-bangladesh-order-tracker' ),
				$status_code
			),
		];
	}

	public function track_shipment( string $tracking_number, array $credentials = [] ): array {
		$token = trim( (string) ( $credentials['access_token'] ?? '' ) );
		if ( empty( $token ) ) {
			return [
				'success'           => false,
				'normalized_status' => 'pending',
				'provider_status'   => 'unconfigured',
				'status_message'    => __( 'RedX Access Token is not configured.', 'wpcalibrate-bangladesh-order-tracker' ),
				'timestamp'         => current_time( 'mysql' ),
				'error_message'     => __( 'Missing API token.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$clean_id = trim( $tracking_number );
		if ( empty( $clean_id ) ) {
			return [
				'success'           => false,
				'normalized_status' => 'pending',
				'provider_status'   => 'empty_id',
				'status_message'    => __( 'Tracking ID is empty.', 'wpcalibrate-bangladesh-order-tracker' ),
				'timestamp'         => current_time( 'mysql' ),
				'error_message'     => 'Empty tracking ID',
			];
		}

		$base_url = $this->get_base_url( $credentials );
		$response = wp_remote_get(
			$base_url . '/parcel/track/' . rawurlencode( $clean_id ),
			[
				'headers' => [
					'API-ACCESS-TOKEN' => 'Bearer ' . $token,
					'Accept'           => 'application/json',
				],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success'           => false,
				'normalized_status' => 'pending',
				'provider_status'   => 'network_error',
				'status_message'    => $response->get_error_message(),
				'timestamp'         => current_time( 'mysql' ),
				'error_message'     => $response->get_error_message(),
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code || ! is_array( $body ) ) {
			return [
				'success'           => false,
				'normalized_status' => 'pending',
				'provider_status'   => 'http_' . $status_code,
				'status_message'    => sprintf(
					/* translators: %d: HTTP status code */
					__( 'RedX API error: HTTP %d', 'wpcalibrate-bangladesh-order-tracker' ),
					$status_code
				),
				'timestamp'         => current_time( 'mysql' ),
				'raw_response'      => $body,
				'error_message'     => is_array( $body ) && isset( $body['message'] ) ? (string) $body['message'] : 'HTTP ' . $status_code,
			];
		}

		$events = $body['tracking'] ?? [];
		if ( empty( $events ) || ! is_array( $events ) ) {
			return [
				'success'           => true,
				'normalized_status' => 'pending',
				'provider_status'   => 'created',
				'status_message'    => __( 'Package created in RedX system.', 'wpcalibrate-bangladesh-order-tracker' ),
				'timestamp'         => current_time( 'mysql' ),
				'raw_response'      => $body,
			];
		}

		// The latest event is typically the last item in the tracking history array.
		$latest_event    = end( $events );
		$provider_status = (string) ( $latest_event['message_en'] ?? '' );
		$normalized      = $this->normalize_status( $provider_status );
		$event_time      = ! empty( $latest_event['time'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( (string) $latest_event['time'] ) ) : current_time( 'mysql' );

		return [
			'success'           => true,
			'normalized_status' => $normalized,
			'provider_status'   => $provider_status,
			'status_message'    => $provider_status,
			'timestamp'         => $event_time,
			'raw_response'      => $body,
		];
	}

	public function normalize_status( string $raw_status ): string {
		$status = strtolower( trim( $raw_status ) );

		if ( str_contains( $status, 'created' ) || str_contains( $status, 'placed' ) ) {
			return 'pending';
		}
		if ( str_contains( $status, 'picked' ) || str_contains( $status, 'pickup' ) ) {
			return 'ready_for_dispatch';
		}
		if ( str_contains( $status, 'transit' ) || str_contains( $status, 'hub' ) ) {
			return 'in_transit';
		}
		if ( str_contains( $status, 'out for delivery' ) || str_contains( $status, 'delivering' ) ) {
			return 'out_for_delivery';
		}
		if ( str_contains( $status, 'delivered' ) ) {
			return 'delivered';
		}
		if ( str_contains( $status, 'returned' ) || str_contains( $status, 'return' ) ) {
			return 'returned';
		}
		if ( str_contains( $status, 'failed' ) || str_contains( $status, 'hold' ) ) {
			return 'delivery_failed';
		}
		if ( str_contains( $status, 'cancelled' ) ) {
			return 'cancelled';
		}

		return 'in_transit';
	}

	public function validate_webhook( $request, array $credentials = [] ): bool {
		$secret = trim( (string) ( $credentials['webhook_secret'] ?? '' ) );
		if ( empty( $secret ) ) {
			return true;
		}

		if ( is_a( $request, 'WP_REST_Request' ) ) {
			$header = (string) ( $request->get_header( 'x-redx-secret' ) ?: $request->get_header( 'x-webhook-token' ) );
			return hash_equals( $secret, $header );
		}

		return false;
	}

	public function parse_webhook( $request ): array {
		if ( ! is_a( $request, 'WP_REST_Request' ) ) {
			return [
				'success'           => false,
				'tracking_number'   => '',
				'normalized_status' => '',
				'note'              => '',
				'error_message'     => __( 'Invalid request object for RedX webhook.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$params     = $request->get_json_params() ?: $request->get_params();
		$tid        = (string) ( $params['tracking_id'] ?? ( $params['tracking_number'] ?? '' ) );
		$raw_status = (string) ( $params['status'] ?? ( $params['message'] ?? '' ) );

		if ( empty( $tid ) ) {
			return [
				'success'           => false,
				'tracking_number'   => '',
				'normalized_status' => '',
				'note'              => '',
				'error_message'     => __( 'No tracking ID found in RedX webhook payload.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$normalized = $this->normalize_status( $raw_status );

		return [
			'success'           => true,
			'tracking_number'   => sanitize_text_field( $tid ),
			'normalized_status' => $normalized,
			'note'              => sprintf(
				/* translators: %s: status description */
				__( 'Status updated via RedX webhook: %s', 'wpcalibrate-bangladesh-order-tracker' ),
				$raw_status
			),
		];
	}
}
