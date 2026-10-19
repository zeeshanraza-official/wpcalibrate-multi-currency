<?php
/**
 * Payment Gateway Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Gateways
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Gateways;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters available payment gateways per active currency and renders checkout warnings.
 */
final class GatewayManager {

	/**
	 * Register gateway hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		add_filter( 'woocommerce_available_payment_gateways', [ self::class, 'filter_available_gateways' ], 20 );
		add_action( 'woocommerce_before_checkout_form', [ self::class, 'render_no_gateway_notice' ], 10 );
	}

	/**
	 * Filter available payment gateways by active currency allowed gateways list.
	 *
	 * @param array<string, \WC_Payment_Gateway> $available_gateways
	 * @return array<string, \WC_Payment_Gateway>
	 */
	public static function filter_available_gateways( array $available_gateways ): array {
		// In Display-Only mode, checkout is in base currency, so base currency gateway rules apply
		$operating_mode = (string) SettingsManager::get( 'operating_mode', 'full' );
		$currency_code  = ( 'display_only' === $operating_mode )
			? CurrencyRepository::get_base_currency_code()
			: CurrencyResolver::get_active_currency();

		$currency = CurrencyRepository::get( $currency_code );
		if ( ! $currency || empty( $currency->allowed_gateways ) ) {
			return $available_gateways; // No restrictions configured: all otherwise-valid gateways remain
		}

		$filtered = [];
		foreach ( $available_gateways as $gateway_id => $gateway ) {
			if ( in_array( $gateway_id, $currency->allowed_gateways, true ) ) {
				$filtered[ $gateway_id ] = $gateway;
			}
		}

		return $filtered;
	}

	/**
	 * Render clear notice on checkout if no gateways are available for the active currency.
	 *
	 * @return void
	 */
	public static function render_no_gateway_notice(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return;
		}

		$operating_mode = (string) SettingsManager::get( 'operating_mode', 'full' );
		if ( 'display_only' === $operating_mode ) {
			return;
		}

		$available = WC()->payment_gateways()->get_available_payment_gateways();
		if ( empty( $available ) ) {
			$active_currency = CurrencyResolver::get_active_currency();
			$template        = (string) SettingsManager::get(
				'no_gateway_notice_text',
				__( 'No payment gateway is currently available for %s. Please select another currency or contact store support.', 'wpcalibrate-multi-currency' )
			);

			$message = sprintf( $template, esc_html( $active_currency ) );

			wc_print_notice( $message, 'error' );
		}
	}
}
