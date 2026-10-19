<?php
/**
 * License Provider Interface for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Licensing\Contracts
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Licensing\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract for commercial license verification providers.
 */
interface LicenseProviderInterface {

	/**
	 * Unique provider identifier.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Human readable provider label.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Whether the license provider is configured with endpoints and credentials.
	 *
	 * @return bool
	 */
	public function is_configured(): bool;

	/**
	 * Activate a license key for this site.
	 *
	 * @param string $license_key
	 * @return array{success: bool, message: string, status: string, expires_at: ?int}
	 */
	public function activate( string $license_key ): array;

	/**
	 * Deactivate the currently active license key.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function deactivate(): array;

	/**
	 * Check status of the currently stored license key.
	 *
	 * @return array{status: string, expires_at: ?int, message: string}
	 */
	public function check_status(): array;

	/**
	 * Get license renewal URL.
	 *
	 * @return string|null
	 */
	public function get_renewal_url(): ?string;
}
