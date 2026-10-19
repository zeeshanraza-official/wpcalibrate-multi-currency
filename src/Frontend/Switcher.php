<?php
/**
 * Frontend Switcher Renderer and Request Handler.
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
 * Handles rendering the currency switcher and processing switch requests.
 */
final class Switcher {

	public const NONCE_ACTION = 'wpcalibrate_mc_switch_currency';

	/**
	 * Register switcher hooks, shortcode, and automatic placements.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Process manual currency switch requests from forms / GET links
		add_action( 'init', [ self::class, 'handle_switch_request' ], 5 );

		// Register Shortcode
		add_shortcode( 'wpcalibrate_currency_switcher', [ self::class, 'render_shortcode' ] );

		// Register Dynamic Gutenberg Block
		add_action( 'init', [ self::class, 'register_block' ] );

		// Automatic Placements
		self::register_automatic_placements();
	}

	/**
	 * Process currency switch form submission or GET parameter.
	 *
	 * @return void
	 */
	public static function handle_switch_request(): void {
		// 1. Handle POST submission from dropdown
		if ( isset( $_POST['wpcalibrate_mc_action'] ) && 'switch_currency' === $_POST['wpcalibrate_mc_action'] ) {
			if ( ! isset( $_POST['wpcalibrate_mc_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wpcalibrate_mc_nonce'] ) ), self::NONCE_ACTION ) ) {
				return;
			}

