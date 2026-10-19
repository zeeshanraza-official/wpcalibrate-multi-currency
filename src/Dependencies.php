<?php
/**
 * Dependency checker for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles environment and dependency checks.
 */
final class Dependencies {

	public const MIN_PHP_VERSION       = '8.2.0';
	public const MIN_WP_VERSION        = '6.8';
	public const MIN_WC_VERSION        = '10.0.0';
	public const TESTED_WP_VERSION     = '7.1.3';
	public const TESTED_WC_VERSION     = '11.2.1';

	/**
	 * List of unmet dependency messages.
	 *
	 * @var array<string>
	 */
	private static array $errors = [];

	/**
	 * Check if all dependencies are satisfied.
	 *
	 * @return bool True if all dependencies are met, false otherwise.
	 */
	public static function check(): bool {
		self::$errors = [];

		if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
			self::$errors[] = sprintf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				__( 'WPCalibrate Multi-Currency requires PHP %1$s or higher. Your server is running PHP %2$s.', 'wpcalibrate-multi-currency' ),
				self::MIN_PHP_VERSION,
				PHP_VERSION
			);
		}

		global $wp_version;
		if ( isset( $wp_version ) && version_compare( $wp_version, self::MIN_WP_VERSION, '<' ) ) {
			self::$errors[] = sprintf(
				/* translators: 1: Required WP version, 2: Current WP version */
				__( 'WPCalibrate Multi-Currency requires WordPress %1$s or higher. Your site is running WordPress %2$s.', 'wpcalibrate-multi-currency' ),
				self::MIN_WP_VERSION,
				$wp_version
			);
		}

		if ( ! self::is_woocommerce_active() ) {
			self::$errors[] = __( 'WPCalibrate Multi-Currency requires WooCommerce to be installed and active.', 'wpcalibrate-multi-currency' );
		} elseif ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, self::MIN_WC_VERSION, '<' ) ) {
			self::$errors[] = sprintf(
				/* translators: 1: Required WooCommerce version, 2: Current WooCommerce version */
				__( 'WPCalibrate Multi-Currency requires WooCommerce %1$s or higher. Your store is running WooCommerce %2$s.', 'wpcalibrate-multi-currency' ),
				self::MIN_WC_VERSION,
				WC_VERSION
			);
		}

		if ( ! empty( self::$errors ) ) {
			add_action( 'admin_notices', [ self::class, 'render_admin_notices' ] );
			return false;
		}

		return true;
	}

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active(): bool {
		if ( class_exists( '\WooCommerce' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$is_active = is_plugin_active( 'woocommerce/woocommerce.php' );
		if ( ! $is_active && is_multisite() ) {
			$is_active = is_plugin_active_for_network( 'woocommerce/woocommerce.php' );
		}

		return $is_active;
	}

	/**
	 * Render error notices in the WordPress admin.
	 *
	 * @return void
	 */
	public static function render_admin_notices(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		foreach ( self::$errors as $error ) {
			printf(
				'<div class="notice notice-error"><p><strong>%s:</strong> %s</p></div>',
				esc_html__( 'WPCalibrate Multi-Currency for WooCommerce', 'wpcalibrate-multi-currency' ),
				esc_html( $error )
			);
		}
	}

	/**
	 * Get error messages.
	 *
	 * @return array<string>
	 */
	public static function get_errors(): array {
		return self::$errors;
	}
}
