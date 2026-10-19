<?php
/**
 * Shipping Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Shipping
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Shipping;

use WPCalibrate\MultiCurrency\Conversion\ConversionService;
use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles currency conversion for shipping costs and shipping taxes.
 */
final class ShippingManager {

	/**
	 * Register shipping hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		add_filter( 'woocommerce_package_rates', [ self::class, 'filter_package_rates' ], 20, 2 );
	}

	/**
	 * Convert shipping rates and taxes to active customer currency.
	 *
	 * @param array<\WC_Shipping_Rate> $rates
	 * @param array<string, mixed>     $package
	 * @return array<\WC_Shipping_Rate>
	 */
	public static function filter_package_rates( array $rates, array $package ): array {
		$active_code = CurrencyResolver::get_active_currency();
		$base_code   = CurrencyRepository::get_base_currency_code();

		if ( $active_code === $base_code ) {
			return $rates;
		}

		$converted_rates = [];

		foreach ( $rates as $rate_id => $rate ) {
			if ( ! ( $rate instanceof \WC_Shipping_Rate ) ) {
				$converted_rates[ $rate_id ] = $rate;
				continue;
			}

			// Retrieve or store original base cost to prevent double-conversion
			$meta_data = $rate->get_meta_data();
			$base_cost = $meta_data['_wpcalibrate_base_cost'] ?? null;

			if ( null === $base_cost ) {
				$base_cost = (float) $rate->get_cost();
				$rate->add_meta_data( '_wpcalibrate_base_cost', (string) $base_cost, true );
			} else {
				$base_cost = (float) $base_cost;
			}

			$converted_cost = ConversionService::convert( $base_cost, $active_code );
			$rate->set_cost( (string) $converted_cost );

			// Convert shipping taxes
			$taxes      = $rate->get_taxes();
			$base_taxes = $meta_data['_wpcalibrate_base_taxes'] ?? null;

			if ( null === $base_taxes && is_array( $taxes ) ) {
				$base_taxes = $taxes;
				$rate->add_meta_data( '_wpcalibrate_base_taxes', $base_taxes, true );
			} elseif ( ! is_array( $base_taxes ) ) {
				$base_taxes = is_array( $taxes ) ? $taxes : [];
			}

			$converted_taxes = [];
			foreach ( $base_taxes as $tax_id => $tax_amount ) {
				$converted_taxes[ $tax_id ] = (string) ConversionService::convert( (float) $tax_amount, $active_code, false );
			}
			$rate->set_taxes( $converted_taxes );

			$converted_rates[ $rate_id ] = $rate;
		}

		return $converted_rates;
	}
}
