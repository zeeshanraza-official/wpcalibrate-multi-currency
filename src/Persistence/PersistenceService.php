<?php
/**
 * Currency Persistence Service for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Persistence
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Persistence;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages currency persistence across cookies, WooCommerce session, and user meta.
 */
final class PersistenceService {

	public const COOKIE_NAME   = 'wpc_currency';
	public const MANUAL_COOKIE = 'wpc_currency_manual';
	public const SESSION_KEY   = 'wpcalibrate_currency';
	public const USER_META_KEY = '_wpcalibrate_currency';

	/**
	 * Save currency choice for current visitor.
	 *
	 * @param string $currency_code 3-letter currency code.
	 * @param bool   $is_manual_choice Whether this was an explicit user action.
	 * @return void
	 */
	public static function set_currency( string $currency_code, bool $is_manual_choice = true ): void {
		$currency_code = strtoupper( substr( trim( $currency_code ), 0, 3 ) );
		if ( ! CurrencyRepository::is_enabled( $currency_code ) ) {
			return;
		}

		// 1. Save in WooCommerce Session if available
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( self::SESSION_KEY, $currency_code );
			if ( $is_manual_choice ) {
				WC()->session->set( 'wpcalibrate_currency_manual', 1 );
			}
		}

		// 2. Save in User Meta if user is logged in
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			update_user_meta( $user_id, self::USER_META_KEY, $currency_code );
			if ( $is_manual_choice ) {
				update_user_meta( $user_id, '_wpcalibrate_currency_manual', 1 );
			}
		}

		// 3. Set minimal First-Party Cookie for guests (only headers not sent)
		if ( ! headers_sent() ) {
			$lifetime_days = (int) SettingsManager::get( 'cookie_lifetime_days', 30 );
			$expiry        = time() + ( $lifetime_days * 86400 );
			$path          = COOKIEPATH ?: '/';
			$domain        = COOKIE_DOMAIN ?: '';
			$secure        = is_ssl();

			setcookie(
				self::COOKIE_NAME,
				$currency_code,
				[
					'expires'  => $expiry,
					'path'     => $path,
					'domain'   => $domain,
					'secure'   => $secure,
					'httponly' => false, // Accessible to client-side switcher for cache-safe init
					'samesite' => 'Lax',
				]
			);

			if ( $is_manual_choice ) {
				setcookie(
					self::MANUAL_COOKIE,
					'1',
					[
						'expires'  => $expiry,
						'path'     => $path,
						'domain'   => $domain,
						'secure'   => $secure,
						'httponly' => true,
						'samesite' => 'Lax',
					]
				);
			}
		}

		// Update $_COOKIE superglobal in memory for immediate subsequent reads
		$_COOKIE[ self::COOKIE_NAME ] = $currency_code;
		if ( $is_manual_choice ) {
			$_COOKIE[ self::MANUAL_COOKIE ] = '1';
		}
	}

	/**
	 * Get stored currency from session, cookie, or user meta.
	 *
	 * @return string|null
	 */
	public static function get_stored_currency(): ?string {
		// Check User Meta
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			$meta = get_user_meta( $user_id, self::USER_META_KEY, true );
			if ( is_string( $meta ) && ! empty( $meta ) && CurrencyRepository::is_enabled( $meta ) ) {
				return strtoupper( $meta );
			}
		}

		// Check WooCommerce Session
		if ( function_exists( 'WC' ) && WC()->session ) {
			$session_curr = WC()->session->get( self::SESSION_KEY );
			if ( is_string( $session_curr ) && ! empty( $session_curr ) && CurrencyRepository::is_enabled( $session_curr ) ) {
				return strtoupper( $session_curr );
			}
		}

		// Check Cookie
		if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$cookie_curr = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
			if ( CurrencyRepository::is_enabled( $cookie_curr ) ) {
				return strtoupper( $cookie_curr );
			}
		}

		return null;
	}

	/**
	 * Check if user previously made an explicit manual currency choice.
	 *
	 * @return bool
	 */
	public static function has_manual_choice(): bool {
		$user_id = get_current_user_id();
		if ( $user_id > 0 && get_user_meta( $user_id, '_wpcalibrate_currency_manual', true ) ) {
			return true;
		}

		if ( function_exists( 'WC' ) && WC()->session && WC()->session->get( 'wpcalibrate_currency_manual' ) ) {
			return true;
		}

		return ! empty( $_COOKIE[ self::MANUAL_COOKIE ] );
	}
}
