<?php
/**
 * Product Admin Pricing Fields for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Pricing
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Pricing;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds per-currency pricing fields to Simple and Variable product edit screens.
 */
final class ProductAdminFields {

	public const NONCE_ACTION = 'wpcalibrate_mc_save_product_pricing';
	public const NONCE_NAME   = 'wpcalibrate_mc_product_pricing_nonce';

	/**
	 * Register admin product field hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Simple products pricing fields
		add_action( 'woocommerce_product_options_pricing', [ self::class, 'render_simple_product_fields' ] );
		add_action( 'woocommerce_process_product_meta', [ self::class, 'save_simple_product_fields' ] );

		// Variations pricing fields
		add_action( 'woocommerce_variation_options_pricing', [ self::class, 'render_variation_fields' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ self::class, 'save_variation_fields' ], 10, 2 );
	}

	/**
	 * Render fixed currency fields on simple product edit page.
	 *
	 * @return void
	 */
	public static function render_simple_product_fields(): void {
		global $post;
		if ( ! $post ) {
			return;
		}

		$enabled_currencies = CurrencyRepository::get_enabled();
		$base_code          = CurrencyRepository::get_base_currency_code();

		// Only show if other currencies exist besides base
		$other_currencies = array_filter(
			$enabled_currencies,
			static fn( $c ) => $c->code !== $base_code
		);

		if ( empty( $other_currencies ) ) {
			return;
		}

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<div class="options_group wpcalibrate_mc_pricing_group">';
		echo '<h4 style="margin-left:12px; margin-top:15px; margin-bottom:5px;">' . esc_html__( 'WPCalibrate Multi-Currency Fixed Pricing', 'wpcalibrate-multi-currency' ) . '</h4>';
		echo '<p class="description" style="margin-left:12px; margin-bottom:12px;">' . esc_html__( 'Leave fields blank to use automatic rate conversion. Enter 0 or specific amounts to override.', 'wpcalibrate-multi-currency' ) . '</p>';

		foreach ( $other_currencies as $currency ) {
			$reg_key = ProductPricing::META_REGULAR_PREFIX . $currency->code;
			$sale_key = ProductPricing::META_SALE_PREFIX . $currency->code;

			$reg_val  = get_post_meta( $post->ID, $reg_key, true );
			$sale_val = get_post_meta( $post->ID, $sale_key, true );

			woocommerce_wp_text_input(
				[
					'id'          => $reg_key,
					'name'        => $reg_key,
					'label'       => sprintf(
						/* translators: 1: Currency code, 2: Currency symbol */
						__( 'Regular Price (%1$s %2$s)', 'wpcalibrate-multi-currency' ),
						$currency->code,
						$currency->symbol
					),
					'placeholder' => __( 'Auto-convert', 'wpcalibrate-multi-currency' ),
					'value'       => ( '' !== $reg_val && false !== $reg_val ) ? $reg_val : '',
					'data_type'   => 'price',
					'desc_tip'    => true,
					'description' => sprintf(
						/* translators: %s: Currency code */
						__( 'Fixed regular price for %s. Leave empty to convert from base price.', 'wpcalibrate-multi-currency' ),
						$currency->code
					),
				]
			);

			woocommerce_wp_text_input(
				[
					'id'          => $sale_key,
					'name'        => $sale_key,
					'label'       => sprintf(
						/* translators: 1: Currency code, 2: Currency symbol */
						__( 'Sale Price (%1$s %2$s)', 'wpcalibrate-multi-currency' ),
						$currency->code,
						$currency->symbol
					),
					'placeholder' => __( 'Auto-convert', 'wpcalibrate-multi-currency' ),
					'value'       => ( '' !== $sale_val && false !== $sale_val ) ? $sale_val : '',
					'data_type'   => 'price',
					'desc_tip'    => true,
					'description' => sprintf(
						/* translators: %s: Currency code */
						__( 'Fixed sale price for %s. Leave empty to convert from base sale price.', 'wpcalibrate-multi-currency' ),
						$currency->code
					),
				]
			);
		}

		echo '</div>';
	}

