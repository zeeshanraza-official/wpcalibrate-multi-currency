<?php
/**
 * Checkout Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Checkout
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Checkout;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles checkout currency behavior, display-only mode reversion, and notices.
 */
final class CheckoutManager {

	/**
	 * Register checkout hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		add_action( 'woocommerce_before_checkout_form', [ self::class, 'handle_checkout_notices' ], 5 );
		add_action( 'woocommerce_checkout_init', [ self::class, 'handle_display_only_reversion' ] );
	}

	/**
	 * If in Display-Only mode, revert checkout context to base currency and display notice.
	 *
	 * @return void
	 */
	public static function handle_display_only_reversion(): void {
		$operating_mode = (string) SettingsManager::get( 'operating_mode', 'full' );
		if ( 'display_only' !== $operating_mode ) {
			return;
		}

		$base_code   = CurrencyRepository::get_base_currency_code();
		$active_code = CurrencyResolver::get_active_currency();

		if ( $active_code !== $base_code ) {
			// Temporarily override currency resolver for checkout processing
			CurrencyResolver::set_override_currency( $base_code );

			if ( function_exists( 'WC' ) && WC()->cart ) {
				WC()->cart->calculate_totals();
			}
		}
	}

	/**
	 * Render informative customer notices at checkout.
	 *
	 * @return void
	 */
	public static function handle_checkout_notices(): void {
		$operating_mode = (string) SettingsManager::get( 'operating_mode', 'full' );
		if ( 'display_only' !== $operating_mode ) {
			return;
		}

		$base_currency = CurrencyRepository::get_base();
		$template      = (string) SettingsManager::get(
			'display_only_notice_text',
			__( 'Prices were displayed in your selected currency for reference. Payment will be processed in our store base currency: %s.', 'wpcalibrate-multi-currency' )
		);

		$message = sprintf( $template, esc_html( $base_currency->code . ' (' . $base_currency->symbol . ')' ) );

		wc_print_notice( $message, 'notice' );
	}
}
