<?php
/**
 * Currency Repository for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Currency
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Currency;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository for managing enabled currencies, defaults, and persistence.
 */
final class CurrencyRepository {

	public const OPTION_NAME = 'wpcalibrate_mc_currencies';

	/**
	 * In-memory cache of Currency objects.
	 *
	 * @var array<string, Currency>|null
	 */
	private static ?array $currencies_cache = null;

	/**
	 * Get all configured currencies.
	 *
	 * @return array<string, Currency> Keyed by uppercase 3-letter currency code.
	 */
	public static function get_all(): array {
		if ( null !== self::$currencies_cache ) {
			return self::$currencies_cache;
		}

		$stored = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		$base_code = self::get_base_currency_code();
		$currencies = [];

		foreach ( $stored as $code => $data ) {
			if ( ! is_array( $data ) ) {
				continue;
			}
			$c = new Currency( $data );
			$currencies[ $c->code ] = $c;
		}

		// Ensure store base currency is always present, enabled, and locked at rate 1
		if ( ! isset( $currencies[ $base_code ] ) ) {
			$currencies[ $base_code ] = self::create_default_currency( $base_code, true );
		} else {
			$currencies[ $base_code ]->is_base       = true;
			$currencies[ $base_code ]->enabled       = true;
			$currencies[ $base_code ]->exchange_rate = 1.0;
		}

		// Sort by sort_order then code
		uasort(
			$currencies,
			static fn( Currency $a, Currency $b ) => $a->sort_order <=> $b->sort_order ?: strcmp( $a->code, $b->code )
		);

		self::$currencies_cache = $currencies;
		return self::$currencies_cache;
	}

	/**
	 * Get only enabled currencies.
	 *
	 * @return array<string, Currency>
	 */
	public static function get_enabled(): array {
		$all = self::get_all();
		return array_filter( $all, static fn( Currency $c ) => $c->enabled );
	}

	/**
	 * Get a specific currency by code.
	 *
	 * @param string $code 3-letter currency code.
	 * @return Currency|null
	 */
	public static function get( string $code ): ?Currency {
		$code = strtoupper( substr( trim( $code ), 0, 3 ) );
		$all  = self::get_all();
		return $all[ $code ] ?? null;
	}

	/**
	 * Check if a currency code is enabled.
	 *
	 * @param string $code
	 * @return bool
	 */
	public static function is_enabled( string $code ): bool {
		$currency = self::get( $code );
		return null !== $currency && $currency->enabled;
	}

	/**
	 * Get the store base currency code from WooCommerce.
	 *
	 * Directly reads the stored 'woocommerce_currency' option to avoid infinite
	 * recursion loops from the 'woocommerce_currency' filter hook.
	 *
	 * @return string
	 */
	public static function get_base_currency_code(): string {
		$code = get_option( 'woocommerce_currency', 'USD' );
		if ( ! empty( $code ) && is_string( $code ) ) {
			return strtoupper( trim( $code ) );
		}
		return 'USD';
	}

	/**
	 * Get the base Currency instance.
	 *
	 * @return Currency
	 */
	public static function get_base(): Currency {
		$base_code = self::get_base_currency_code();
		$base      = self::get( $base_code );
		if ( ! $base ) {
			$base = self::create_default_currency( $base_code, true );
			self::$currencies_cache[ $base_code ] = $base;
		}
		return $base;
	}

	/**
	 * Save/update currencies array.
	 *
	 * @param array<string, array<string, mixed>|Currency> $currencies
	 * @return bool
	 */
	public static function save_all( array $currencies ): bool {
		$base_code = self::get_base_currency_code();
		$to_store  = [];

		foreach ( $currencies as $code => $item ) {
			$data = $item instanceof Currency ? $item->to_array() : (array) $item;
			$c    = new Currency( $data );

			if ( $c->code === $base_code ) {
				$c->is_base       = true;
				$c->enabled       = true;
				$c->exchange_rate = 1.0;
			} else {
				$c->is_base       = false;
			}

			$to_store[ $c->code ] = $c->to_array();
		}

		if ( ! isset( $to_store[ $base_code ] ) ) {
			$base_default = self::create_default_currency( $base_code, true );
			$to_store[ $base_code ] = $base_default->to_array();
		}

		$updated = update_option( self::OPTION_NAME, $to_store );
		self::flush_cache();
		return $updated;
	}

