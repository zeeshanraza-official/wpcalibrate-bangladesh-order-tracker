<?php
/**
 * Main Plugin Coordinator Class.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Bootstraps and orchestrates all plugin subsystems.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	public Settings $settings;

	public Shipment_Repository $repository;

	public Courier_Manager $courier_manager;

	public ?Admin $admin = null;

	public Tracking_Page $tracking_page;

	public Notifications $notifications;

	public REST_API $rest_api;

	public Cron $cron;

	public Privacy $privacy;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_subsystems();
		$this->init_hooks();
	}

	/**
	 * Require all component files.
	 */
	private function load_dependencies(): void {
		// Integrations.
		require_once WPCALIBRATE_BOT_PATH . 'includes/integrations/interface-courier-provider.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/integrations/class-manual-provider.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/integrations/class-steadfast-provider.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/integrations/class-pathao-provider.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/integrations/class-redx-provider.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/integrations/class-paperfly-provider.php';

		// Core classes.
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-courier-manager.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-settings.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-shipment-repository.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-admin.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-tracking-page.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-notifications.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-rest-api.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-cron.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-privacy.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-activator.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-deactivator.php';
	}

	/**
	 * Instantiate plugin subsystems.
	 */
	private function init_subsystems(): void {
		$this->settings        = new Settings();
		$this->repository      = new Shipment_Repository();
		$this->courier_manager = Courier_Manager::instance();
		$this->tracking_page   = new Tracking_Page( $this->settings, $this->repository );
		$this->notifications   = new Notifications( $this->settings, $this->repository );
		$this->rest_api        = new REST_API( $this->settings, $this->repository );
		$this->cron            = new Cron( $this->settings, $this->repository );
		$this->privacy         = new Privacy( $this->settings, $this->repository );

		if ( is_admin() ) {
			$this->admin = new Admin( $this->settings, $this->repository );
		}
	}

	/**
	 * Register general hooks.
	 */
	private function init_hooks(): void {
		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_filter( 'plugin_action_links_' . WPCALIBRATE_BOT_BASENAME, [ $this, 'add_action_links' ] );
	}

	/**
	 * Load translation text domain.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wpcalibrate-bangladesh-order-tracker',
			false,
			dirname( WPCALIBRATE_BOT_BASENAME ) . '/languages'
		);
	}

	/**
	 * Add "Settings" and "Support" action links to the plugins table.
	 *
	 * @param array<string, string> $links
	 * @return array<string, string>
	 */
	public function add_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker' ) ),
			esc_html__( 'Settings', 'wpcalibrate-bangladesh-order-tracker' )
		);

		$support_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker&tab=support' ) ),
			esc_html__( 'Support', 'wpcalibrate-bangladesh-order-tracker' )
		);

		array_unshift( $links, $support_link );
		array_unshift( $links, $settings_link );

		return $links;
	}
}
