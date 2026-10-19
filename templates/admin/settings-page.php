<?php
/**
 * Main Settings Page Template.
 *
 * @package WPCalibrate\MultiCurrency\Admin
 *
 * @var string $current_tab
 */

declare(strict_types=1);

namespace WPCalibrate\MultiCurrency\Admin;

use WPCalibrate\MultiCurrency\Currency\CurrencyRepository;
use WPCalibrate\MultiCurrency\ExchangeRates\RateUpdater;
use WPCalibrate\MultiCurrency\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings   = SettingsManager::get_all();
$base_code  = CurrencyRepository::get_base_currency_code();
$currencies = CurrencyRepository::get_all();
$stale      = RateUpdater::get_stale_currencies();
$version    = defined( 'WPCALIBRATE_MC_VERSION' ) ? WPCALIBRATE_MC_VERSION : '1.0.0';

$tabs = [
	'overview'    => [ 'label' => __( 'Overview', 'wpcalibrate-multi-currency' ), 'icon' => 'dashicons-info' ],
	'general'     => [ 'label' => __( 'General', 'wpcalibrate-multi-currency' ), 'icon' => 'dashicons-admin-generic' ],
	'currencies'  => [ 'label' => __( 'Currencies', 'wpcalibrate-multi-currency' ), 'icon' => 'dashicons-money-alt', 'count' => count( $currencies ) ],
	'rates'       => [ 'label' => __( 'Exchange Rates', 'wpcalibrate-multi-currency' ), 'icon' => 'dashicons-update', 'stale' => count( $stale ) ],
	'display'     => [ 'label' => __( 'Display & Switcher', 'wpcalibrate-multi-currency' ), 'icon' => 'dashicons-desktop' ],
	'geolocation' => [ 'label' => __( 'Geolocation', 'wpcalibrate-multi-currency' ), 'icon' => 'dashicons-location-alt' ],
	'checkout'    => [ 'label' => __( 'Checkout & Gateways', 'wpcalibrate-multi-currency' ), 'icon' => 'dashicons-cart' ],
	'advanced'    => [ 'label' => __( 'Advanced', 'wpcalibrate-multi-currency' ), 'icon' => 'dashicons-admin-tools' ],
	'license'     => [ 'label' => __( 'License', 'wpcalibrate-multi-currency' ), 'icon' => 'dashicons-admin-network' ],
	'support'     => [ 'label' => __( 'Support', 'wpcalibrate-multi-currency' ), 'icon' => 'dashicons-sos' ],
];