	/**
	 * Create a default Currency instance with standard formatting for an ISO code.
	 *
	 * @param string $code
	 * @param bool   $is_base
	 * @return Currency
	 */
	public static function create_default_currency( string $code, bool $is_base = false ): Currency {
		$code     = strtoupper( substr( trim( $code ), 0, 3 ) );
		$defaults = self::get_iso_currency_info( $code );

		return new Currency( [
			'code'                 => $code,
			'name'                 => $defaults['name'],
			'symbol'               => $defaults['symbol'],
			'symbol_position'      => $defaults['symbol_position'],
			'decimal_places'       => $defaults['decimal_places'],
			'decimal_separator'    => $defaults['decimal_separator'],
			'thousands_separator'  => $defaults['thousands_separator'],
			'enabled'              => true,
			'is_base'              => $is_base,
			'sort_order'           => 0,
			'exchange_rate'        => 1.0,
			'rate_source'          => 'manual',
			'manual_rate'          => 1.0,
			'rate_markup'          => 0.0,
			'rounding_method'      => 'none',
			'rounding_increment'   => 1.0,
			'psychological_ending' => null,
			'associated_countries' => $defaults['countries'],
			'allowed_gateways'     => [],
			'last_updated'         => time(),
			'rate_status'          => 'valid',
			'error_message'        => '',
		] );
	}

	/**
	 * Get standard ISO information for a currency code.
	 *
	 * @param string $code
	 * @return array<string, mixed>
	 */
	public static function get_iso_currency_info( string $code ): array {
		$symbols = function_exists( 'get_woocommerce_currency_symbols' ) ? get_woocommerce_currency_symbols() : [];
		$names   = function_exists( 'get_woocommerce_currencies' ) ? get_woocommerce_currencies() : [];

		// Well-known zero decimal currencies
		$zero_decimals = [ 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' ];
		// 3-decimal currencies
		$three_decimals = [ 'BHD', 'IQD', 'JOD', 'KWD', 'OMR', 'TND' ];

		$decimals = 2;
		if ( in_array( $code, $zero_decimals, true ) ) {
			$decimals = 0;
		} elseif ( in_array( $code, $three_decimals, true ) ) {
			$decimals = 3;
		}

		$symbol = $symbols[ $code ] ?? ( function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol( $code ) : '$' );
		$name   = $names[ $code ] ?? $code;

		// Default countries for major currencies
		$countries = match ( $code ) {
			'USD' => [ 'US' ],
			'EUR' => [ 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'PT', 'IE', 'FI', 'GR' ],
			'GBP' => [ 'GB' ],
			'CAD' => [ 'CA' ],
			'AUD' => [ 'AU' ],
			'JPY' => [ 'JP' ],
			'CHF' => [ 'CH' ],
			'NZD' => [ 'NZ' ],
			'INR' => [ 'IN' ],
			'CNY' => [ 'CN' ],
			'BRL' => [ 'BR' ],
			'MXN' => [ 'MX' ],
			'ZAR' => [ 'ZA' ],
			'AED' => [ 'AE' ],
			'SAR' => [ 'SA' ],
			'SGD' => [ 'SG' ],
			'HKD' => [ 'HK' ],
			default => [],
		};

		return [
			'name'                => $name,
			'symbol'              => $symbol,
			'symbol_position'     => in_array( $code, [ 'EUR', 'BRL' ], true ) ? 'right_space' : 'left',
			'decimal_places'      => $decimals,
			'decimal_separator'   => in_array( $code, [ 'EUR', 'BRL' ], true ) ? ',' : '.',
			'thousands_separator' => in_array( $code, [ 'EUR', 'BRL' ], true ) ? '.' : ',',
			'countries'           => $countries,
		];
	}

	/**
	 * Reset static memory cache.
	 *
	 * @return void
	 */
	public static function flush_cache(): void {
		self::$currencies_cache = null;
	}
}
