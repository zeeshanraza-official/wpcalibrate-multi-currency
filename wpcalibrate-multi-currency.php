<?php
/**
 * Plugin Name: WPCalibrate Multi-Currency for WooCommerce
 * Plugin URI: https://marketplace.wpcalibrate.com/
 * Description: Enterprise multi-currency engine and currency switcher for WooCommerce. Supports multiple currencies, live/manual exchange rates, fixed product pricing, HPOS, and Cart/Checkout Blocks.
 * Version: 1.0.0
 * Author: WPCalibrate
 * Author URI: https://wpcalibrate.com
 * Text Domain: wpcalibrate-multi-currency
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * WC requires at least: 10.0
 * WC tested up to: 11.2.1
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WPCalibrate\MultiCurrency
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'WPCALIBRATE_MC_VERSION', '1.0.0' );
define( 'WPCALIBRATE_MC_FILE', __FILE__ );
define( 'WPCALIBRATE_MC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCALIBRATE_MC_URL', plugin_dir_url( __FILE__ ) );

// Autoloader
require_once WPCALIBRATE_MC_PATH . 'src/Autoloader.php';
\WPCalibrate\MultiCurrency\Autoloader::register( WPCALIBRATE_MC_PATH . 'src' );

// Activation and Deactivation hooks
register_activation_hook( __FILE__, [ \WPCalibrate\MultiCurrency\Activation::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \WPCalibrate\MultiCurrency\Deactivation::class, 'deactivate' ] );

// Global template helper
if ( ! function_exists( 'wpcalibrate_currency_switcher' ) ) {
	/**
	 * Output or return the WPCalibrate Currency Switcher.
	 *
	 * @param array<string, mixed> $args
	 * @param bool                 $echo
	 * @return string|void
	 */
	function wpcalibrate_currency_switcher( array $args = [], bool $echo = true ) {
		$output = \WPCalibrate\MultiCurrency\Frontend\Switcher::render( $args );
		if ( $echo ) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		return $output;
	}
}

// Bootstrap plugin instance
\WPCalibrate\MultiCurrency\Plugin::instance();
