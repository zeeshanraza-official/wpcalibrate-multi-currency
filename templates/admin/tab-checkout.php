<?php
/**
 * Admin Checkout & Gateways Settings Tab.
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
$gateways   = ( function_exists( 'WC' ) && WC()->payment_gateways() ) ? WC()->payment_gateways()->payment_gateways() : [];
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=checkout' ) ); ?>">
	<?php wp_nonce_field( SettingsManager::NONCE_ACTION, SettingsManager::NONCE_NAME ); ?>
	<input type="hidden" name="wpcalibrate_mc_action" value="save_settings">
	<input type="hidden" name="current_tab" value="checkout">

	<!-- Operating Mode Details -->
	<div class="wpcalibrate-card">
		<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Checkout Flow Configuration', 'wpcalibrate-multi-currency' ); ?></h2>
		<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Control messaging and behavior when customers transition from browsing to payment.', 'wpcalibrate-multi-currency' ); ?></p>

		<!-- Display-Only Customer Notice Template -->
		<div class="wpcalibrate-form-row">
			<label for="display_only_notice_text"><?php esc_html_e( 'Display-Only Mode Checkout Notice', 'wpcalibrate-multi-currency' ); ?></label>
			<textarea id="display_only_notice_text" name="display_only_notice_text" rows="3" style="max-width: 600px;"><?php echo esc_textarea( $settings['display_only_notice_text'] ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Shown prominently on the checkout page when Display-Only Mode is active. Use %s placeholder for store base currency name.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>

		<hr style="margin: 20px 0; border:0; border-top: 1px solid #e5e7eb;">

		<!-- No Gateway Warning Template -->
		<div class="wpcalibrate-form-row">
			<label for="no_gateway_notice_text"><?php esc_html_e( 'No Compatible Gateway Available Notice', 'wpcalibrate-multi-currency' ); ?></label>
			<textarea id="no_gateway_notice_text" name="no_gateway_notice_text" rows="3" style="max-width: 600px;"><?php echo esc_textarea( $settings['no_gateway_notice_text'] ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Notice displayed to the customer if an enabled currency has no active payment gateways mapped to it. Use %s for currency code.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>
	</div>

	<!-- Payment Gateways Mapping Overview -->
	<div class="wpcalibrate-card">
		<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Active Payment Gateway Restrictions', 'wpcalibrate-multi-currency' ); ?></h2>
		<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Summary of gateway availability rules. If a currency has no restrictions configured, all otherwise valid WooCommerce gateways remain available for it.', 'wpcalibrate-multi-currency' ); ?></p>

		<?php if ( empty( $gateways ) ) : ?>
			<p style="color:#6b7280;"><?php esc_html_e( 'No payment gateways are currently installed in WooCommerce.', 'wpcalibrate-multi-currency' ); ?></p>
		<?php else : ?>
			<table class="wpcalibrate-table" style="max-width: 750px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Currency', 'wpcalibrate-multi-currency' ); ?></th>
						<th><?php esc_html_e( 'Allowed Payment Gateways', 'wpcalibrate-multi-currency' ); ?></th>
						<th><?php esc_html_e( 'Status', 'wpcalibrate-multi-currency' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $currencies as $c ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $c->code ); ?></strong> (<?php echo esc_html( $c->symbol ); ?>)</td>
							<td>
								<?php if ( empty( $c->allowed_gateways ) ) : ?>
									<span style="color:#059669; font-weight:500;"><?php esc_html_e( 'All available gateways permitted', 'wpcalibrate-multi-currency' ); ?></span>
								<?php else : ?>
									<?php
									$labels = [];
									foreach ( $c->allowed_gateways as $gid ) {
										$labels[] = isset( $gateways[ $gid ] ) ? $gateways[ $gid ]->get_title() : $gid;
									}
									echo esc_html( implode( ', ', $labels ) );
									?>
								<?php endif; ?>
							</td>
							<td>
								<span class="wpcalibrate-badge wpcalibrate-badge-success"><?php esc_html_e( 'Ready', 'wpcalibrate-multi-currency' ); ?></span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<div class="wpcalibrate-actions-bar">
		<button type="submit" class="button button-primary button-large">
			<?php esc_html_e( 'Save Checkout Settings', 'wpcalibrate-multi-currency' ); ?>
		</button>
	</div>
</form>
