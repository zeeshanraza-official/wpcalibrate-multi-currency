<?php
/**
 * WooCommerce Feature Compatibility Declarations (HPOS and Blocks).
 *
 * @package WPCalibrate\MultiCurrency\Compatibility
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Compatibility;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declares compatibility with WooCommerce High-Performance Order Storage (HPOS) and Cart/Checkout Blocks.
 */
final class CompatibilityDeclarations {

	/**
	 * Register compatibility declarations with WooCommerce.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'before_woocommerce_init', [ self::class, 'declare_features' ] );
	}

	/**
	 * Declare HPOS and Blocks features compatibility.
	 *
	 * @return void
	 */
	public static function declare_features(): void {
		if ( class_exists( FeaturesUtil::class ) ) {
			// Declare High-Performance Order Storage (HPOS / Custom Order Tables) compatibility
			FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				WPCALIBRATE_MC_FILE,
				true
			);

			// Declare Cart and Checkout Blocks compatibility
			FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				WPCALIBRATE_MC_FILE,
				true
			);
		}
	}
}
