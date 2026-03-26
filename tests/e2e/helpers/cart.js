/**
 * Helpers para el carrito de WooCommerce BLOCKS.
 * El tema usa el bloque de carrito (no el shortcode clasico), por lo que
 * los selectores son wc-block-components-* en lugar de shop_table / cart_totals.
 *
 * El plugin aplica descuentos via WC()->cart->add_fee() con monto negativo.
 * En el carrito de bloques aparecen como:
 *   .wc-block-components-totals-item.wc-block-components-totals-fees
 */
require('dotenv').config({ path: require('path').join(__dirname, '../.env') });

const { execSync } = require('child_process');
const path = require('path');

const BASE_URL       = process.env.BASE_URL  || 'http://localhost/pw-ofertas';
const CART_PATH      = process.env.WC_CART_PATH || '/cart/';
const CART_URL       = `${BASE_URL}${CART_PATH}`;
const WP_ROOT        = process.env.WP_ROOT   || '/Applications/XAMPP/xamppfiles/htdocs/pw-ofertas';
const XAMPP_PHP      = process.env.XAMPP_PHP  || '/Applications/XAMPP/xamppfiles/bin/php';
const CLEAR_CART_PHP = path.join(__dirname, 'clear-cart.php');

/**
 * Limpia el carrito, agrega un producto y navega al carrito.
 * Siempre parte de un carrito limpio para garantizar estado predecible.
 */
async function addToCart(page, productId, quantity = 1) {
  await clearCart(page);
  // Reintentar hasta 3 veces: a veces WC no guarda la sesion antes de navegar al carrito
  for (let attempt = 0; attempt < 3; attempt++) {
    await page.goto(`${BASE_URL}/?add-to-cart=${productId}&quantity=${quantity}`);
    await page.waitForLoadState('networkidle');
    await page.goto(CART_URL);
    try {
      await waitForCartReady(page);
      return;
    } catch (e) {
      if (attempt === 2) throw e;
      await page.waitForTimeout(600);
    }
  }
}

/**
 * Vacia el carrito via PHP CLI (fiable) y navega al carrito para refrescar el estado.
 * Borra tanto el user meta _woocommerce_persistent_cart_1 como la sesion WC en DB.
 */
async function clearCart(page) {
  try {
    execSync(`${XAMPP_PHP} ${CLEAR_CART_PHP} ${WP_ROOT} 1`, { timeout: 8000 });
  } catch (e) {
    // fallback silencioso — el carrito puede estar ya vacio
  }
  await page.goto(CART_URL);
  await page.waitForLoadState('networkidle');
}

/**
 * Espera a que el carrito de bloques cargue completamente.
 * El indicador es la fila de total final (.wc-block-components-totals-footer-item).
 */
async function waitForCartReady(page) {
  // Esperar a que aparezca el total (siempre presente cuando el bloque carga)
  await page.waitForSelector('.wc-block-components-totals-footer-item', { timeout: 15000 });
  // Pequena espera extra para que React termine de recalcular fees
  await page.waitForTimeout(800);
}

/**
 * Navega al carrito y devuelve la suma de todos los descuentos (fees negativos).
 * El plugin PWOA agrega exactamente 1 fee. Esta funcion suma todos los que haya.
 *
 * @returns {Promise<number>} monto total de descuento (positivo)
 */
async function getDiscountAmount(page) {
  await page.goto(CART_URL);
  await waitForCartReady(page);

  const feeValues = await page
    .locator('.wc-block-components-totals-fees .wc-block-components-totals-item__value')
    .allTextContents();

  let total = 0;
  for (const text of feeValues) {
    // Texto puede ser "-$20,00" o "-$20.00" dependiendo del locale
    const normalized = text.replace(/[^\d.,]/g, '').replace(',', '.');
    const value = parseFloat(normalized);
    if (!isNaN(value)) total += value;
  }
  return total;
}

/**
 * Devuelve el total estimado del carrito como numero.
 */
async function getCartTotal(page) {
  await waitForCartReady(page);
  const text = await page
    .locator('.wc-block-components-totals-footer-item .wc-block-components-totals-item__value')
    .last()
    .textContent();
  return parseFloat(text.replace(/[^\d.,]/g, '').replace(',', '.'));
}

/**
 * Verifica que el descuento sea aproximadamente el esperado (tolerancia 2%).
 * Lanza un error descriptivo si no coincide.
 */
function expectDiscount(actual, expected, tolerancePct = 0.02) {
  const tolerance = Math.max(expected * tolerancePct, 0.5); // minimo 0.50 de tolerancia
  if (Math.abs(actual - expected) > tolerance) {
    throw new Error(
      `Descuento esperado: ${expected.toFixed(2)}, obtenido: ${actual.toFixed(2)} (tolerancia: ±${tolerance.toFixed(2)})`
    );
  }
}

module.exports = { addToCart, clearCart, getDiscountAmount, getCartTotal, expectDiscount, waitForCartReady, CART_URL };
