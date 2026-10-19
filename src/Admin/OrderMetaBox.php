<?php
/**
 * Order Meta Box for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use WPCalibrate\MultiCurrency\Orders\OrderManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays order currency snapshot details in WooCommerce order screens (HPOS and Classic).
 */
final class OrderMetaBox {

	/**
	 * Register meta box hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', [ self::class, 'register_meta_box' ], 25 );
	}

	/**
	 * Register meta box for both HPOS and classic order post type.
	 *
	 * @return void
	 */
	public static function register_meta_box(): void {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop_order' ) : 'shop_order';

		add_meta_box(
			'wpcalibrate_mc_order_details',
			__( 'Multi-Currency Snapshot (WPCalibrate)', 'wpcalibrate-multi-currency' ),
			[ self::class, 'render' ],
			$screen,
			'side',
			'default'
		);
	}

	/**
	 * Render the order meta box.
	 *
	 * @param \WP_Post|\WC_Order $post_or_order
	 * @return void
	 */
	public static function render( mixed $post_or_order ): void {
		$order = ( $post_or_order instanceof \WC_Order ) ? $post_or_order : wc_get_order( $post_or_order->ID );
		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Unable to load order details.', 'wpcalibrate-multi-currency' ) . '</p>';
			return;
		}

		$snapshot = OrderManager::get_order_snapshot( $order );
		if ( ! $snapshot ) {
			echo '<p style="color:#6b7280;">' . esc_html__( 'This order was placed in standard store base currency without multi-currency conversion.', 'wpcalibrate-multi-currency' ) . '</p>';
			return;
		}

		$order_currency = $snapshot['order_currency'];
		$base_currency  = $snapshot['base_currency'];
		$rate           = $snapshot['exchange_rate'];
		$markup         = $snapshot['rate_markup'];
		$source         = $snapshot['rate_source'];
		$timestamp      = $snapshot['rate_timestamp'];
		$mode           = $snapshot['pricing_mode'];

		echo '<table style="width:100%; border-collapse:collapse; font-size:13px;">';
		echo '<tbody>';

		printf(
			'<tr style="border-bottom:1px solid #e5e7eb;"><td style="padding:6px 0; font-weight:600;">%s:</td><td style="padding:6px 0; text-align:right;"><code>%s</code></td></tr>',
			esc_html__( 'Pricing Mode', 'wpcalibrate-multi-currency' ),
			esc_html( ucfirst( str_replace( '_', ' ', $mode ) ) )
		);

		printf(
			'<tr style="border-bottom:1px solid #e5e7eb;"><td style="padding:6px 0; font-weight:600;">%s:</td><td style="padding:6px 0; text-align:right;"><span style="font-weight:bold; color:#1d4ed8;">%s</span></td></tr>',
			esc_html__( 'Order Currency', 'wpcalibrate-multi-currency' ),
			esc_html( $order_currency )
		);

		printf(
			'<tr style="border-bottom:1px solid #e5e7eb;"><td style="padding:6px 0; font-weight:600;">%s:</td><td style="padding:6px 0; text-align:right;">%s</td></tr>',
			esc_html__( 'Base Currency', 'wpcalibrate-multi-currency' ),
			esc_html( $base_currency )
		);

		if ( 'display_only' !== $mode ) {
			printf(
				'<tr style="border-bottom:1px solid #e5e7eb;"><td style="padding:6px 0; font-weight:600;">%s:</td><td style="padding:6px 0; text-align:right;">1 %s = %s %s</td></tr>',
				esc_html__( 'Exchange Rate', 'wpcalibrate-multi-currency' ),
				esc_html( $base_currency ),
				esc_html( (string) $rate ),
				esc_html( $order_currency )
			);

			if ( 0.0 !== $markup ) {
				printf(
					'<tr style="border-bottom:1px solid #e5e7eb;"><td style="padding:6px 0; font-weight:600;">%s:</td><td style="padding:6px 0; text-align:right;">%s%%</td></tr>',
					esc_html__( 'Rate Markup', 'wpcalibrate-multi-currency' ),
					esc_html( ( $markup > 0 ? '+' : '' ) . $markup )
				);
			}

			printf(
				'<tr style="border-bottom:1px solid #e5e7eb;"><td style="padding:6px 0; font-weight:600;">%s:</td><td style="padding:6px 0; text-align:right;">%s</td></tr>',
				esc_html__( 'Rate Source', 'wpcalibrate-multi-currency' ),
				esc_html( ucfirst( $source ) )
			);

			if ( $timestamp > 0 ) {
				printf(
					'<tr style="border-bottom:1px solid #e5e7eb;"><td style="padding:6px 0; font-weight:600;">%s:</td><td style="padding:6px 0; text-align:right;">%s</td></tr>',
					esc_html__( 'Rate Timestamp', 'wpcalibrate-multi-currency' ),
					esc_html( wp_date( 'Y-m-d H:i:s', $timestamp ) )
				);
			}

			// Calculate base total equivalent
			$order_total = (float) $order->get_total();
			$base_total  = $rate > 0 ? ( $order_total / $rate ) : $order_total;

			printf(
				'<tr><td style="padding:6px 0; font-weight:600; color:#374151;">%s:</td><td style="padding:6px 0; text-align:right; font-weight:600;">%s %s</td></tr>',
				esc_html__( 'Base Equivalent', 'wpcalibrate-multi-currency' ),
				esc_html( number_format( $base_total, 2 ) ),
				esc_html( $base_currency )
			);
		}

		echo '</tbody>';
		echo '</table>';
	}
}
