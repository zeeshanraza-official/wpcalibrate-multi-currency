<?php
/**
 * Frontend Assets Enqueue Service for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Frontend
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Frontend;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues frontend styles and scripts.
 */
final class FrontendAssets {

	/**
	 * Register frontend hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue frontend CSS and JS.
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		$version = defined( 'WPCALIBRATE_MC_VERSION' ) ? WPCALIBRATE_MC_VERSION : '1.0.0';

		wp_enqueue_style(
			'wpcalibrate-mc-switcher',
			WPCALIBRATE_MC_URL . 'assets/frontend/css/switcher.css',
			[],
			$version
		);

		wp_enqueue_script(
			'wpcalibrate-mc-switcher',
			WPCALIBRATE_MC_URL . 'assets/frontend/js/switcher.js',
			[],
			$version,
			true
		);

		wp_localize_script(
			'wpcalibrate-mc-switcher',
			'wpcalibrateMultiCurrency',
			[
				'currentCurrency' => CurrencyResolver::get_active_currency(),
				'baseCurrency'    => CurrencyRepository::get_base_currency_code(),
				'restUrl'         => esc_url_raw( rest_url( 'wpcalibrate/v1/' ) ),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
			]
		);
	}
}
