<?php
/**
 * Order Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Orders
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Orders;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles immutable multi-currency snapshot meta creation on order placement.
 */
final class OrderManager {

	public const META_PRICING_MODE     = '_wpcalibrate_mc_pricing_mode';
	public const META_ORDER_CURRENCY   = '_wpcalibrate_mc_order_currency';
	public const META_BASE_CURRENCY    = '_wpcalibrate_mc_base_currency';
	public const META_EXCHANGE_RATE    = '_wpcalibrate_mc_exchange_rate';
	public const META_RATE_MARKUP      = '_wpcalibrate_mc_rate_markup';
	public const META_RATE_SOURCE      = '_wpcalibrate_mc_rate_source';
	public const META_RATE_TIMESTAMP   = '_wpcalibrate_mc_rate_timestamp';
	public const META_DISPLAY_CURRENCY = '_wpcalibrate_mc_display_currency';
	public const META_PLUGIN_VERSION   = '_wpcalibrate_mc_plugin_version';

	/**
	 * Register order hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		add_action( 'woocommerce_checkout_create_order', [ self::class, 'snapshot_order_currency' ], 20, 2 );
	}

	/**
	 * Snapshot currency and rate metadata on order creation.
	 *
	 * Uses WC CRUD APIs exclusively (HPOS compatible).
	 *
	 * @param \WC_Order            $order
	 * @param array<string, mixed> $data
	 * @return void
	 */
	public static function snapshot_order_currency( \WC_Order $order, array $data ): void {
		$operating_mode  = (string) SettingsManager::get( 'operating_mode', 'full' );
		$base_code       = CurrencyRepository::get_base_currency_code();
		$version         = defined( 'WPCALIBRATE_MC_VERSION' ) ? WPCALIBRATE_MC_VERSION : '1.0.0';

		if ( 'display_only' === $operating_mode ) {
			$display_curr = CurrencyResolver::get_active_currency();

			$order->set_currency( $base_code );
			$order->update_meta_data( self::META_PRICING_MODE, 'display_only' );
			$order->update_meta_data( self::META_BASE_CURRENCY, $base_code );
			$order->update_meta_data( self::META_DISPLAY_CURRENCY, $display_curr );
			$order->update_meta_data( self::META_PLUGIN_VERSION, $version );
			return;
		}

		// Full Multi-Currency Mode
		$active_code = CurrencyResolver::get_active_currency();
		$currency    = CurrencyRepository::get( $active_code );

		$order->set_currency( $active_code );
		$order->update_meta_data( self::META_PRICING_MODE, 'full' );
		$order->update_meta_data( self::META_ORDER_CURRENCY, $active_code );
		$order->update_meta_data( self::META_BASE_CURRENCY, $base_code );
		$order->update_meta_data( self::META_EXCHANGE_RATE, (string) ( $currency ? $currency->exchange_rate : 1.0 ) );
		$order->update_meta_data( self::META_RATE_MARKUP, (string) ( $currency ? $currency->rate_markup : 0.0 ) );
		$order->update_meta_data( self::META_RATE_SOURCE, $currency ? $currency->rate_source : 'base' );
		$order->update_meta_data( self::META_RATE_TIMESTAMP, (string) ( $currency ? $currency->last_updated : time() ) );
		$order->update_meta_data( self::META_PLUGIN_VERSION, $version );
	}

	/**
	 * Get order snapshot information.
	 *
	 * @param \WC_Order $order
	 * @return array<string, mixed>|null
	 */
	public static function get_order_snapshot( \WC_Order $order ): ?array {
		$mode = $order->get_meta( self::META_PRICING_MODE );
		if ( empty( $mode ) ) {
			return null;
		}

		return [
			'pricing_mode'     => $mode,
			'order_currency'   => $order->get_meta( self::META_ORDER_CURRENCY ) ?: $order->get_currency(),
			'base_currency'    => $order->get_meta( self::META_BASE_CURRENCY ) ?: CurrencyRepository::get_base_currency_code(),
			'exchange_rate'    => (float) ( $order->get_meta( self::META_EXCHANGE_RATE ) ?: 1.0 ),
			'rate_markup'      => (float) ( $order->get_meta( self::META_RATE_MARKUP ) ?: 0.0 ),
			'rate_source'      => $order->get_meta( self::META_RATE_SOURCE ) ?: 'manual',
			'rate_timestamp'   => (int) ( $order->get_meta( self::META_RATE_TIMESTAMP ) ?: 0 ),
			'display_currency' => $order->get_meta( self::META_DISPLAY_CURRENCY ) ?: '',
			'plugin_version'   => $order->get_meta( self::META_PLUGIN_VERSION ) ?: '',
		];
	}
}
