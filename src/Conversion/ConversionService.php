<?php
/**
 * Conversion Engine for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Conversion
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Conversion;

use WPCalibrate\MultiCurrency\Currency\Currency;
use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Authoritative, deterministic conversion service.
 */
final class ConversionService {

	/**
	 * Tracks hashes of converted amounts within the current request to prevent double conversion.
	 *
	 * @var array<string, float>
	 */
	private static array $conversion_cache = [];

	/**
	 * Convert a base amount into a target currency.
	 *
	 * Execution order:
	 * 1. Return original if target currency is the store base currency.
	 * 2. Return 0.0 if base amount is zero or empty.
	 * 3. Base amount * Exchange rate.
	 * 4. Apply rate markup adjustment percentage.
	 * 5. Apply rounding rule.
	 * 6. Apply psychological price ending (if configured).
	 * 7. Format with target currency decimal places.
	 *
	 * @param float|int|string|null $base_amount Numeric monetary amount in store base currency.
	 * @param Currency|string       $target      Currency object or 3-letter currency code.
	 * @param bool                  $apply_rules Whether to apply markup, rounding and psychological ending.
	 * @return float Converted monetary amount.
	 */
	public static function convert( mixed $base_amount, Currency|string $target, bool $apply_rules = true ): float {
		if ( null === $base_amount || '' === $base_amount ) {
			return 0.0;
		}

		$amount = (float) $base_amount;
		if ( 0.0 === $amount ) {
			return 0.0;
		}

		$currency = is_string( $target ) ? CurrencyRepository::get( $target ) : $target;
		if ( ! $currency ) {
			return $amount;
		}

		// If target is base currency, no conversion needed
		if ( $currency->is_base || $currency->code === CurrencyRepository::get_base_currency_code() ) {
			return round( $amount, $currency->decimal_places );
		}

		// Exchange rate with filter
		$rate = (float) apply_filters(
			'wpcalibrate_multi_currency_exchange_rate',
			$currency->exchange_rate,
			$currency->code,
			$amount
		);

		if ( $rate <= 0.0 ) {
			$rate = 1.0;
		}

		// Step 1: Base * Rate
		$converted = $amount * $rate;

		if ( ! $apply_rules ) {
			return round( $converted, $currency->decimal_places );
		}

		// Step 2: Rate adjustment/markup percentage
		if ( 0.0 !== $currency->rate_markup ) {
			$converted *= ( 1.0 + ( $currency->rate_markup / 100.0 ) );
		}

		// Step 3: Rounding rule
		$converted = self::apply_rounding( $converted, $currency );

		// Step 4: Psychological pricing
		if ( null !== $currency->psychological_ending ) {
			$converted = self::apply_psychological_ending( $converted, $currency->psychological_ending, $currency->decimal_places );
		}

		// Step 5: Final precision
		$final = round( $converted, $currency->decimal_places );

		return (float) apply_filters(
			'wpcalibrate_multi_currency_converted_amount',
			$final,
			$amount,
			$currency->code,
			$currency
		);
	}

	/**
	 * Convert amount from target currency BACK to store base currency (reverse conversion).
	 *
	 * @param float|int|string|null $target_amount
	 * @param Currency|string       $source
	 * @return float
	 */
	public static function convert_to_base( mixed $target_amount, Currency|string $source ): float {
		if ( null === $target_amount || '' === $target_amount ) {
			return 0.0;
		}

		$amount   = (float) $target_amount;
		$currency = is_string( $source ) ? CurrencyRepository::get( $source ) : $source;

		if ( ! $currency || $currency->is_base || $currency->exchange_rate <= 0.0 ) {
			return $amount;
		}

		// Reverse markup if present
		$effective_rate = $currency->exchange_rate;
		if ( 0.0 !== $currency->rate_markup ) {
			$effective_rate *= ( 1.0 + ( $currency->rate_markup / 100.0 ) );
		}

		$base = $amount / $effective_rate;
		$base_currency = CurrencyRepository::get_base();

		return round( $base, $base_currency->decimal_places );
	}

	/**
	 * Apply rounding rules.
	 *
	 * @param float    $amount
	 * @param Currency $currency
	 * @return float
	 */
	public static function apply_rounding( float $amount, Currency $currency ): float {
		return match ( $currency->rounding_method ) {
			'standard'  => round( $amount, 0 ),
			'up'        => ceil( $amount ),
			'down'      => floor( $amount ),
			'increment' => self::round_to_increment( $amount, $currency->rounding_increment ),
			'none'      => $amount,
			default     => $amount,
		};
	}

	/**
	 * Round to nearest increment (e.g. 0.05, 0.50, 1.00).
	 *
	 * @param float $amount
	 * @param float $increment
	 * @return float
	 */
	public static function round_to_increment( float $amount, float $increment ): float {
		if ( $increment <= 0.0 ) {
			return $amount;
		}
		return round( $amount / $increment ) * $increment;
	}

	/**
	 * Apply psychological pricing ending (e.g., .99).
	 *
	 * @param float $amount
	 * @param float $ending E.g. 0.99
	 * @param int   $decimals Currency decimal places
	 * @return float
	 */
	public static function apply_psychological_ending( float $amount, float $ending, int $decimals ): float {
		if ( $decimals <= 0 ) {
			return $amount;
		}

		$integer_part = floor( $amount );
		$candidate    = $integer_part + $ending;

		// If candidate is substantially lower than amount (e.g. amount was 15.80 and candidate is 15.20),
		// keep candidate in consistent direction
		if ( $candidate <= 0.0 ) {
			return $amount;
		}

		return round( $candidate, $decimals );
	}

	/**
	 * Helper to cache a converted value for an entity during the request.
	 *
	 * @param string $entity_id
	 * @param string $currency_code
	 * @param float  $value
	 * @return void
	 */
	public static function cache_converted( string $entity_id, string $currency_code, float $value ): void {
		self::$conversion_cache[ $entity_id . '_' . $currency_code ] = $value;
	}

	/**
	 * Helper to get a cached converted value for an entity.
	 *
	 * @param string $entity_id
	 * @param string $currency_code
	 * @return float|null
	 */
	public static function get_cached_converted( string $entity_id, string $currency_code ): ?float {
		return self::$conversion_cache[ $entity_id . '_' . $currency_code ] ?? null;
	}

	/**
	 * Clear conversion memory cache.
	 *
	 * @return void
	 */
	public static function flush_cache(): void {
		self::$conversion_cache = [];
	}
}
