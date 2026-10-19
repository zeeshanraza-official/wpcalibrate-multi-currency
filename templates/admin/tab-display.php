<?php
/**
 * Admin Display & Switcher Settings Tab.
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
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=display' ) ); ?>">
	<?php wp_nonce_field( SettingsManager::NONCE_ACTION, SettingsManager::NONCE_NAME ); ?>
	<input type="hidden" name="wpcalibrate_mc_action" value="save_settings">
	<input type="hidden" name="current_tab" value="display">

	<!-- Switcher Appearance -->
	<div class="wpcalibrate-card">
		<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Currency Switcher Appearance', 'wpcalibrate-multi-currency' ); ?></h2>
		<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Configure the visual format and control type for storefront currency switchers.', 'wpcalibrate-multi-currency' ); ?></p>

		<!-- Style -->
		<div class="wpcalibrate-form-row">
			<label for="switcher_style"><?php esc_html_e( 'Switcher Layout Style', 'wpcalibrate-multi-currency' ); ?></label>
			<select id="switcher_style" name="switcher_style" style="max-width: 300px;">
				<option value="dropdown" <?php selected( 'dropdown', $settings['switcher_style'] ); ?>><?php esc_html_e( 'Select Dropdown', 'wpcalibrate-multi-currency' ); ?></option>
				<option value="buttons" <?php selected( 'buttons', $settings['switcher_style'] ); ?>><?php esc_html_e( 'Inline Buttons / Pills', 'wpcalibrate-multi-currency' ); ?></option>
			</select>
		</div>

		<!-- Format -->
		<div class="wpcalibrate-form-row">
			<label for="switcher_format"><?php esc_html_e( 'Currency Display Format', 'wpcalibrate-multi-currency' ); ?></label>
			<select id="switcher_format" name="switcher_format" style="max-width: 300px;">
				<option value="code_symbol" <?php selected( 'code_symbol', $settings['switcher_format'] ); ?>><?php esc_html_e( 'Code and Symbol — e.g. USD ($)', 'wpcalibrate-multi-currency' ); ?></option>
				<option value="code" <?php selected( 'code', $settings['switcher_format'] ); ?>><?php esc_html_e( 'Code Only — e.g. USD', 'wpcalibrate-multi-currency' ); ?></option>
				<option value="symbol" <?php selected( 'symbol', $settings['switcher_format'] ); ?>><?php esc_html_e( 'Symbol Only — e.g. $', 'wpcalibrate-multi-currency' ); ?></option>
				<option value="code_name" <?php selected( 'code_name', $settings['switcher_format'] ); ?>><?php esc_html_e( 'Code and Name — e.g. USD - US Dollar', 'wpcalibrate-multi-currency' ); ?></option>
			</select>
		</div>
	</div>

	<!-- Automatic Storefront Placements -->
	<div class="wpcalibrate-card">
		<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Automatic Placements', 'wpcalibrate-multi-currency' ); ?></h2>
		<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Automatically inject the currency switcher into standard WooCommerce storefront template locations.', 'wpcalibrate-multi-currency' ); ?></p>

		<div class="wpcalibrate-form-row">
			<label style="font-weight: 500;">
				<input type="checkbox" name="placement_before_shop_loop" value="1" <?php checked( ! empty( $settings['placement_before_shop_loop'] ) ); ?>>
				<?php esc_html_e( 'Shop Archive Header (Before product loop)', 'wpcalibrate-multi-currency' ); ?>
			</label>
		</div>

		<div class="wpcalibrate-form-row">
			<label style="font-weight: 500;">
				<input type="checkbox" name="placement_single_product" value="1" <?php checked( ! empty( $settings['placement_single_product'] ) ); ?>>
				<?php esc_html_e( 'Single Product Page (Near price summary)', 'wpcalibrate-multi-currency' ); ?>
			</label>
		</div>

		<div class="wpcalibrate-form-row">
			<label style="font-weight: 500;">
				<input type="checkbox" name="placement_cart" value="1" <?php checked( ! empty( $settings['placement_cart'] ) ); ?>>
				<?php esc_html_e( 'Classic Cart Page (Top of cart)', 'wpcalibrate-multi-currency' ); ?>
			</label>
		</div>

		<div class="wpcalibrate-form-row">
			<label style="font-weight: 500;">
				<input type="checkbox" name="placement_checkout" value="1" <?php checked( ! empty( $settings['placement_checkout'] ) ); ?>>
				<?php esc_html_e( 'Classic Checkout Page (Above checkout form)', 'wpcalibrate-multi-currency' ); ?>
			</label>
		</div>

		<div class="wpcalibrate-form-row">
			<label style="font-weight: 500;">
				<input type="checkbox" name="placement_my_account" value="1" <?php checked( ! empty( $settings['placement_my_account'] ) ); ?>>
				<?php esc_html_e( 'My Account Dashboard', 'wpcalibrate-multi-currency' ); ?>
			</label>
		</div>

		<hr style="margin: 20px 0; border:0; border-top: 1px solid #e5e7eb;">

		<!-- Floating Switcher -->
		<div class="wpcalibrate-form-row">
			<label for="placement_floating"><?php esc_html_e( 'Enable Floating Sticky Switcher', 'wpcalibrate-multi-currency' ); ?></label>
			<label class="wpcalibrate-switch">
				<input type="checkbox" id="placement_floating" name="placement_floating" value="1" <?php checked( ! empty( $settings['placement_floating'] ) ); ?>>
				<span class="wpcalibrate-slider"></span>
			</label>
			<p class="description"><?php esc_html_e( 'Renders a modern floating pill widget fixed to a corner of the screen.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>

		<div class="wpcalibrate-form-row">
			<label for="floating_position"><?php esc_html_e( 'Floating Widget Position', 'wpcalibrate-multi-currency' ); ?></label>
			<select id="floating_position" name="floating_position" style="max-width: 250px;">
				<option value="bottom_right" <?php selected( 'bottom_right', $settings['floating_position'] ); ?>><?php esc_html_e( 'Bottom Right', 'wpcalibrate-multi-currency' ); ?></option>
				<option value="bottom_left" <?php selected( 'bottom_left', $settings['floating_position'] ); ?>><?php esc_html_e( 'Bottom Left', 'wpcalibrate-multi-currency' ); ?></option>
				<option value="top_right" <?php selected( 'top_right', $settings['floating_position'] ); ?>><?php esc_html_e( 'Top Right', 'wpcalibrate-multi-currency' ); ?></option>
				<option value="top_left" <?php selected( 'top_left', $settings['floating_position'] ); ?>><?php esc_html_e( 'Top Left', 'wpcalibrate-multi-currency' ); ?></option>
			</select>
		</div>
	</div>

	<!-- Shortcode & Block Guide -->
	<div class="wpcalibrate-card">
		<h3 class="wpcalibrate-card-title"><?php esc_html_e( 'Manual Shortcode & Block Usage', 'wpcalibrate-multi-currency' ); ?></h3>
		<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Place switchers anywhere in your menus, widget areas, or page content.', 'wpcalibrate-multi-currency' ); ?></p>

		<table class="wpcalibrate-table" style="max-width: 700px;">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Shortcode:', 'wpcalibrate-multi-currency' ); ?></strong></td>
					<td><code>[wpcalibrate_currency_switcher]</code></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'With Options:', 'wpcalibrate-multi-currency' ); ?></strong></td>
					<td><code>[wpcalibrate_currency_switcher style="buttons" format="code_symbol"]</code></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Gutenberg Block:', 'wpcalibrate-multi-currency' ); ?></strong></td>
					<td><?php esc_html_e( 'Search for "Currency Switcher (WPCalibrate)" in the block inserter.', 'wpcalibrate-multi-currency' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'PHP Helper:', 'wpcalibrate-multi-currency' ); ?></strong></td>
					<td><code>&lt;?php echo \WPCalibrate\MultiCurrency\Frontend\Switcher::render(); ?&gt;</code></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="wpcalibrate-actions-bar">
		<button type="submit" class="button button-primary button-large">
			<?php esc_html_e( 'Save Display Settings', 'wpcalibrate-multi-currency' ); ?>
		</button>
	</div>
</form>
