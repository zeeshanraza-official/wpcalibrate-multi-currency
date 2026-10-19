<?php
/**
 * Migration Runner for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Migration
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Migration;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database versioning, upgrades, and migrations safely and idempotently.
 */
final class MigrationRunner {

	public const DB_VERSION_OPTION = 'wpcalibrate_mc_db_version';
	public const TARGET_VERSION    = '1.0.0';

	/**
	 * Run migrations if needed.
	 *
	 * @return void
	 */
	public static function run(): void {
		$installed_version = get_option( self::DB_VERSION_OPTION, '0.0.0' );

		if ( version_compare( (string) $installed_version, self::TARGET_VERSION, '<' ) ) {
			self::migrate_to_1_0_0();
			update_option( self::DB_VERSION_OPTION, self::TARGET_VERSION );
		}
	}

	/**
	 * Migration to 1.0.0 baseline.
	 *
	 * @return void
	 */
	private static function migrate_to_1_0_0(): void {
		// 1. Initialize default settings if not yet stored
		$existing_settings = get_option( SettingsManager::OPTION_NAME, null );
		if ( null === $existing_settings ) {
			update_option( SettingsManager::OPTION_NAME, SettingsManager::get_defaults() );
		}

		// 2. Ensure base currency is stored in currency repository
		$base_code  = CurrencyRepository::get_base_currency_code();
		$currencies = CurrencyRepository::get_all();

		if ( ! isset( $currencies[ $base_code ] ) ) {
			$currencies[ $base_code ] = CurrencyRepository::create_default_currency( $base_code, true );
			CurrencyRepository::save_all( $currencies );
		}
	}
}
