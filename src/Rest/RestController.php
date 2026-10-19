<?php
/**
 * REST API Controller for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Rest
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Rest;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Currency\CurrencyResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles public and administrative REST API endpoints.
 */
final class RestController {

	public const REST_NAMESPACE = 'wpcalibrate/v1';

	/**
	 * Register REST hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	/**
	 * Register plugin REST routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/switch-currency',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'handle_switch_currency' ],
				'permission_callback' => '__return_true', // Public endpoint for store visitors
				'args'                => [
					'currency' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => static fn( $val ) => strtoupper( substr( sanitize_text_field( (string) $val ), 0, 3 ) ),
						'validate_callback' => static fn( $val ) => CurrencyRepository::is_enabled( (string) $val ),
					],
				],
			]
		);
	}

	/**
	 * Handle currency switch request.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function handle_switch_currency( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$code = (string) $request->get_param( 'currency' );

		if ( ! CurrencyRepository::is_enabled( $code ) ) {
			return new \WP_Error(
				'invalid_currency',
				__( 'The requested currency is not enabled or does not exist.', 'wpcalibrate-multi-currency' ),
				[ 'status' => 400 ]
			);
		}

		$success = CurrencyResolver::switch_currency( $code );
		if ( ! $success ) {
			return new \WP_Error(
				'switch_failed',
				__( 'Could not switch currency.', 'wpcalibrate-multi-currency' ),
				[ 'status' => 500 ]
			);
		}

		$currency = CurrencyRepository::get( $code );

		return new \WP_REST_Response(
			[
				'success'  => true,
				'currency' => $code,
				'symbol'   => $currency ? $currency->symbol : '$',
				'rate'     => $currency ? $currency->exchange_rate : 1.0,
			],
			200
		);
	}
}
