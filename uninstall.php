<?php
/**
 * Plugin Uninstall Handler.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

// Prevent direct execution.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// 1. Check if the administrator explicitly opted-in to data removal upon uninstall.
$settings = get_option( 'wpcalibrate_bot_settings', [] );
$delete_data = 'yes' === ( $settings['privacy']['delete_data_on_uninstall'] ?? 'no' );

if ( ! $delete_data ) {
	// Preserve all shipment data, order meta, and configuration settings by default.
	return;
}

global $wpdb;

// 2. Remove isolated plugin settings and license options.
delete_option( 'wpcalibrate_bot_settings' );
delete_option( 'wpcalibrate_bot_license' );

// 3. Delete transients.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpcalibrate_bot_%' OR option_name LIKE '_transient_timeout_wpcalibrate_bot_%'" );

// 4. Remove shipment metadata from orders (HPOS and legacy tables).
$meta_keys = [
	'_wpcalibrate_bot_shipments',
	'_wpcalibrate_bot_tracking_numbers',
];

foreach ( $meta_keys as $key ) {
	// Legacy postmeta table.
	$wpdb->delete(
		$wpdb->postmeta,
		[ 'meta_key' => $key ],
		[ '%s' ]
	);

	// WooCommerce HPOS orders meta table (if it exists).
	$hpos_meta_table = $wpdb->prefix . 'wc_orders_meta';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $hpos_meta_table ) ) === $hpos_meta_table ) {
		$wpdb->delete(
			$hpos_meta_table,
			[ 'meta_key' => $key ],
			[ '%s' ]
		);
	}
}
