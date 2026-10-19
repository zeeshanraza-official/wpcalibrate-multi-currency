<?php
/**
 * Admin License Settings Tab.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

use WPCalibrate\MultiCurrency\Licensing\LicenseManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$license_data = LicenseManager::get_license_data();
$provider     = LicenseManager::get_provider();
$masked_key   = LicenseManager::mask_key( $license_data['key'] );
?>

<div class="wpcalibrate-card">
	<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Commercial License Management', 'wpcalibrate-multi-currency' ); ?></h2>
	<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Manage your WPCalibrate product license key and updates subscription.', 'wpcalibrate-multi-currency' ); ?></p>

	<!-- Provider Status Alert -->
	<div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px 16px; margin-bottom: 22px;">
		<p style="margin: 0 0 6px 0; font-weight: 600; color: #334155;">
			<span class="dashicons dashicons-info" style="color: #64748b; vertical-align: middle;"></span>
			<?php esc_html_e( 'License Provider Status:', 'wpcalibrate-multi-currency' ); ?>
			<span class="wpcalibrate-badge wpcalibrate-badge-info"><?php echo esc_html( $provider->get_label() ); ?></span>
		</p>
		<p style="margin: 0; font-size: 13px; color: #64748b;">
			<?php esc_html_e( 'Commercial license provider is not configured in this release. Remote verification and automatic commercial updates are dormant until an external licensing endpoint is specified.', 'wpcalibrate-multi-currency' ); ?>
		</p>
	</div>

	<!-- License Form -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=license' ) ); ?>">
		<?php wp_nonce_field( LicenseManager::NONCE_ACTION, 'wpcalibrate_mc_license_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_mc_action" value="save_license">

		<div class="wpcalibrate-form-row">
			<label for="license_key"><?php esc_html_e( 'License Key', 'wpcalibrate-multi-currency' ); ?></label>
			<input
				type="text"
				id="license_key"
				name="license_key"
				value="<?php echo esc_attr( $license_data['key'] ); ?>"
				placeholder="<?php echo esc_attr( $masked_key ?: 'wpc_live_xxxxxxxxxxxxxxxxxxxxxxxx' ); ?>"
				style="max-width: 450px;"
			>
			<p class="description">
				<?php esc_html_e( 'Enter the license key received with your WPCalibrate purchase. Stored keys are masked for security.', 'wpcalibrate-multi-currency' ); ?>
			</p>
		</div>

		<div class="wpcalibrate-actions-bar">
			<button type="submit" class="button button-primary button-large">
				<?php esc_html_e( 'Save License Key', 'wpcalibrate-multi-currency' ); ?>
			</button>
		</div>
	</form>

	<?php if ( ! empty( $license_data['key'] ) ) : ?>
		<hr style="margin: 24px 0; border:0; border-top: 1px solid #e5e7eb;">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=license' ) ); ?>">
			<?php wp_nonce_field( LicenseManager::NONCE_ACTION, 'wpcalibrate_mc_license_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_mc_action" value="deactivate_license">

			<button type="submit" class="button button-link-delete" style="color: #b91c1c;">
				<?php esc_html_e( 'Deactivate & Remove Stored License Key', 'wpcalibrate-multi-currency' ); ?>
			</button>
		</form>
	<?php endif; ?>
</div>
