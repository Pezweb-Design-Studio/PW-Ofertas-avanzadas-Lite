=== PW - Ofertas Avanzadas Lite ===
Contributors: pezweb
Donate link: https://pezweb.com/
Tags: woocommerce, descuentos, marketing, campañas, ofertas, lite
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 2.0.18
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Edición Lite gratuita: campañas de descuento en WooCommerce (límite de campañas y de estrategias). La versión Pro se vende por separado.

== Description ==

**PW - Ofertas Avanzadas Lite** te permite crear y gestionar campañas de descuento en WooCommerce con un asistente guiado, varias estrategias orientadas al marketing y badges opcionales en el producto.

**Límites Lite (resumen):** menos campañas simultáneas y solo estrategias incluidas en Lite. Los enlaces de actualización a Pro apuntan a la página de venta del autor; puedes definir la URL definitiva con el filtro indicado abajo cuando la tengas.

**Idiomas:** Las cadenas pasadas por gettext usan inglés como `msgid` en el código; el paquete incluye traducción al español (`languages/pw-ofertas-avanzadas-es_ES.mo`). Con el sitio en español, WordPress cargará esas traducciones para menús, mensajes AJAX y textos localizados al JS.

**Versión Pro:** No se distribuye en este listado de WordPress.org; se comercializa aparte. Usa el filtro `pwoa_upgrade_url` (o la URL por defecto hasta que la cambies) en los botones de mejora.

== Installation ==

1. Sube la carpeta del plugin a `/wp-content/plugins/` o instálalo desde **Plugins → Añadir nuevo**.
2. Activa el plugin.
3. Mantén **WooCommerce** instalado y activo.
4. Abre el menú **Ofertas** (el texto puede mostrarse traducido) para crear campañas.

== Frequently Asked Questions ==

= ¿Funciona sin WooCommerce? =

No. WooCommerce es obligatorio.

= ¿Dónde configuro la URL de venta de Pro? =

Con:

`add_filter( 'pwoa_upgrade_url', function () { return 'https://tu-tienda.com/pro/'; } );`

Hasta entonces el plugin usa una URL por defecto del dominio del autor.

= ¿Dónde se guardan las campañas? =

En tablas personalizadas que se crean al activar el plugin.

== External services ==

El plugin carga **Tailwind CSS** desde una CDN pública (`cdn.tailwindcss.com`) para estilizar el asistente y otras pantallas de administración en Lite.

* **Qué:** Tailwind CSS (compilado para navegador).
* **Cuándo:** Al cargar páginas de administración del plugin que encolan ese script.
* **Datos enviados:** No se envían datos de la tienda ni de clientes a Tailwind; el navegador del visitante solicita el script.
* **Términos / privacidad:** Consulta https://tailwindcss.com/ y las políticas de Tailwind Labs.

== Changelog ==

= 2.0.18 =
* Readme y marca alineados con PW - Ofertas Avanzadas; traducción es_ES; URL Pro filtrable.

== Upgrade Notice ==

= 2.0.18 =
Actualización recomendada por documentación y traducciones.
