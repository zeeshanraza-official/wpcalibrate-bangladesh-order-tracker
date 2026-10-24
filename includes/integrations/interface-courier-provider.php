<?php
/**
 * Interface for Courier Providers.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Courier_Provider_Interface
 *
 * Defines the contract that all Bangladesh courier providers (manual, Steadfast, Pathao, RedX, etc.)
 * must implement.
 */
interface Courier_Provider_Interface {

	/**
	 * Unique identifier for the provider (e.g., 'steadfast', 'pathao', 'redx', 'manual').
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Human-readable display name.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Default tracking URL template for this courier.
	 * Placeholders supported: {tracking_number}, {tracking_code}, {consignment_id}.
	 *
	 * @return string
	 */
	public function get_default_tracking_url(): string;

	/**
	 * Build public tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number Tracking number or consignment ID.
	 * @param string $custom_template Optional override template.
	 * @return string Safe tracking URL or empty string if invalid.
	 */
	public function build_tracking_url( string $tracking_number, string $custom_template = '' ): string;

	/**
	 * Whether this provider supports automated API tracking.
	 *
	 * @return bool
	 */
	public function supports_api(): bool;

	/**
	 * Whether this provider supports receiving real-time webhooks.
	 *
	 * @return bool
	 */
	public function supports_webhooks(): bool;

	/**
	 * Return configuration schema / fields for the provider settings.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_settings_fields(): array;

	/**
	 * Validate credentials provided in settings.
	 *
	 * @param array<string, mixed> $credentials
	 * @return array{success: bool, message: string}
	 */
	public function test_connection( array $credentials ): array;

	/**
	 * Fetch remote tracking information for a tracking number.
	 *
	 * @param string $tracking_number Consignment / tracking identifier.
	 * @param array<string, mixed> $credentials Configured provider credentials.
	 * @return array{
	 *     success: bool,
	 *     normalized_status: string,
	 *     provider_status: string,
	 *     status_message: string,
	 *     timestamp: string,
	 *     raw_response?: mixed,
	 *     error_message?: string
	 * }
	 */
	public function track_shipment( string $tracking_number, array $credentials = [] ): array;

	/**
	 * Normalize a provider-specific delivery status to one of the standard core statuses.
	 * Standard core statuses:
	 * pending, ready_for_dispatch, shipped, in_transit, out_for_delivery, delivered, delivery_failed, returned, cancelled.
	 *
	 * @param string $raw_status
	 * @return string
	 */
	public function normalize_status( string $raw_status ): string;

	/**
	 * Validate an incoming webhook request from this courier provider.
	 *
	 * @param \WP_REST_Request|array<string, mixed> $request
	 * @param array<string, mixed> $credentials
	 * @return bool
	 */
	public function validate_webhook( $request, array $credentials = [] ): bool;

	/**
	 * Parse and extract shipment update from incoming webhook payload.
	 *
	 * @param \WP_REST_Request|array<string, mixed> $request
	 * @return array{
	 *     success: bool,
	 *     tracking_number: string,
	 *     normalized_status: string,
	 *     note: string,
	 *     error_message?: string
	 * }
	 */
	public function parse_webhook( $request ): array;
}
