<?php
/**
 * Action Scheduler Service for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Scheduling
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Scheduling;

use WPCalibrate\MultiCurrency\ExchangeRates\RateUpdater;
use WPCalibrate\MultiCurrency\Logging\Logger;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles recurring rate updates via WooCommerce Action Scheduler.
 */
final class ActionSchedulerService {

	public const HOOK_NAME = 'wpcalibrate_multi_currency_refresh_rates';
	public const GROUP     = 'wpcalibrate-multi-currency';
	public const LOCK_KEY  = 'wpcalibrate_mc_refresh_running_lock';

	/**
	 * Register hook listeners.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( self::HOOK_NAME, [ self::class, 'handle_scheduled_refresh' ] );
	}

	/**
	 * Handle scheduled refresh execution.
	 *
	 * @return void
	 */
	public static function handle_scheduled_refresh(): void {
		// Idempotency lock using transient (prevents concurrent execution)
		if ( get_transient( self::LOCK_KEY ) ) {
			Logger::info( 'ActionScheduler: Scheduled rate refresh skipped due to existing execution lock.' );
			return;
		}

		set_transient( self::LOCK_KEY, 1, 300 ); // 5-minute lock

		try {
			Logger::info( 'ActionScheduler: Executing scheduled exchange rate refresh.' );
			RateUpdater::refresh_rates();
		} catch ( \Throwable $e ) {
			Logger::error( 'ActionScheduler: Exception during rate refresh: ' . $e->getMessage() );
		} finally {
			delete_transient( self::LOCK_KEY );
		}
	}

	/**
	 * Synchronize recurring schedule with plugin settings.
	 *
	 * @return void
	 */
	public static function sync_schedule(): void {
		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		self::unschedule();

		$interval = (string) SettingsManager::get( 'rate_update_interval', 'daily' );
		if ( 'manual' === $interval ) {
			return;
		}

		$interval_seconds = match ( $interval ) {
			'hourly'      => 3600,
			'twicedaily'  => 43200,
			'daily'       => 86400,
			default       => 86400,
		};

		// Schedule starting 1 interval from now
		as_schedule_recurring_action(
			time() + $interval_seconds,
			$interval_seconds,
			self::HOOK_NAME,
			[],
			self::GROUP
		);

		Logger::info( sprintf( 'ActionScheduler: Recurring rate refresh scheduled every %d seconds.', $interval_seconds ) );
	}

	/**
	 * Unschedule all pending actions.
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::HOOK_NAME, [], self::GROUP );
		}
	}
}
