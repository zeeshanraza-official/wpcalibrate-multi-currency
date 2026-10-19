<?php
/**
 * Manual Exchange Rate Provider for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\ExchangeRates\Providers
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\ExchangeRates\Providers;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\ExchangeRates\Contracts\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allows store administrators to set fixed manual exchange rates without any external API.
 */
final class ManualProvider implements ProviderInterface {

	public function get_id(): string {
		return 'manual';
	}

	public function get_label(): string {
		return __( 'Manual Exchange Rates', 'wpcalibrate-multi-currency' );
	}

	public function is_configured(): bool {
		return true;
	}

	public function supports_currency( string $code ): bool {
		return true;
	}

	public function get_rates( string $base_currency, array $target_currencies ): array|\WP_Error {
		$rates = [];
		foreach ( $target_currencies as $code ) {
			$code     = strtoupper( $code );
			$currency = CurrencyRepository::get( $code );
			if ( $currency ) {
				$rate = $currency->manual_rate ?? $currency->exchange_rate;
				$rates[ $code ] = $rate > 0 ? (float) $rate : 1.0;
			}
		}
		return $rates;
	}

	public function validate_credentials(): bool {
		return true;
	}

	public function get_minimum_update_interval(): int {
		return 0;
	}
}
