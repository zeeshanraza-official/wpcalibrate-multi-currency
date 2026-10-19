=== WPCalibrate Multi-Currency for WooCommerce ===
Contributors: wpcalibrate
Tags: woocommerce, multi-currency, currency switcher, exchange rates, currency converter
Requires at least: 6.8
Tested up to: 7.1.3
Requires PHP: 8.2
WC requires at least: 10.0
WC tested up to: 11.2.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise multi-currency engine and currency switcher for WooCommerce. Multi-currency checkout, fixed product pricing, HPOS, and Blocks.

== Description ==

**WPCalibrate Multi-Currency for WooCommerce** is an enterprise-grade multi-currency solution built for high-performance WooCommerce stores. It enables international customers to view product prices, calculate shipping and taxes, and complete purchases in their chosen currency.

### Primary Operating Modes

1. **Full Multi-Currency Mode**: Customers browse, add items to cart, and complete checkout in their active currency. Orders are created directly in the customer's currency, with an immutable historical snapshot of the exact exchange rates and pricing rules used at the moment of purchase. Compatible payment gateways receive transactions in the customer's selected currency.
2. **Display-Only Mode**: Storefront and catalog display converted prices for customer convenience. At checkout, the cart clearly and transparently reverts to your store base currency with an informative customer notice before payment, and the order is completed in base currency.

### Key Capabilities

* **Multiple Enabled Currencies**: Support for global ISO fiat currencies.
* **Automatic & Manual Exchange Rates**: Live rates from European Central Bank reference data via Frankfurter or Open Exchange Rates, plus manual rate overrides.
* **WooCommerce Action Scheduler**: Automated rate updates run cleanly in the background without affecting storefront page load times.
* **Fixed Product Pricing**: Optional manual regular and sale prices per currency for simple and variable products.
* **Rounding & Psychological Pricing**: Configure rounding (standard, round up, round down, or increments) and price endings (e.g. .99) per currency.
* **High-Performance Order Storage (HPOS)**: Full native compatibility with WooCommerce custom order tables.
* **Cart & Checkout Blocks**: Integrated with WooCommerce Blocks and Store API.
* **Flexible Switcher Displays**: Shortcode `[wpcalibrate_currency_switcher]`, Gutenberg block, PHP helper, automatic placements, and a responsive floating switcher.
* **Payment Gateway Restrictions**: Restrict specific payment gateways to designated currencies.
* **First-Party Persistence**: Safe guest cookies and session persistence without bloat.
* **Privacy Conscious**: No raw visitor IP addresses are stored.

---

== External Services Disclosure ==

This plugin can optionally connect to external third-party APIs to retrieve currency exchange rates. External requests occur exclusively in the background via WooCommerce Action Scheduler or when an administrator manually triggers a refresh from the dashboard. **No customer requests or frontend page views ever trigger external API calls.**

### 1. Frankfurter API (Default No-Key Provider)
* **Service:** Public reference exchange rates based on data published by the European Central Bank (ECB).
* **Provider URL:** https://api.frankfurter.dev/
* **Terms of Service & Privacy:** https://github.com/hakanensari/frankfurter
* **Data Transmitted:** The store base currency code and target currency codes (e.g. `base=USD&symbols=EUR,GBP`). No user data, customer personal information, order data, or store transactions are ever transmitted.
* **Timing:** When automated refresh is scheduled (e.g., daily) or when manually triggered by store admin.

### 2. Open Exchange Rates (Optional Credential-Based Provider)
* **Service:** Commercial and free currency exchange rate API.
* **Provider URL:** https://openexchangerates.org/
* **Terms of Service:** https://openexchangerates.org/terms
* **Privacy Policy:** https://openexchangerates.org/privacy
* **Data Transmitted:** Your configured App ID and currency codes. No user data, customer personal information, or order data is ever transmitted.
* **Timing:** According to configured Action Scheduler schedule or manual refresh.

---

== Installation ==

1. Upload the `wpcalibrate-multi-currency` folder to your `/wp-content/plugins/` directory, or install the ZIP file via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **WPCalibrate > Multi-Currency** in your WordPress dashboard to enable currencies and configure your exchange rate provider.
4. Add the currency switcher to your site using automatic placements, block inserter, or shortcode `[wpcalibrate_currency_switcher]`.

== Frequently Asked Questions ==

= Does this plugin work with WooCommerce High-Performance Order Storage (HPOS)? =
Yes. WPCalibrate Multi-Currency has been engineered and tested with WooCommerce HPOS enabled. All order data and currency snapshots utilize WooCommerce CRUD APIs exclusively.

= Does it support WooCommerce Cart and Checkout Blocks? =
Yes. The plugin integrates with WooCommerce Store API and declares compatibility with Cart and Checkout Blocks.

= Does this plugin slow down my storefront? =
No. External exchange rate APIs are never contacted during customer frontend page views. Stored rates are cached in WordPress options and transients, and updates are processed asynchronously via WooCommerce Action Scheduler.

= What happens if an exchange rate API is down? =
The plugin preserves the last known good rates and marks rates with an alert notice in your admin dashboard. Your storefront never goes down or fails due to an external API outage.

= Can I set manual prices for products instead of converting? =
Yes. On the product edit page (and on individual variations for variable products), you can enter fixed regular and sale prices for any enabled currency. If left empty, automatic rate conversion is used.

= What cookies does this plugin set? =
A minimal first-party cookie named `wpc_currency` is set to store the customer's selected currency preference across page visits. It contains only a 3-letter currency code (e.g. `EUR`) and no personal data.

== Changelog ==

= 1.0.0 =
* Initial production release.
* Full Multi-Currency Mode and Display-Only Mode.
* Automatic rates via Frankfurter (ECB) and Open Exchange Rates.
* Manual rate override and percentage markup.
* Per-currency rounding and psychological price endings (.99).
* Fixed per-currency regular and sale pricing for simple and variable products.
* Currency switcher shortcode, dynamic Gutenberg block, and floating widget.
* Payment gateway mapping per currency.
* WooCommerce HPOS and Cart/Checkout Blocks compatibility.
* WooCommerce Action Scheduler background rate refresh.
* Dedicated WPCalibrate dashboard integration.

== Upgrade Notice ==

= 1.0.0 =
Initial release of WPCalibrate Multi-Currency for WooCommerce.
