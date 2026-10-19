<?php
/**
 * Unconfigured License Provider for WPCalibrate Multi-Currency for WooCommerce.
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
 * Default provider when no external license server is connected.
 * Never fakes activation or creates synthetic dates.
 */
final class UnconfiguredLicenseProvider implements LicenseProviderInterface {

	public function get_id(): string {
		return 'unconfigured';
	}

	public function get_label(): string {
		return __( 'Unconfigured License Provider', 'wpcalibrate-multi-currency' );
	}

	public function is_configured(): bool {
		return false;
	}

	public function activate( string $license_key ): array {
		return [
			'success'    => false,
			'status'     => 'unconfigured',
			'message'    => __( 'Commercial license provider is not configured. Remote verification is currently unavailable.', 'wpcalibrate-multi-currency' ),
			'expires_at' => null,
		];
	}

	public function deactivate(): array {
		return [
			'success' => true,
			'message' => __( 'License cleared locally. Commercial provider is not configured.', 'wpcalibrate-multi-currency' ),
		];
	}

	public function check_status(): array {
		return [
			'status'     => 'unconfigured',
			'expires_at' => null,
			'message'    => __( 'License provider not configured.', 'wpcalibrate-multi-currency' ),
		];
	}

	public function get_renewal_url(): ?string {
		return 'https://marketplace.wpcalibrate.com/';
	}
}
