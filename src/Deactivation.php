<?php
/**
 * Plugin Deactivation Handler for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency;

use WPCalibrate\MultiCurrency\Scheduling\ActionSchedulerService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles deactivation tasks without destroying user data.
 */
final class Deactivation {

	/**
	 * Run deactivation logic.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Stop scheduled recurring rate updates
		ActionSchedulerService::unschedule();

		// Clear transient locks
		delete_transient( ActionSchedulerService::LOCK_KEY );
	}
}
