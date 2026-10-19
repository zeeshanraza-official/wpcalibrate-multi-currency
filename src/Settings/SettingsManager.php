<?php
/**
 * Settings Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Settings
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin configuration options, sanitization, validation, and defaults.
 */
final class SettingsManager {

	public const OPTION_NAME        = 'wpcalibrate_mc_settings';
	public const CREDENTIALS_OPTION = 'wpcalibrate_mc_credentials';
	public const NONCE_ACTION       = 'wpcalibrate_mc_save_settings_action';
	public const NONCE_NAME         = 'wpcalibrate_mc_settings_nonce';

	/**
	 * Cached settings in memory during request.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $settings_cache = null;

	/**
	 * Default settings array.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return [
			// General
			'enabled'                      => true,
			'operating_mode'               => 'full', // 'full' or 'display_only'
			'allow_currency_in_url'        => true,
			'auto_switch_on_checkout'      => true,

			// Rates
			'rate_provider'                => 'frankfurter', // 'manual', 'frankfurter', 'openexchangerates'
			'rate_update_interval'         => 'daily', // 'manual', 'hourly', 'twicedaily', 'daily'
			'stale_rate_threshold_hours'   => 48,

			// Display / Switcher
			'switcher_style'               => 'dropdown', // 'dropdown', 'buttons'
			'switcher_format'              => 'code_symbol', // 'code_symbol', 'code', 'symbol', 'code_name'
			'placement_before_shop_loop'   => false,
			'placement_single_product'     => false,
			'placement_cart'               => false,
			'placement_checkout'           => false,
			'placement_my_account'         => false,
			'placement_floating'           => false,
			'floating_position'            => 'bottom_right',

			// Geolocation
			'enable_geolocation'           => false,
			'country_currency_map'         => [],
			'geolocation_fallback'         => 'base',

			// Checkout & Notices
			'display_only_notice_text'     => function_exists( 'did_action' ) && did_action( 'init' )
				? __( 'Prices were displayed in your selected currency for reference. Payment will be processed in our store base currency: %s.', 'wpcalibrate-multi-currency' )
				: 'Prices were displayed in your selected currency for reference. Payment will be processed in our store base currency: %s.',
			'no_gateway_notice_text'       => function_exists( 'did_action' ) && did_action( 'init' )
				? __( 'No payment gateway is currently available for %s. Please select another currency or contact support.', 'wpcalibrate-multi-currency' )
				: 'No payment gateway is currently available for %s. Please select another currency or contact support.',

			// Advanced
			'debug_logging'                => false,
			'delete_data_on_uninstall'     => false,
			'cookie_lifetime_days'         => 30,
		];
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		if ( null !== self::$settings_cache ) {
			return self::$settings_cache;
		}

		$stored = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		self::$settings_cache = array_merge( self::get_defaults(), $stored );
		return self::$settings_cache;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Optional override default.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$all = self::get_all();
		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}
		return $default ?? self::get_defaults()[ $key ] ?? null;
	}

	/**
	 * Update settings array.
	 *
	 * @param array<string, mixed> $new_settings
	 * @return bool
	 */
	public static function update_all( array $new_settings ): bool {
		$sanitized = self::sanitize_and_validate( $new_settings );
		$updated   = update_option( self::OPTION_NAME, $sanitized );
		self::$settings_cache = $sanitized;
		return $updated;
	}

