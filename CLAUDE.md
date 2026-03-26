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

## Lo que NO tocar
- `vendor/` — generado por Composer
- `node_modules/` — generado por npm
- `releases/` — builds de distribución
