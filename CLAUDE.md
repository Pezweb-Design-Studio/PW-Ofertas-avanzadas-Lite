# PW - Ofertas Avanzadas

Plugin WooCommerce de descuentos/ofertas con estrategias de marketing. Dos ediciones: **Pro** y **Lite**.

## Stack
- PHP 7.4+, WordPress + WooCommerce
- Namespace: `PW\OfertasAvanzadas\`
- Autoload: Composer (`vendor/autoload.php`)
- Build: `./build-deploy.sh [pro|lite]` o `npm run build:pro`

## Archivos de entrada
- `pw-ofertas-avanzadas-pro.php` — edición Pro (PWOA_EDITION=pro)
- `pw-ofertas-avanzadas.lite.php` — edición Lite (PWOA_EDITION=lite)

## Estructura `src/`
```
src/
  Core/         # Plugin, Activator, Deactivator
  Strategies/
    Pro/        # Estrategias exclusivas Pro
    Lite/       # Estrategias edición Lite
  Admin/
    Views/      # Vistas del panel admin
  Repositories/ # Acceso a datos
  Shortcodes/   # Shortcodes registrados
  Handlers/     # Manejadores de eventos/hooks
```

## Constantes clave
- `PWOA_VERSION`, `PWOA_EDITION` (`pro`|`lite`), `PWOA_PATH`, `PWOA_URL`

## Convenciones
- Singleton: `Plugin::getInstance()->init()`
- Edicion-aware: usar `PWOA_EDITION` para bifurcar lógica Pro/Lite
- Tipos de ofertas documentados en `TIPOS-DE-OFERTAS-PRO.md` / `TIPOS-DE-OFERTAS-LITE.md`
- Releases en `releases/pro/` y `releases/lite/`

## Preferencias de trabajo
- Cambios minimalistas. Resolver desde la raíz, no con parches.
- Sin estilos inline (`style="..."`). Usar componentes del design system o clases CSS estructuradas.
- **No editar la copia del plugin bajo `wp-content/plugins/...`** salvo pruebas locales desechables. El flujo correcto es cambiar este repo, ejecutar `./build-deploy.sh [lite|pro]` y desplegar el artefacto en `releases/…` (o el proceso que uses a partir del build). Así el paquete Lite incluye siempre `Schema.php`, `I18n.php`, idiomas y assets compartidos.

## Lecciones técnicas aprendidas

### WordPress screen IDs (BackendUI `screens`)
WordPress construye el `screen_id` de las subpáginas usando el **título sanitizado del menú padre**, no el slug.

Ejemplo: si `add_menu_page` tiene título `"Ofertas"` y slug `"pwoa-dashboard"`:
- ✅ Screen ID real: `ofertas_page_pwoa-new-campaign`
- ❌ Incorrecto: `pwoa-dashboard_page_pwoa-new-campaign`

Ante cualquier duda, diagnosticar con:
```php
add_action('admin_enqueue_scripts', function ($hook) {
    $screen = get_current_screen();
    error_log('hook=' . $hook . ' screen_id=' . ($screen ? $screen->id : 'null'));
});
```

### PHP closures anidadas en vistas (BackendUI)
Cuando un callback de `render_page` o `card` tiene closures internas, las variables del scope externo no se heredan automáticamente. Siempre pasarlas explícitamente por `use`:

```php
// ✅ Correcto
'content' => function ($bui) use ($data): void {
    $ui = $bui->ui();
    $ui->card([
        'content' => function () use ($data, $ui): void {  // $ui explícito
            $ui->badge([...]);
        },
    ]);
},
```

### Base de datos: tabla `pwoa_stats`
- **Pro** y **Lite** deben poder asumir que existe `{$wpdb->prefix}pwoa_stats` (dashboard, `CampaignRepository::hasStats`, etc.).
- `src/Core/Schema.php`: `ensureStatsTable()` (comprueba con `SHOW TABLES` + `dbDelta`) se llama al inicio de `Plugin::init()` / `Plugin.lite.php`; los activadores usan el mismo SQL vía `Schema::statsTableSql()`.
- `CampaignRepository::hasStats()` debe tolerar ausencia de tabla (por si acaso) sin disparar errores en log.

### WooCommerce HPOS y avisos de compatibilidad
- Declarar compatibilidad en el archivo principal del plugin, en `before_woocommerce_init`, con `Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PWOA_PLUGIN_FILE, true )`.
- No contar pedidos con SQL directo a `wp_posts` (`post_type = shop_order`); usar `wc_get_orders()` (API del almacén de pedidos) para métricas compatibles con HPOS.

### Internacionalización (WordPress 6.5+ y regiones `es_*`)
- En runtime WordPress solo carga **`.mo`**, no `.po`. Versionar en git **`languages/pw-ofertas-avanzadas-es_ES.mo`** (regenerar con `msgfmt` tras cambios en el `.po`).
- Tras añadir cadenas en código: actualizar el POT (`wp i18n make-pot` o flujo equivalente), **`msgmerge`** al `es_ES.po`, completar traducciones y **`msgfmt -o … .mo`**.
- **`WP_Translation_Controller`** (WP 6.5+) indexa traducciones por **locale de la petición** (`determine_locale()`, p. ej. `es_CL`). Al cargar el único paquete `pw-ofertas-avanzadas-es_ES.mo`, hay que llamar `load_textdomain( $domain, $ruta_al_mo, $locale_peticion )` con ese locale, aunque el archivo en disco sea `…-es_ES.mo`. Resolver la ruta del archivo con fallback (p. ej. probar `es_CL` y luego `es_ES`). Normalizar `es-CL` → `es_CL` si hiciera falta.
- Cargar el dominio del plugin en **`init`** (p. ej. `I18n::register()` solo engancha `loadTextdomain` ahí), alineado con las recomendaciones post-6.7.

### Build (`build-deploy.sh`)
- Bloque compartido post Lite/Pro: copia **`src/Core/Schema.php`** junto a `I18n.php` y `UpgradeUrl.php`.
- Si existe `msgfmt`, recompila los `.po` del directorio `languages/` en el artefacto para que el ZIP siempre lleve `.mo` actualizados.

## Lo que NO tocar
- `vendor/` — generado por Composer
- `node_modules/` — generado por npm
- `releases/` — builds de distribución
