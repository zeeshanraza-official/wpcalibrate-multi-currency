<?php
/**
 * Admin Support & Documentation Tab.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wpcalibrate-card">
	<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'WPCalibrate Support & Resources', 'wpcalibrate-multi-currency' ); ?></h2>
	<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Our dedicated engineering support team is available to help you configure, scale, and optimize your multi-currency store.', 'wpcalibrate-multi-currency' ); ?></p>

	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-top: 15px;">
		<!-- Direct Support Channels -->
		<div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
			<h3 style="margin-top:0; font-size:16px; color:#111827;">
				<span class="dashicons dashicons-email-alt" style="color:#2563eb; vertical-align:middle;"></span>
				<?php esc_html_e( 'Email Support', 'wpcalibrate-multi-currency' ); ?>
			</h3>
			<p style="font-size:13px; color:#4b5563; margin-bottom:12px;">
				<?php esc_html_e( 'For technical queries, bug reports, or feature requests, contact our engineering desk:', 'wpcalibrate-multi-currency' ); ?>
			</p>
			<a href="mailto:support@wpcalibrate.com" class="button button-secondary" style="font-weight:600;">
				support@wpcalibrate.com
			</a>
		</div>

		<!-- Phone & WhatsApp -->
		<div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
			<h3 style="margin-top:0; font-size:16px; color:#111827;">
				<span class="dashicons dashicons-phone" style="color:#2563eb; vertical-align:middle;"></span>
				<?php esc_html_e( 'Phone & WhatsApp', 'wpcalibrate-multi-currency' ); ?>
			</h3>
			<p style="font-size:13px; color:#4b5563; margin-bottom:12px;">
				<?php esc_html_e( 'Direct business hotline and instant WhatsApp support channel:', 'wpcalibrate-multi-currency' ); ?>
			</p>
			<a href="tel:+447474795976" class="button button-secondary" style="font-weight:600;">
				+44 7474 795976
			</a>
		</div>

		<!-- Marketplace & Updates -->
		<div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
			<h3 style="margin-top:0; font-size:16px; color:#111827;">
				<span class="dashicons dashicons-cart" style="color:#2563eb; vertical-align:middle;"></span>
				<?php esc_html_e( 'WPCalibrate Marketplace', 'wpcalibrate-multi-currency' ); ?>
			</h3>
			<p style="font-size:13px; color:#4b5563; margin-bottom:12px;">
				<?php esc_html_e( 'Discover complementary WooCommerce performance, pricing, and checkout plugins:', 'wpcalibrate-multi-currency' ); ?>
			</p>
			<a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="font-weight:600;">
				<?php esc_html_e( 'Visit Marketplace &rarr;', 'wpcalibrate-multi-currency' ); ?>
			</a>
		</div>
	</div>

	<hr style="margin: 24px 0; border:0; border-top: 1px solid #e5e7eb;">

	<div style="font-size: 13px; color: #6b7280; line-height: 1.6;">
		<p>
			<strong><?php esc_html_e( 'Official Website:', 'wpcalibrate-multi-currency' ); ?></strong>
			<a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer" style="color:#2563eb; text-decoration:none;">https://wpcalibrate.com</a>
		</p>
		<p>
			<?php esc_html_e( 'WPCalibrate Multi-Currency for WooCommerce is engineered to provide bulletproof currency conversion, deterministic order snapshots, and complete compliance with WooCommerce HPOS and modern block checkout.', 'wpcalibrate-multi-currency' ); ?>
		</p>
	</div>
</div>
