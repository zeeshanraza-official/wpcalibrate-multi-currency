<?php
/**
 * Blocks Integration for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Blocks
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Blocks;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;
use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce Blocks Store API data extensions.
 */
final class BlocksIntegration {

	public const IDENTIFIER = 'wpcalibrate-multi-currency';

	/**
	 * Register blocks and Store API integration.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'woocommerce_blocks_loaded', [ self::class, 'register_store_api_extension' ] );
	}

	/**
	 * Register Store API data extension.
	 *
	 * @return void
	 */
	public static function register_store_api_extension(): void {
		if ( ! class_exists( StoreApi::class ) || ! class_exists( ExtendSchema::class ) ) {
			return;
		}

		$extend = StoreApi::container()->get( ExtendSchema::class );
		if ( ! $extend ) {
			return;
		}

		$extend->register_endpoint_data(
			[
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => [ self::class, 'extend_cart_data' ],
				'schema_callback' => [ self::class, 'extend_cart_schema' ],
				'schema_type'     => ARRAY_A,
			]
		);
	}

	/**
	 * Data returned in Store API cart responses.
	 *
	 * @return array<string, mixed>
	 */
	public static function extend_cart_data(): array {
		$active   = CurrencyResolver::get_active_currency();
		$currency = CurrencyRepository::get( $active );

		return [
			'active_currency' => $active,
			'symbol'          => $currency ? $currency->symbol : '$',
			'rate'            => $currency ? $currency->exchange_rate : 1.0,
			'is_base'         => $currency ? $currency->is_base : true,
		];
	}

	/**
	 * Schema definition for Store API cart extension.
	 *
	 * @return array<string, mixed>
	 */
	public static function extend_cart_schema(): array {
		return [
			'active_currency' => [
				'description' => __( 'Active customer currency ISO code.', 'wpcalibrate-multi-currency' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'symbol'          => [
				'description' => __( 'Active currency symbol.', 'wpcalibrate-multi-currency' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'rate'            => [
				'description' => __( 'Active currency exchange rate against base currency.', 'wpcalibrate-multi-currency' ),
				'type'        => 'number',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
			'is_base'         => [
				'description' => __( 'Whether active currency is the store base currency.', 'wpcalibrate-multi-currency' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
		];
	}
}
