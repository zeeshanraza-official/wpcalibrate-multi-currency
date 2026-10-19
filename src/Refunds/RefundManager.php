<?php
/**
 * Refund Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Refunds
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Refunds;

use WPCalibrate\MultiCurrency\Orders\OrderManager;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles refund preservation of historical order exchange rates and currencies.
 */
final class RefundManager {

	/**
	 * Register refund hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		add_action( 'woocommerce_order_refunded', [ self::class, 'snapshot_refund_currency' ], 20, 2 );
	}

	/**
	 * Snapshot historical order currency context onto refund object.
	 *
	 * @param int $order_id
	 * @param int $refund_id
	 * @return void
	 */
	public static function snapshot_refund_currency( int $order_id, int $refund_id ): void {
		$order  = wc_get_order( $order_id );
		$refund = wc_get_order( $refund_id );

		if ( ! $order || ! $refund ) {
			return;
		}

		$snapshot = OrderManager::get_order_snapshot( $order );
		if ( ! $snapshot ) {
			return;
		}

		// Preserve historical order exchange rate context on the refund
		$refund->update_meta_data( OrderManager::META_PRICING_MODE, $snapshot['pricing_mode'] );
		$refund->update_meta_data( OrderManager::META_ORDER_CURRENCY, $snapshot['order_currency'] );
		$refund->update_meta_data( OrderManager::META_BASE_CURRENCY, $snapshot['base_currency'] );
		$refund->update_meta_data( OrderManager::META_EXCHANGE_RATE, (string) $snapshot['exchange_rate'] );
		$refund->update_meta_data( OrderManager::META_RATE_SOURCE, $snapshot['rate_source'] );
		$refund->update_meta_data( OrderManager::META_RATE_TIMESTAMP, (string) $snapshot['rate_timestamp'] );
		$refund->save();
	}
}
