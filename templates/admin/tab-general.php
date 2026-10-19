<?php
/**
 * Admin General Settings Tab.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 *
 * @var array<string, mixed> $settings
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=general' ) ); ?>">
	<?php wp_nonce_field( SettingsManager::NONCE_ACTION, SettingsManager::NONCE_NAME ); ?>
	<input type="hidden" name="wpcalibrate_mc_action" value="save_settings">
	<input type="hidden" name="current_tab" value="general">

	<div class="wpcalibrate-card">
		<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'System Status & Operating Mode', 'wpcalibrate-multi-currency' ); ?></h2>
		<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Configure how the multi-currency engine operates on your WooCommerce storefront and checkout.', 'wpcalibrate-multi-currency' ); ?></p>

		<!-- Master Enable Switch -->
		<div class="wpcalibrate-form-row">
			<label for="enabled"><?php esc_html_e( 'Enable Multi-Currency Engine', 'wpcalibrate-multi-currency' ); ?></label>
			<label class="wpcalibrate-switch">
				<input type="checkbox" id="enabled" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
				<span class="wpcalibrate-slider"></span>
			</label>
			<p class="description"><?php esc_html_e( 'Temporarily enable or disable all currency switching functionality without deactivating the plugin.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>

		<hr style="margin: 20px 0; border:0; border-top: 1px solid #e5e7eb;">

		<!-- Operating Mode -->
		<div class="wpcalibrate-form-row">
			<label><?php esc_html_e( 'Operating Mode', 'wpcalibrate-multi-currency' ); ?></label>

			<div style="margin-top: 8px;">
				<label style="font-weight: 600; display: block; margin-bottom: 4px;">
					<input type="radio" name="operating_mode" value="full" <?php checked( 'full' === $settings['operating_mode'] ); ?>>
					<?php esc_html_e( 'Full Multi-Currency Mode (Recommended)', 'wpcalibrate-multi-currency' ); ?>
				</label>
				<p class="description" style="margin-left: 24px; margin-bottom: 14px;">
					<?php esc_html_e( 'Customers view prices, add to cart, and checkout in their selected currency. Compatible payment gateways receive the active currency, and orders are permanently created in that currency with frozen exchange-rate snapshots.', 'wpcalibrate-multi-currency' ); ?>
				</p>

				<label style="font-weight: 600; display: block; margin-bottom: 4px;">
					<input type="radio" name="operating_mode" value="display_only" <?php checked( 'display_only' === $settings['operating_mode'] ); ?>>
					<?php esc_html_e( 'Display-Only Mode (Conversion for Browsing)', 'wpcalibrate-multi-currency' ); ?>
				</label>
				<p class="description" style="margin-left: 24px; margin-bottom: 10px;">
					<?php esc_html_e( 'Storefront and product catalog show converted prices for customer convenience. At checkout, the cart clearly reverts to your store base currency with a prominent, non-deceptive customer notice before payment, and the order is completed in base currency.', 'wpcalibrate-multi-currency' ); ?>
				</p>
			</div>
		</div>

		<hr style="margin: 20px 0; border:0; border-top: 1px solid #e5e7eb;">

		<!-- URL Currency Parameter -->
		<div class="wpcalibrate-form-row">
			<label for="allow_currency_in_url"><?php esc_html_e( 'Allow Currency in URL Links', 'wpcalibrate-multi-currency' ); ?></label>
			<label class="wpcalibrate-switch">
				<input type="checkbox" id="allow_currency_in_url" name="allow_currency_in_url" value="1" <?php checked( ! empty( $settings['allow_currency_in_url'] ) ); ?>>
				<span class="wpcalibrate-slider"></span>
			</label>
			<p class="description"><?php esc_html_e( 'Allows marketing campaigns and deliberate links to switch currency via URL parameters, e.g. https://yourstore.com/?wpc_currency=EUR', 'wpcalibrate-multi-currency' ); ?></p>
		</div>

		<!-- Auto-switch on Checkout Country Change -->
		<div class="wpcalibrate-form-row">
			<label for="auto_switch_on_checkout"><?php esc_html_e( 'Auto-Match Country on Checkout', 'wpcalibrate-multi-currency' ); ?></label>
			<label class="wpcalibrate-switch">
				<input type="checkbox" id="auto_switch_on_checkout" name="auto_switch_on_checkout" value="1" <?php checked( ! empty( $settings['auto_switch_on_checkout'] ) ); ?>>
				<span class="wpcalibrate-slider"></span>
			</label>
			<p class="description"><?php esc_html_e( 'Automatically adjust currency if a customer selects a billing/shipping country that is mapped to a specific currency at checkout, unless they have made a deliberate manual choice.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>
	</div>

	<div class="wpcalibrate-actions-bar">
		<button type="submit" class="button button-primary button-large">
			<?php esc_html_e( 'Save Changes', 'wpcalibrate-multi-currency' ); ?>
		</button>
	</div>
</form>
