<?php
/**
 * Dropdown Currency Switcher Template.
 *
 * @package WPCalibrate\MultiCurrency
 *
 * @var array<string, \WPCalibrate\MultiCurrency\Currency\Currency> $currencies
 * @var string $active_currency
 * @var string $format
 * @var string $redirect_url
 * @var string $nonce
 * @var array<string, mixed> $args
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpcalibrate-currency-switcher-wrapper wpcalibrate-switcher-dropdown <?php echo esc_attr( $args['wrapper_class'] ?? '' ); ?>">
	<form method="post" action="<?php echo esc_url( $redirect_url ); ?>" class="wpcalibrate-currency-switcher-form">
		<input type="hidden" name="wpcalibrate_mc_action" value="switch_currency">
		<input type="hidden" name="wpcalibrate_mc_nonce" value="<?php echo esc_attr( $nonce ); ?>">
		<input type="hidden" name="wpcalibrate_redirect" value="<?php echo esc_url( $redirect_url ); ?>">

		<label for="wpcalibrate-currency-select-<?php echo esc_attr( $args['id_suffix'] ?? 'main' ); ?>" class="screen-reader-text">
			<?php esc_html_e( 'Select Currency', 'wpcalibrate-multi-currency' ); ?>
		</label>

		<div class="wpcalibrate-select-container">
			<select
				id="wpcalibrate-currency-select-<?php echo esc_attr( $args['id_suffix'] ?? 'main' ); ?>"
				name="wpcalibrate_currency"
				class="wpcalibrate-currency-select"
				aria-label="<?php esc_attr_e( 'Select Store Currency', 'wpcalibrate-multi-currency' ); ?>"
				data-active="<?php echo esc_attr( $active_currency ); ?>"
				onchange="this.form.submit()"
			>
				<?php foreach ( $currencies as $code => $currency ) : ?>
					<?php
					$label = match ( $format ) {
						'code'      => $currency->code,
						'symbol'    => $currency->symbol . ' (' . $currency->code . ')',
						'code_name' => $currency->code . ' - ' . $currency->name,
						default     => $currency->code . ' (' . $currency->symbol . ')',
					};
					?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $active_currency ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<noscript>
				<button type="submit" class="wpcalibrate-submit-btn button">
					<?php esc_html_e( 'Switch', 'wpcalibrate-multi-currency' ); ?>
				</button>
			</noscript>
		</div>
	</form>
</div>
