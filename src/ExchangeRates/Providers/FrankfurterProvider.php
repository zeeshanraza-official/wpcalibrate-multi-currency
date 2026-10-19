<?php
/**
 * Frankfurter Exchange Rate Provider (No-key ECB Reference Rates).
 *
 * @package WPCalibrate\MultiCurrency\ExchangeRates\Providers
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\ExchangeRates\Providers;

use WPCalibrate\MultiCurrency\ExchangeRates\Contracts\ProviderInterface;
use WPCalibrate\MultiCurrency\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public reference rate provider using European Central Bank reference data via Frankfurter API.
 */
final class FrankfurterProvider implements ProviderInterface {

	private const API_ENDPOINT = 'https://api.frankfurter.dev/v1/latest';

	/**
	 * Supported ECB fiat currency codes.
	 *
	 * @var array<string>
	 */
	private const SUPPORTED_CURRENCIES = [
		'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'EUR',
		'GBP', 'HKD', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JPY', 'KRW',
		'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'SEK', 'SGD',
		'THB', 'TRY', 'USD', 'ZAR',
	];

	public function get_id(): string {
		return 'frankfurter';
	}

	public function get_label(): string {
		return __( 'Frankfurter (Public ECB Reference Rates - No API Key)', 'wpcalibrate-multi-currency' );
	}

	public function is_configured(): bool {
		return true;
	}

	public function supports_currency( string $code ): bool {
		return in_array( strtoupper( $code ), self::SUPPORTED_CURRENCIES, true );
	}

	public function get_rates( string $base_currency, array $target_currencies ): array|\WP_Error {
		$base_currency = strtoupper( trim( $base_currency ) );

		if ( ! $this->supports_currency( $base_currency ) ) {
			return new \WP_Error(
				'unsupported_base_currency',
				sprintf(
					/* translators: %s: Currency code */
					__( 'Base currency %s is not supported by the Frankfurter ECB rate provider.', 'wpcalibrate-multi-currency' ),
					$base_currency
				)
			);
		}

		$valid_targets = [];
		foreach ( $target_currencies as $curr ) {
			$curr = strtoupper( trim( $curr ) );
			if ( $curr !== $base_currency && $this->supports_currency( $curr ) ) {
				$valid_targets[] = $curr;
			}
		}

		if ( empty( $valid_targets ) ) {
			return [];
		}

		$url = add_query_arg(
			[
				'base'    => $base_currency,
				'symbols' => implode( ',', $valid_targets ),
			],
			self::API_ENDPOINT
		);

		$response = wp_safe_remote_get(
			$url,
			[
				'timeout'    => 10,
				'user-agent' => 'WPCalibrate-MultiCurrency/' . ( defined( 'WPCALIBRATE_MC_VERSION' ) ? WPCALIBRATE_MC_VERSION : '1.0.0' ),
				'headers'    => [
					'Accept' => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			Logger::error( 'Frankfurter API connection error', [ 'error' => $response->get_error_message() ] );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( 200 !== $status_code ) {
			$msg = sprintf(
				/* translators: %d: HTTP status code */
				__( 'Frankfurter API returned HTTP error %d.', 'wpcalibrate-multi-currency' ),
				$status_code
			);
			Logger::error( $msg, [ 'status' => $status_code ] );
			return new \WP_Error( 'api_http_error', $msg );
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || ! isset( $data['rates'] ) || ! is_array( $data['rates'] ) ) {
			$msg = __( 'Frankfurter API returned an invalid response structure.', 'wpcalibrate-multi-currency' );
			Logger::error( $msg );
			return new \WP_Error( 'api_invalid_payload', $msg );
		}

		$rates = [];
		foreach ( $data['rates'] as $code => $rate ) {
			$code = strtoupper( (string) $code );
			if ( is_numeric( $rate ) && (float) $rate > 0 && ! is_nan( (float) $rate ) && ! is_infinite( (float) $rate ) ) {
				$rates[ $code ] = (float) $rate;
			}
		}

		return $rates;
	}

	public function validate_credentials(): bool {
		return true;
	}

	public function get_minimum_update_interval(): int {
		return 3600; // 1 hour minimum interval (ECB publishes once per business day)
	}
}
