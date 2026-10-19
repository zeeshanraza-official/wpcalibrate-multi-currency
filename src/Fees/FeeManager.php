<?php
/**
 * Fee Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Fees
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Fees;

use WPCalibrate\MultiCurrency\Conversion\ConversionService;
use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles cart fee conversion and double-conversion prevention.
 */
final class FeeManager {

	/**
	 * IDs of fees already converted in this calculation cycle.
	 *
	 * @var array<string, bool>
	 */
	private static array $converted_fees = [];

	/**
	 * Register fee hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		add_action( 'woocommerce_cart_calculate_fees', [ self::class, 'convert_cart_fees' ], 99 );
	}

	/**
	 * Convert cart fees to active currency.
	 *
	 * @param \WC_Cart $cart
	 * @return void
	 */
	public static function convert_cart_fees( \WC_Cart $cart ): void {
		$active_code = CurrencyResolver::get_active_currency();
		$base_code   = CurrencyRepository::get_base_currency_code();

		if ( $active_code === $base_code ) {
			return;
		}

		$fees = $cart->get_fees();
		if ( empty( $fees ) ) {
			return;
		}

		foreach ( $fees as $fee_id => $fee ) {
			// Prevent double-conversion within the same request
			$cache_key = $fee_id . '_' . (string) $fee->amount . '_' . $active_code;
			if ( ! empty( self::$converted_fees[ $cache_key ] ) ) {
				continue;
			}

			// Allow external developers to declare fee already converted
			$is_already_converted = (bool) apply_filters(
				'wpcalibrate_multi_currency_fee_already_converted',
				false,
				$fee,
				$active_code
			);

			if ( $is_already_converted ) {
				continue;
			}

			$converted_amount = ConversionService::convert( (float) $fee->amount, $active_code );
			$fee->amount      = $converted_amount;

			self::$converted_fees[ $cache_key ] = true;
		}
	}
}
