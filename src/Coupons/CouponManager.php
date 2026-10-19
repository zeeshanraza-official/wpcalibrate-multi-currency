<?php
/**
 * Coupon Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Coupons
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Coupons;

use WPCalibrate\MultiCurrency\Conversion\ConversionService;
use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles coupon discount conversion and currency restrictions.
 */
final class CouponManager {

	public const META_ALLOWED_CURRENCIES = '_wpcalibrate_allowed_currencies';

	/**
	 * Register coupon hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		// Convert fixed coupon discounts
		add_filter( 'woocommerce_coupon_get_amount', [ self::class, 'filter_coupon_amount' ], 20, 2 );

		// Validate currency restrictions
		add_filter( 'woocommerce_coupon_is_valid', [ self::class, 'validate_coupon_currency' ], 20, 2 );

		// Admin coupon usage restrictions panel
		add_action( 'woocommerce_coupon_options_usage_restriction', [ self::class, 'render_coupon_fields' ] );
		add_action( 'woocommerce_coupon_options_save', [ self::class, 'save_coupon_fields' ] );
	}

	/**
	 * Convert fixed-cart and fixed-product coupon amounts to active currency.
	 * Percentage coupons (percent) do not require monetary conversion.
	 *
	 * @param float|string $amount
	 * @param \WC_Coupon   $coupon
	 * @return float|string
	 */
	public static function filter_coupon_amount( mixed $amount, \WC_Coupon $coupon ): mixed {
		if ( ! is_numeric( $amount ) || (float) $amount <= 0 ) {
			return $amount;
		}

		$discount_type = $coupon->get_discount_type();
		if ( 'percent' === $discount_type ) {
			return $amount;
		}

		$active_code = CurrencyResolver::get_active_currency();
		$base_code   = CurrencyRepository::get_base_currency_code();

		if ( $active_code === $base_code ) {
			return $amount;
		}

		// Convert monetary coupon value
		return (string) ConversionService::convert( (float) $amount, $active_code );
	}

	/**
	 * Validate if coupon is allowed in current active currency.
	 *
	 * @param bool       $is_valid
	 * @param \WC_Coupon $coupon
	 * @return bool
	 */
	public static function validate_coupon_currency( bool $is_valid, \WC_Coupon $coupon ): bool {
		if ( ! $is_valid ) {
			return false;
		}

		$allowed = $coupon->get_meta( self::META_ALLOWED_CURRENCIES );
		if ( empty( $allowed ) || ! is_array( $allowed ) ) {
			return true; // No restrictions configured = valid for all
		}

		$active_code = CurrencyResolver::get_active_currency();
		if ( ! in_array( $active_code, $allowed, true ) ) {
			throw new \Exception(
				sprintf(
					/* translators: 1: Coupon code, 2: Currency code */
					__( 'Coupon "%1$s" is not valid for orders in %2$s.', 'wpcalibrate-multi-currency' ),
					$coupon->get_code(),
					$active_code
				)
			);
		}

		return true;
	}

	/**
	 * Render currency restriction fields in coupon admin.
	 *
	 * @return void
	 */
	public static function render_coupon_fields(): void {
		global $post;
		if ( ! $post ) {
			return;
		}

		$enabled_currencies = CurrencyRepository::get_enabled();
		$selected_currencies = get_post_meta( $post->ID, self::META_ALLOWED_CURRENCIES, true );
		if ( ! is_array( $selected_currencies ) ) {
			$selected_currencies = [];
		}

		echo '<div class="options_group">';
		echo '<p class="form-field"><label for="wpcalibrate_mc_coupon_currencies">' . esc_html__( 'Allowed Currencies', 'wpcalibrate-multi-currency' ) . '</label>';
		echo '<select id="wpcalibrate_mc_coupon_currencies" name="wpcalibrate_mc_coupon_currencies[]" class="wc-enhanced-select" multiple="multiple" style="width: 50%;" data-placeholder="' . esc_attr__( 'All currencies allowed', 'wpcalibrate-multi-currency' ) . '">';

		foreach ( $enabled_currencies as $curr ) {
			$is_selected = in_array( $curr->code, $selected_currencies, true ) ? 'selected="selected"' : '';
			printf(
				'<option value="%s" %s>%s (%s)</option>',
				esc_attr( $curr->code ),
				$is_selected,
				esc_html( $curr->name ),
				esc_html( $curr->code )
			);
		}

		echo '</select>';
		echo '<span class="description">' . esc_html__( 'Leave empty to allow this coupon for all currencies.', 'wpcalibrate-multi-currency' ) . '</span>';
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Save allowed currencies for coupon.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public static function save_coupon_fields( int $post_id ): void {
		if ( ! current_user_can( 'edit_shop_coupon', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['wpcalibrate_mc_coupon_currencies'] ) && is_array( $_POST['wpcalibrate_mc_coupon_currencies'] ) ) {
			$clean = [];
			foreach ( $_POST['wpcalibrate_mc_coupon_currencies'] as $c ) {
				$code = strtoupper( substr( sanitize_text_field( (string) $c ), 0, 3 ) );
				if ( CurrencyRepository::is_enabled( $code ) ) {
					$clean[] = $code;
				}
			}
			update_post_meta( $post_id, self::META_ALLOWED_CURRENCIES, array_unique( $clean ) );
		} else {
			delete_post_meta( $post_id, self::META_ALLOWED_CURRENCIES );
		}
	}
}
