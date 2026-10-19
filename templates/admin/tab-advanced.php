<?php
/**
 * Admin Advanced Settings Tab.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 *
 * @var array<string, mixed> $settings
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WPCalibrate\MultiCurrency\Dependencies;
use WPCalibrate\MultiCurrency\Logging\Logger;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_hpos = class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled();
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=advanced' ) ); ?>">
	<?php wp_nonce_field( SettingsManager::NONCE_ACTION, SettingsManager::NONCE_NAME ); ?>
	<input type="hidden" name="wpcalibrate_mc_action" value="save_settings">
	<input type="hidden" name="current_tab" value="advanced">

	<div class="wpcalibrate-card">
		<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Diagnostics & Data Lifecycle', 'wpcalibrate-multi-currency' ); ?></h2>
		<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Manage debug logging, cookie lifespans, and uninstallation behavior.', 'wpcalibrate-multi-currency' ); ?></p>

		<!-- Debug Logging -->
		<div class="wpcalibrate-form-row">
			<label for="debug_logging"><?php esc_html_e( 'Enable Debug Logging', 'wpcalibrate-multi-currency' ); ?></label>
			<label class="wpcalibrate-switch">
				<input type="checkbox" id="debug_logging" name="debug_logging" value="1" <?php checked( ! empty( $settings['debug_logging'] ) ); ?>>
				<span class="wpcalibrate-slider"></span>
			</label>
			<p class="description">
				<?php
				printf(
					/* translators: %s: Log source name */
					esc_html__( 'Logs rate updates, conversions, and provider payloads to WooCommerce Status > Logs under source "%s". Sensitive API keys and user information are strictly redacted.', 'wpcalibrate-multi-currency' ),
					'<code>' . esc_html( Logger::SOURCE ) . '</code>'
				);
				?>
			</p>
		</div>

		<hr style="margin: 20px 0; border:0; border-top: 1px solid #e5e7eb;">

		<!-- Cookie Lifetime -->
		<div class="wpcalibrate-form-row">
			<label for="cookie_lifetime_days"><?php esc_html_e( 'Currency Cookie Lifetime (Days)', 'wpcalibrate-multi-currency' ); ?></label>
			<input type="number" id="cookie_lifetime_days" name="cookie_lifetime_days" value="<?php echo esc_attr( (string) $settings['cookie_lifetime_days'] ); ?>" min="1" max="365" style="width: 100px;">
			<p class="description"><?php esc_html_e( 'How many days guest visitor currency choices persist in the wpc_currency cookie.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>

		<hr style="margin: 20px 0; border:0; border-top: 1px solid #e5e7eb;">

		<!-- Uninstall Data Retention -->
		<div class="wpcalibrate-form-row">
			<label for="delete_data_on_uninstall" style="color: #b91c1c;"><?php esc_html_e( 'Delete Plugin Data on Uninstall', 'wpcalibrate-multi-currency' ); ?></label>
			<label class="wpcalibrate-switch">
				<input type="checkbox" id="delete_data_on_uninstall" name="delete_data_on_uninstall" value="1" <?php checked( ! empty( $settings['delete_data_on_uninstall'] ) ); ?>>
				<span class="wpcalibrate-slider"></span>
			</label>
			<p class="description">
				<?php esc_html_e( 'When enabled, uninstalling the plugin will wipe all plugin options, transients, and Action Scheduler jobs. Immutable financial order snapshots remain preserved to maintain accurate WooCommerce bookkeeping.', 'wpcalibrate-multi-currency' ); ?>
			</p>
		</div>
	</div>

	<!-- System Environment Overview -->
	<div class="wpcalibrate-card">
		<h3 class="wpcalibrate-card-title"><?php esc_html_e( 'System Compatibility Snapshot', 'wpcalibrate-multi-currency' ); ?></h3>

		<table class="wpcalibrate-table" style="max-width: 600px;">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'PHP Version:', 'wpcalibrate-multi-currency' ); ?></strong></td>
					<td><?php echo esc_html( PHP_VERSION ); ?> (<?php esc_html_e( 'Required: 8.2+', 'wpcalibrate-multi-currency' ); ?>)</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WordPress Version:', 'wpcalibrate-multi-currency' ); ?></strong></td>
					<td><?php echo esc_html( $GLOBALS['wp_version'] ?? 'Unknown' ); ?> (<?php esc_html_e( 'Required: 6.8+', 'wpcalibrate-multi-currency' ); ?>)</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WooCommerce Version:', 'wpcalibrate-multi-currency' ); ?></strong></td>
					<td><?php echo defined( 'WC_VERSION' ) ? esc_html( WC_VERSION ) : 'N/A'; ?> (<?php esc_html_e( 'Required: 10.0+', 'wpcalibrate-multi-currency' ); ?>)</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WooCommerce HPOS:', 'wpcalibrate-multi-currency' ); ?></strong></td>
					<td>
						<?php if ( $is_hpos ) : ?>
							<span class="wpcalibrate-badge wpcalibrate-badge-success"><?php esc_html_e( 'Enabled (Compatible)', 'wpcalibrate-multi-currency' ); ?></span>
						<?php else : ?>
							<span class="wpcalibrate-badge wpcalibrate-badge-info"><?php esc_html_e( 'Classic / Disabled', 'wpcalibrate-multi-currency' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Cart & Checkout Blocks:', 'wpcalibrate-multi-currency' ); ?></strong></td>
					<td><span class="wpcalibrate-badge wpcalibrate-badge-success"><?php esc_html_e( 'Compatible', 'wpcalibrate-multi-currency' ); ?></span></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="wpcalibrate-actions-bar">
		<button type="submit" class="button button-primary button-large">
			<?php esc_html_e( 'Save Advanced Settings', 'wpcalibrate-multi-currency' ); ?>
		</button>
	</div>
</form>
