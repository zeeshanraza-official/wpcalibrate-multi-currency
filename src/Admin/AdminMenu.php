<?php
/**
 * Admin Menu for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

use WPCalibrate\MultiCurrency\Currency\Currency;
use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\ExchangeRates\RateUpdater;
use WPCalibrate\MultiCurrency\Licensing\LicenseManager;
use WPCalibrate\MultiCurrency\Scheduling\ActionSchedulerService;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles shared WPCalibrate top-level menu registration and plugin submenu pages.
 */
final class AdminMenu {

	public const PARENT_SLUG  = 'wpcalibrate';
	public const SUBMENU_SLUG = 'wpcalibrate-multi-currency';
	public const CAPABILITY   = 'manage_woocommerce';

	/**
	 * Register admin menu hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'register_menu' ], 9 );
		add_action( 'admin_init', [ self::class, 'handle_form_actions' ] );
	}

	/**
	 * Register or reuse shared WPCalibrate parent menu and register this plugin's submenu.
	 *
	 * @return void
	 */
	public static function register_menu(): void {
		global $admin_page_hooks;

		// Check if another WPCalibrate plugin already registered the parent menu
		if ( ! isset( $admin_page_hooks[ self::PARENT_SLUG ] ) ) {
			$icon_url = WPCALIBRATE_MC_URL . 'assets/branding/icon-white.png';

			add_menu_page(
				'WPCalibrate',
				'WPCalibrate',
				self::CAPABILITY,
				self::PARENT_SLUG,
				[ self::class, 'render_parent_redirect' ],
				$icon_url,
				57
			);
		}

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Multi-Currency - WPCalibrate', 'wpcalibrate-multi-currency' ),
			__( 'Multi-Currency', 'wpcalibrate-multi-currency' ),
			self::CAPABILITY,
			self::SUBMENU_SLUG,
			[ self::class, 'render_settings_page' ]
		);
	}

	/**
	 * Redirect parent menu click to this plugin's submenu if it's the primary/first page.
	 *
	 * @return void
	 */
	public static function render_parent_redirect(): void {
		self::render_settings_page();
	}

	/**
	 * Process form actions for settings, currencies, rates refresh, and license.
	 *
	 * @return void
	 */
	public static function handle_form_actions(): void {
		if ( ! isset( $_POST['wpcalibrate_mc_action'] ) ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'wpcalibrate-multi-currency' ) );
		}

		$action = sanitize_key( wp_unslash( $_POST['wpcalibrate_mc_action'] ) );

		// 1. Save Settings
		if ( 'save_settings' === $action ) {
			check_admin_referer( SettingsManager::NONCE_ACTION, SettingsManager::NONCE_NAME );

			$tab = sanitize_key( $_POST['current_tab'] ?? 'general' );
			$raw_settings = SettingsManager::get_all();

			if ( 'general' === $tab ) {
				$raw_settings['enabled']                 = ! empty( $_POST['enabled'] );
				$raw_settings['operating_mode']          = sanitize_key( $_POST['operating_mode'] ?? 'full' );
				$raw_settings['allow_currency_in_url']   = ! empty( $_POST['allow_currency_in_url'] );
				$raw_settings['auto_switch_on_checkout'] = ! empty( $_POST['auto_switch_on_checkout'] );
			} elseif ( 'rates' === $tab ) {
				$raw_settings['rate_provider']              = sanitize_key( $_POST['rate_provider'] ?? 'frankfurter' );
				$raw_settings['rate_update_interval']       = sanitize_key( $_POST['rate_update_interval'] ?? 'daily' );
				$raw_settings['stale_rate_threshold_hours'] = (int) ( $_POST['stale_rate_threshold_hours'] ?? 48 );

				// Update OpenExchangeRates credentials if submitted
				if ( isset( $_POST['openexchangerates_app_id'] ) ) {
					$app_id = sanitize_text_field( wp_unslash( $_POST['openexchangerates_app_id'] ) );
					SettingsManager::update_credentials( [ 'openexchangerates_app_id' => $app_id ] );
				}

				// Synchronize recurring action schedule
				ActionSchedulerService::sync_schedule();
			} elseif ( 'display' === $tab ) {
				$raw_settings['switcher_style']             = sanitize_key( $_POST['switcher_style'] ?? 'dropdown' );
				$raw_settings['switcher_format']            = sanitize_key( $_POST['switcher_format'] ?? 'code_symbol' );
				$raw_settings['placement_before_shop_loop'] = ! empty( $_POST['placement_before_shop_loop'] );
				$raw_settings['placement_single_product']   = ! empty( $_POST['placement_single_product'] );
				$raw_settings['placement_cart']             = ! empty( $_POST['placement_cart'] );
				$raw_settings['placement_checkout']         = ! empty( $_POST['placement_checkout'] );
				$raw_settings['placement_my_account']       = ! empty( $_POST['placement_my_account'] );
				$raw_settings['placement_floating']         = ! empty( $_POST['placement_floating'] );
				$raw_settings['floating_position']          = sanitize_key( $_POST['floating_position'] ?? 'bottom_right' );
			} elseif ( 'geolocation' === $tab ) {
				$raw_settings['enable_geolocation']   = ! empty( $_POST['enable_geolocation'] );
				$raw_settings['geolocation_fallback'] = ( 'default' === ( $_POST['geolocation_fallback'] ?? '' ) ) ? 'default' : 'base';
			} elseif ( 'checkout' === $tab ) {
				$raw_settings['display_only_notice_text'] = sanitize_text_field( wp_unslash( $_POST['display_only_notice_text'] ?? '' ) );
				$raw_settings['no_gateway_notice_text']   = sanitize_text_field( wp_unslash( $_POST['no_gateway_notice_text'] ?? '' ) );
			} elseif ( 'advanced' === $tab ) {
				$raw_settings['debug_logging']            = ! empty( $_POST['debug_logging'] );
				$raw_settings['delete_data_on_uninstall'] = ! empty( $_POST['delete_data_on_uninstall'] );
				$raw_settings['cookie_lifetime_days']     = max( 1, (int) ( $_POST['cookie_lifetime_days'] ?? 30 ) );
			}

			SettingsManager::update_all( $raw_settings );

			self::redirect_with_notice( 'settings_saved', $tab );
		}

		// 2. Save Currencies
		if ( 'save_currencies' === $action ) {
			check_admin_referer( 'wpcalibrate_mc_currencies_nonce_action', 'wpcalibrate_mc_currencies_nonce' );

			$submitted = $_POST['currencies'] ?? [];
			if ( is_array( $submitted ) ) {
				$existing = CurrencyRepository::get_all();
				$to_save  = [];

				foreach ( $submitted as $code => $row ) {
					$code = strtoupper( substr( sanitize_text_field( (string) $code ), 0, 3 ) );
					if ( empty( $code ) ) {
						continue;
					}

					$old = $existing[ $code ] ?? null;

					$associated_countries = [];
					if ( ! empty( $row['associated_countries'] ) ) {
						$raw_countries = explode( ',', sanitize_text_field( (string) $row['associated_countries'] ) );
						foreach ( $raw_countries as $c ) {
							$clean_c = strtoupper( substr( trim( $c ), 0, 2 ) );
							if ( 2 === strlen( $clean_c ) ) {
								$associated_countries[] = $clean_c;
							}
						}
					}

					$allowed_gateways = [];
					if ( isset( $row['allowed_gateways'] ) && is_array( $row['allowed_gateways'] ) ) {
						foreach ( $row['allowed_gateways'] as $g ) {
							$clean_g = sanitize_key( (string) $g );
							if ( ! empty( $clean_g ) ) {
								$allowed_gateways[] = $clean_g;
							}
						}
					}

					$data = [
						'code'                 => $code,
						'name'                 => sanitize_text_field( (string) ( $row['name'] ?? ( $old ? $old->name : $code ) ) ),
						'symbol'               => sanitize_text_field( (string) ( $row['symbol'] ?? ( $old ? $old->symbol : '$' ) ) ),
						'symbol_position'      => sanitize_key( (string) ( $row['symbol_position'] ?? 'left' ) ),
						'decimal_places'       => (int) ( $row['decimal_places'] ?? 2 ),
						'decimal_separator'    => sanitize_text_field( (string) ( $row['decimal_separator'] ?? '.' ) ),
						'thousands_separator'  => sanitize_text_field( (string) ( $row['thousands_separator'] ?? ',' ) ),
						'enabled'              => ! empty( $row['enabled'] ),
						'is_base'              => $old ? $old->is_base : false,
						'sort_order'           => (int) ( $row['sort_order'] ?? 0 ),
						'exchange_rate'        => (float) ( $row['exchange_rate'] ?? 1.0 ),
						'rate_source'          => sanitize_key( (string) ( $row['rate_source'] ?? 'manual' ) ),
						'manual_rate'          => isset( $row['manual_rate'] ) && is_numeric( $row['manual_rate'] ) ? (float) $row['manual_rate'] : (float) ( $row['exchange_rate'] ?? 1.0 ),
						'rate_markup'          => (float) ( $row['rate_markup'] ?? 0.0 ),
						'rounding_method'      => sanitize_key( (string) ( $row['rounding_method'] ?? 'none' ) ),
						'rounding_increment'   => (float) ( $row['rounding_increment'] ?? 1.0 ),
						'psychological_ending' => ( '' !== ( $row['psychological_ending'] ?? '' ) ) ? (float) $row['psychological_ending'] : null,
						'associated_countries' => $associated_countries,
						'allowed_gateways'     => $allowed_gateways,
						'last_updated'         => time(),
						'rate_status'          => 'valid',
						'error_message'        => '',
					];

					$to_save[ $code ] = $data;
				}

				CurrencyRepository::save_all( $to_save );
			}

			self::redirect_with_notice( 'currencies_saved', 'currencies' );
		}

		// 3. Add New Currency
		if ( 'add_currency' === $action ) {
			check_admin_referer( 'wpcalibrate_mc_add_currency_nonce_action', 'wpcalibrate_mc_add_currency_nonce' );

			$code = strtoupper( substr( sanitize_text_field( (string) ( $_POST['new_currency_code'] ?? '' ) ), 0, 3 ) );
			if ( 3 === strlen( $code ) ) {
				$all = CurrencyRepository::get_all();
				if ( ! isset( $all[ $code ] ) ) {
					$all[ $code ] = CurrencyRepository::create_default_currency( $code );
					CurrencyRepository::save_all( $all );
				}
			}

			self::redirect_with_notice( 'currency_added', 'currencies' );
		}

		// 4. Delete Currency
		if ( 'delete_currency' === $action ) {
			check_admin_referer( 'wpcalibrate_mc_delete_currency_nonce_action', 'wpcalibrate_mc_delete_currency_nonce' );

			$code = strtoupper( substr( sanitize_text_field( (string) ( $_POST['currency_code'] ?? '' ) ), 0, 3 ) );
			$base = CurrencyRepository::get_base_currency_code();

			if ( 3 === strlen( $code ) && $code !== $base ) {
				$all = CurrencyRepository::get_all();
				unset( $all[ $code ] );
				CurrencyRepository::save_all( $all );
			}

			self::redirect_with_notice( 'currency_deleted', 'currencies' );
		}

		// 5. Manual Rates Refresh Trigger
		if ( 'refresh_rates' === $action ) {
			check_admin_referer( 'wpcalibrate_mc_refresh_rates_nonce_action', 'wpcalibrate_mc_refresh_rates_nonce' );

			$result = RateUpdater::refresh_rates( true );
			$notice = $result['success'] ? 'rates_refreshed' : 'rates_refresh_failed';

			self::redirect_with_notice( $notice, 'rates' );
		}

		// 6. Save License Key
		if ( 'save_license' === $action ) {
			check_admin_referer( LicenseManager::NONCE_ACTION, 'wpcalibrate_mc_license_nonce' );

			$key = sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) );
			LicenseManager::save_key( $key );

			self::redirect_with_notice( 'license_saved', 'license' );
		}

		// 7. Deactivate License
		if ( 'deactivate_license' === $action ) {
			check_admin_referer( LicenseManager::NONCE_ACTION, 'wpcalibrate_mc_license_nonce' );

			LicenseManager::deactivate();

			self::redirect_with_notice( 'license_deactivated', 'license' );
		}
	}

	/**
	 * Safe redirect with flash notice message.
	 *
	 * @param string $notice
	 * @param string $tab
	 * @return void
	 */
	private static function redirect_with_notice( string $notice, string $tab ): void {
		$url = add_query_arg(
			[
				'page'                   => self::SUBMENU_SLUG,
				'tab'                    => $tab,
				'wpcalibrate_mc_message' => $notice,
			],
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render settings page view.
	 *
	 * @return void
	 */
	public static function render_settings_page(): void {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		$valid_tabs  = [ 'overview', 'general', 'currencies', 'rates', 'display', 'geolocation', 'checkout', 'advanced', 'license', 'support' ];

		if ( ! in_array( $current_tab, $valid_tabs, true ) ) {
			$current_tab = 'overview';
		}

		$file = WPCALIBRATE_MC_PATH . 'templates/admin/settings-page.php';
		if ( file_exists( $file ) ) {
			include $file;
		}
	}
}
