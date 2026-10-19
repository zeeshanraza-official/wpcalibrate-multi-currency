<?php
/**
 * Plugin Activation Handler for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency;

use WPCalibrate\MultiCurrency\Migration\MigrationRunner;
use WPCalibrate\MultiCurrency\Scheduling\ActionSchedulerService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles activation tasks.
 */
final class Activation {

	/**
	 * Run activation logic.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Run initial migrations / defaults
		MigrationRunner::run();

		// Set up scheduled Action Scheduler tasks if WooCommerce is present
		if ( Dependencies::is_woocommerce_active() ) {
			ActionSchedulerService::sync_schedule();
		}
	}
}
