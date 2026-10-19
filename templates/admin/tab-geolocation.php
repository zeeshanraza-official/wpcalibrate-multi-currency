<?php
/**
 * Admin Geolocation Settings Tab.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 *
 * @var array<string, mixed> $settings
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$currencies = CurrencyRepository::get_enabled();
$base_code  = CurrencyRepository::get_base_currency_code();
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=geolocation' ) ); ?>">
	<?php wp_nonce_field( SettingsManager::NONCE_ACTION, SettingsManager::NONCE_NAME ); ?>
	<input type="hidden" name="wpcalibrate_mc_action" value="save_settings">
	<input type="hidden" name="current_tab" value="geolocation">

	<div class="wpcalibrate-card">
		<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Geographic Currency Detection', 'wpcalibrate-multi-currency' ); ?></h2>
		<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Detect visitor country on first visit and automatically select the corresponding currency.', 'wpcalibrate-multi-currency' ); ?></p>

		<!-- Enable Geolocation -->
		<div class="wpcalibrate-form-row">
			<label for="enable_geolocation"><?php esc_html_e( 'Enable Automatic Geolocation', 'wpcalibrate-multi-currency' ); ?></label>
			<label class="wpcalibrate-switch">
				<input type="checkbox" id="enable_geolocation" name="enable_geolocation" value="1" <?php checked( ! empty( $settings['enable_geolocation'] ) ); ?>>
				<span class="wpcalibrate-slider"></span>
			</label>
			<p class="description">
				<?php esc_html_e( 'Leverages WooCommerce native Geolocation infrastructure. If disabled, visitors start in your store base currency until they make a selection.', 'wpcalibrate-multi-currency' ); ?>
			</p>
		</div>

		<hr style="margin: 20px 0; border:0; border-top: 1px solid #e5e7eb;">

		<!-- Fallback Strategy -->
		<div class="wpcalibrate-form-row">
			<label for="geolocation_fallback"><?php esc_html_e( 'Country Fallback Behavior', 'wpcalibrate-multi-currency' ); ?></label>
			<select id="geolocation_fallback" name="geolocation_fallback" style="max-width: 300px;">
				<option value="base" <?php selected( 'base', $settings['geolocation_fallback'] ); ?>>
					<?php
					/* translators: %s: Base currency code */
					printf( esc_html__( 'Store Base Currency (%s)', 'wpcalibrate-multi-currency' ), esc_html( $base_code ) );
					?>
				</option>
			</select>
			<p class="description"><?php esc_html_e( 'If visitor country cannot be determined or is unmapped, fall back cleanly to your base store currency.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>

		<hr style="margin: 20px 0; border:0; border-top: 1px solid #e5e7eb;">

		<!-- Country Mapping Summary -->
		<h3 style="font-size: 15px; margin-bottom: 8px;"><?php esc_html_e( 'Active Country Mappings', 'wpcalibrate-multi-currency' ); ?></h3>
		<p class="description" style="margin-bottom: 14px;">
			<?php esc_html_e( 'Country codes mapped in the Currencies tab are used for geographic routing:', 'wpcalibrate-multi-currency' ); ?>
		</p>

		<table class="wpcalibrate-table" style="max-width: 650px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Currency', 'wpcalibrate-multi-currency' ); ?></th>
					<th><?php esc_html_e( 'Mapped ISO Countries', 'wpcalibrate-multi-currency' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $currencies as $c ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $c->code ); ?></strong> (<?php echo esc_html( $c->symbol ); ?>)</td>
						<td>
							<?php if ( ! empty( $c->associated_countries ) ) : ?>
								<code><?php echo esc_html( implode( ', ', $c->associated_countries ) ); ?></code>
							<?php else : ?>
								<span style="color:#9ca3af;"><?php esc_html_e( 'None specified (Available via manual switcher only)', 'wpcalibrate-multi-currency' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Privacy Note -->
		<div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 16px; margin-top: 20px;">
			<h4 style="margin:0 0 4px 0; font-size:13px; color:#374151;"><?php esc_html_e( 'Privacy & Data Protection Notice', 'wpcalibrate-multi-currency' ); ?></h4>
			<p style="margin:0; font-size:12px; color:#6b7280;">
				<?php esc_html_e( 'WPCalibrate Multi-Currency does not persist raw visitor IP addresses in the database. Geolocation detection is performed once per customer session, cached securely in cookie/session, and respects deliberate manual customer choices.', 'wpcalibrate-multi-currency' ); ?>
			</p>
		</div>
	</div>

	<div class="wpcalibrate-actions-bar">
		<button type="submit" class="button button-primary button-large">
			<?php esc_html_e( 'Save Geolocation Settings', 'wpcalibrate-multi-currency' ); ?>
		</button>
	</div>
</form>
