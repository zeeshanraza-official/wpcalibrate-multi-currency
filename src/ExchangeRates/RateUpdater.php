<?php
/**
 * Rate Updater orchestrator for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\ExchangeRates
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\ExchangeRates;

use WPCalibrate\MultiCurrency\Currency\Currency;
use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\ExchangeRates\Contracts\ProviderInterface;
use WPCalibrate\MultiCurrency\ExchangeRates\Providers\FrankfurterProvider;
use WPCalibrate\MultiCurrency\ExchangeRates\Providers\ManualProvider;
use WPCalibrate\MultiCurrency\ExchangeRates\Providers\OpenExchangeRatesProvider;
use WPCalibrate\MultiCurrency\Logging\Logger;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates fetching, validating, and updating exchange rates across providers.
 */
final class RateUpdater {

	/**
	 * Available providers list.
	 *
	 * @var array<string, ProviderInterface>|null
	 */
	private static ?array $providers = null;

	/**
	 * Get all registered exchange rate providers.
	 *
	 * @return array<string, ProviderInterface>
	 */
	public static function get_providers(): array {
		if ( null !== self::$providers ) {
			return self::$providers;
		}

		$list = [
			'manual'            => new ManualProvider(),
			'frankfurter'       => new FrankfurterProvider(),
			'openexchangerates' => new OpenExchangeRatesProvider(),
		];

		/**
		 * Filter available exchange rate providers.
		 *
		 * @param array<string, ProviderInterface> $list
		 */
		self::$providers = (array) apply_filters( 'wpcalibrate_multi_currency_rate_providers', $list );
		return self::$providers;
	}

	/**
	 * Get a specific provider by ID.
	 *
	 * @param string $id
	 * @return ProviderInterface|null
	 */
	public static function get_provider( string $id ): ?ProviderInterface {
		$providers = self::get_providers();
		return $providers[ $id ] ?? null;
	}

	/**
	 * Refresh exchange rates using the currently configured provider.
	 *
	 * @param bool $force Force refresh even if within interval.
	 * @return array{success: bool, message: string, updated_count: int, rates: array<string, float>}
	 */
	public static function refresh_rates( bool $force = false ): array {
		$provider_id = (string) SettingsManager::get( 'rate_provider', 'frankfurter' );
		$provider    = self::get_provider( $provider_id );

		if ( ! $provider ) {
			$msg = sprintf(
				/* translators: %s: Provider ID */
				__( 'Exchange rate provider "%s" is not registered.', 'wpcalibrate-multi-currency' ),
				$provider_id
			);
			Logger::error( $msg );
			return [
				'success'       => false,
				'message'       => $msg,
				'updated_count' => 0,
				'rates'         => [],
			];
		}

		if ( 'manual' === $provider_id ) {
			return [
				'success'       => true,
				'message'       => __( 'Manual rate provider is active. Rates are maintained manually.', 'wpcalibrate-multi-currency' ),
				'updated_count' => 0,
				'rates'         => [],
			];
		}

		if ( ! $provider->is_configured() ) {
			$msg = sprintf(
				/* translators: %s: Provider label */
				__( 'Provider "%s" is not configured (missing credentials).', 'wpcalibrate-multi-currency' ),
				$provider->get_label()
			);
			Logger::warning( $msg );
			return [
				'success'       => false,
				'message'       => $msg,
				'updated_count' => 0,
				'rates'         => [],
			];
		}

		$base_code   = CurrencyRepository::get_base_currency_code();
		$currencies  = CurrencyRepository::get_all();
		$target_codes = [];

		foreach ( $currencies as $c ) {
			if ( $c->code !== $base_code && $c->enabled ) {
				// Only fetch if not manually overridden
				if ( 'manual' !== $c->rate_source ) {
					$target_codes[] = $c->code;
				}
			}
		}

		if ( empty( $target_codes ) ) {
			return [
				'success'       => true,
				'message'       => __( 'No enabled automatic currencies to update.', 'wpcalibrate-multi-currency' ),
				'updated_count' => 0,
				'rates'         => [],
			];
		}

		$results = $provider->get_rates( $base_code, $target_codes );

		if ( is_wp_error( $results ) ) {
			$error_message = $results->get_error_message();

			// Mark currencies as having an error without clearing their last known good rate
			foreach ( $target_codes as $code ) {
				if ( isset( $currencies[ $code ] ) ) {
					$currencies[ $code ]->rate_status   = 'error';
					$currencies[ $code ]->error_message = $error_message;
				}
			}
			CurrencyRepository::save_all( $currencies );

			self::record_refresh_log( false, $error_message );

			return [
				'success'       => false,
				'message'       => $error_message,
				'updated_count' => 0,
				'rates'         => [],
			];
		}

		$updated_count = 0;
		$now           = time();

		foreach ( $results as $code => $rate ) {
			$code = strtoupper( (string) $code );
			if ( isset( $currencies[ $code ] ) && $rate > 0.0 ) {
				$currencies[ $code ]->exchange_rate = $rate;
				$currencies[ $code ]->last_updated  = $now;
				$currencies[ $code ]->rate_status   = 'valid';
				$currencies[ $code ]->error_message = '';
				$currencies[ $code ]->rate_source   = $provider_id;
				++$updated_count;
			}
		}

		CurrencyRepository::save_all( $currencies );

		$msg = sprintf(
			/* translators: 1: Count of currencies, 2: Provider name */
			__( 'Successfully updated %1$d currency rates from %2$s.', 'wpcalibrate-multi-currency' ),
			$updated_count,
			$provider->get_label()
		);

		self::record_refresh_log( true, $msg );
		Logger::info( $msg );

		return [
			'success'       => true,
			'message'       => $msg,
			'updated_count' => $updated_count,
			'rates'         => $results,
		];
	}

	/**
	 * Check for stale rates across all enabled currencies.
	 *
	 * @return array<Currency> List of stale currencies.
	 */
	public static function get_stale_currencies(): array {
		$threshold_hours = (int) SettingsManager::get( 'stale_rate_threshold_hours', 48 );
		$cutoff          = time() - ( $threshold_hours * 3600 );
		$enabled         = CurrencyRepository::get_enabled();
		$stale           = [];

		foreach ( $enabled as $c ) {
			if ( $c->is_base || 'manual' === $c->rate_source ) {
				continue;
			}
			if ( $c->last_updated < $cutoff || 'error' === $c->rate_status ) {
				$stale[] = $c;
			}
		}

		return $stale;
	}

	/**
	 * Record refresh history in transient/option.
	 *
	 * @param bool   $success
	 * @param string $message
	 * @return void
	 */
	private static function record_refresh_log( bool $success, string $message ): void {
		update_option(
			'wpcalibrate_mc_last_rate_refresh',
			[
				'timestamp' => time(),
				'success'   => $success,
				'message'   => $message,
			],
			false
		);
	}

	/**
	 * Get last refresh log.
	 *
	 * @return array{timestamp: int, success: bool, message: string}|null
	 */
	public static function get_last_refresh_log(): ?array {
		$log = get_option( 'wpcalibrate_mc_last_rate_refresh', null );
		return is_array( $log ) ? $log : null;
	}
}
