<?php
/**
 * Manual / Custom Courier Provider.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Manual_Provider
 *
 * Fallback provider for couriers that do not have an automated API or when
 * the merchant chooses to track parcels manually.
 */
class Manual_Provider implements Courier_Provider_Interface {

	/**
	 * Unique ID.
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * Display name.
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * Default tracking URL template.
	 *
	 * @var string
	 */
	protected string $tracking_url_template;

	/**
	 * Constructor.
	 *
	 * @param string $id Unique provider ID.
	 * @param string $name Provider display name.
	 * @param string $tracking_url_template Optional tracking URL template.
	 */
	public function __construct( string $id = 'manual', string $name = 'Custom Courier', string $tracking_url_template = '' ) {
		$this->id                    = sanitize_key( $id );
		$this->name                  = sanitize_text_field( $name );
		$this->tracking_url_template = $tracking_url_template;
	}

	public function get_id(): string {
		return $this->id;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_default_tracking_url(): string {
		return $this->tracking_url_template;
	}

	public function build_tracking_url( string $tracking_number, string $custom_template = '' ): string {
		$template = ! empty( $custom_template ) ? $custom_template : $this->tracking_url_template;
		if ( empty( $template ) || empty( $tracking_number ) ) {
			return '';
		}

		$encoded = rawurlencode( trim( $tracking_number ) );
		$url     = str_replace(
			[ '{tracking_number}', '{tracking_code}', '{consignment_id}', '{id}' ],
			$encoded,
			$template
		);

		// Ensure scheme is only http or https to prevent javascript: or unsafe schemes.
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
			'tracking_url_template' => [
				'label'       => __( 'Tracking URL Template', 'wpcalibrate-bangladesh-order-tracker' ),
				'type'        => 'text',
				'placeholder' => 'https://example.com/track?id={tracking_number}',
				'description' => __( 'Use {tracking_number} as placeholder.', 'wpcalibrate-bangladesh-order-tracker' ),
			],
		];
	}

	public function test_connection( array $credentials ): array {
		return [
			'success' => true,
			'message' => __( 'Manual provider does not require remote credentials.', 'wpcalibrate-bangladesh-order-tracker' ),
		];
	}

	public function track_shipment( string $tracking_number, array $credentials = [] ): array {
		return [
			'success'           => false,
			'normalized_status' => 'pending',
			'provider_status'   => 'manual',
			'status_message'    => __( 'Automated tracking is not supported by manual provider.', 'wpcalibrate-bangladesh-order-tracker' ),
			'timestamp'         => current_time( 'mysql' ),
			'error_message'     => __( 'Manual provider does not support remote tracking.', 'wpcalibrate-bangladesh-order-tracker' ),
		];
	}

	public function normalize_status( string $raw_status ): string {
		$allowed = [
			'pending',
			'ready_for_dispatch',
			'shipped',
			'in_transit',
			'out_for_delivery',
			'delivered',
			'delivery_failed',
			'returned',
			'cancelled',
		];
		$sanitized = sanitize_key( $raw_status );
		return in_array( $sanitized, $allowed, true ) ? $sanitized : 'pending';
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
			'error_message'     => __( 'Webhooks are not supported for manual courier.', 'wpcalibrate-bangladesh-order-tracker' ),
		];
	}
}
