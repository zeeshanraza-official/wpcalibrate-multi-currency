<?php
/**
 * Tax Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Tax
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Tax;

use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles consistency for tax calculations across currencies.
 */
final class TaxManager {

	/**
	 * Register tax hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		// Ensure tax line rounding respects active currency decimals
		add_filter( 'woocommerce_tax_round_at_subtotal', [ self::class, 'filter_tax_round_at_subtotal' ] );
	}

	/**
	 * Filter tax round at subtotal option if necessary.
	 *
	 * @param bool $round_at_subtotal
	 * @return bool
	 */
	public static function filter_tax_round_at_subtotal( bool $round_at_subtotal ): bool {
		return $round_at_subtotal;
	}
}
