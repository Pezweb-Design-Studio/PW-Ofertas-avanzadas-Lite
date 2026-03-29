=== PW - Ofertas Avanzadas Lite ===
Contributors: pezweb
Donate link: https://pezweb.com/
Tags: woocommerce, discounts, marketing, campaigns, lite
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 2.1.2
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

This plugin does **not** call remote APIs or send store data to third-party servers in the background. Wizard and dashboard admin layouts use utility CSS **bundled** in the plugin (`assets/css/pwoa-tailwind-admin.css`). The bundled design library (`pw/backend-ui`) loads its own JavaScript and CSS from the plugin package only; it does not load the Tailwind Play CDN.

Optional **upgrade to Pro** links open in the visitor’s browser when clicked (same as any normal hyperlink). The default destination is https://pezweb.com/ until you change it with the `pwoa_upgrade_url` filter. No store or customer data is sent to that URL by the plugin; only standard web requests your browser makes when you open the page. For that site’s terms and privacy practices, see the policies linked from https://pezweb.com/ (typically the site footer).

== Changelog ==

= 2.1.2 =
* WordPress.org: uninstall handler, ZIP verification in build, readme external services; deploy option to keep current version; admin assets and shortcode hygiene.

= 2.1.1 =
* WordPress.org readiness: readme stable tag, external services disclosure, English plugin header description.
* Pro/Lite conflict: Pro correctly deactivates the Lite package when both are installed.
* i18n: Pro strategy labels use literal translatable strings; product expiry field and storefront notice are translatable.
* Styles: product expiry admin/frontend use enqueued CSS (no inline styles); admin Tailwind utilities built locally (no `cdn.tailwindcss.com`).

= 2.0.18 =
* Readme and branding aligned with PW - Ofertas Avanzadas; Spanish (es_ES) translation; filterable Pro upgrade URL.

== Upgrade Notice ==

= 2.1.2 =
Maintenance and WordPress.org packaging improvements.

= 2.1.1 =
Recommended update for WordPress.org metadata, translations, and admin/frontend styling.
