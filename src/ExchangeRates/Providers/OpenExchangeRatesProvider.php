<?php
/**
 * Open Exchange Rates Provider for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\ExchangeRates\Providers
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\ExchangeRates\Providers;

use WPCalibrate\MultiCurrency\ExchangeRates\Contracts\ProviderInterface;
use WPCalibrate\MultiCurrency\Logging\Logger;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Open Exchange Rates credential-based API provider.
 */
final class OpenExchangeRatesProvider implements ProviderInterface {

	private const API_ENDPOINT = 'https://openexchangerates.org/api/latest.json';

	public function get_id(): string {
		return 'openexchangerates';
	}

	public function get_label(): string {
		return __( 'Open Exchange Rates (Requires App ID)', 'wpcalibrate-multi-currency' );
	}

	public function is_configured(): bool {
		$app_id = $this->get_app_id();
		return ! empty( $app_id );
	}

	public function supports_currency( string $code ): bool {
		// Open Exchange Rates supports over 160 currencies
		return 3 === strlen( trim( $code ) );
	}

	public function get_rates( string $base_currency, array $target_currencies ): array|\WP_Error {
		$app_id = $this->get_app_id();
		if ( empty( $app_id ) ) {
			return new \WP_Error(
				'missing_credentials',
				__( 'Open Exchange Rates App ID is not configured.', 'wpcalibrate-multi-currency' )
			);
		}

		$base_currency = strtoupper( trim( $base_currency ) );

		// Most Open Exchange Rates accounts (free tier) have base fixed to USD.
		// We fetch USD rates and derive cross-rates if base is not USD.
		$url = add_query_arg(
			[
				'app_id' => $app_id,
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
			Logger::error( 'Open Exchange Rates request failed', [ 'error' => $response->get_error_message() ] );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( 200 !== $status_code ) {
			$msg = sprintf(
				/* translators: %d: HTTP status code */
				__( 'Open Exchange Rates returned HTTP error %d.', 'wpcalibrate-multi-currency' ),
				$status_code
			);
			Logger::error( $msg, [ 'status' => $status_code ] );
			return new \WP_Error( 'api_http_error', $msg );
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || ! isset( $data['rates'] ) || ! is_array( $data['rates'] ) ) {
			$msg = __( 'Open Exchange Rates returned an unexpected payload.', 'wpcalibrate-multi-currency' );
			Logger::error( $msg );
			return new \WP_Error( 'api_invalid_payload', $msg );
		}

		$usd_rates = $data['rates'];
		$base_to_usd_rate = 1.0;

		if ( 'USD' !== $base_currency ) {
			if ( ! isset( $usd_rates[ $base_currency ] ) || (float) $usd_rates[ $base_currency ] <= 0 ) {
				return new \WP_Error(
					'unsupported_base_currency',
					sprintf(
						/* translators: %s: Currency code */
						__( 'Could not determine base rate for %s from Open Exchange Rates.', 'wpcalibrate-multi-currency' ),
						$base_currency
					)
				);
			}
			$base_to_usd_rate = (float) $usd_rates[ $base_currency ];
		}

		$rates = [];
		foreach ( $target_currencies as $curr ) {
			$curr = strtoupper( trim( $curr ) );
			if ( $curr === $base_currency ) {
				continue;
			}

			if ( isset( $usd_rates[ $curr ] ) && is_numeric( $usd_rates[ $curr ] ) ) {
				$usd_target_rate = (float) $usd_rates[ $curr ];
				if ( $usd_target_rate > 0 ) {
					// Cross-rate calculation: rate(Base -> Target) = (USD -> Target) / (USD -> Base)
					$rate = $usd_target_rate / $base_to_usd_rate;
					$rates[ $curr ] = round( $rate, 6 );
				}
			}
		}

		return $rates;
	}

	public function validate_credentials(): bool {
		$app_id = $this->get_app_id();
		return ! empty( $app_id ) && strlen( $app_id ) >= 8;
	}

	public function get_minimum_update_interval(): int {
		return 3600; // 1 hour minimum
	}

	private function get_app_id(): string {
		$credentials = SettingsManager::get_credentials();
		return trim( (string) ( $credentials['openexchangerates_app_id'] ?? '' ) );
	}
}
