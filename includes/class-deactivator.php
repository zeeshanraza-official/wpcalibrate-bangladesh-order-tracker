<?php
/**
 * Plugin Deactivator Handler.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Deactivator
 *
 * Runs upon plugin deactivation: unschedules cron jobs while preserving customer data.
 */
class Deactivator {

	public static function deactivate(): void {
		Cron::unschedule_all();
	}
}
