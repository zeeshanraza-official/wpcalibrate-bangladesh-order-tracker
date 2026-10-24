<?php
/**
 * Plugin Name:       WPCalibrate Bangladesh Order Tracker
 * Plugin URI:        https://wpcalibrate.com
 * Description:       High-performance WooCommerce shipment tracking and customer tracking portal for Bangladesh stores with Steadfast, Pathao, RedX, Paperfly, and domestic courier support.
 * Version:           1.0.0
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-bangladesh-order-tracker
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * WC requires at least: 8.0
 * WC tested up to:   10.2
 *
 * @package           WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'WPCALIBRATE_BOT_VERSION', '1.0.0' );
define( 'WPCALIBRATE_BOT_FILE', __FILE__ );
define( 'WPCALIBRATE_BOT_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPCALIBRATE_BOT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCALIBRATE_BOT_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCALIBRATE_BOT_MIN_PHP', '8.2' );
define( 'WPCALIBRATE_BOT_MIN_WP', '6.2' );
define( 'WPCALIBRATE_BOT_MIN_WC', '8.0' );

/**
 * Declare WooCommerce HPOS and Cart/Checkout Blocks compatibility.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

/**
 * Environment and dependency verification before booting plugin.
 */
function wpcalibrate_bot_check_environment(): bool {
	// 1. PHP Version check.
	if ( version_compare( PHP_VERSION, WPCALIBRATE_BOT_MIN_PHP, '<' ) ) {
		add_action(
			'admin_notices',
			function () {
				printf(
					'<div class="notice notice-error"><p><strong>%1$s:</strong> %2$s</p></div>',
					esc_html__( 'WPCalibrate Bangladesh Order Tracker', 'wpcalibrate-bangladesh-order-tracker' ),
					sprintf(
						/* translators: 1: required PHP version, 2: current PHP version */
						esc_html__( 'Requires PHP version %1$s or higher. Your server is running PHP %2$s. Please upgrade PHP to use this plugin.', 'wpcalibrate-bangladesh-order-tracker' ),
						esc_html( WPCALIBRATE_BOT_MIN_PHP ),
						esc_html( PHP_VERSION )
					)
				);
			}
		);
		return false;
	}

	// 2. WordPress Version check.
	global $wp_version;
	if ( version_compare( $wp_version, WPCALIBRATE_BOT_MIN_WP, '<' ) ) {
		add_action(
			'admin_notices',
			function () use ( $wp_version ) {
				printf(
					'<div class="notice notice-error"><p><strong>%1$s:</strong> %2$s</p></div>',
					esc_html__( 'WPCalibrate Bangladesh Order Tracker', 'wpcalibrate-bangladesh-order-tracker' ),
					sprintf(
						/* translators: 1: required WP version, 2: current WP version */
						esc_html__( 'Requires WordPress version %1$s or higher. Your site is running WordPress %2$s.', 'wpcalibrate-bangladesh-order-tracker' ),
						esc_html( WPCALIBRATE_BOT_MIN_WP ),
						esc_html( $wp_version )
					)
				);
			}
		);
		return false;
	}

	return true;
}

/**
 * WooCommerce dependency check and plugin bootstrap.
 */
function wpcalibrate_bot_init(): void {
	if ( ! wpcalibrate_bot_check_environment() ) {
		return;
	}

	// Verify WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				printf(
					'<div class="notice notice-warning is-dismissible"><p><strong>%1$s:</strong> %2$s</p></div>',
					esc_html__( 'WPCalibrate Bangladesh Order Tracker', 'wpcalibrate-bangladesh-order-tracker' ),
					esc_html__( 'WooCommerce must be installed and activated for order tracking functionality to operate.', 'wpcalibrate-bangladesh-order-tracker' )
				);
			}
		);
		return;
	}

	// Load core coordinator.
	require_once WPCALIBRATE_BOT_PATH . 'includes/class-plugin.php';
	\WPCalibrate\BangladeshOrderTracker\Plugin::instance();
}
add_action( 'plugins_loaded', 'wpcalibrate_bot_init', 10 );

/**
 * Register activation hook.
 */
register_activation_hook(
	__FILE__,
	function () {
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-settings.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-activator.php';
		\WPCalibrate\BangladeshOrderTracker\Activator::activate();
	}
);

/**
 * Register deactivation hook.
 */
register_deactivation_hook(
	__FILE__,
	function () {
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-cron.php';
		require_once WPCALIBRATE_BOT_PATH . 'includes/class-deactivator.php';
		\WPCalibrate\BangladeshOrderTracker\Deactivator::deactivate();
	}
);
