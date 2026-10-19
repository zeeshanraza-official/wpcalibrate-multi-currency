<?php
/**
 * Admin Exchange Rates Tab.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 *
 * @var array<string, mixed> $settings
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

use WPCalibrate\MultiCurrency\ExchangeRates\RateUpdater;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$last_log    = RateUpdater::get_last_refresh_log();
$credentials = SettingsManager::get_credentials();
$app_id      = $credentials['openexchangerates_app_id'] ?? '';
$masked_app  = ! empty( $app_id ) ? ( substr( $app_id, 0, 4 ) . '••••••••' . substr( $app_id, -4 ) ) : '';
?>

<div class="wpcalibrate-card">
	<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Exchange Rate Providers & Automation', 'wpcalibrate-multi-currency' ); ?></h2>
	<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Select your rate data source, configure API keys securely, and establish automated Action Scheduler intervals.', 'wpcalibrate-multi-currency' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=rates' ) ); ?>">
		<?php wp_nonce_field( SettingsManager::NONCE_ACTION, SettingsManager::NONCE_NAME ); ?>
		<input type="hidden" name="wpcalibrate_mc_action" value="save_settings">
		<input type="hidden" name="current_tab" value="rates">

		<!-- Provider Selection -->
		<div class="wpcalibrate-form-row">
			<label for="rate_provider"><?php esc_html_e( 'Rate Provider', 'wpcalibrate-multi-currency' ); ?></label>
			<select id="rate_provider" name="rate_provider" style="max-width: 450px;">
				<option value="frankfurter" <?php selected( 'frankfurter', $settings['rate_provider'] ); ?>>
					<?php esc_html_e( 'Frankfurter (Public European Central Bank Reference Rates - No Key Required)', 'wpcalibrate-multi-currency' ); ?>
				</option>
				<option value="openexchangerates" <?php selected( 'openexchangerates', $settings['rate_provider'] ); ?>>
					<?php esc_html_e( 'Open Exchange Rates (Requires Free or Paid App ID)', 'wpcalibrate-multi-currency' ); ?>
				</option>
				<option value="manual" <?php selected( 'manual', $settings['rate_provider'] ); ?>>
					<?php esc_html_e( 'Manual Only (No Remote API Requests)', 'wpcalibrate-multi-currency' ); ?>
				</option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Frankfurter delivers reliable public fiat reference data directly from the European Central Bank. Open Exchange Rates supports 160+ currencies with an App ID.', 'wpcalibrate-multi-currency' ); ?>
			</p>
		</div>

		<!-- OpenExchangeRates Credentials -->
		<div id="openexchangerates_credentials_group" class="wpcalibrate-form-row" style="margin-top: 15px; <?php echo ( 'openexchangerates' !== $settings['rate_provider'] ) ? 'display:none;' : ''; ?>">
			<label for="openexchangerates_app_id"><?php esc_html_e( 'Open Exchange Rates App ID', 'wpcalibrate-multi-currency' ); ?></label>
			<input type="text" id="openexchangerates_app_id" name="openexchangerates_app_id" value="<?php echo esc_attr( $app_id ); ?>" placeholder="<?php echo esc_attr( $masked_app ?: 'Enter your Open Exchange Rates App ID' ); ?>" style="max-width: 450px;">
			<p class="description">
				<?php
				printf(
					/* translators: %s: External link */
					esc_html__( 'Get your free or commercial App ID at %s. Credentials are stored securely with autoload disabled and never written to logs.', 'wpcalibrate-multi-currency' ),
					'<a href="https://openexchangerates.org/signup" target="_blank" rel="noopener noreferrer">openexchangerates.org</a>'
				);
				?>
			</p>
		</div>

		<hr style="margin: 20px 0; border:0; border-top: 1px solid #e5e7eb;">

		<!-- Update Interval -->
		<div class="wpcalibrate-form-row">
			<label for="rate_update_interval"><?php esc_html_e( 'Automatic Update Schedule (WooCommerce Action Scheduler)', 'wpcalibrate-multi-currency' ); ?></label>
			<select id="rate_update_interval" name="rate_update_interval" style="max-width: 300px;">
				<option value="daily" <?php selected( 'daily', $settings['rate_update_interval'] ); ?>><?php esc_html_e( 'Once Daily (Recommended for ECB)', 'wpcalibrate-multi-currency' ); ?></option>
				<option value="twicedaily" <?php selected( 'twicedaily', $settings['rate_update_interval'] ); ?>><?php esc_html_e( 'Twice Daily', 'wpcalibrate-multi-currency' ); ?></option>
				<option value="hourly" <?php selected( 'hourly', $settings['rate_update_interval'] ); ?>><?php esc_html_e( 'Hourly', 'wpcalibrate-multi-currency' ); ?></option>
				<option value="manual" <?php selected( 'manual', $settings['rate_update_interval'] ); ?>><?php esc_html_e( 'Manual Refresh Only', 'wpcalibrate-multi-currency' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Uses WooCommerce native Action Scheduler background recurring queue.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>

		<!-- Stale Rate Threshold -->
		<div class="wpcalibrate-form-row">
			<label for="stale_rate_threshold_hours"><?php esc_html_e( 'Stale Rate Warning Threshold (Hours)', 'wpcalibrate-multi-currency' ); ?></label>
			<input type="number" id="stale_rate_threshold_hours" name="stale_rate_threshold_hours" value="<?php echo esc_attr( (string) $settings['stale_rate_threshold_hours'] ); ?>" min="1" max="720" style="width: 120px;">
			<p class="description"><?php esc_html_e( 'Show an administrator dashboard notice if automatic currency rates have not updated within this number of hours.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>

		<div class="wpcalibrate-actions-bar">
			<button type="submit" class="button button-primary button-large">
				<?php esc_html_e( 'Save Exchange Rate Settings', 'wpcalibrate-multi-currency' ); ?>
			</button>
		</div>
	</form>
</div>

<!-- Last Refresh Status & Manual Refresh Trigger -->
<div class="wpcalibrate-card">
	<h3 class="wpcalibrate-card-title"><?php esc_html_e( 'Manual Synchronization & Last Refresh Status', 'wpcalibrate-multi-currency' ); ?></h3>

	<?php if ( $last_log ) : ?>
		<div style="background: <?php echo $last_log['success'] ? '#f0fdf4' : '#fef2f2'; ?>; border: 1px solid <?php echo $last_log['success'] ? '#bbf7d0' : '#fecaca'; ?>; border-radius: 6px; padding: 14px; margin-bottom: 18px;">
			<p style="margin:0 0 6px 0; font-weight:600; color:<?php echo $last_log['success'] ? '#166534' : '#991b1b'; ?>;">
				<?php echo $last_log['success'] ? esc_html__( 'Last rate synchronization succeeded.', 'wpcalibrate-multi-currency' ) : esc_html__( 'Last rate synchronization failed.', 'wpcalibrate-multi-currency' ); ?>
			</p>
			<p style="margin:0; font-size:13px; color:#374151;">
				<strong><?php esc_html_e( 'Timestamp:', 'wpcalibrate-multi-currency' ); ?></strong> <?php echo esc_html( wp_date( 'Y-m-d H:i:s', (int) $last_log['timestamp'] ) ); ?><br>
				<strong><?php esc_html_e( 'Message:', 'wpcalibrate-multi-currency' ); ?></strong> <?php echo esc_html( (string) $last_log['message'] ); ?>
			</p>
		</div>
	<?php else : ?>
		<p style="color:#6b7280; margin-bottom: 18px;"><?php esc_html_e( 'No synchronization history recorded yet.', 'wpcalibrate-multi-currency' ); ?></p>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=rates' ) ); ?>">
		<?php wp_nonce_field( 'wpcalibrate_mc_refresh_rates_nonce_action', 'wpcalibrate_mc_refresh_rates_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_mc_action" value="refresh_rates">

		<button type="submit" class="button button-secondary button-large">
			<span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Refresh Rates Now', 'wpcalibrate-multi-currency' ); ?>
		</button>
	</form>
</div>