	/**
	 * Save fixed pricing fields for simple products.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public static function save_simple_product_fields( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		$enabled_currencies = CurrencyRepository::get_enabled();
		$base_code          = CurrencyRepository::get_base_currency_code();

		foreach ( $enabled_currencies as $currency ) {
			if ( $currency->code === $base_code ) {
				continue;
			}

			$reg_key  = ProductPricing::META_REGULAR_PREFIX . $currency->code;
			$sale_key = ProductPricing::META_SALE_PREFIX . $currency->code;

			self::save_price_meta( $post_id, $reg_key );
			self::save_price_meta( $post_id, $sale_key );
		}
	}

	/**
	 * Render fixed currency pricing fields for a product variation.
	 *
	 * @param int      $loop
	 * @param array    $variation_data
	 * @param \WP_Post $variation
	 * @return void
	 */
	public static function render_variation_fields( int $loop, array $variation_data, \WP_Post $variation ): void {
		$enabled_currencies = CurrencyRepository::get_enabled();
		$base_code          = CurrencyRepository::get_base_currency_code();

		$other_currencies = array_filter(
			$enabled_currencies,
			static fn( $c ) => $c->code !== $base_code
		);

		if ( empty( $other_currencies ) ) {
			return;
		}

		echo '<div class="wpcalibrate_mc_variation_pricing" style="width:100%; clear:both; margin-top:10px; padding:10px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:4px;">';
		echo '<p><strong>' . esc_html__( 'WPCalibrate Multi-Currency Fixed Pricing (Leave empty for auto-conversion):', 'wpcalibrate-multi-currency' ) . '</strong></p>';

		foreach ( $other_currencies as $currency ) {
			$reg_key  = ProductPricing::META_REGULAR_PREFIX . $currency->code;
			$sale_key = ProductPricing::META_SALE_PREFIX . $currency->code;

			$reg_val  = get_post_meta( $variation->ID, $reg_key, true );
			$sale_val = get_post_meta( $variation->ID, $sale_key, true );

			echo '<div style="display:flex; gap:10px; margin-bottom:8px;">';

			woocommerce_wp_text_input(
				[
					'id'            => "{$reg_key}_{$loop}",
					'name'          => "{$reg_key}[{$variation->ID}]",
					'label'         => sprintf(
						/* translators: 1: Currency code, 2: Symbol */
						__( 'Regular (%1$s %2$s)', 'wpcalibrate-multi-currency' ),
						$currency->code,
						$currency->symbol
					),
					'placeholder'   => __( 'Auto', 'wpcalibrate-multi-currency' ),
					'value'         => ( '' !== $reg_val && false !== $reg_val ) ? $reg_val : '',
					'data_type'     => 'price',
					'wrapper_class' => 'form-row form-row-first',
				]
			);

			woocommerce_wp_text_input(
				[
					'id'            => "{$sale_key}_{$loop}",
					'name'          => "{$sale_key}[{$variation->ID}]",
					'label'         => sprintf(
						/* translators: 1: Currency code, 2: Symbol */
						__( 'Sale (%1$s %2$s)', 'wpcalibrate-multi-currency' ),
						$currency->code,
						$currency->symbol
					),
					'placeholder'   => __( 'Auto', 'wpcalibrate-multi-currency' ),
					'value'         => ( '' !== $sale_val && false !== $sale_val ) ? $sale_val : '',
					'data_type'     => 'price',
					'wrapper_class' => 'form-row form-row-last',
				]
			);

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Save fixed pricing fields for product variation.
	 *
	 * @param int $variation_id
	 * @param int $loop
	 * @return void
	 */
	public static function save_variation_fields( int $variation_id, int $loop ): void {
		if ( ! current_user_can( 'edit_product', $variation_id ) ) {
			return;
		}

		$enabled_currencies = CurrencyRepository::get_enabled();
		$base_code          = CurrencyRepository::get_base_currency_code();

		foreach ( $enabled_currencies as $currency ) {
			if ( $currency->code === $base_code ) {
				continue;
			}

			$reg_key  = ProductPricing::META_REGULAR_PREFIX . $currency->code;
			$sale_key = ProductPricing::META_SALE_PREFIX . $currency->code;

			if ( isset( $_POST[ $reg_key ][ $variation_id ] ) ) {
				$val = sanitize_text_field( wp_unslash( $_POST[ $reg_key ][ $variation_id ] ) );
				if ( '' === $val ) {
					delete_post_meta( $variation_id, $reg_key );
				} elseif ( is_numeric( $val ) ) {
					update_post_meta( $variation_id, $reg_key, (string) (float) $val );
				}
			}

			if ( isset( $_POST[ $sale_key ][ $variation_id ] ) ) {
				$val = sanitize_text_field( wp_unslash( $_POST[ $sale_key ][ $variation_id ] ) );
				if ( '' === $val ) {
					delete_post_meta( $variation_id, $sale_key );
				} elseif ( is_numeric( $val ) ) {
					update_post_meta( $variation_id, $sale_key, (string) (float) $val );
				}
			}
		}
	}

	/**
	 * Helper to save a single price meta field.
	 *
	 * @param int    $post_id
	 * @param string $meta_key
	 * @return void
	 */
	private static function save_price_meta( int $post_id, string $meta_key ): void {
		if ( ! isset( $_POST[ $meta_key ] ) ) {
			return;
		}

		$val = sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) );
		if ( '' === $val ) {
			delete_post_meta( $post_id, $meta_key );
		} elseif ( is_numeric( $val ) ) {
			update_post_meta( $post_id, $meta_key, (string) (float) $val );
		}
	}
}
