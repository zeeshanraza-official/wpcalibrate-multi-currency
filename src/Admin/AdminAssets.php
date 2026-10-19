<?php
/**
 * Admin Assets Enqueue Service for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues CSS and JS strictly on relevant plugin and product admin screens.
 */
final class AdminAssets {

	/**
	 * Register admin asset hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
		add_action( 'admin_head', [ self::class, 'render_admin_menu_icon_styles' ] );
	}

	/**
	 * Output global CSS in admin_head to ensure WPCalibrate sidebar menu icon is crisply sized and centered.
	 *
	 * @return void
	 */
	public static function render_admin_menu_icon_styles(): void {
		?>
		<style id="wpcalibrate-menu-icon-styles">
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image {
				display: flex;
				align-items: center;
				justify-content: center;
			}
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding: 0 !important;
				object-fit: contain;
				opacity: 0.85;
				transition: opacity 0.2s ease, transform 0.2s ease;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu .wp-menu-image img {
				opacity: 1;
				transform: scale(1.08);
			}
		</style>
		<?php
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook_suffix
	 * @return void
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}

		$is_plugin_settings = ( false !== strpos( $screen->id, 'wpcalibrate' ) );
		$is_product_edit    = ( 'product' === $screen->id );
		$is_order_edit      = ( 'shop_order' === $screen->id || 'woocommerce_page_wc-orders' === $screen->id );

		// Only load assets where needed
		if ( ! $is_plugin_settings && ! $is_product_edit && ! $is_order_edit ) {
			return;
		}

		$css_path = WPCALIBRATE_MC_PATH . 'assets/admin/css/admin.css';
		$js_path  = WPCALIBRATE_MC_PATH . 'assets/admin/js/admin.js';
		$version  = defined( 'WPCALIBRATE_MC_VERSION' ) ? WPCALIBRATE_MC_VERSION : '1.0.0';
		$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : $version;
		$js_ver   = file_exists( $js_path ) ? (string) filemtime( $js_path ) : $version;

		wp_enqueue_style(
			'wpcalibrate-mc-admin',
			WPCALIBRATE_MC_URL . 'assets/admin/css/admin.css',
			[],
			$css_ver
		);

		wp_enqueue_script(
			'wpcalibrate-mc-admin',
			WPCALIBRATE_MC_URL . 'assets/admin/js/admin.js',
			[ 'jquery' ],
			$js_ver,
			true
		);

		wp_localize_script(
			'wpcalibrate-mc-admin',
			'wpcalibrateAdminParams',
			[
				'confirmDelete' => __( 'Are you sure you want to delete this currency? This action cannot be undone.', 'wpcalibrate-multi-currency' ),
				'confirmReset'  => __( 'Are you sure you want to reset all rates?', 'wpcalibrate-multi-currency' ),
			]
		);
	}
}
