<?php
/**
 * Geolocation Service for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Geolocation
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Geolocation;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles automatic country detection and currency mapping using WooCommerce Geolocation.
 */
final class GeolocationService {

	public const GEO_COUNTRY_SESSION = 'wpcalibrate_geo_country';

	/**
	 * Detect customer country and resolve corresponding enabled currency code.
	 *
	 * @return string|null 3-letter currency code, or null if cannot be determined.
	 */
	public static function get_currency_for_visitor(): ?string {
		if ( ! SettingsManager::get( 'enable_geolocation', false ) ) {
			return null;
		}

		$country = self::get_visitor_country();
		if ( empty( $country ) ) {
			return null;
		}

		return self::get_currency_for_country( $country );
	}

	/**
	 * Get visitor country code using WooCommerce's native Geolocation.
	 *
	 * @return string|null 2-letter ISO country code.
	 */
	public static function get_visitor_country(): ?string {
		// Check session cache first
		if ( function_exists( 'WC' ) && WC()->session ) {
			$cached = WC()->session->get( self::GEO_COUNTRY_SESSION );
			if ( ! empty( $cached ) && is_string( $cached ) ) {
				return strtoupper( $cached );
			}
		}

		// Use WooCommerce native Geolocation
		if ( class_exists( '\WC_Geolocation' ) ) {
			$geo_data = \WC_Geolocation::geolocate_ip();
			$country  = $geo_data['country'] ?? '';
			if ( ! empty( $country ) && 2 === strlen( $country ) ) {
				$country = strtoupper( $country );
				if ( function_exists( 'WC' ) && WC()->session ) {
					WC()->session->set( self::GEO_COUNTRY_SESSION, $country );
				}
				return $country;
			}
		}

		return null;
	}

	/**
	 * Map a country code to an enabled currency code.
	 *
	 * @param string $country_code 2-letter ISO country code.
	 * @return string|null
	 */
	public static function get_currency_for_country( string $country_code ): ?string {
		$country_code = strtoupper( substr( trim( $country_code ), 0, 2 ) );
		if ( empty( $country_code ) ) {
			return null;
		}

		// 1. Check custom country-currency map from settings
		$custom_map = (array) SettingsManager::get( 'country_currency_map', [] );
		if ( isset( $custom_map[ $country_code ] ) ) {
			$mapped_code = strtoupper( (string) $custom_map[ $country_code ] );
			if ( CurrencyRepository::is_enabled( $mapped_code ) ) {
				return $mapped_code;
			}
		}

		// 2. Check associated_countries on enabled currencies
		$enabled = CurrencyRepository::get_enabled();
		foreach ( $enabled as $currency ) {
			if ( in_array( $country_code, $currency->associated_countries, true ) ) {
				return $currency->code;
			}
		}

		return null;
	}
}
