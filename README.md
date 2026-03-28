=== PW - Ofertas Avanzadas Lite ===
Contributors: pezweb
Donate link: https://pezweb.com/
Tags: woocommerce, discounts, marketing, campaigns, offers, lite
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 2.0.18
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free Lite edition: discount campaigns for WooCommerce (campaign and strategy limits). The Pro version is sold separately by the author.

== Description ==

**PW - Ofertas Avanzadas Lite** helps you create and manage discount campaigns in WooCommerce using a guided wizard, several marketing-oriented strategies, and optional on-product badges.

**Lite limits (summary):** fewer simultaneous campaigns and only strategies included in Lite. Upgrade links point to the author’s sales page; you can set the final URL with the filter below when it is ready.

**Languages:** Strings passed through gettext use English as the `msgid` in code; the package includes a Spanish translation (`languages/pw-ofertas-avanzadas-es_ES.mo`). With the site language set to Spanish, WordPress will load those translations for menus, AJAX messages, and JS-localized strings.

**Pro version:** It is not distributed through this WordPress.org listing; it is sold separately. Upgrade buttons use the `pwoa_upgrade_url` filter (or the default URL until you change it).

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install it from **Plugins → Add New**.
2. Activate the plugin.
3. Keep **WooCommerce** installed and active.
4. Open the **Offers** menu (the label may appear translated) to create campaigns.

== Frequently Asked Questions ==

= Does it work without WooCommerce? =

No. WooCommerce is required.

= Where do I set the Pro sales URL? =

Use:

`add_filter( 'pwoa_upgrade_url', function () { return 'https://your-store.example/pro/'; } );`

Until then, the plugin uses a default URL on the author’s domain.

= Where are campaigns stored? =

In custom database tables created when the plugin is activated.

== External services ==

This plugin loads **Tailwind CSS** from a public CDN (`cdn.tailwindcss.com`) to style the wizard and other Lite admin screens.

* **What:** Tailwind CSS (browser build).
* **When:** When you load plugin admin pages that enqueue that script.
* **Data sent:** No shop or customer data is sent to Tailwind; the visitor’s browser requests the script.
* **Terms / privacy:** See https://tailwindcss.com/ and Tailwind Labs’ policy pages.

== Changelog ==

= 2.0.18 =
* Readme and branding aligned with PW - Ofertas Avanzadas; Spanish (es_ES) translation; filterable Pro upgrade URL.

== Upgrade Notice ==

= 2.0.18 =
Recommended update for documentation and translations.
