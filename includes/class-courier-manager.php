<?php
/**
 * Courier Manager Class.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

use WPCalibrate\BangladeshOrderTracker\Integrations\Courier_Provider_Interface;
use WPCalibrate\BangladeshOrderTracker\Integrations\Manual_Provider;
use WPCalibrate\BangladeshOrderTracker\Integrations\Steadfast_Provider;
use WPCalibrate\BangladeshOrderTracker\Integrations\Pathao_Provider;
use WPCalibrate\BangladeshOrderTracker\Integrations\RedX_Provider;
use WPCalibrate\BangladeshOrderTracker\Integrations\Paperfly_Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Courier_Manager
 *
 * Coordinates courier providers, registration, and URL resolution.
 */
class Courier_Manager {

	/**
	 * Registry of provider instances.
	 *
	 * @var array<string, Courier_Provider_Interface>
	 */
	protected array $providers = [];

	/**
	 * Instance singleton.
	 *
	 * @var self|null
	 */
	protected static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->register_default_providers();
	}

	/**
	 * Register default Bangladesh and manual providers.
	 */
	protected function register_default_providers(): void {
		// Register verified automated providers.
		$this->register_provider( new Steadfast_Provider() );
		$this->register_provider( new Pathao_Provider() );
		$this->register_provider( new RedX_Provider() );
		$this->register_provider( new Paperfly_Provider() );

		// Register popular domestic couriers with standard URL templates.
		$this->register_provider(
			new Manual_Provider(
				'sundarban',
				__( 'Sundarban Courier Service', 'wpcalibrate-bangladesh-order-tracker' ),
				'https://sundarbancourierltd.com/track/{tracking_number}'
			)
		);

		$this->register_provider(
			new Manual_Provider(
				'sa_paribahan',
				__( 'SA Paribahan', 'wpcalibrate-bangladesh-order-tracker' ),
				'https://saparibahan.com/tracking?cn={tracking_number}'
			)
		);

		$this->register_provider(
			new Manual_Provider(
				'ecourier',
				__( 'eCourier', 'wpcalibrate-bangladesh-order-tracker' ),
				'https://ecourier.com.bd/track-order/?tracking_id={tracking_number}'
			)
		);

		$this->register_provider(
			new Manual_Provider(
				'karatoa',
				__( 'Karatoa Courier', 'wpcalibrate-bangladesh-order-tracker' ),
				'https://karatoacourier.com/track/{tracking_number}'
			)
		);

		$this->register_provider(
			new Manual_Provider(
				'custom',
				__( 'Other / Merchant Delivery', 'wpcalibrate-bangladesh-order-tracker' ),
				''
			)
		);

		/**
		 * Allow third-party extensions to register custom courier providers.
		 *
		 * @param Courier_Manager $this Courier manager instance.
		 */
		do_action( 'wpcalibrate_bot_register_courier_providers', $this );
	}

	/**
	 * Register a provider instance.
	 *
	 * @param Courier_Provider_Interface $provider
	 */
	public function register_provider( Courier_Provider_Interface $provider ): void {
		$this->providers[ $provider->get_id() ] = $provider;
	}

	/**
	 * Get all registered providers.
	 *
	 * @return array<string, Courier_Provider_Interface>
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/**
	 * Get a specific provider by ID.
	 *
	 * @param string $id
	 * @return Courier_Provider_Interface|null
	 */
	public function get_provider( string $id ): ?Courier_Provider_Interface {
		return $this->providers[ $id ] ?? null;
	}

	/**
	 * Get list of couriers suitable for select dropdowns.
	 *
	 * @return array<string, string> Key => Label
	 */
	public function get_couriers_list(): array {
		$list = [];
		foreach ( $this->providers as $id => $provider ) {
			$list[ $id ] = $provider->get_name();
		}
		return apply_filters( 'wpcalibrate_bot_courier_list', $list );
	}

	/**
	 * Resolve public tracking URL for a shipment.
	 *
	 * @param string $courier_id
	 * @param string $tracking_number
	 * @param string $custom_template
	 * @return string
	 */
	public function resolve_tracking_url( string $courier_id, string $tracking_number, string $custom_template = '' ): string {
		$provider = $this->get_provider( $courier_id );
		if ( ! $provider ) {
			// Fallback to manual custom resolution.
			$manual = new Manual_Provider( 'temp', 'Temp', $custom_template );
			return $manual->build_tracking_url( $tracking_number, $custom_template );
		}

		return $provider->build_tracking_url( $tracking_number, $custom_template );
	}
}
