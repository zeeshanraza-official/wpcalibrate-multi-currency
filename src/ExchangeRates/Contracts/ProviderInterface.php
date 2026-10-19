<?php
/**
 * Exchange Rate Provider Interface for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\ExchangeRates\Contracts
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\ExchangeRates\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for all exchange rate providers.
 */
interface ProviderInterface {

	/**
	 * Unique provider ID.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Human-readable provider label.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Check whether the provider is configured and ready to fetch rates.
	 *
	 * @return bool
	 */
	public function is_configured(): bool;

	/**
	 * Check if the provider supports the given ISO currency code.
	 *
	 * @param string $code 3-letter currency code.
	 * @return bool
	 */
	public function supports_currency( string $code ): bool;

	/**
	 * Fetch exchange rates for target currencies against the store base currency.
	 *
	 * Returns an associative array of uppercase currency codes mapping to positive float exchange rates.
	 * Example: ['EUR' => 0.92, 'GBP' => 0.79]
	 *
	 * @param string        $base_currency
	 * @param array<string> $target_currencies
	 * @return array<string, float>|\WP_Error
	 */
	public function get_rates( string $base_currency, array $target_currencies ): array|\WP_Error;

	/**
	 * Validate provider credentials/connectivity.
	 *
	 * @return bool
	 */
	public function validate_credentials(): bool;

	/**
	 * Minimum update interval in seconds.
	 *
	 * @return int
	 */
	public function get_minimum_update_interval(): int;
}
