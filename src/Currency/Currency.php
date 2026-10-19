<?php
/**
 * Currency Value Object for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Currency
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Currency;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents a single currency and its formatting, rate, and rounding rules.
 */
final class Currency {

	public string $code;
	public string $name;
	public string $symbol;
	public string $symbol_position;
	public int $decimal_places;
	public string $decimal_separator;
	public string $thousands_separator;
	public bool $enabled;
	public bool $is_base;
	public int $sort_order;
	public float $exchange_rate;
	public string $rate_source;
	public ?float $manual_rate;
	public float $rate_markup;
	public string $rounding_method;
	public float $rounding_increment;
	public ?float $psychological_ending;
	/** @var array<string> */
	public array $associated_countries;
	/** @var array<string> */
	public array $allowed_gateways;
	public int $last_updated;
	public string $rate_status;
	public string $error_message;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data
	 */
	public function __construct( array $data ) {
		$this->code                 = strtoupper( substr( trim( (string) ( $data['code'] ?? 'USD' ) ), 0, 3 ) );
		$this->name                 = sanitize_text_field( (string) ( $data['name'] ?? $this->code ) );
		$this->symbol               = sanitize_text_field( (string) ( $data['symbol'] ?? '$' ) );
		$symbol_pos                 = (string) ( $data['symbol_position'] ?? 'left' );
		$this->symbol_position      = in_array( $symbol_pos, [ 'left', 'right', 'left_space', 'right_space' ], true )
			? $symbol_pos
			: 'left';
		$this->decimal_places       = max( 0, min( 4, (int) ( $data['decimal_places'] ?? 2 ) ) );
		$this->decimal_separator    = (string) ( $data['decimal_separator'] ?? '.' );
		$this->thousands_separator  = (string) ( $data['thousands_separator'] ?? ',' );
		$this->enabled              = ! empty( $data['enabled'] );
		$this->is_base              = ! empty( $data['is_base'] );
		$this->sort_order           = (int) ( $data['sort_order'] ?? 0 );

		if ( $this->is_base ) {
			$this->exchange_rate = 1.0;
		} else {
			$raw_rate = (float) ( $data['exchange_rate'] ?? 1.0 );
			$this->exchange_rate = $raw_rate > 0 ? $raw_rate : 1.0;
		}

		$this->rate_source          = sanitize_key( (string) ( $data['rate_source'] ?? 'manual' ) );
		$this->manual_rate          = isset( $data['manual_rate'] ) && is_numeric( $data['manual_rate'] ) ? (float) $data['manual_rate'] : null;
		$this->rate_markup          = (float) ( $data['rate_markup'] ?? 0.0 );
		$rounding                   = (string) ( $data['rounding_method'] ?? 'none' );
		$this->rounding_method      = in_array( $rounding, [ 'none', 'standard', 'up', 'down', 'increment' ], true )
			? $rounding
			: 'none';
		$this->rounding_increment   = max( 0.01, (float) ( $data['rounding_increment'] ?? 1.0 ) );
		$this->psychological_ending = isset( $data['psychological_ending'] ) && is_numeric( $data['psychological_ending'] )
			? (float) $data['psychological_ending']
			: null;

		$countries = [];
		if ( isset( $data['associated_countries'] ) && is_array( $data['associated_countries'] ) ) {
			foreach ( $data['associated_countries'] as $c ) {
				$code = strtoupper( substr( sanitize_text_field( (string) $c ), 0, 2 ) );
				if ( 2 === strlen( $code ) ) {
					$countries[] = $code;
				}
			}
		}
		$this->associated_countries = array_unique( $countries );

		$gateways = [];
		if ( isset( $data['allowed_gateways'] ) && is_array( $data['allowed_gateways'] ) ) {
			foreach ( $data['allowed_gateways'] as $g ) {
				$clean_g = sanitize_key( (string) $g );
				if ( ! empty( $clean_g ) ) {
					$gateways[] = $clean_g;
				}
			}
		}
		$this->allowed_gateways     = array_unique( $gateways );
		$this->last_updated         = (int) ( $data['last_updated'] ?? time() );
		$this->rate_status          = sanitize_key( (string) ( $data['rate_status'] ?? 'valid' ) );
		$this->error_message        = sanitize_text_field( (string) ( $data['error_message'] ?? '' ) );
	}

	/**
	 * Convert to array for storage.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return [
			'code'                 => $this->code,
			'name'                 => $this->name,
			'symbol'               => $this->symbol,
			'symbol_position'      => $this->symbol_position,
			'decimal_places'       => $this->decimal_places,
			'decimal_separator'    => $this->decimal_separator,
			'thousands_separator'  => $this->thousands_separator,
			'enabled'              => $this->enabled,
			'is_base'              => $this->is_base,
			'sort_order'           => $this->sort_order,
			'exchange_rate'        => $this->exchange_rate,
			'rate_source'          => $this->rate_source,
			'manual_rate'          => $this->manual_rate,
			'rate_markup'          => $this->rate_markup,
			'rounding_method'      => $this->rounding_method,
			'rounding_increment'   => $this->rounding_increment,
			'psychological_ending' => $this->psychological_ending,
			'associated_countries' => $this->associated_countries,
			'allowed_gateways'     => $this->allowed_gateways,
			'last_updated'         => $this->last_updated,
			'rate_status'          => $this->rate_status,
			'error_message'        => $this->error_message,
		];
	}

	/**
	 * Format an amount in this currency's custom formatting rules.
	 *
	 * @param float $amount
	 * @return string
	 */
	public function format_price( float $amount ): string {
		$negative = $amount < 0;
		$abs      = abs( $amount );

		$formatted = number_format(
			$abs,
			$this->decimal_places,
			$this->decimal_separator,
			$this->thousands_separator
		);

		$sign = $negative ? '-' : '';

		return match ( $this->symbol_position ) {
			'left'        => $sign . $this->symbol . $formatted,
			'right'       => $sign . $formatted . $this->symbol,
			'left_space'  => $sign . $this->symbol . ' ' . $formatted,
			'right_space' => $sign . $formatted . ' ' . $this->symbol,
			default       => $sign . $this->symbol . $formatted,
		};
	}
}
