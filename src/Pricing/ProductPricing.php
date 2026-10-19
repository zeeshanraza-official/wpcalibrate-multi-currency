<?php
/**
 * Product Pricing Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Pricing
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Pricing;

use WPCalibrate\MultiCurrency\Conversion\ConversionService;
use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters WooCommerce product prices, variations, price ranges, and formatting.
 */
final class ProductPricing {

	public const META_REGULAR_PREFIX = '_wpcalibrate_regular_price_';
	public const META_SALE_PREFIX    = '_wpcalibrate_sale_price_';

	/**
	 * Register pricing filters.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Only run when plugin is enabled
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		// Simple & variable product price filters
		add_filter( 'woocommerce_product_get_price', [ self::class, 'filter_price' ], 20, 2 );
		add_filter( 'woocommerce_product_get_regular_price', [ self::class, 'filter_regular_price' ], 20, 2 );
		add_filter( 'woocommerce_product_get_sale_price', [ self::class, 'filter_sale_price' ], 20, 2 );

		// Variations price filters
		add_filter( 'woocommerce_product_variation_get_price', [ self::class, 'filter_price' ], 20, 2 );
		add_filter( 'woocommerce_product_variation_get_regular_price', [ self::class, 'filter_regular_price' ], 20, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', [ self::class, 'filter_sale_price' ], 20, 2 );

		// Variation price hash (ensures variation prices are cached per currency)
		add_filter( 'woocommerce_get_variation_prices_hash', [ self::class, 'filter_variation_prices_hash' ], 20, 3 );
		add_filter( 'woocommerce_variation_prices_price', [ self::class, 'filter_variation_cached_price' ], 20, 3 );
		add_filter( 'woocommerce_variation_prices_regular_price', [ self::class, 'filter_variation_cached_regular_price' ], 20, 3 );
		add_filter( 'woocommerce_variation_prices_sale_price', [ self::class, 'filter_variation_cached_sale_price' ], 20, 3 );

		// Currency symbol and formatting filters for active currency
		add_filter( 'woocommerce_currency', [ self::class, 'filter_woocommerce_currency' ], 20 );
		add_filter( 'wc_price_args', [ self::class, 'filter_wc_price_args' ], 20 );
	}

	/**
	 * Should we convert prices in the current context?
	 *
	 * @return bool
	 */
	public static function should_convert(): bool {
		// Do not convert in WP Admin when editing products or options (except during AJAX)
		if ( is_admin() && ! wp_doing_ajax() ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( $screen && in_array( $screen->id, [ 'product', 'edit-product', 'woocommerce_page_wc-settings' ], true ) ) {
				return false;
			}
		}

		$active_code = CurrencyResolver::get_active_currency();
		$base_code   = CurrencyRepository::get_base_currency_code();

		return $active_code !== $base_code;
	}

	/**
	 * Filter product active price.
	 *
	 * @param mixed        $price
	 * @param \WC_Product $product
	 * @return mixed
	 */
	public static function filter_price( mixed $price, mixed $product ): mixed {
		if ( '' === $price || null === $price || ! self::should_convert() || ! ( $product instanceof \WC_Product ) ) {
			return $price;
		}

		$active_currency = CurrencyResolver::get_active_currency();

		// 1. Check fixed sale price if product is on sale
		if ( $product->is_on_sale( 'edit' ) ) {
			$fixed_sale = self::get_fixed_price( $product->get_id(), self::META_SALE_PREFIX, $active_currency );
			if ( null !== $fixed_sale ) {
				return (string) $fixed_sale;
			}
		}

		// 2. Check fixed regular price
		$fixed_regular = self::get_fixed_price( $product->get_id(), self::META_REGULAR_PREFIX, $active_currency );
		if ( null !== $fixed_regular ) {
			if ( ! $product->is_on_sale( 'edit' ) ) {
				return (string) $fixed_regular;
			}
		}

		// 3. Convert dynamically via authoritative conversion engine
		return (string) ConversionService::convert( (float) $price, $active_currency );
	}

	/**
	 * Filter product regular price.
	 *
	 * @param mixed        $price
	 * @param \WC_Product $product
	 * @return mixed
	 */
	public static function filter_regular_price( mixed $price, mixed $product ): mixed {
		if ( '' === $price || null === $price || ! self::should_convert() || ! ( $product instanceof \WC_Product ) ) {
			return $price;
		}

		$active_currency = CurrencyResolver::get_active_currency();
		$fixed = self::get_fixed_price( $product->get_id(), self::META_REGULAR_PREFIX, $active_currency );
		if ( null !== $fixed ) {
			return (string) $fixed;
		}

		return (string) ConversionService::convert( (float) $price, $active_currency );
	}

