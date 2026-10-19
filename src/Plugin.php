<?php
/**
 * Main Plugin Orchestrator for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency;

use WPCalibrate\MultiCurrency\Admin\AdminAssets;
use WPCalibrate\MultiCurrency\Admin\AdminMenu;
use WPCalibrate\MultiCurrency\Admin\OrderMetaBox;
use WPCalibrate\MultiCurrency\Blocks\BlocksIntegration;
use WPCalibrate\MultiCurrency\Cart\CartManager;
use WPCalibrate\MultiCurrency\Checkout\CheckoutManager;
use WPCalibrate\MultiCurrency\Compatibility\CompatibilityDeclarations;
use WPCalibrate\MultiCurrency\Coupons\CouponManager;
use WPCalibrate\MultiCurrency\Fees\FeeManager;
use WPCalibrate\MultiCurrency\Frontend\FrontendAssets;
use WPCalibrate\MultiCurrency\Frontend\Switcher;
use WPCalibrate\MultiCurrency\Gateways\GatewayManager;
use WPCalibrate\MultiCurrency\Migration\MigrationRunner;
use WPCalibrate\MultiCurrency\Orders\OrderManager;
use WPCalibrate\MultiCurrency\Pricing\ProductAdminFields;
use WPCalibrate\MultiCurrency\Pricing\ProductPricing;
use WPCalibrate\MultiCurrency\Refunds\RefundManager;
use WPCalibrate\MultiCurrency\Rest\RestController;
use WPCalibrate\MultiCurrency\Scheduling\ActionSchedulerService;
use WPCalibrate\MultiCurrency\Shipping\ShippingManager;
use WPCalibrate\MultiCurrency\Tax\TaxManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main singleton orchestrator class.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->bootstrap();
	}

	/**
	 * Bootstrap plugin services and lifecycle hooks.
	 *
	 * @return void
	 */
	private function bootstrap(): void {
		// 1. Declare WooCommerce features compatibility early
		CompatibilityDeclarations::init();

		// 2. Load translations
		add_action( 'init', [ $this, 'load_textdomain' ], 1 );

		// 3. Check dependencies on plugins_loaded
		add_action( 'plugins_loaded', [ $this, 'init_services' ], 10 );
	}

	/**
	 * Load plugin textdomain for internationalization.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wpcalibrate-multi-currency',
			false,
			dirname( plugin_basename( WPCALIBRATE_MC_FILE ) ) . '/languages'
		);
	}

	/**
	 * Initialize plugin services once all plugins have loaded.
	 *
	 * @return void
	 */
	public function init_services(): void {
		if ( ! Dependencies::check() ) {
			return;
		}

		// Run pending versioned migrations
		MigrationRunner::run();

		// Admin & Settings
		AdminMenu::init();
		AdminAssets::init();
		ProductAdminFields::init();
		OrderMetaBox::init();

		// Pricing & Storefront
		ProductPricing::init();
		Switcher::init();
		FrontendAssets::init();

		// Cart, Checkout & Orders
		CartManager::init();
		ShippingManager::init();
		TaxManager::init();
		CouponManager::init();
		FeeManager::init();
		GatewayManager::init();
		CheckoutManager::init();
		OrderManager::init();
		RefundManager::init();

		// Blocks & REST
		BlocksIntegration::init();
		RestController::init();

		// Background scheduler
		ActionSchedulerService::init();
	}
}
