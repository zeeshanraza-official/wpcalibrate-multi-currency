<?php
/**
 * Cart Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Cart
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Cart;

use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensures cart items, mini-cart, and totals recalculate consistently with active currency.
 */
final class CartManager {

	/**
	 * Register cart hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		// When currency changes, recalculate cart totals
		add_action( 'wpcalibrate_multi_currency_switched', [ self::class, 'on_currency_switched' ] );

		// Ensure mini-cart fragments and cart hash vary by currency
		add_filter( 'woocommerce_cart_hash', [ self::class, 'filter_cart_hash' ] );
	}

	/**
	 * Recalculate cart totals when customer switches currency.
	 *
	 * @param string $new_currency
	 * @return void
	 */
	public static function on_currency_switched( string $new_currency ): void {
		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Make cart hash depend on active currency so browser caching & fragments stay valid.
	 *
	 * @param string $cart_hash
	 * @return string
	 */
	public static function filter_cart_hash( string $cart_hash ): string {
		$active = CurrencyResolver::get_active_currency();
		return md5( $cart_hash . '_' . $active );
	}
}
