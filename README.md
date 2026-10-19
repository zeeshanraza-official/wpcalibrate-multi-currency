# WPCalibrate Multi-Currency for WooCommerce

![WPCalibrate Multi-Currency Banner](assets/branding/icon-dark.png)

[![WordPress 6.8+](https://img.shields.io/badge/WordPress-6.8%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce 10.0+](https://img.shields.io/badge/WooCommerce-10.0%2B-purple.svg)](https://woocommerce.com/)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-indigo.svg)](https://php.net/)
[![HPOS Ready](https://img.shields.io/badge/WooCommerce-HPOS%20Compatible-success.svg)](https://woocommerce.com/)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](LICENSE)

An enterprise-grade, high-performance multi-currency engine and currency switcher built specifically for WooCommerce. Enable international shoppers to browse, calculate, and checkout in their native currency with live automated exchange rates, per-currency psychological rounding, geographic currency detection, and fixed product pricing overrides.

---

## 🌟 Key Features

- **Dual Operating Modes:**
  - **Full Multi-Currency Mode (Default):** Complete multi-currency checkout. Customers browse, calculate shipping, apply coupons, and pay through compatible payment gateways in their active currency. Orders are permanently frozen in the customer's currency with immutable exchange rate snapshots.
  - **Display-Only Mode:** Customers browse products and calculate carts in their selected currency for transparency, while payments and checkout orders smoothly revert to your store's base currency before final payment.

- **Automated Exchange Rate Providers:**
  - **Frankfurter API (Default):** Free, real-time European Central Bank (ECB) reference rates for 30+ major fiat currencies with zero API key configuration.
  - **Open Exchange Rates:** Enterprise integration supporting 160+ fiat currencies with automated base cross-rate derivations.
  - **Manual Fixed Rates:** Full administrator control to define fixed exchange rates per currency.
  - **WooCommerce Action Scheduler Automation:** Hourly, twice-daily, or daily background rate updates with transient idempotency locks and staleness alerts.

- **Mathematical Precision & Rounding Engine:**
  - Percentage markup adjustments per currency (e.g. +2.5% to offset gateway conversion fees).
  - Configurable rounding rules: `None`, `Standard (Round to nearest integer)`, `Up (Ceil)`, `Down (Floor)`, or custom `Increment` (e.g. `0.50`).
  - Psychological pricing endings (e.g. force all converted prices to end in `.99` or `.95`).
  - Deterministic calculations preventing double-conversion drift across cart items, shipping, fees, and taxes.

- **Fixed Per-Currency Product Pricing:**
  - Override automatic conversions with fixed regular and sale prices per currency on simple products and variable product variations.
  - Empty fields automatically fall back to live currency exchange rates.

- **Automatic Geolocation Detection:**
  - Automatically identifies customer country using WooCommerce IP Geolocation and switches to the mapped domestic currency on first visit.
  - Protects customer intent: explicit customer currency selections are preserved across user meta, WooCommerce sessions, and first-party cookies.

- **Flexible Currency Switcher UI:**
  - Shortcode: `[wpcalibrate_currency_switcher style="dropdown|buttons" format="code_symbol|code|symbol|code_name"]`
  - Native Gutenberg Block: `WPCalibrate Currency Switcher` with real-time inspector settings.
  - Automatic placements: Shop catalog loop, single product page, cart, checkout, or floating sticky widget.
  - Full RTL (Right-to-Left) language support and responsive touch targets.

- **100% WooCommerce HPOS & Blocks Compatibility:**
  - Declared compatibility with High-Performance Order Storage (`custom_order_tables`).
  - Built exclusively using WooCommerce CRUD APIs—zero direct SQL queries to legacy post tables.
  - Declared compatibility with Cart and Checkout Blocks (`cart_checkout_blocks`) and Store API cart schema extensions.

---

## 📋 Requirements

| Component | Minimum Version | Tested Up To |
|---|---|---|
| **PHP** | 8.2 | 8.4 |
| **WordPress** | 6.8 | 7.1.3 |
| **WooCommerce** | 10.0 | 11.2.1 |
| **MySQL / MariaDB** | 5.7 / 10.4 | 8.0+ |

---

## 🚀 Installation

### Option 1: WordPress Dashboard Upload (ZIP)
1. Download the latest release `wpcalibrate-multi-currency-1.0.0.zip` from the [Releases](https://github.com/zeeshanraza-official/wpcalibrate-multi-currency/releases) page.
2. In your WordPress admin, navigate to **Plugins > Add New > Upload Plugin**.
3. Choose the downloaded ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

### Option 2: Manual FTP / SFTP Installation
1. Extract `wpcalibrate-multi-currency-1.0.0.zip`.
2. Upload the `wpcalibrate-multi-currency` folder into your server's `/wp-content/plugins/` directory.
3. Navigate to **Plugins > Installed Plugins** in WordPress and activate **WPCalibrate Multi-Currency for WooCommerce**.

---

## 📖 Documentation & Setup Guide

### 1. Enable Target Currencies
1. Navigate to **WPCalibrate > Multi-Currency > Currencies**.
2. Select target currencies (EUR, GBP, CAD, AUD, JPY, etc.).
3. Customize currency symbols, decimal precision, symbol position, and country mappings.
4. Save changes.

### 2. Configure Rate Automation
1. Navigate to **WPCalibrate > Multi-Currency > Exchange Rates**.
2. Choose your rate source (**Frankfurter**, **Open Exchange Rates**, or **Manual**).
3. Set your preferred update interval (e.g., Daily or Hourly via Action Scheduler).
4. Click **Refresh Exchange Rates Now** to pull the latest live rates.

### 3. Display the Currency Switcher
- **Widget / Automatic Placements:** Go to **WPCalibrate > Multi-Currency > Display & Switcher** to enable placement on Product Pages, Cart, Checkout, or Floating Widget.
- **Shortcode:** Insert `[wpcalibrate_currency_switcher]` anywhere in your content, header, or footer templates.
- **Gutenberg Block:** Search for "Currency Switcher" in the block inserter.

### 4. Choose Operating Mode
- In **General Settings**, select **Full Multi-Currency Mode** for international checkout or **Display-Only Mode** for catalog browsing with base currency payment.

---

## 🛠️ Developer Hooks & Filters

```php
// Programmatically override active currency for custom conditions
add_filter( 'wpcalibrate_mc_resolved_currency', function( $currency, $user_id ) {
    if ( is_user_logged_in() && current_user_can( 'vip_customer' ) ) {
        return 'EUR';
    }
    return $currency;
}, 10, 2 );

// Modify converted price programmatically
add_filter( 'wpcalibrate_mc_converted_price', function( $converted, $base_price, $target_currency ) {
    return $converted;
}, 10, 3 );

// Filter fee conversion
add_filter( 'wpcalibrate_mc_convert_fee', function( $convert, $fee, $cart ) {
    return true; // Set to false to bypass fee conversion
}, 10, 3 );
```

---

## 📝 Changelog

### 1.0.0 (2026-10-19)
- **Initial Production Release:**
  - Enterprise multi-currency engine for WooCommerce.
  - Dual operating modes: Full Multi-Currency Checkout and Display-Only Mode.
  - Automated exchange rate providers: Frankfurter (ECB rates, no-key), Open Exchange Rates, and Manual rates.
  - Action Scheduler recurring background rate updater with idempotency transient locks.
  - Fixed per-currency pricing overrides for simple products and variations.
  - Deterministic conversion engine supporting markups, rounding rules, and psychological pricing (.99).
  - Geolocation automatic country-currency detection.
  - Currency switcher shortcode, Gutenberg block, and floating widget with full RTL support.
  - Declared native compatibility with WooCommerce HPOS (`custom_order_tables`) and Cart & Checkout Blocks (`cart_checkout_blocks`).
  - Immutable order meta snapshots freezing historical exchange rate contexts.
  - Shared WPCalibrate admin dashboard integration with modernized segmented pill navigation.

---

## 📄 License & Author

- **Author:** [WPCalibrate](https://wpcalibrate.com)
- **Support:** support@wpcalibrate.com | Hotline/WhatsApp: +447474795976
- **Marketplace:** [marketplace.wpcalibrate.com](https://marketplace.wpcalibrate.com/)
- **License:** GNU General Public License v2.0 or later ([GPL-2.0-or-later](LICENSE))
