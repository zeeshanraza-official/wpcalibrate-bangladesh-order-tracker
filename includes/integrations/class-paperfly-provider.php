<?php
/**
 * Paperfly Courier Provider Integration.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Paperfly_Provider
 *
 * Paperfly Courier Bangladesh integration.
 */
class Paperfly_Provider implements Courier_Provider_Interface {

	public const PROVIDER_ID = 'paperfly';

	public const DEFAULT_TRACKING_URL = 'https://paperfly.com.bd/tracking.php?tracking_id={tracking_number}';

	public function get_id(): string {
		return self::PROVIDER_ID;
	}

	public function get_name(): string {
		return __( 'Paperfly', 'wpcalibrate-bangladesh-order-tracker' );
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
		return false;
	}

	public function supports_webhooks(): bool {
		return false;
	}

	public function get_settings_fields(): array {
		return [
			'username' => [
				'label'       => __( 'Paperfly Wings Username', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'text',
				'placeholder' => __( 'Wings Portal Username', 'wpcalibrate-bangladesh-order-tracker' ),
			],
			'password' => [
				'label'       => __( 'Paperfly Wings Password', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'password',
				'placeholder' => __( 'Wings Portal Password', 'wpcalibrate-bangladesh-order-tracker' ),
			],
			'tracking_url_template' => [
				'label'       => __( 'Tracking URL Template', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'text',
				'placeholder' => self::DEFAULT_TRACKING_URL,
			],
		];
	}

	public function test_connection( array $credentials ): array {
		$user = trim( (string) ( $credentials['username'] ?? '' ) );
		$pass = trim( (string) ( $credentials['password'] ?? '' ) );

		if ( empty( $user ) || empty( $pass ) ) {
			return [
				'success' => false,
				'message' => __( 'Paperfly Wings credentials not set.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		return [
			'success' => true,
			'message' => __( 'Paperfly Wings configuration saved.', 'wpcalibrate-bangladesh-order-tracker' ),
		];
	}

	public function track_shipment( string $tracking_number, array $credentials = [] ): array {
		return [
			'success'           => false,
			'normalized_status' => 'pending',
			'provider_status'   => 'manual',
			'status_message'    => __( 'Automated API tracking for Paperfly requires merchant custom gateway credentials. Use manual tracking link.', 'wpcalibrate-bangladesh-order-tracker' ),
			'timestamp'         => current_time( 'mysql' ),
			'error_message'     => 'API tracking not enabled for Paperfly',
		];
	}

	public function normalize_status( string $raw_status ): string {
		$status = strtolower( trim( $raw_status ) );

		if ( str_contains( $status, 'delivered' ) ) {
			return 'delivered';
		}
		if ( str_contains( $status, 'transit' ) ) {
			return 'in_transit';
		}
		if ( str_contains( $status, 'return' ) ) {
			return 'returned';
		}

		return 'in_transit';
	}

	public function validate_webhook( $request, array $credentials = [] ): bool {
		return false;
	}

	public function parse_webhook( $request ): array {
		return [
			'success'           => false,
			'tracking_number'   => '',
			'normalized_status' => '',
			'note'              => '',
			'error_message'     => __( 'Webhooks are not configured for Paperfly.', 'wpcalibrate-bangladesh-order-tracker' ),
		];
	}
}
