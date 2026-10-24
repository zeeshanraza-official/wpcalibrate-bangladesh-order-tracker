<?php
/**
 * Steadfast Courier Provider Integration.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Steadfast_Provider
 *
 * Official Steadfast Courier Bangladesh integration based on Steadfast Portal API v1.
 */
class Steadfast_Provider implements Courier_Provider_Interface {

	public const PROVIDER_ID = 'steadfast';

	public const BASE_URL = 'https://portal.steadfast.com.bd/api/v1';

	public const DEFAULT_TRACKING_URL = 'https://steadfast.com.bd/t/{tracking_number}';

	public function get_id(): string {
		return self::PROVIDER_ID;
	}

	public function get_name(): string {
		return __( 'Steadfast Courier', 'wpcalibrate-bangladesh-order-tracker' );
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
		$url     = str_replace( [ '{tracking_number}', '{tracking_code}' ], $encoded, $template );

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
			'api_key' => [
				'label'       => __( 'Steadfast API Key', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'text',
				'placeholder' => __( 'Enter Steadfast Api-Key', 'wpcalibrate-bangladesh-order-tracker' ),
				'description' => __( 'Found in Steadfast Merchant Portal > Settings > API Settings.', 'wpcalibrate-bangladesh-order-tracker' ),
			],
			'secret_key' => [
				'label'       => __( 'Steadfast Secret Key', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'password',
				'placeholder' => __( 'Enter Steadfast Secret-Key', 'wpcalibrate-bangladesh-order-tracker' ),
				'description' => __( 'Found alongside your API Key.', 'wpcalibrate-bangladesh-order-tracker' ),
			],
			'webhook_secret' => [
				'label'       => __( 'Webhook Secret / Token (Optional)', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'text',
				'placeholder' => __( 'Optional custom secret for webhook validation', 'wpcalibrate-bangladesh-order-tracker' ),
				'description' => __( 'Used to verify webhook payloads if configured in the portal.', 'wpcalibrate-bangladesh-order-tracker' ),
			],
			'tracking_url_template' => [
				'label'       => __( 'Custom Tracking URL Template', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'text',
				'placeholder' => self::DEFAULT_TRACKING_URL,
				'description' => __( 'Defaults to https://steadfast.com.bd/t/{tracking_number}', 'wpcalibrate-bangladesh-order-tracker' ),
			],
		];
	}

	public function test_connection( array $credentials ): array {
		$api_key    = trim( (string) ( $credentials['api_key'] ?? '' ) );
		$secret_key = trim( (string) ( $credentials['secret_key'] ?? '' ) );

		if ( empty( $api_key ) || empty( $secret_key ) ) {
			return [
				'success' => false,
				'message' => __( 'Please provide both Steadfast API Key and Secret Key.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		// Perform a ping check against status endpoint with a test identifier.
		$response = wp_remote_get(
			self::BASE_URL . '/status_by_trackingcode/TEST_CONNECTION',
			[
				'headers' => [
					'Api-Key'      => $api_key,
					'Secret-Key'   => $secret_key,
					'Content-Type' => 'application/json',
				],
				'timeout' => 12,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Connection failed: %s', 'wpcalibrate-bangladesh-order-tracker' ),
					$response->get_error_message()
				),
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		// 401 or 403 means bad credentials.
		if ( 401 === $status_code || 403 === $status_code ) {
			return [
				'success' => false,
				'message' => __( 'Authentication failed. Please verify your Steadfast API Key and Secret Key.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		// 200 or 404 (tracking code not found) confirms that the API credentials were authenticated by Steadfast.
		if ( 200 === $status_code || 404 === $status_code || ( is_array( $body ) && isset( $body['status'] ) ) ) {
			return [
				'success' => true,
				'message' => __( 'Successfully connected to Steadfast Courier API.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		return [
			'success' => false,
			'message' => sprintf(
				/* translators: %d: HTTP status code */
				__( 'Unexpected response from Steadfast API (HTTP %d).', 'wpcalibrate-bangladesh-order-tracker' ),
				$status_code
			),
		];
	}

	public function track_shipment( string $tracking_number, array $credentials = [] ): array {
		$api_key    = trim( (string) ( $credentials['api_key'] ?? '' ) );
		$secret_key = trim( (string) ( $credentials['secret_key'] ?? '' ) );

		if ( empty( $api_key ) || empty( $secret_key ) ) {
			return [
				'success'           => false,
				'normalized_status' => 'pending',
				'provider_status'   => 'unconfigured',
				'status_message'    => __( 'Steadfast API credentials are not configured.', 'wpcalibrate-bangladesh-order-tracker' ),
				'timestamp'         => current_time( 'mysql' ),
				'error_message'     => __( 'Missing API credentials.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$clean_tracking = trim( $tracking_number );
		if ( empty( $clean_tracking ) ) {
			return [
				'success'           => false,
				'normalized_status' => 'pending',
				'provider_status'   => 'invalid',
				'status_message'    => __( 'Tracking number is empty.', 'wpcalibrate-bangladesh-order-tracker' ),
				'timestamp'         => current_time( 'mysql' ),
				'error_message'     => __( 'Empty tracking number.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		// Query Steadfast tracking endpoint.
		$url      = self::BASE_URL . '/status_by_trackingcode/' . rawurlencode( $clean_tracking );
		$response = wp_remote_get(
			$url,
			[
				'headers' => [
					'Api-Key'      => $api_key,
					'Secret-Key'   => $secret_key,
					'Content-Type' => 'application/json',
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
		$raw_body    = wp_remote_retrieve_body( $response );
		$data        = json_decode( $raw_body, true );

		if ( 200 !== $status_code || ! is_array( $data ) ) {
			return [
				'success'           => false,
				'normalized_status' => 'pending',
				'provider_status'   => 'http_' . $status_code,
				'status_message'    => sprintf(
					/* translators: %d: HTTP status code */
					__( 'Steadfast API error: HTTP %d', 'wpcalibrate-bangladesh-order-tracker' ),
					$status_code
				),
				'timestamp'         => current_time( 'mysql' ),
				'raw_response'      => $data,
				'error_message'     => is_array( $data ) && isset( $data['message'] ) ? (string) $data['message'] : 'HTTP ' . $status_code,
			];
		}

		$provider_status   = (string) ( $data['delivery_status'] ?? 'unknown' );
		$normalized_status = $this->normalize_status( $provider_status );

		return [
			'success'           => true,
			'normalized_status' => $normalized_status,
			'provider_status'   => $provider_status,
			'status_message'    => sprintf(
				/* translators: %s: status name */
				__( 'Steadfast status: %s', 'wpcalibrate-bangladesh-order-tracker' ),
				ucwords( str_replace( '_', ' ', $provider_status ) )
			),
			'timestamp'         => current_time( 'mysql' ),
			'raw_response'      => $data,
		];
	}

	public function normalize_status( string $raw_status ): string {
		$status = strtolower( trim( $raw_status ) );

		return match ( $status ) {
			'in_review'                       => 'pending',
			'pending'                         => 'ready_for_dispatch',
			'hold'                            => 'in_transit',
			'in_transit', 'delivering'        => 'in_transit',
			'out_for_delivery', 'with_rider'  => 'out_for_delivery',
			'delivered', 'partial_delivered'  => 'delivered',
			'cancelled'                       => 'cancelled',
			'returned', 'return'              => 'returned',
			'delivery_failed', 'failed'       => 'delivery_failed',
			default                           => 'in_transit',
		};
	}

	public function validate_webhook( $request, array $credentials = [] ): bool {
		$webhook_secret = trim( (string) ( $credentials['webhook_secret'] ?? '' ) );
		if ( empty( $webhook_secret ) ) {
			// If no webhook secret is configured, require basic API key matching in header or query.
			$api_key = trim( (string) ( $credentials['api_key'] ?? '' ) );
			if ( empty( $api_key ) ) {
				return false;
			}
			if ( is_a( $request, 'WP_REST_Request' ) ) {
				$provided_key = (string) ( $request->get_header( 'x-api-key' ) ?: $request->get_param( 'api_key' ) );
				return hash_equals( $api_key, $provided_key );
			}
			return false;
		}

		if ( is_a( $request, 'WP_REST_Request' ) ) {
			$provided_secret = (string) ( $request->get_header( 'x-steadfast-secret' ) ?: $request->get_param( 'secret' ) );
			return hash_equals( $webhook_secret, $provided_secret );
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
				'error_message'     => __( 'Invalid request object for Steadfast webhook.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$params          = $request->get_json_params() ?: $request->get_params();
		$tracking_number = (string) ( $params['tracking_code'] ?? ( $params['consignment_id'] ?? '' ) );
		$raw_status      = (string) ( $params['status'] ?? ( $params['delivery_status'] ?? '' ) );

		if ( empty( $tracking_number ) ) {
			return [
				'success'           => false,
				'tracking_number'   => '',
				'normalized_status' => '',
				'note'              => '',
				'error_message'     => __( 'No tracking code found in Steadfast webhook payload.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$normalized = $this->normalize_status( $raw_status );

		return [
			'success'           => true,
			'tracking_number'   => sanitize_text_field( $tracking_number ),
			'normalized_status' => $normalized,
			'note'              => sprintf(
				/* translators: %s: status name */
				__( 'Status updated via Steadfast webhook to: %s', 'wpcalibrate-bangladesh-order-tracker' ),
				ucwords( str_replace( '_', ' ', $raw_status ) )
			),
		];
	}
}