$message = isset( $_GET['wpcalibrate_mc_message'] ) ? sanitize_key( wp_unslash( $_GET['wpcalibrate_mc_message'] ) ) : '';
?>
<div class="wrap wpcalibrate-admin-wrap">

	<!-- Header with official WPCalibrate branding -->
	<header class="wpcalibrate-header">
		<div class="wpcalibrate-header-brand">
			<img src="<?php echo esc_url( WPCALIBRATE_MC_URL . 'assets/branding/icon-dark.png' ); ?>" alt="WPCalibrate" class="wpcalibrate-brand-logo">
			<div>
				<h1 class="wpcalibrate-header-title"><?php esc_html_e( 'Multi-Currency for WooCommerce', 'wpcalibrate-multi-currency' ); ?></h1>
				<p class="wpcalibrate-header-subtitle"><?php esc_html_e( 'Enterprise Multi-Currency Engine & Currency Switcher by WPCalibrate', 'wpcalibrate-multi-currency' ); ?></p>
			</div>
		</div>
		<div class="wpcalibrate-header-meta">
			<span class="wpcalibrate-badge wpcalibrate-badge-version">v<?php echo esc_html( $version ); ?></span>
			<?php if ( ! empty( $settings['enabled'] ) ) : ?>
				<span class="wpcalibrate-badge wpcalibrate-badge-success">
					<span class="wpcalibrate-dot wpcalibrate-dot-success"></span>
					<?php esc_html_e( 'Active', 'wpcalibrate-multi-currency' ); ?>
				</span>
			<?php else : ?>
				<span class="wpcalibrate-badge wpcalibrate-badge-warning">
					<span class="wpcalibrate-dot wpcalibrate-dot-warning"></span>
					<?php esc_html_e( 'Disabled', 'wpcalibrate-multi-currency' ); ?>
				</span>
			<?php endif; ?>
			<span class="wpcalibrate-badge wpcalibrate-badge-info">
				<?php
				/* translators: %s: Currency code */
				printf( esc_html__( 'Base: %s', 'wpcalibrate-multi-currency' ), esc_html( $base_code ) );
				?>
			</span>
		</div>
	</header>

	<!-- Notices -->
	<?php if ( 'settings_saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved successfully.', 'wpcalibrate-multi-currency' ); ?></p></div>
	<?php elseif ( 'currencies_saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Currencies and rate configurations updated.', 'wpcalibrate-multi-currency' ); ?></p></div>
	<?php elseif ( 'currency_added' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Currency added successfully.', 'wpcalibrate-multi-currency' ); ?></p></div>
	<?php elseif ( 'currency_deleted' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Currency removed.', 'wpcalibrate-multi-currency' ); ?></p></div>
	<?php elseif ( 'rates_refreshed' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Exchange rates refreshed successfully from the configured provider.', 'wpcalibrate-multi-currency' ); ?></p></div>
	<?php elseif ( 'rates_refresh_failed' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Exchange rate refresh encountered an error. Stored rates were preserved.', 'wpcalibrate-multi-currency' ); ?></p></div>
	<?php elseif ( 'license_saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'License key updated.', 'wpcalibrate-multi-currency' ); ?></p></div>
	<?php elseif ( 'license_deactivated' === $message ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'License deactivated.', 'wpcalibrate-multi-currency' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! empty( $stale ) ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Stale Exchange Rates Warning:', 'wpcalibrate-multi-currency' ); ?></strong>
				<?php
				$stale_codes = implode( ', ', array_map( static fn( $c ) => $c->code, $stale ) );
				printf(
					/* translators: %s: Comma-separated list of currency codes */
					esc_html__( 'The following currencies have rates older than your configured stale threshold (%s). Please check your exchange rate provider settings or trigger a refresh.', 'wpcalibrate-multi-currency' ),
					esc_html( $stale_codes )
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Tabs Navigation -->
	<nav class="wpcalibrate-tabs" aria-label="<?php esc_attr_e( 'Plugin Settings Tabs', 'wpcalibrate-multi-currency' ); ?>">
		<?php foreach ( $tabs as $tab_key => $tab_data ) : ?>
			<?php
			$tab_url   = add_query_arg( [ 'page' => AdminMenu::SUBMENU_SLUG, 'tab' => $tab_key ], admin_url( 'admin.php' ) );
			$is_active = ( $current_tab === $tab_key );
			?>
			<a href="<?php echo esc_url( $tab_url ); ?>" class="wpcalibrate-tab-link <?php echo $is_active ? 'is-active' : ''; ?>">
				<span class="dashicons <?php echo esc_attr( $tab_data['icon'] ); ?>"></span>
				<span class="wpcalibrate-tab-text"><?php echo esc_html( $tab_data['label'] ); ?></span>
				<?php if ( isset( $tab_data['count'] ) ) : ?>
					<span class="wpcalibrate-tab-count"><?php echo (int) $tab_data['count']; ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $tab_data['stale'] ) ) : ?>
					<span class="wpcalibrate-tab-count wpcalibrate-tab-count-stale"><?php echo (int) $tab_data['stale']; ?></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<!-- Tab Content Container -->
	<div class="wpcalibrate-tab-content">
		<?php
		$tab_file = WPCALIBRATE_MC_PATH . 'templates/admin/tab-' . $current_tab . '.php';
		if ( file_exists( $tab_file ) ) {
			include $tab_file;
		} else {
			echo '<p>' . esc_html__( 'Selected tab does not exist.', 'wpcalibrate-multi-currency' ) . '</p>';
		}
		?>
	</div>

</div>
