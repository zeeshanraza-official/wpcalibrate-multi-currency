<?php
/**
 * Plugin Uninstaller for WPCalibrate Multi-Currency for WooCommerce.
 *
 * @package WPCalibrate\MultiCurrency
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check administrator opt-in for complete data deletion
$settings = get_option( 'wpcalibrate_mc_settings', [] );
$delete_data = ! empty( $settings['delete_data_on_uninstall'] );

if ( $delete_data ) {
	// Delete plugin options
	delete_option( 'wpcalibrate_mc_settings' );
	delete_option( 'wpcalibrate_mc_currencies' );
	delete_option( 'wpcalibrate_mc_credentials' );
	delete_option( 'wpcalibrate_mc_license' );
	delete_option( 'wpcalibrate_mc_db_version' );
	delete_option( 'wpcalibrate_mc_last_rate_refresh' );

	// Delete transients
	delete_transient( 'wpcalibrate_mc_refresh_running_lock' );

	// Clean up any remaining Action Scheduler tasks
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'wpcalibrate_multi_currency_refresh_rates', [], 'wpcalibrate-multi-currency' );
	}
}
