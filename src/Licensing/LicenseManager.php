<?php
/**
 * License Manager for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Licensing
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Licensing;

use WPCalibrate\MultiCurrency\Licensing\Contracts\LicenseProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages commercial license state, masking, and actions.
 */
final class LicenseManager {

	public const OPTION_NAME  = 'wpcalibrate_mc_license';
	public const NONCE_ACTION = 'wpcalibrate_mc_license_action';

	private static ?LicenseProviderInterface $provider = null;

	/**
	 * Get the active license provider.
	 *
	 * @return LicenseProviderInterface
	 */
	public static function get_provider(): LicenseProviderInterface {
		if ( null !== self::$provider ) {
			return self::$provider;
		}

		$provider = new UnconfiguredLicenseProvider();

		/**
		 * Filter the license provider.
		 *
		 * @param LicenseProviderInterface $provider
		 */
		self::$provider = apply_filters( 'wpcalibrate_multi_currency_license_provider', $provider );
		return self::$provider;
	}

	/**
	 * Get stored license details.
	 *
	 * @return array{key: string, status: string, expires_at: ?int, last_checked: int}
	 */
	public static function get_license_data(): array {
		$data = get_option( self::OPTION_NAME, [] );
		return [
			'key'          => (string) ( $data['key'] ?? '' ),
			'status'       => (string) ( $data['status'] ?? 'inactive' ),
			'expires_at'   => isset( $data['expires_at'] ) ? (int) $data['expires_at'] : null,
			'last_checked' => (int) ( $data['last_checked'] ?? 0 ),
		];
	}

	/**
	 * Mask license key for display.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function mask_key( string $key ): string {
		$len = strlen( $key );
		if ( $len <= 6 ) {
			return str_repeat( '•', $len );
		}
		$last4 = substr( $key, -4 );
		return 'wpc_••••••••' . $last4;
	}

	/**
	 * Save license key.
	 *
	 * @param string $key
	 * @return bool
	 */
	public static function save_key( string $key ): bool {
		$data = self::get_license_data();
		$data['key'] = trim( $key );
		return update_option( self::OPTION_NAME, $data, false );
	}

	/**
	 * Deactivate and clear stored key.
	 *
	 * @return bool
	 */
	public static function deactivate(): bool {
		$provider = self::get_provider();
		$provider->deactivate();

		return delete_option( self::OPTION_NAME );
	}
}
