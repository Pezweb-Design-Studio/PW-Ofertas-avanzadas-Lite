# WordPress.org — Auditoría pre-envío (histórico + seguimiento)

**Fecha del informe original:** 2026-03-28  
**Plugin:** PW - Ofertas Avanzadas (Pro / Lite)

Este archivo conserva el informe de revisión tipo WordPress.org y sirve como checklist de lo corregido en el código.

---

## Veredicto original: BLOCKED

### Bloqueantes identificados (resumen)

1. Sin `readme.txt` estándar (formato WordPress.org).
2. Cabeceras incompletas (licencia, Requires at least WP, Tested up to, Author URI, etc.); Plugin URI devolvía 404.
3. JS/CSS incrustados con etiquetas `<script>` / `<style>` en PHP.
4. Servicio externo (Tailwind CDN) sin documentar en readme.
5. Sin `load_plugin_textdomain`; casi sin funciones de traducción.
6. `error_log` / `print_r` en `CampaignRepository`.
7. Incoherencia PHP 7.4 en cabecera vs Composer/backend-ui (PHP ≥ 8.0).

### Advertencias (resumen)

- `console.log` en `wizard.lite-addon.js`.
- Placeholder `https://tu-sitio.com/pro` en vista Lite.
- Posible zip con archivos de desarrollo (tests, docs internas).

---

## Cambios aplicados en el repositorio (seguimiento)

- [x] `readme.txt` en inglés (orientado a **Lite** / WordPress.org) con **External services** (Tailwind CDN).
- [x] Nombre de producto **PW - Ofertas Avanzadas** en `__()` y cabeceras Pro/Lite; `languages/pw-ofertas-avanzadas-es_ES.{po,mo}`; URL Pro filtrable `pwoa_upgrade_url` (`UpgradeUrl::get()`).
- [x] Cabeceras Pro/Lite: licencia GPL, URIs, Requires at least, Tested up to, Requires PHP 8.0, Author URI, Domain Path.
- [x] `Plugin URI` → `https://pezweb.com/` (página operativa).
- [x] Scripts de admin extraídos a `assets/js/admin-*.js` + `AdminAssets.php`.
- [x] Estilos del wizard en `assets/css/admin-wizard.css` (+ extra Lite).
- [x] Badges en front: `assets/css/product-badges.css` + `assets/js/product-badges.js` + encolado.
- [x] `CampaignRepository`: sin logs de depuración en producción.
- [x] `composer.json`: licencia GPL-2.0-or-later; PHP alineado con dependencias.
- [x] `PWOA_PLUGIN_FILE` + `I18n::register()` + `load_plugin_textdomain`.
- [x] Cadenas traducibles (inglés fuente) en bootstraps, menús admin, respuestas AJAX principales y textos pasados a JS vía `wp_localize_script`.
- [x] Enlace Pro en wizard Lite → URL real de upgrade.
- [x] Eliminados `console.log` de `wizard.lite-addon.js`.
- [x] Lite: `esc_html` en errores DB alineado con Pro.
- [x] Modo edición wizard: comprobación con `absint( $_GET['edit'] )`.

### Pendiente / ampliación recomendada

- **Traducción completa de vistas** (`dashboard.php`, `wizard.php`, `settings.php`, `analytics.php`, textos en `wizard.lite-addon.js`, etc.): muchas cadenas siguen en español literal; conviene envolverlas en `__()` / `esc_html__()` con mensaje fuente en inglés y generar `.pot` en `languages/`.
- **Readme Lite**: si publicas solo Lite, duplica/adapta `readme.txt` con `Stable tag` = versión del bootstrap Lite (p. ej. 2.0.18) y título acorde.
- **Stable tag** en `readme.txt`: debe coincidir con el `Version:` del ZIP que subas (Pro vs Lite pueden llevar versiones distintas).
- **Excluir del ZIP** `.git`, `tests/`, `CLAUDE.md`, etc., según el script de release.

---

*Informe técnico detallado en inglés: ver conversación de auditoría / skill wp-plugin-review.*
