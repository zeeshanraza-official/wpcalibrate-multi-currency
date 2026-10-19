<?php
/**
 * Admin Overview Tab (Plugin Purpose, How to Use & Methodology).
 *
 * @package WPCalibrate\MultiCurrency\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\ExchangeRates\RateUpdater;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings      = SettingsManager::get_all();
$base_code     = CurrencyRepository::get_base_currency_code();
$currencies    = CurrencyRepository::get_all();
$enabled_curr  = CurrencyRepository::get_enabled();
$provider_id   = (string) SettingsManager::get( 'rate_provider', 'frankfurter' );
$provider      = RateUpdater::get_provider( $provider_id );
$last_refresh  = RateUpdater::get_last_refresh_log();
$is_hpos       = class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled();
$mode          = (string) SettingsManager::get( 'operating_mode', 'full' );
?>

<!-- Overview Hero Banner -->
<div class="wpcalibrate-card wpcalibrate-overview-hero">
	<div class="wpcalibrate-overview-hero-content">
		<span class="wpcalibrate-badge wpcalibrate-badge-primary" style="margin-bottom: 10px;">
			<?php esc_html_e( 'Getting Started & Architecture', 'wpcalibrate-multi-currency' ); ?>
		</span>
		<h2 class="wpcalibrate-overview-title">
			<?php esc_html_e( 'Enterprise Multi-Currency Engine for WooCommerce', 'wpcalibrate-multi-currency' ); ?>
		</h2>
		<p class="wpcalibrate-overview-lead">
			<?php esc_html_e( 'WPCalibrate Multi-Currency empowers your WooCommerce store to sell globally with live exchange rates, per-currency rounding, geographic currency detection, fixed product pricing, and seamless multi-currency checkout.', 'wpcalibrate-multi-currency' ); ?>
		</p>
	</div>

	<!-- Live Status Pills Grid -->
	<div class="wpcalibrate-overview-stats-grid">
		<div class="wpcalibrate-stat-card">
			<span class="wpcalibrate-stat-label"><?php esc_html_e( 'Operating Mode', 'wpcalibrate-multi-currency' ); ?></span>
			<span class="wpcalibrate-stat-value">
				<?php if ( 'full' === $mode ) : ?>
					<span class="wpcalibrate-dot wpcalibrate-dot-success"></span> <?php esc_html_e( 'Full Checkout', 'wpcalibrate-multi-currency' ); ?>
				<?php else : ?>
					<span class="wpcalibrate-dot wpcalibrate-dot-warning"></span> <?php esc_html_e( 'Display-Only', 'wpcalibrate-multi-currency' ); ?>
				<?php endif; ?>
			</span>
		</div>

		<div class="wpcalibrate-stat-card">
			<span class="wpcalibrate-stat-label"><?php esc_html_e( 'Base Currency', 'wpcalibrate-multi-currency' ); ?></span>
			<span class="wpcalibrate-stat-value wpcalibrate-stat-highlight">
				<?php echo esc_html( $base_code ); ?> (1.000000)
			</span>
		</div>

		<div class="wpcalibrate-stat-card">
			<span class="wpcalibrate-stat-label"><?php esc_html_e( 'Enabled Currencies', 'wpcalibrate-multi-currency' ); ?></span>
			<span class="wpcalibrate-stat-value">
				<?php echo (int) count( $enabled_curr ); ?> / <?php echo (int) count( $currencies ); ?>
			</span>
		</div>

		<div class="wpcalibrate-stat-card">
			<span class="wpcalibrate-stat-label"><?php esc_html_e( 'Rate Provider', 'wpcalibrate-multi-currency' ); ?></span>
			<span class="wpcalibrate-stat-value">
				<?php echo esc_html( $provider ? $provider->get_label() : ucfirst( $provider_id ) ); ?>
			</span>
		</div>

		<div class="wpcalibrate-stat-card">
			<span class="wpcalibrate-stat-label"><?php esc_html_e( 'WooCommerce HPOS', 'wpcalibrate-multi-currency' ); ?></span>
			<span class="wpcalibrate-stat-value">
				<?php echo $is_hpos ? esc_html__( 'HPOS Active', 'wpcalibrate-multi-currency' ) : esc_html__( 'Classic Storage', 'wpcalibrate-multi-currency' ); ?>
			</span>
		</div>
	</div>
</div>

<!-- 4-Step Quickstart Guide -->
<div class="wpcalibrate-card">
	<h3 class="wpcalibrate-card-title">
		<span class="dashicons dashicons-flag" style="color:#2563eb; vertical-align:middle;"></span>
		<?php esc_html_e( 'How to Use — 4-Step Setup Guide', 'wpcalibrate-multi-currency' ); ?>
	</h3>
	<p class="wpcalibrate-card-desc">
		<?php esc_html_e( 'Follow these four simple steps to fully configure international pricing on your store.', 'wpcalibrate-multi-currency' ); ?>
	</p>

	<div class="wpcalibrate-steps-grid">
		<!-- Step 1 -->
		<div class="wpcalibrate-step-item">
			<div class="wpcalibrate-step-number">1</div>
			<div class="wpcalibrate-step-content">
				<h4 class="wpcalibrate-step-title"><?php esc_html_e( 'Enable Target Currencies', 'wpcalibrate-multi-currency' ); ?></h4>
				<p class="wpcalibrate-step-desc">
					<?php esc_html_e( 'Navigate to the Currencies tab. Add your target fiat currencies (e.g. EUR, GBP, CAD, JPY). Customise currency symbols, position (Left/Right/Space), decimal precision, and assign country mappings.', 'wpcalibrate-multi-currency' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=currencies' ) ); ?>" class="wpcalibrate-step-link">
					<?php esc_html_e( 'Configure Currencies &rarr;', 'wpcalibrate-multi-currency' ); ?>
				</a>
			</div>
		</div>

		<!-- Step 2 -->
		<div class="wpcalibrate-step-item">
			<div class="wpcalibrate-step-number">2</div>
			<div class="wpcalibrate-step-content">
				<h4 class="wpcalibrate-step-title"><?php esc_html_e( 'Select Rate Source & Automation', 'wpcalibrate-multi-currency' ); ?></h4>
				<p class="wpcalibrate-step-desc">
					<?php esc_html_e( 'In the Exchange Rates tab, choose between Frankfurter (free European Central Bank reference rates, no API key required), Open Exchange Rates, or Fixed Manual rates. Set up Action Scheduler update intervals.', 'wpcalibrate-multi-currency' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=rates' ) ); ?>" class="wpcalibrate-step-link">
					<?php esc_html_e( 'Configure Rates & Automation &rarr;', 'wpcalibrate-multi-currency' ); ?>
				</a>
			</div>
		</div>

		<!-- Step 3 -->
		<div class="wpcalibrate-step-item">
			<div class="wpcalibrate-step-number">3</div>
			<div class="wpcalibrate-step-content">
				<h4 class="wpcalibrate-step-title"><?php esc_html_e( 'Place Currency Switcher', 'wpcalibrate-multi-currency' ); ?></h4>
				<p class="wpcalibrate-step-desc">
					<?php esc_html_e( 'Enable automatic placements (Shop Loop, Product Page, Cart, Checkout, or Floating Sticky Widget) in the Display tab, or insert shortcode [wpcalibrate_currency_switcher] or Gutenberg block.', 'wpcalibrate-multi-currency' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=display' ) ); ?>" class="wpcalibrate-step-link">
					<?php esc_html_e( 'Setup Switcher Display &rarr;', 'wpcalibrate-multi-currency' ); ?>
				</a>
			</div>
		</div>

		<!-- Step 4 -->
		<div class="wpcalibrate-step-item">
			<div class="wpcalibrate-step-number">4</div>
			<div class="wpcalibrate-step-content">
				<h4 class="wpcalibrate-step-title"><?php esc_html_e( 'Select Checkout Operating Mode', 'wpcalibrate-multi-currency' ); ?></h4>
				<p class="wpcalibrate-step-desc">
					<?php esc_html_e( 'Choose Full Multi-Currency Mode to accept international payments in the customer currency, or Display-Only Mode to convert prices during browsing while processing payments in base currency.', 'wpcalibrate-multi-currency' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=general' ) ); ?>" class="wpcalibrate-step-link">
					<?php esc_html_e( 'Set Operating Mode &rarr;', 'wpcalibrate-multi-currency' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>

<!-- Methodology & Architectural Pipeline -->
<div class="wpcalibrate-card">
	<h3 class="wpcalibrate-card-title">
		<span class="dashicons dashicons-calculator" style="color:#2563eb; vertical-align:middle;"></span>
		<?php esc_html_e( 'Conversion Methodology & Calculation Pipeline', 'wpcalibrate-multi-currency' ); ?>
	</h3>
	<p class="wpcalibrate-card-desc">
		<?php esc_html_e( 'How WPCalibrate Multi-Currency processes monetary values deterministically without rounding drift or double conversion.', 'wpcalibrate-multi-currency' ); ?>
	</p>

	<div class="wpcalibrate-pipeline-box">
		<div class="wpcalibrate-pipeline-step">
			<span class="wpcalibrate-pipe-badge"><?php esc_html_e( 'Step 1', 'wpcalibrate-multi-currency' ); ?></span>
			<strong><?php esc_html_e( 'Fixed Price Check', 'wpcalibrate-multi-currency' ); ?></strong>
			<p><?php esc_html_e( 'Checks if the product has a manual fixed price in the active currency. If defined, uses it directly.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>
		<div class="wpcalibrate-pipeline-arrow">&rarr;</div>
		<div class="wpcalibrate-pipeline-step">
			<span class="wpcalibrate-pipe-badge"><?php esc_html_e( 'Step 2', 'wpcalibrate-multi-currency' ); ?></span>
			<strong><?php esc_html_e( 'Exchange Rate', 'wpcalibrate-multi-currency' ); ?></strong>
			<p><?php esc_html_e( 'Multiplies base price by the active currency exchange rate against your store base currency.', 'wpcalibrate-multi-currency' ); ?></p>
		</div>
		<div class="wpcalibrate-pipeline-arrow">&rarr;</div>
		<div class="wpcalibrate-pipeline-step">
			<span class="wpcalibrate-pipe-badge"><?php esc_html_e( 'Step 3', 'wpcalibrate-multi-currency' ); ?></span>
			<strong><?php esc_html_e( 'Rate Markup %', 'wpcalibrate-multi-currency' ); ?></strong>
			<p><?php esc_html_e( 'Applies administrator markup adjustment (e.g. +2.5% to offset payment gateway conversion costs).', 'wpcalibrate-multi-currency' ); ?></p>
		</div>
		<div class="wpcalibrate-pipeline-arrow">&rarr;</div>
		<div class="wpcalibrate-pipeline-step">
			<span class="wpcalibrate-pipe-badge"><?php esc_html_e( 'Step 4', 'wpcalibrate-multi-currency' ); ?></span>
			<strong><?php esc_html_e( 'Rounding & Ending', 'wpcalibrate-multi-currency' ); ?></strong>
			<p><?php esc_html_e( 'Applies rounding rule (standard, up, down, increment) followed by psychological ending (.99).', 'wpcalibrate-multi-currency' ); ?></p>
		</div>
	</div>

	<hr style="margin: 20px 0; border:0; border-top: 1px solid #e5e7eb;">

	<!-- Core Architectural Protections -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
		<div class="wpcalibrate-feature-card">
			<h4 style="margin:0 0 6px 0; font-size:14px; color:#111827;">
				<span class="dashicons dashicons-shield" style="color:#059669; vertical-align:middle;"></span>
				<?php esc_html_e( 'Double-Conversion Prevention', 'wpcalibrate-multi-currency' ); ?>
			</h4>
			<p style="margin:0; font-size:13px; color:#6b7280; line-height: 1.5;">
				<?php esc_html_e( 'Original base costs and taxes are tracked via immutable entity metadata. Prices are never multiplied twice across carts, shipping, or fee calculations.', 'wpcalibrate-multi-currency' ); ?>
			</p>
		</div>

		<div class="wpcalibrate-feature-card">
			<h4 style="margin:0 0 6px 0; font-size:14px; color:#111827;">
				<span class="dashicons dashicons-camera" style="color:#2563eb; vertical-align:middle;"></span>
				<?php esc_html_e( 'Immutable Order Snapshots', 'wpcalibrate-multi-currency' ); ?>
			</h4>
			<p style="margin:0; font-size:13px; color:#6b7280; line-height: 1.5;">
				<?php esc_html_e( 'When an order is created, the exact exchange rate, provider, markup, and timestamp are frozen in WooCommerce order meta. Future exchange rate updates never alter historical financials.', 'wpcalibrate-multi-currency' ); ?>
			</p>
		</div>

		<div class="wpcalibrate-feature-card">
			<h4 style="margin:0 0 6px 0; font-size:14px; color:#111827;">
				<span class="dashicons dashicons-database" style="color:#7c3aed; vertical-align:middle;"></span>
				<?php esc_html_e( 'High-Performance Order Storage (HPOS)', 'wpcalibrate-multi-currency' ); ?>
			</h4>
			<p style="margin:0; font-size:13px; color:#6b7280; line-height: 1.5;">
				<?php esc_html_e( 'Engineered using WooCommerce CRUD APIs exclusively. Fully compatible with HPOS custom order tables without direct SQL queries to wp_posts or wp_postmeta.', 'wpcalibrate-multi-currency' ); ?>
			</p>
		</div>
	</div>
</div>

<!-- Quick Navigation Bar -->
<div class="wpcalibrate-card" style="background:#f8fafc; border-color:#cbd5e1;">
	<div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
		<div>
			<h4 style="margin:0 0 4px 0; font-size:15px; color:#1e293b;"><?php esc_html_e( 'Need Help or Integration Guidance?', 'wpcalibrate-multi-currency' ); ?></h4>
			<p style="margin:0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Our engineering team is standing by to assist with currency configuration and custom gateway integration.', 'wpcalibrate-multi-currency' ); ?>
			</p>
		</div>
		<div style="display:flex; gap:10px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=currencies' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Manage Currencies', 'wpcalibrate-multi-currency' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AdminMenu::SUBMENU_SLUG . '&tab=support' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Contact Support', 'wpcalibrate-multi-currency' ); ?>
			</a>
		</div>
	</div>
</div>
