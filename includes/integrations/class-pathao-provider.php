<?php
/**
 * Pathao Courier Provider Integration.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pathao_Provider
 *
 * Official Pathao Courier Bangladesh integration based on Pathao Merchant API.
 */
class Pathao_Provider implements Courier_Provider_Interface {

	public const PROVIDER_ID = 'pathao';

	public const SANDBOX_BASE_URL = 'https://courier-api-sandbox.pathao.com';

	public const LIVE_BASE_URL = 'https://api-hermes.pathao.com';

	public const DEFAULT_TRACKING_URL = 'https://merchant.pathao.com/tracking?consignment_id={tracking_number}';

	public function get_id(): string {
		return self::PROVIDER_ID;
	}

	public function get_name(): string {
		return __( 'Pathao Courier', 'wpcalibrate-bangladesh-order-tracker' );
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
		$url     = str_replace( [ '{tracking_number}', '{tracking_code}', '{consignment_id}' ], $encoded, $template );

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
			'client_id' => [
				'label'       => __( 'Client ID', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'text',
				'placeholder' => __( 'Pathao OAuth Client ID', 'wpcalibrate-bangladesh-order-tracker' ),
			],
			'client_secret' => [
				'label'       => __( 'Client Secret', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'password',
				'placeholder' => __( 'Pathao OAuth Client Secret', 'wpcalibrate-bangladesh-order-tracker' ),
			],
			'username' => [
				'label'       => __( 'Username / Email', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'text',
				'placeholder' => __( 'Registered Pathao merchant email', 'wpcalibrate-bangladesh-order-tracker' ),
			],
			'password' => [
				'label'       => __( 'Password', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'password',
				'placeholder' => __( 'Pathao account password', 'wpcalibrate-bangladesh-order-tracker' ),
			],
			'webhook_secret' => [
				'label'       => __( 'Webhook Secret / Token (Optional)', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'text',
				'placeholder' => __( 'Configured Pathao webhook callback secret', 'wpcalibrate-bangladesh-order-tracker' ),
			],
		];
	}

	/**
	 * Get API base URL depending on configured environment.
	 */
	protected function get_base_url( array $credentials ): string {
		$env = (string) ( $credentials['environment'] ?? 'live' );
		return 'sandbox' === $env ? self::SANDBOX_BASE_URL : self::LIVE_BASE_URL;
	}

	/**
	 * Retrieve or refresh Pathao OAuth2 Access Token.
	 *
	 * @param array<string, mixed> $credentials
	 * @return array{success: bool, token?: string, error?: string}
	 */
	public function get_access_token( array $credentials ): array {
		$client_id     = trim( (string) ( $credentials['client_id'] ?? '' ) );
		$client_secret = trim( (string) ( $credentials['client_secret'] ?? '' ) );
		$username      = trim( (string) ( $credentials['username'] ?? '' ) );
		$password      = trim( (string) ( $credentials['password'] ?? '' ) );

		if ( empty( $client_id ) || empty( $client_secret ) || empty( $username ) || empty( $password ) ) {
			return [
				'success' => false,
				'error'   => __( 'Missing Pathao credentials (client ID, secret, username, or password).', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$cache_key = 'wpcalibrate_bot_pathao_token_' . md5( $client_id . $username );
		$cached    = get_transient( $cache_key );
		if ( ! empty( $cached ) && is_string( $cached ) ) {
			return [
				'success' => true,
				'token'   => $cached,
			];
		}

		$base_url = $this->get_base_url( $credentials );
		$response = wp_remote_post(
			$base_url . '/aladdin/api/v1/issue-token',
			[
				'headers' => [
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'client_id'     => $client_id,
						'client_secret' => $client_secret,
						'username'      => $username,
						'password'      => $password,
						'grant_type'    => 'password',
					]
				),
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'error'   => $response->get_error_message(),
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$data        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code || ! is_array( $data ) || empty( $data['access_token'] ) ) {
			$err = is_array( $data ) && isset( $data['message'] ) ? (string) $data['message'] : 'HTTP ' . $status_code;
			return [
				'success' => false,
				'error'   => sprintf(
					/* translators: %s: error message */
					__( 'Pathao token issue failed: %s', 'wpcalibrate-bangladesh-order-tracker' ),
					$err
				),
			];
		}

		$token      = (string) $data['access_token'];
		$expires_in = isset( $data['expires_in'] ) ? max( 300, (int) $data['expires_in'] - 300 ) : 3600;

		set_transient( $cache_key, $token, $expires_in );

		return [
			'success' => true,
			'token'   => $token,
		];
	}

	public function test_connection( array $credentials ): array {
		$auth = $this->get_access_token( $credentials );
		if ( ! $auth['success'] ) {
			return [
				'success' => false,
				'message' => $auth['error'] ?? __( 'Failed to authenticate with Pathao API.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		return [
			'success' => true,
			'message' => __( 'Pathao OAuth authentication succeeded. Token acquired.', 'wpcalibrate-bangladesh-order-tracker' ),
		];
	}

	public function track_shipment( string $tracking_number, array $credentials = [] ): array {
		$auth = $this->get_access_token( $credentials );
		if ( ! $auth['success'] ) {
			return [
				'success'           => false,
				'normalized_status' => 'pending',
				'provider_status'   => 'auth_failed',
				'status_message'    => $auth['error'] ?? __( 'Pathao authentication failed.', 'wpcalibrate-bangladesh-order-tracker' ),
				'timestamp'         => current_time( 'mysql' ),
				'error_message'     => $auth['error'] ?? 'Auth failed',
			];
		}

		$token     = $auth['token'];
		$base_url  = $this->get_base_url( $credentials );
		$clean_cid = trim( $tracking_number );

		$url      = $base_url . '/aladdin/api/v1/orders/' . rawurlencode( $clean_cid );
		$response = wp_remote_get(
			$url,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/json',
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
					__( 'Pathao API error: HTTP %d', 'wpcalibrate-bangladesh-order-tracker' ),
					$status_code
				),
				'timestamp'         => current_time( 'mysql' ),
				'raw_response'      => $body,
				'error_message'     => is_array( $body ) && isset( $body['message'] ) ? (string) $body['message'] : 'HTTP ' . $status_code,
			];
		}

		$order_data      = $body['data'] ?? $body;
		$provider_status = (string) ( $order_data['order_status'] ?? ( $order_data['status'] ?? 'unknown' ) );
		$normalized      = $this->normalize_status( $provider_status );

		return [
			'success'           => true,
			'normalized_status' => $normalized,
			'provider_status'   => $provider_status,
			'status_message'    => sprintf(
				/* translators: %s: status name */
				__( 'Pathao status: %s', 'wpcalibrate-bangladesh-order-tracker' ),
				ucwords( str_replace( '_', ' ', $provider_status ) )
			),
			'timestamp'         => current_time( 'mysql' ),
			'raw_response'      => $body,
		];
	}

	public function normalize_status( string $raw_status ): string {
		$status = strtolower( trim( $raw_status ) );

		return match ( $status ) {
			'pending', 'order placed'                          => 'pending',
			'picked', 'pickup requested', 'parcel accepted'    => 'ready_for_dispatch',
			'in transit', 'assigned_hub', 'in_transit'         => 'in_transit',
			'rider assigned', 'out for delivery', 'out_for_delivery' => 'out_for_delivery',
			'delivered', 'partial delivered', 'partial_delivered' => 'delivered',
			'returned', 'return_received', 'return'            => 'returned',
			'delivery failed', 'failed', 'hold'                => 'delivery_failed',
			'cancelled', 'order cancelled'                     => 'cancelled',
			default                                            => 'in_transit',
		};
	}

	public function validate_webhook( $request, array $credentials = [] ): bool {
		$secret = trim( (string) ( $credentials['webhook_secret'] ?? '' ) );
		if ( empty( $secret ) ) {
			return true;
		}

		if ( is_a( $request, 'WP_REST_Request' ) ) {
			$header = (string) ( $request->get_header( 'x-pathao-signature' ) ?: $request->get_header( 'x-webhook-secret' ) );
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
				'error_message'     => __( 'Invalid request object for Pathao webhook.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$params     = $request->get_json_params() ?: $request->get_params();
		$cid        = (string) ( $params['consignment_id'] ?? ( $params['order_id'] ?? '' ) );
		$raw_status = (string) ( $params['order_status'] ?? ( $params['status'] ?? '' ) );

		if ( empty( $cid ) ) {
			return [
				'success'           => false,
				'tracking_number'   => '',
				'normalized_status' => '',
				'note'              => '',
				'error_message'     => __( 'No consignment ID found in Pathao webhook payload.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$normalized = $this->normalize_status( $raw_status );

		return [
			'success'           => true,
			'tracking_number'   => sanitize_text_field( $cid ),
			'normalized_status' => $normalized,
			'note'              => sprintf(
				/* translators: %s: status name */
				__( 'Status updated via Pathao webhook to: %s', 'wpcalibrate-bangladesh-order-tracker' ),
				ucwords( str_replace( '_', ' ', $raw_status ) )
			),
		];
	}
}
