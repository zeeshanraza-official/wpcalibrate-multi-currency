<?php
/**
 * Buttons / Inline Pills Currency Switcher Template.
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
<div class="wpcalibrate-currency-switcher-wrapper wpcalibrate-switcher-buttons <?php echo esc_attr( $args['wrapper_class'] ?? '' ); ?>">
	<nav aria-label="<?php esc_attr_e( 'Currency Selection', 'wpcalibrate-multi-currency' ); ?>" class="wpcalibrate-buttons-nav">
		<ul class="wpcalibrate-currency-button-list" role="list">
			<?php foreach ( $currencies as $code => $currency ) : ?>
				<?php
				$is_active = ( $code === $active_currency );
				$label     = match ( $format ) {
					'code'      => $currency->code,
					'symbol'    => $currency->symbol,
					'code_name' => $currency->code . ' ' . $currency->name,
					default     => $currency->code . ' ' . $currency->symbol,
				};
				$switch_url = add_query_arg(
					[
						'wpc_currency' => $code,
						'_wpnonce'     => $nonce,
					],
					$redirect_url
				);
				?>
				<li class="wpcalibrate-currency-item">
					<a
						href="<?php echo esc_url( $switch_url ); ?>"
						class="wpcalibrate-currency-btn <?php echo $is_active ? 'is-active' : ''; ?>"
						aria-current="<?php echo $is_active ? 'true' : 'false'; ?>"
						data-currency="<?php echo esc_attr( $code ); ?>"
					>
						<?php echo esc_html( $label ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
</div>
