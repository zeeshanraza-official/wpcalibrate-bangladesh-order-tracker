<?php
/**
 * Settings Management Class.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 *
 * Handles plugin options, schema defaults, sanitization, and isolated persistence.
 */
class Settings {

	public const OPTION_KEY = 'wpcalibrate_bot_settings';

	public const LICENSE_OPTION_KEY = 'wpcalibrate_bot_license';

	/**
	 * Cached settings array.
	 *
	 * @var array<string, mixed>|null
	 */
	protected ?array $settings = null;

	/**
	 * Default plugin options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return [
			'general'       => [
				'default_status'         => 'pending',
				'sync_order_status'      => 'no', // 'yes' or 'no'
				'delivered_order_status' => 'wc-completed',
				'shipped_order_status'   => '',
				'enable_cron_sync'       => 'no',
				'cron_frequency'         => 'hourly', // 'twicedaily', 'hourly', 'daily'
			],
			'tracking_page' => [
				'tracking_page_id'       => 0,
				'require_login'          => 'no',
				'allow_phone_lookup'     => 'yes',
				'allow_email_lookup'     => 'yes',
				'show_timeline'          => 'yes',
				'show_courier_link'      => 'yes',
				'rate_limit_max_lookup'  => 15, // max failed lookups per IP
				'rate_limit_window_mins' => 15,
			],
			'couriers'      => [
				'default_courier' => 'steadfast',
				'custom_names'    => [],
				'templates'       => [],
			],
			'notifications' => [
				'enable_email_notifications' => 'yes',
				'notify_on_statuses'         => [ 'ready_for_dispatch', 'shipped', 'out_for_delivery', 'delivered' ],
				'email_subject'              => __( 'Tracking update for your order #{order_number}', 'wpcalibrate-bangladesh-order-tracker' ),
				'email_heading'              => __( 'Shipment Update', 'wpcalibrate-bangladesh-order-tracker' ),
				'attach_to_wc_emails'        => 'yes',
			],
			'integrations'  => [
				'steadfast' => [
					'api_key'               => '',
					'secret_key'            => '',
					'webhook_secret'        => '',
					'tracking_url_template' => '',
				],
				'pathao'    => [
					'environment'    => 'live',
					'client_id'      => '',
					'client_secret'  => '',
					'username'       => '',
					'password'       => '',
					'webhook_secret' => '',
				],
				'redx'      => [
					'environment'    => 'live',
					'access_token'   => '',
					'webhook_secret' => '',
				],
				'paperfly'  => [
					'username'              => '',
					'password'              => '',
					'tracking_url_template' => '',
				],
			],
			'privacy'       => [
				'delete_data_on_uninstall' => 'no',
				'erase_personal_data'      => 'anonymize', // 'delete', 'anonymize'
				'log_retention_days'       => 30,
			],
			'diagnostics'   => [
				'debug_logging' => 'no',
			],
		];
	}

	/**
	 * Retrieve all settings with defaults merged.
	 *
	 * @return array<string, mixed>
	 */
	public function get_all(): array {
		if ( null !== $this->settings ) {
			return $this->settings;
		}

		$saved    = get_option( self::OPTION_KEY, [] );
		$defaults = self::get_defaults();

		$this->settings = $this->merge_recursive( $defaults, is_array( $saved ) ? $saved : [] );
		return $this->settings;
	}

	/**
	 * Get a specific section or setting value.
	 *
	 * @param string $section
	 * @param string|null $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get( string $section, ?string $key = null, mixed $default = null ): mixed {
		$all = $this->get_all();

		if ( null === $key ) {
			return $all[ $section ] ?? $default;
		}

		return $all[ $section ][ $key ] ?? $default;
	}

	/**
	 * Update settings.
	 *
	 * @param array<string, mixed> $new_settings
	 * @return bool
	 */
	public function update_all( array $new_settings ): bool {
		$sanitized      = $this->sanitize_settings( $new_settings );
		$this->settings = $sanitized;
		return update_option( self::OPTION_KEY, $sanitized );
	}

