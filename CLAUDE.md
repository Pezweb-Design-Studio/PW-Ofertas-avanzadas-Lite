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

## Lo que NO tocar
- `vendor/` — generado por Composer
- `node_modules/` — generado por npm
- `releases/` — builds de distribución