	/**
	 * Filter product sale price.
	 *
	 * @param mixed        $price
	 * @param \WC_Product $product
	 * @return mixed
	 */
	public static function filter_sale_price( mixed $price, mixed $product ): mixed {
		if ( '' === $price || null === $price || ! self::should_convert() || ! ( $product instanceof \WC_Product ) ) {
			return $price;
		}

		$active_currency = CurrencyResolver::get_active_currency();
		$fixed = self::get_fixed_price( $product->get_id(), self::META_SALE_PREFIX, $active_currency );
		if ( null !== $fixed ) {
			return (string) $fixed;
		}

		return (string) ConversionService::convert( (float) $price, $active_currency );
	}

	/**
	 * Get fixed currency price for a product.
	 *
	 * Returns float if set, or null if unset/empty string.
	 *
	 * @param int    $product_id
	 * @param string $prefix
	 * @param string $currency_code
	 * @return float|null
	 */
	public static function get_fixed_price( int $product_id, string $prefix, string $currency_code ): ?float {
		$meta_key = $prefix . strtoupper( $currency_code );
		$val      = get_post_meta( $product_id, $meta_key, true );

		if ( '' !== $val && null !== $val && false !== $val && is_numeric( $val ) ) {
			return (float) $val;
		}

		return null;
	}

	/**
	 * Append active currency to variation prices transient hash.
	 *
	 * @param array<string, mixed> $hash
	 * @param \WC_Product_Variable  $product
	 * @param bool                 $for_display
	 * @return array<string, mixed>
	 */
	public static function filter_variation_prices_hash( array $hash, $product, bool $for_display ): array {
		$hash['wpcalibrate_currency'] = CurrencyResolver::get_active_currency();
		return $hash;
	}

	/**
	 * Filter cached variation active price.
	 *
	 * @param mixed $price
	 * @param \WC_Product_Variation $variation
	 * @param \WC_Product_Variable  $product
	 * @return mixed
	 */
	public static function filter_variation_cached_price( mixed $price, $variation, $product ): mixed {
		return self::filter_price( $price, $variation );
	}

	/**
	 * Filter cached variation regular price.
	 *
	 * @param mixed $price
	 * @param \WC_Product_Variation $variation
	 * @param \WC_Product_Variable  $product
	 * @return mixed
	 */
	public static function filter_variation_cached_regular_price( mixed $price, $variation, $product ): mixed {
		return self::filter_regular_price( $price, $variation );
	}

	/**
	 * Filter cached variation sale price.
	 *
	 * @param mixed $price
	 * @param \WC_Product_Variation $variation
	 * @param \WC_Product_Variable  $product
	 * @return mixed
	 */
	public static function filter_variation_cached_sale_price( mixed $price, $variation, $product ): mixed {
		return self::filter_sale_price( $price, $variation );
	}

	/**
	 * Recursion lock flag for currency filtering.
	 *
	 * @var bool
	 */
	private static bool $is_filtering_currency = false;

	/**
	 * Filter woocommerce_currency to match active customer currency on storefront.
	 *
	 * @param string $currency
	 * @return string
	 */
	public static function filter_woocommerce_currency( string $currency ): string {
		if ( self::$is_filtering_currency ) {
			return $currency;
		}

		// In admin screens outside of AJAX, preserve default WooCommerce currency
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $currency;
		}

		self::$is_filtering_currency = true;
		try {
			return CurrencyResolver::get_active_currency();
		} finally {
			self::$is_filtering_currency = false;
		}
	}

	/**
	 * Filter formatting arguments (decimals, separators, position) to match active currency.
	 *
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>
	 */
	public static function filter_wc_price_args( array $args ): array {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $args;
		}

		$currency = CurrencyResolver::get_active_currency_instance();
		$args['currency']           = $currency->code;
		$args['decimals']           = $currency->decimal_places;
		$args['decimal_separator']  = $currency->decimal_separator;
		$args['thousand_separator'] = $currency->thousands_separator;

		$format = match ( $currency->symbol_position ) {
			'left'        => '%1$s%2$s',
			'right'       => '%2$s%1$s',
			'left_space'  => '%1$s&nbsp;%2$s',
			'right_space' => '%2$s&nbsp;%1$s',
			default       => '%1$s%2$s',
		};
		$args['price_format'] = $format;

		return $args;
	}
}