	/**
	 * Sanitize submitted settings values.
	 *
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $input ): array {
		$defaults  = self::get_defaults();
		$sanitized = [];

		// General.
		$sanitized['general'] = [
			'default_status'         => sanitize_key( $input['general']['default_status'] ?? $defaults['general']['default_status'] ),
			'sync_order_status'      => 'yes' === ( $input['general']['sync_order_status'] ?? 'no' ) ? 'yes' : 'no',
			'delivered_order_status' => sanitize_text_field( $input['general']['delivered_order_status'] ?? $defaults['general']['delivered_order_status'] ),
			'shipped_order_status'   => sanitize_text_field( $input['general']['shipped_order_status'] ?? '' ),
			'enable_cron_sync'       => 'yes' === ( $input['general']['enable_cron_sync'] ?? 'no' ) ? 'yes' : 'no',
			'cron_frequency'         => in_array( $input['general']['cron_frequency'] ?? '', [ 'hourly', 'twicedaily', 'daily' ], true ) ? $input['general']['cron_frequency'] : 'hourly',
		];

		// Tracking page.
		$sanitized['tracking_page'] = [
			'tracking_page_id'       => absint( $input['tracking_page']['tracking_page_id'] ?? 0 ),
			'require_login'          => 'yes' === ( $input['tracking_page']['require_login'] ?? 'no' ) ? 'yes' : 'no',
			'allow_phone_lookup'     => 'yes' === ( $input['tracking_page']['allow_phone_lookup'] ?? 'yes' ) ? 'yes' : 'no',
			'allow_email_lookup'     => 'yes' === ( $input['tracking_page']['allow_email_lookup'] ?? 'yes' ) ? 'yes' : 'no',
			'show_timeline'          => 'yes' === ( $input['tracking_page']['show_timeline'] ?? 'yes' ) ? 'yes' : 'no',
			'show_courier_link'      => 'yes' === ( $input['tracking_page']['show_courier_link'] ?? 'yes' ) ? 'yes' : 'no',
			'rate_limit_max_lookup'  => max( 3, absint( $input['tracking_page']['rate_limit_max_lookup'] ?? 15 ) ),
			'rate_limit_window_mins' => max( 1, absint( $input['tracking_page']['rate_limit_window_mins'] ?? 15 ) ),
		];

		// Couriers.
		$sanitized['couriers'] = [
			'default_courier' => sanitize_key( $input['couriers']['default_courier'] ?? $defaults['couriers']['default_courier'] ),
			'custom_names'    => is_array( $input['couriers']['custom_names'] ?? null ) ? array_map( 'sanitize_text_field', $input['couriers']['custom_names'] ) : [],
			'templates'       => is_array( $input['couriers']['templates'] ?? null ) ? array_map( 'esc_url_raw', $input['couriers']['templates'] ) : [],
		];

		// Notifications.
		$statuses           = Shipment_Repository::get_statuses();
		$valid_notify_stats = [];
		if ( ! empty( $input['notifications']['notify_on_statuses'] ) && is_array( $input['notifications']['notify_on_statuses'] ) ) {
			foreach ( $input['notifications']['notify_on_statuses'] as $stat ) {
				$key = sanitize_key( $stat );
				if ( isset( $statuses[ $key ] ) ) {
					$valid_notify_stats[] = $key;
				}
			}
		}

		$sanitized['notifications'] = [
			'enable_email_notifications' => 'yes' === ( $input['notifications']['enable_email_notifications'] ?? 'yes' ) ? 'yes' : 'no',
			'notify_on_statuses'         => $valid_notify_stats,
			'email_subject'              => sanitize_text_field( $input['notifications']['email_subject'] ?? $defaults['notifications']['email_subject'] ),
			'email_heading'              => sanitize_text_field( $input['notifications']['email_heading'] ?? $defaults['notifications']['email_heading'] ),
			'attach_to_wc_emails'        => 'yes' === ( $input['notifications']['attach_to_wc_emails'] ?? 'yes' ) ? 'yes' : 'no',
		];

		// Integrations - preserve existing passwords/keys if input is empty or masked.
		$existing = $this->get_all();

		$sanitized['integrations']['steadfast'] = [
			'api_key'               => ! empty( $input['integrations']['steadfast']['api_key'] ) ? sanitize_text_field( $input['integrations']['steadfast']['api_key'] ) : ( $existing['integrations']['steadfast']['api_key'] ?? '' ),
			'secret_key'            => ! empty( $input['integrations']['steadfast']['secret_key'] ) && '********' !== $input['integrations']['steadfast']['secret_key'] ? sanitize_text_field( $input['integrations']['steadfast']['secret_key'] ) : ( $existing['integrations']['steadfast']['secret_key'] ?? '' ),
			'webhook_secret'        => sanitize_text_field( $input['integrations']['steadfast']['webhook_secret'] ?? '' ),
			'tracking_url_template' => esc_url_raw( $input['integrations']['steadfast']['tracking_url_template'] ?? '' ),
		];

		$sanitized['integrations']['pathao'] = [
			'environment'    => in_array( $input['integrations']['pathao']['environment'] ?? '', [ 'live', 'sandbox' ], true ) ? $input['integrations']['pathao']['environment'] : 'live',
			'client_id'      => sanitize_text_field( $input['integrations']['pathao']['client_id'] ?? '' ),
			'client_secret'  => ! empty( $input['integrations']['pathao']['client_secret'] ) && '********' !== $input['integrations']['pathao']['client_secret'] ? sanitize_text_field( $input['integrations']['pathao']['client_secret'] ) : ( $existing['integrations']['pathao']['client_secret'] ?? '' ),
			'username'       => sanitize_email( $input['integrations']['pathao']['username'] ?? '' ),
			'password'       => ! empty( $input['integrations']['pathao']['password'] ) && '********' !== $input['integrations']['pathao']['password'] ? (string) $input['integrations']['pathao']['password'] : ( $existing['integrations']['pathao']['password'] ?? '' ),
			'webhook_secret' => sanitize_text_field( $input['integrations']['pathao']['webhook_secret'] ?? '' ),
		];

		$sanitized['integrations']['redx'] = [
			'environment'    => in_array( $input['integrations']['redx']['environment'] ?? '', [ 'live', 'sandbox' ], true ) ? $input['integrations']['redx']['environment'] : 'live',
			'access_token'   => ! empty( $input['integrations']['redx']['access_token'] ) && '********' !== $input['integrations']['redx']['access_token'] ? sanitize_text_field( $input['integrations']['redx']['access_token'] ) : ( $existing['integrations']['redx']['access_token'] ?? '' ),
			'webhook_secret' => sanitize_text_field( $input['integrations']['redx']['webhook_secret'] ?? '' ),
		];

		$sanitized['integrations']['paperfly'] = [
			'username'              => sanitize_text_field( $input['integrations']['paperfly']['username'] ?? '' ),
			'password'              => ! empty( $input['integrations']['paperfly']['password'] ) && '********' !== $input['integrations']['paperfly']['password'] ? (string) $input['integrations']['paperfly']['password'] : ( $existing['integrations']['paperfly']['password'] ?? '' ),
			'tracking_url_template' => esc_url_raw( $input['integrations']['paperfly']['tracking_url_template'] ?? '' ),
		];

		// Privacy.
		$sanitized['privacy'] = [
			'delete_data_on_uninstall' => 'yes' === ( $input['privacy']['delete_data_on_uninstall'] ?? 'no' ) ? 'yes' : 'no',
			'erase_personal_data'      => in_array( $input['privacy']['erase_personal_data'] ?? '', [ 'delete', 'anonymize' ], true ) ? $input['privacy']['erase_personal_data'] : 'anonymize',
			'log_retention_days'       => max( 1, absint( $input['privacy']['log_retention_days'] ?? 30 ) ),
		];

		// Diagnostics.
		$sanitized['diagnostics'] = [
			'debug_logging' => 'yes' === ( $input['diagnostics']['debug_logging'] ?? 'no' ) ? 'yes' : 'no',
		];

		return $sanitized;
	}

	/**
	 * Helper for recursive merging.
	 *
	 * @param array<string, mixed> $defaults
	 * @param array<string, mixed> $saved
	 * @return array<string, mixed>
	 */
	protected function merge_recursive( array $defaults, array $saved ): array {
		$merged = $defaults;
		foreach ( $saved as $key => $value ) {
			if ( isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) && is_array( $value ) ) {
				$merged[ $key ] = $this->merge_recursive( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}
		return $merged;
	}

	/**
	 * Get saved license data.
	 *
	 * @return array{key: string, status: string, expires: string}
	 */
	public function get_license(): array {
		$default = [
			'key'     => '',
			'status'  => 'inactive',
			'expires' => '',
		];
		$saved   = get_option( self::LICENSE_OPTION_KEY, [] );
		return is_array( $saved ) ? array_merge( $default, $saved ) : $default;
	}

	/**
	 * Update license data.
	 *
	 * @param array<string, string> $data
	 * @return bool
	 */
	public function update_license( array $data ): bool {
		$clean = [
			'key'     => sanitize_text_field( $data['key'] ?? '' ),
			'status'  => sanitize_key( $data['status'] ?? 'inactive' ),
			'expires' => sanitize_text_field( $data['expires'] ?? '' ),
		];
		return update_option( self::LICENSE_OPTION_KEY, $clean );
	}
}
