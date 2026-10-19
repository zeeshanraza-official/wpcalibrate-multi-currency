<?php
/**
 * Admin Currencies Management Tab.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$currencies      = CurrencyRepository::get_all();
$base_code       = CurrencyRepository::get_base_currency_code();
$all_wc_curr     = function_exists( 'get_woocommerce_currencies' ) ? get_woocommerce_currencies() : [ 'USD' => 'US Dollar', 'EUR' => 'Euro', 'GBP' => 'Pound Sterling' ];
$available_gw    = ( function_exists( 'WC' ) && WC()->payment_gateways() ) ? WC()->payment_gateways()->payment_gateways() : [];
?>

<div class="wpcalibrate-card">
	<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Enabled Currencies & Pricing Rules', 'wpcalibrate-multi-currency' ); ?></h2>
	<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Manage exchange rates, rate adjustments, rounding rules, formatting, and country mappings per currency.', 'wpcalibrate-multi-currency' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=currencies' ) ); ?>">
		<?php wp_nonce_field( 'wpcalibrate_mc_currencies_nonce_action', 'wpcalibrate_mc_currencies_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_mc_action" value="save_currencies">

		<div style="overflow-x: auto;">
			<table class="wpcalibrate-table">
				<thead>
					<tr>
						<th style="width: 40px; text-align:center;"><?php esc_html_e( 'Active', 'wpcalibrate-multi-currency' ); ?></th>
						<th><?php esc_html_e( 'Currency', 'wpcalibrate-multi-currency' ); ?></th>
						<th><?php esc_html_e( 'Symbol', 'wpcalibrate-multi-currency' ); ?></th>
						<th><?php esc_html_e( 'Rate (1 ' . esc_html( $base_code ) . ' =)', 'wpcalibrate-multi-currency' ); ?></th>
						<th><?php esc_html_e( 'Source', 'wpcalibrate-multi-currency' ); ?></th>
						<th><?php esc_html_e( 'Markup %', 'wpcalibrate-multi-currency' ); ?></th>
						<th><?php esc_html_e( 'Rounding & Ending', 'wpcalibrate-multi-currency' ); ?></th>
						<th><?php esc_html_e( 'Decimals', 'wpcalibrate-multi-currency' ); ?></th>
						<th><?php esc_html_e( 'Countries', 'wpcalibrate-multi-currency' ); ?></th>
						<th style="text-align: center;"><?php esc_html_e( 'Actions', 'wpcalibrate-multi-currency' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $currencies as $code => $c ) : ?>
						<tr style="<?php echo $c->is_base ? 'background-color:#eff6ff;' : ''; ?>">
							<!-- Active Checkbox -->
							<td style="text-align:center;">
								<?php if ( $c->is_base ) : ?>
									<input type="checkbox" name="currencies[<?php echo esc_attr( $code ); ?>][enabled]" value="1" checked disabled>
									<input type="hidden" name="currencies[<?php echo esc_attr( $code ); ?>][enabled]" value="1">
								<?php else : ?>
									<input type="checkbox" name="currencies[<?php echo esc_attr( $code ); ?>][enabled]" value="1" <?php checked( $c->enabled ); ?>>
								<?php endif; ?>
							</td>

							<!-- Currency Code & Name -->
							<td>
								<strong><?php echo esc_html( $c->code ); ?></strong>
								<?php if ( $c->is_base ) : ?>
									<span class="wpcalibrate-badge wpcalibrate-badge-info" style="margin-left: 4px;"><?php esc_html_e( 'Base', 'wpcalibrate-multi-currency' ); ?></span>
								<?php endif; ?>
								<br>
								<input type="text" name="currencies[<?php echo esc_attr( $code ); ?>][name]" value="<?php echo esc_attr( $c->name ); ?>" style="width: 110px; font-size:12px;">
							</td>

							<!-- Symbol & Position -->
							<td>
								<input type="text" name="currencies[<?php echo esc_attr( $code ); ?>][symbol]" value="<?php echo esc_attr( $c->symbol ); ?>" style="width: 45px; text-align:center;">
								<select name="currencies[<?php echo esc_attr( $code ); ?>][symbol_position]" style="width: 85px; font-size:11px;">
									<option value="left" <?php selected( 'left', $c->symbol_position ); ?>><?php esc_html_e( 'Left ($10)', 'wpcalibrate-multi-currency' ); ?></option>
									<option value="right" <?php selected( 'right', $c->symbol_position ); ?>><?php esc_html_e( 'Right (10$)', 'wpcalibrate-multi-currency' ); ?></option>
									<option value="left_space" <?php selected( 'left_space', $c->symbol_position ); ?>><?php esc_html_e( 'Left Space ($ 10)', 'wpcalibrate-multi-currency' ); ?></option>
									<option value="right_space" <?php selected( 'right_space', $c->symbol_position ); ?>><?php esc_html_e( 'Right Space (10 $)', 'wpcalibrate-multi-currency' ); ?></option>
								</select>
							</td>

							<!-- Rate -->
							<td>
								<?php if ( $c->is_base ) : ?>
									<span style="font-weight: 600;">1.000000</span>
									<input type="hidden" name="currencies[<?php echo esc_attr( $code ); ?>][exchange_rate]" value="1">
									<input type="hidden" name="currencies[<?php echo esc_attr( $code ); ?>][manual_rate]" value="1">
								<?php else : ?>
									<input type="number" step="0.000001" min="0.000001" name="currencies[<?php echo esc_attr( $code ); ?>][exchange_rate]" value="<?php echo esc_attr( (string) $c->exchange_rate ); ?>" style="width: 100px;">
									<input type="hidden" name="currencies[<?php echo esc_attr( $code ); ?>][manual_rate]" value="<?php echo esc_attr( (string) ( $c->manual_rate ?? $c->exchange_rate ) ); ?>">
								<?php endif; ?>
							</td>

							<!-- Source -->
							<td>
								<?php if ( $c->is_base ) : ?>
									<span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'Fixed Base', 'wpcalibrate-multi-currency' ); ?></span>
									<input type="hidden" name="currencies[<?php echo esc_attr( $code ); ?>][rate_source]" value="base">
								<?php else : ?>
									<select name="currencies[<?php echo esc_attr( $code ); ?>][rate_source]" style="font-size:12px; width: 90px;">
										<option value="auto" <?php selected( 'manual' !== $c->rate_source ); ?>><?php esc_html_e( 'Auto', 'wpcalibrate-multi-currency' ); ?></option>
										<option value="manual" <?php selected( 'manual', $c->rate_source ); ?>><?php esc_html_e( 'Manual', 'wpcalibrate-multi-currency' ); ?></option>
									</select>
								<?php endif; ?>
							</td>

							<!-- Markup % -->
							<td>
								<?php if ( $c->is_base ) : ?>
									<span style="color:#9ca3af;">—</span>
									<input type="hidden" name="currencies[<?php echo esc_attr( $code ); ?>][rate_markup]" value="0">
								<?php else : ?>
									<input type="number" step="0.1" name="currencies[<?php echo esc_attr( $code ); ?>][rate_markup]" value="<?php echo esc_attr( (string) $c->rate_markup ); ?>" style="width: 60px;">%
								<?php endif; ?>
							</td>

							<!-- Rounding & Psychological Ending -->
							<td>
								<select name="currencies[<?php echo esc_attr( $code ); ?>][rounding_method]" style="font-size:11px; width: 85px;">
									<option value="none" <?php selected( 'none', $c->rounding_method ); ?>><?php esc_html_e( 'None', 'wpcalibrate-multi-currency' ); ?></option>
									<option value="standard" <?php selected( 'standard', $c->rounding_method ); ?>><?php esc_html_e( 'Standard', 'wpcalibrate-multi-currency' ); ?></option>
									<option value="up" <?php selected( 'up', $c->rounding_method ); ?>><?php esc_html_e( 'Round Up', 'wpcalibrate-multi-currency' ); ?></option>
									<option value="down" <?php selected( 'down', $c->rounding_method ); ?>><?php esc_html_e( 'Round Down', 'wpcalibrate-multi-currency' ); ?></option>
								</select>
								<input type="number" step="0.01" min="0" max="0.99" placeholder=".99" name="currencies[<?php echo esc_attr( $code ); ?>][psychological_ending]" value="<?php echo esc_attr( null !== $c->psychological_ending ? (string) $c->psychological_ending : '' ); ?>" style="width: 55px;" title="<?php esc_attr_e( 'Psychological ending (e.g. 0.99)', 'wpcalibrate-multi-currency' ); ?>">
							</td>

							<!-- Decimals -->
							<td>
								<input type="number" min="0" max="4" name="currencies[<?php echo esc_attr( $code ); ?>][decimal_places]" value="<?php echo esc_attr( (string) $c->decimal_places ); ?>" style="width: 45px; text-align:center;">
								<input type="hidden" name="currencies[<?php echo esc_attr( $code ); ?>][decimal_separator]" value="<?php echo esc_attr( $c->decimal_separator ); ?>">
								<input type="hidden" name="currencies[<?php echo esc_attr( $code ); ?>][thousands_separator]" value="<?php echo esc_attr( $c->thousands_separator ); ?>">
								<input type="hidden" name="currencies[<?php echo esc_attr( $code ); ?>][sort_order]" value="<?php echo esc_attr( (string) $c->sort_order ); ?>">
							</td>

							<!-- Countries -->
							<td>
								<input type="text" name="currencies[<?php echo esc_attr( $code ); ?>][associated_countries]" value="<?php echo esc_attr( implode( ',', $c->associated_countries ) ); ?>" placeholder="US,CA" style="width: 100px; font-size:12px;" title="<?php esc_attr_e( 'Comma-separated 2-letter country codes', 'wpcalibrate-multi-currency' ); ?>">
							</td>

							<!-- Actions -->
							<td style="text-align:center;">
								<?php if ( ! $c->is_base ) : ?>
									<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to remove this currency?', 'wpcalibrate-multi-currency' ) ); ?>');">
										<button type="submit" form="wpcalibrate_delete_<?php echo esc_attr( $code ); ?>" class="button button-link-delete" style="color:#b91c1c; font-size:12px;">
											<?php esc_html_e( 'Remove', 'wpcalibrate-multi-currency' ); ?>
										</button>
									</form>
								<?php else : ?>
									<span style="color:#9ca3af; font-size:12px;"><?php esc_html_e( 'Primary', 'wpcalibrate-multi-currency' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="wpcalibrate-actions-bar">
			<button type="submit" class="button button-primary button-large">
				<?php esc_html_e( 'Save Currency Configuration', 'wpcalibrate-multi-currency' ); ?>
			</button>
		</div>
	</form>

	<!-- Separate hidden forms for removing currencies safely -->
	<?php foreach ( $currencies as $code => $c ) : ?>
		<?php if ( ! $c->is_base ) : ?>
			<form id="wpcalibrate_delete_<?php echo esc_attr( $code ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=currencies' ) ); ?>" style="display:none;">
				<?php wp_nonce_field( 'wpcalibrate_mc_delete_currency_nonce_action', 'wpcalibrate_mc_delete_currency_nonce' ); ?>
				<input type="hidden" name="wpcalibrate_mc_action" value="delete_currency">
				<input type="hidden" name="currency_code" value="<?php echo esc_attr( $code ); ?>">
			</form>
		<?php endif; ?>
	<?php endforeach; ?>
</div>

<!-- Add Currency Section -->
<div class="wpcalibrate-card">
	<h3 class="wpcalibrate-card-title"><?php esc_html_e( 'Add Another Currency', 'wpcalibrate-multi-currency' ); ?></h3>
	<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Select an ISO currency code from standard WooCommerce currency definitions.', 'wpcalibrate-multi-currency' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=currencies' ) ); ?>" style="display:flex; align-items:center; gap:12px;">
		<?php wp_nonce_field( 'wpcalibrate_mc_add_currency_nonce_action', 'wpcalibrate_mc_add_currency_nonce' ); ?>
		<input type="hidden" name="wpcalibrate_mc_action" value="add_currency">

		<select name="new_currency_code" style="min-width: 260px; height: 38px;">
			<option value=""><?php esc_html_e( '— Select Currency —', 'wpcalibrate-multi-currency' ); ?></option>
			<?php foreach ( $all_wc_curr as $code => $name ) : ?>
				<?php if ( ! isset( $currencies[ $code ] ) ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>">
						<?php echo esc_html( $code . ' - ' . $name ); ?>
					</option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select>

		<button type="submit" class="button button-secondary button-large">
			<span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Add Currency', 'wpcalibrate-multi-currency' ); ?>
		</button>
	</form>
</div>
