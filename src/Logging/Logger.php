<?php
/**
 * Sanitized logging service for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency\Logging
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Logging;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger class utilizing WC_Logger with security redaction.
 */
final class Logger {

	public const SOURCE = 'wpcalibrate-multi-currency';

	/**
	 * Log an error message.
	 *
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function error( string $message, array $context = [] ): void {
		self::log( 'error', $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function warning( string $message, array $context = [] ): void {
		self::log( 'warning', $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function info( string $message, array $context = [] ): void {
		self::log( 'info', $message, $context );
	}

	/**
	 * Log a debug message (only when debug mode is enabled).
	 *
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function debug( string $message, array $context = [] ): void {
		if ( ! self::is_debug_enabled() ) {
			return;
		}

		self::log( 'debug', $message, $context );
	}

	/**
	 * Internal logging dispatcher.
	 *
	 * @param string $level
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	private static function log( string $level, string $message, array $context = [] ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger = wc_get_logger();
		if ( ! $logger ) {
			return;
		}

		$sanitized_message = self::redact_secrets( $message );
		$sanitized_context = self::redact_array( $context );

		$logger->log(
			$level,
			$sanitized_message,
			array_merge( [ 'source' => self::SOURCE ], $sanitized_context )
		);
	}

	/**
	 * Check if debug logging is enabled in settings.
	 *
	 * @return bool
	 */
	public static function is_debug_enabled(): bool {
		$settings = get_option( 'wpcalibrate_mc_settings', [] );
		return ! empty( $settings['debug_logging'] );
	}

	/**
	 * Redact API keys, tokens, license keys, emails, passwords.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function redact_secrets( string $content ): string {
		// Redact standard API keys / hashes / app IDs
		$patterns = [
			'/(?:api[_-]?key|app[_-]?id|token|secret|license[_-]?key)\s*[:=]\s*["\']?([a-zA-Z0-9_\-]{8,})["\']?/i' => '$0 [REDACTED]',
			'/(?:bearer\s+)([a-zA-Z0-9_\-\.]{15,})/i' => 'Bearer [REDACTED]',
			'/([a-zA-Z0-9_\.\+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-\.]+)/' => '[EMAIL_REDACTED]',
		];

		foreach ( $patterns as $pattern => $replacement ) {
			$content = (string) preg_replace( $pattern, $replacement, $content );
		}

		return $content;
	}

	/**
	 * Recursively redact array data.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private static function redact_array( array $data ): array {
		$redacted = [];
		$sensitive_keys = [ 'key', 'api_key', 'app_id', 'license_key', 'token', 'secret', 'password', 'card', 'cvv' ];

		foreach ( $data as $k => $v ) {
			if ( is_string( $k ) && in_array( strtolower( $k ), $sensitive_keys, true ) ) {
				$redacted[ $k ] = '[REDACTED]';
			} elseif ( is_array( $v ) ) {
				$redacted[ $k ] = self::redact_array( $v );
			} elseif ( is_string( $v ) ) {
				$redacted[ $k ] = self::redact_secrets( $v );
			} else {
				$redacted[ $k ] = $v;
			}
		}

		return $redacted;
	}
}
