# PW - Ofertas Avanzadas Lite

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

Free **Lite** uses the same feature set with lower campaign/strategy caps; **Pro** is sold separately.

| | |
| --- | --- |
| **Contributors** | [pezweb](https://pezweb.com/) |
| **WordPress** | Requires **6.0+** · Tested up to **6.9** |
| **PHP** | **8.0+** |
| **License** | [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html) |
| **Tags** | WooCommerce, discounts, marketing, campaigns, offers |

> WordPress.org uses `readme.txt` in this repo with the same information in [plugin directory format](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/).

---

**PW - Ofertas Avanzadas Lite** helps you create and manage discount campaigns in WooCommerce using a guided wizard, several marketing-oriented strategies, and optional on-product badges.

**Lite limits (summary):** fewer simultaneous campaigns and only strategies included in Lite. Upgrade links point to the author’s sales page; you can set the final URL with the filter below when it is ready.

**Languages:** Strings passed through gettext use English as the `msgid` in code; the package includes a Spanish translation (`languages/pw-ofertas-avanzadas-es_ES.mo`). With the site language set to Spanish, WordPress will load those translations for menus, AJAX messages, and JS-localized strings.

**Pro version:** It is not distributed through the WordPress.org listing; it is sold separately. Upgrade buttons use the `pwoa_upgrade_url` filter (or the default URL until you change it).

---

## Installation

1. Upload the plugin folder to `wp-content/plugins/` or install it from **Plugins → Add New**.
2. Activate the plugin.
3. Keep **WooCommerce** installed and active.
4. Open the **Offers** menu (the label may appear translated) to create campaigns.

---

## FAQ

### Does it work without WooCommerce?

No. WooCommerce is required.

### Where do I set the Pro sales URL?

Add this to your theme’s `functions.php` or a small custom plugin:

```php
add_filter( 'pwoa_upgrade_url', function () {
    return 'https://your-store.example/pro/';
} );
```

Until you do, the plugin uses a default URL on the author’s domain.

### Where are campaigns stored?

In custom database tables created when the plugin is activated.

---

## External services

No background calls to third-party APIs. Admin wizard/dashboard utility styles are **bundled** as `assets/css/pwoa-tailwind-admin.css` (built with Tailwind CLI from `assets/tailwind/`). Pro upgrade links are normal browser navigation; override with `pwoa_upgrade_url`.

---

## Changelog

### 2.1.2

- WordPress.org: uninstall handler, ZIP verification in build, readme external services; deploy option to keep current version; admin assets and shortcode hygiene.

### 2.1.1

- Bundled admin Tailwind CSS (no CDN); WordPress.org metadata and i18n fixes; Pro/Lite deactivation behavior; product expiry styling.

### 2.0.18

- Readme and branding aligned with PW - Ofertas Avanzadas; Spanish (es_ES) translation; filterable Pro upgrade URL.

---

## Upgrade notice

**2.1.2** — Maintenance and WordPress.org packaging improvements.

**2.1.1** — Recommended for bundled CSS and directory metadata.

---

## Pro edition changelog

### 2.0.7

- Same shared admin assets as repo; use `npm run build:tailwind` before build/deploy.
