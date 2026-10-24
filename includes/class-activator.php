<?php
/**
 * Plugin Activator Handler.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Activator
 *
 * Runs upon plugin activation: initializes default options and creates default tracking page.
 */
class Activator {

	public static function activate(): void {
		// Initialize options if not present.
		$existing = get_option( Settings::OPTION_KEY, null );
		if ( null === $existing ) {
			update_option( Settings::OPTION_KEY, Settings::get_defaults() );
		}

		// Ensure tracking page exists or create draft tracking page if none configured.
		self::maybe_create_tracking_page();
	}

	/**
	 * Automatically create a standard tracking page with shortcode if one doesn't exist.
	 */
	protected static function maybe_create_tracking_page(): void {
		$settings = get_option( Settings::OPTION_KEY, [] );
		$page_id  = (int) ( $settings['tracking_page']['tracking_page_id'] ?? 0 );

		if ( $page_id > 0 && get_post( $page_id ) ) {
			return;
		}

		// Check if a page titled "Track Order" or slug "track-order" already exists.
		$existing_page = get_page_by_path( 'track-order' );
		if ( $existing_page ) {
			$settings['tracking_page']['tracking_page_id'] = $existing_page->ID;
			update_option( Settings::OPTION_KEY, $settings );
			return;
		}

		$new_page_id = wp_insert_post(
			[
				'post_title'     => __( 'Track Order', 'wpcalibrate-bangladesh-order-tracker' ),
				'post_name'      => 'track-order',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_content'   => '<!-- wp:shortcode -->[wpcalibrate_order_tracking]<!-- /wp:shortcode -->',
				'comment_status' => 'closed',
			]
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id > 0 ) {
			$settings['tracking_page']['tracking_page_id'] = $new_page_id;
			update_option( Settings::OPTION_KEY, $settings );
		}
	}
}