			$currency = isset( $_POST['wpcalibrate_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcalibrate_currency'] ) ) : '';
			if ( ! empty( $currency ) && CurrencyRepository::is_enabled( $currency ) ) {
				CurrencyResolver::switch_currency( $currency );
			}

			$redirect = isset( $_POST['wpcalibrate_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['wpcalibrate_redirect'] ) ) : '';
			if ( empty( $redirect ) ) {
				$redirect = home_url( add_query_arg( [] ) );
			}

			wp_safe_redirect( $redirect );
			exit;
		}

		// 2. Handle GET submission with nonce from buttons
		if ( isset( $_GET['wpc_currency'] ) && isset( $_GET['_wpnonce'] ) ) {
			if ( wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), self::NONCE_ACTION ) ) {
				$currency = sanitize_text_field( wp_unslash( $_GET['wpc_currency'] ) );
				if ( CurrencyRepository::is_enabled( $currency ) ) {
					CurrencyResolver::switch_currency( $currency );

					$clean_url = remove_query_arg( [ 'wpc_currency', '_wpnonce' ] );
					wp_safe_redirect( $clean_url );
					exit;
				}
			}
		}
	}

	/**
	 * Register automatic placements based on plugin settings.
	 *
	 * @return void
	 */
	private static function register_automatic_placements(): void {
		if ( ! SettingsManager::get( 'enabled', true ) ) {
			return;
		}

		if ( SettingsManager::get( 'placement_before_shop_loop', false ) ) {
			add_action( 'woocommerce_before_shop_loop', [ self::class, 'render_placement_shop_loop' ], 20 );
		}

		if ( SettingsManager::get( 'placement_single_product', false ) ) {
			add_action( 'woocommerce_single_product_summary', [ self::class, 'render_placement_single_product' ], 25 );
		}

		if ( SettingsManager::get( 'placement_cart', false ) ) {
			add_action( 'woocommerce_before_cart', [ self::class, 'render_placement_cart' ], 10 );
		}

		if ( SettingsManager::get( 'placement_checkout', false ) ) {
			add_action( 'woocommerce_before_checkout_form', [ self::class, 'render_placement_checkout' ], 10 );
		}

		if ( SettingsManager::get( 'placement_my_account', false ) ) {
			add_action( 'woocommerce_account_dashboard', [ self::class, 'render_placement_account' ], 10 );
		}

		if ( SettingsManager::get( 'placement_floating', false ) ) {
			add_action( 'wp_footer', [ self::class, 'render_placement_floating' ], 20 );
		}
	}

	public static function render_placement_shop_loop(): void {
		echo self::render( [ 'id_suffix' => 'shop_loop', 'wrapper_class' => 'wpcalibrate-placement-shop-loop' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function render_placement_single_product(): void {
		echo self::render( [ 'id_suffix' => 'single_product', 'wrapper_class' => 'wpcalibrate-placement-single-product' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function render_placement_cart(): void {
		echo self::render( [ 'id_suffix' => 'cart', 'wrapper_class' => 'wpcalibrate-placement-cart' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function render_placement_checkout(): void {
		echo self::render( [ 'id_suffix' => 'checkout', 'wrapper_class' => 'wpcalibrate-placement-checkout' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function render_placement_account(): void {
		echo self::render( [ 'id_suffix' => 'my_account', 'wrapper_class' => 'wpcalibrate-placement-account' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function render_placement_floating(): void {
		$pos = (string) SettingsManager::get( 'floating_position', 'bottom_right' );
		echo '<div class="wpcalibrate-floating-widget wpcalibrate-pos-' . esc_attr( $pos ) . '">';
		echo self::render( [ 'id_suffix' => 'floating', 'wrapper_class' => 'wpcalibrate-placement-floating' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string, mixed>|string $atts
	 * @return string
	 */
	public static function render_shortcode( mixed $atts = [] ): string {
		$parsed_atts = shortcode_atts(
			[
				'style'         => (string) SettingsManager::get( 'switcher_style', 'dropdown' ),
				'format'        => (string) SettingsManager::get( 'switcher_format', 'code_symbol' ),
				'wrapper_class' => '',
				'id_suffix'     => 'shortcode_' . wp_rand( 100, 999 ),
			],
			is_array( $atts ) ? $atts : [],
			'wpcalibrate_currency_switcher'
		);

		return self::render( $parsed_atts );
	}

	/**
	 * Register Gutenberg block.
	 *
	 * @return void
	 */
	public static function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'wpcalibrate/currency-switcher',
			[
				'api_version'     => 3,
				'title'           => __( 'Currency Switcher (WPCalibrate)', 'wpcalibrate-multi-currency' ),
				'description'     => __( 'Displays a customer currency switcher.', 'wpcalibrate-multi-currency' ),
				'category'        => 'woocommerce',
				'icon'            => 'money-alt',
				'keywords'        => [ 'currency', 'switcher', 'wpcalibrate', 'woocommerce' ],
				'render_callback' => [ self::class, 'render_block' ],
				'attributes'      => [
					'style'  => [ 'type' => 'string', 'default' => 'dropdown' ],
					'format' => [ 'type' => 'string', 'default' => 'code_symbol' ],
				],
			]
		);
	}

	/**
	 * Dynamic Gutenberg block render callback.
	 *
	 * @param array<string, mixed> $attributes
	 * @return string
	 */
	public static function render_block( array $attributes ): string {
		return self::render(
			[
				'style'     => $attributes['style'] ?? 'dropdown',
				'format'    => $attributes['format'] ?? 'code_symbol',
				'id_suffix' => 'block_' . wp_rand( 100, 999 ),
			]
		);
	}

	/**
	 * Core switcher render method.
	 *
	 * @param array<string, mixed> $args
	 * @return string HTML output.
	 */
	public static function render( array $args = [] ): string {
		$currencies = CurrencyRepository::get_enabled();
		if ( count( $currencies ) <= 1 ) {
			return ''; // Don't show switcher if only 1 currency is available
		}

		$style           = $args['style'] ?? SettingsManager::get( 'switcher_style', 'dropdown' );
		$format          = $args['format'] ?? SettingsManager::get( 'switcher_format', 'code_symbol' );
		$active_currency = CurrencyResolver::get_active_currency();
		$redirect_url    = home_url( add_query_arg( [] ) );
		$nonce           = wp_create_nonce( self::NONCE_ACTION );

		$template = ( 'buttons' === $style ) ? 'switcher-buttons.php' : 'switcher-dropdown.php';
		$file     = WPCALIBRATE_MC_PATH . 'templates/frontend/' . $template;

		if ( ! file_exists( $file ) ) {
			return '';
		}

		ob_start();
		include $file;
		return (string) ob_get_clean();
	}
}