	/**
	 * Sanitize and validate incoming settings array.
	 *
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public static function sanitize_and_validate( array $input ): array {
		$defaults = self::get_defaults();
		$current  = self::get_all();
		$clean    = [];

		// General
		$clean['enabled']                 = ! empty( $input['enabled'] );
		$operating_mode                   = sanitize_key( $input['operating_mode'] ?? 'full' );
		$clean['operating_mode']          = in_array( $operating_mode, [ 'full', 'display_only' ], true ) ? $operating_mode : 'full';
		$clean['allow_currency_in_url']   = ! empty( $input['allow_currency_in_url'] );
		$clean['auto_switch_on_checkout'] = ! empty( $input['auto_switch_on_checkout'] );

		// Rates
		$provider                         = sanitize_key( $input['rate_provider'] ?? 'frankfurter' );
		$clean['rate_provider']           = in_array( $provider, [ 'manual', 'frankfurter', 'openexchangerates' ], true ) ? $provider : 'frankfurter';
		$interval                         = sanitize_key( $input['rate_update_interval'] ?? 'daily' );
		$clean['rate_update_interval']    = in_array( $interval, [ 'manual', 'hourly', 'twicedaily', 'daily' ], true ) ? $interval : 'daily';
		$clean['stale_rate_threshold_hours'] = max( 1, min( 720, (int) ( $input['stale_rate_threshold_hours'] ?? 48 ) ) );

		// Display / Switcher
		$switcher_style                   = sanitize_key( $input['switcher_style'] ?? 'dropdown' );
		$clean['switcher_style']          = in_array( $switcher_style, [ 'dropdown', 'buttons' ], true ) ? $switcher_style : 'dropdown';
		$switcher_format                  = sanitize_key( $input['switcher_format'] ?? 'code_symbol' );
		$clean['switcher_format']         = in_array( $switcher_format, [ 'code_symbol', 'code', 'symbol', 'code_name' ], true ) ? $switcher_format : 'code_symbol';
		$clean['placement_before_shop_loop'] = ! empty( $input['placement_before_shop_loop'] );
		$clean['placement_single_product']   = ! empty( $input['placement_single_product'] );
		$clean['placement_cart']             = ! empty( $input['placement_cart'] );
		$clean['placement_checkout']         = ! empty( $input['placement_checkout'] );
		$clean['placement_my_account']       = ! empty( $input['placement_my_account'] );
		$clean['placement_floating']         = ! empty( $input['placement_floating'] );
		$floating_pos                        = sanitize_key( $input['floating_position'] ?? 'bottom_right' );
		$clean['floating_position']          = in_array( $floating_pos, [ 'bottom_right', 'bottom_left', 'top_right', 'top_left' ], true ) ? $floating_pos : 'bottom_right';

		// Geolocation
		$clean['enable_geolocation']      = ! empty( $input['enable_geolocation'] );
		$clean['geolocation_fallback']    = ( 'default' === ( $input['geolocation_fallback'] ?? '' ) ) ? 'default' : 'base';
		$clean['country_currency_map']    = [];
		if ( isset( $input['country_currency_map'] ) && is_array( $input['country_currency_map'] ) ) {
			foreach ( $input['country_currency_map'] as $country => $curr ) {
				$country_clean = strtoupper( substr( sanitize_text_field( (string) $country ), 0, 2 ) );
				$curr_clean    = strtoupper( substr( sanitize_text_field( (string) $curr ), 0, 3 ) );
				if ( 2 === strlen( $country_clean ) && 3 === strlen( $curr_clean ) ) {
					$clean['country_currency_map'][ $country_clean ] = $curr_clean;
				}
			}
		}

		// Notices
		$clean['display_only_notice_text'] = sanitize_text_field( $input['display_only_notice_text'] ?? $defaults['display_only_notice_text'] );
		$clean['no_gateway_notice_text']   = sanitize_text_field( $input['no_gateway_notice_text'] ?? $defaults['no_gateway_notice_text'] );

		// Advanced
		$clean['debug_logging']            = ! empty( $input['debug_logging'] );
		$clean['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] );
		$clean['cookie_lifetime_days']     = max( 1, min( 365, (int) ( $input['cookie_lifetime_days'] ?? 30 ) ) );

		return $clean;
	}

	/**
	 * Get API credentials (stored separately with autoload=false).
	 *
	 * @return array<string, string>
	 */
	public static function get_credentials(): array {
		$creds = get_option( self::CREDENTIALS_OPTION, [] );
		return is_array( $creds ) ? $creds : [];
	}

	/**
	 * Update API credentials.
	 *
	 * @param array<string, string> $credentials
	 * @return bool
	 */
	public static function update_credentials( array $credentials ): bool {
		$sanitized = [];
		foreach ( $credentials as $k => $v ) {
			$sanitized[ sanitize_key( $k ) ] = sanitize_text_field( trim( (string) $v ) );
		}
		return update_option( self::CREDENTIALS_OPTION, $sanitized, false );
	}

	/**
	 * Reset in-memory cache.
	 *
	 * @return void
	 */
	public static function flush_cache(): void {
		self::$settings_cache = null;
	}
}
