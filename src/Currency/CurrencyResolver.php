<?php
/**
 * Currency Resolver for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Currency
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Currency;

use WPCalibrate\MultiCurrency\Geolocation\GeolocationService;
use WPCalibrate\MultiCurrency\Persistence\PersistenceService;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves the currently active currency following strict precedence rules.
 */
final class CurrencyResolver {

	/**
	 * Cached active currency code for the current request.
	 *
	 * @var string|null
	 */
	private static ?string $active_currency_cache = null;

	/**
	 * Temporary override for testing or specific scopes.
	 *
	 * @var string|null
	 */
	private static ?string $override_currency = null;

	/**
	 * Resolve the active currency code.
	 *
	 * Precedence:
	 * 1. Forced override (if set in code).
	 * 2. Deliberate URL query parameter (?wpc_currency=XYZ or ?currency=XYZ) if allowed in settings.
	 * 3. Stored choice (Session, User Meta, Cookie).
	 * 4. Geolocation mapping (if enabled and user has NOT made a manual choice).
	 * 5. Store WooCommerce base currency fallback.
	 *
	 * @return string 3-letter ISO currency code.
	 */
	public static function get_active_currency(): string {
		if ( null !== self::$override_currency ) {
			return self::$override_currency;
		}

		if ( null !== self::$active_currency_cache ) {
			return self::$active_currency_cache;
		}

		$base_currency = CurrencyRepository::get_base_currency_code();

		// Check if plugin is disabled globally
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			self::$active_currency_cache = $base_currency;
			return $base_currency;
		}

		$resolved = null;

		// 1. Check URL query parameter (e.g. ?wpc_currency=EUR)
		if ( SettingsManager::get( 'allow_currency_in_url', true ) ) {
			$param_curr = null;
			if ( isset( $_GET['wpc_currency'] ) ) {
				$param_curr = sanitize_text_field( wp_unslash( $_GET['wpc_currency'] ) );
			} elseif ( isset( $_GET['currency'] ) ) {
				$param_curr = sanitize_text_field( wp_unslash( $_GET['currency'] ) );
			}

			if ( ! empty( $param_curr ) ) {
				$code = strtoupper( substr( trim( $param_curr ), 0, 3 ) );
				if ( CurrencyRepository::is_enabled( $code ) ) {
					$resolved = $code;
					// Persist choice from deliberate URL parameter
					PersistenceService::set_currency( $code, true );
				}
			}
		}

		// 2. Check stored persistence (User meta, Session, Cookie)
		if ( null === $resolved ) {
			$stored = PersistenceService::get_stored_currency();
			if ( null !== $stored && CurrencyRepository::is_enabled( $stored ) ) {
				$resolved = $stored;
			}
		}

		// 3. Check Geolocation (only if no manual selection has been recorded)
		if ( null === $resolved && ! PersistenceService::has_manual_choice() ) {
			$geo_curr = GeolocationService::get_currency_for_visitor();
			if ( null !== $geo_curr && CurrencyRepository::is_enabled( $geo_curr ) ) {
				$resolved = $geo_curr;
				// Persist automatic detection as non-manual choice
				PersistenceService::set_currency( $geo_curr, false );
			}
		}

		// 4. Fallback to WooCommerce store base currency
		if ( null === $resolved || ! CurrencyRepository::is_enabled( $resolved ) ) {
			$resolved = $base_currency;
		}

		/**
		 * Filter the resolved active currency code.
		 *
		 * @param string $resolved 3-letter currency code.
		 */
		$resolved = (string) apply_filters( 'wpcalibrate_multi_currency_active_currency', $resolved );

		// Validate once more against enabled currencies
		if ( ! CurrencyRepository::is_enabled( $resolved ) ) {
			$resolved = $base_currency;
		}

		self::$active_currency_cache = $resolved;
		return self::$active_currency_cache;
	}

	/**
	 * Get the active Currency instance.
	 *
	 * @return Currency
	 */
	public static function get_active_currency_instance(): Currency {
		$code = self::get_active_currency();
		$curr = CurrencyRepository::get( $code );
		return $curr ?? CurrencyRepository::get_base();
	}

	/**
	 * Explicitly switch customer currency.
	 *
	 * @param string $code
	 * @return bool
	 */
	public static function switch_currency( string $code ): bool {
		$code = strtoupper( substr( trim( $code ), 0, 3 ) );
		if ( ! CurrencyRepository::is_enabled( $code ) ) {
			return false;
		}

		PersistenceService::set_currency( $code, true );
		self::$active_currency_cache = $code;

		do_action( 'wpcalibrate_multi_currency_switched', $code );

		return true;
	}

	/**
	 * Set a temporary override currency (useful for testing or specific scopes).
	 *
	 * @param string|null $code
	 * @return void
	 */
	public static function set_override_currency( ?string $code ): void {
		self::$override_currency = $code ? strtoupper( $code ) : null;
	}

	/**
	 * Reset request memory cache.
	 *
	 * @return void
	 */
	public static function flush_cache(): void {
		self::$active_currency_cache = null;
		self::$override_currency    = null;
	}
}
