/**
 * FreeShippingStrategy — Envio gratis si el carrito supera un monto minimo.
 * El descuento equivale al costo del envio configurado en WooCommerce.
 *
 * PREREQUISITO: debe haber al menos un metodo de envio con costo configurado en WC.
 * Si el envio es gratis por defecto, este test no aplica.
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

const BASE_URL = process.env.BASE_URL || 'http://localhost/pw-ofertas';

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

test.describe('Pro: FreeShippingStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('aplica envio gratis cuando el carrito supera el minimo', async ({ page }) => {
    const { productC } = products(); // $200

    campaignId = await createCampaign(page, {
      name:      'TEST envio gratis $100',
      objective: 'aov',
      strategy:  'free_shipping',
      config:    { min_amount: '100' },
    });

    await addToCart(page, productC.id, 1); // $200 > $100 minimo, ya navega al CART_URL

    // Verificar costo de envio en WC Blocks
    const shippingValue = page.locator('.wc-block-components-totals-shipping .wc-block-components-totals-item__value');
    if (await shippingValue.count() === 0) {
      test.skip(true, 'Sin seccion de envio en el bloque de carrito');
      return;
    }

    const shippingText = await shippingValue.textContent();
    const shippingAmount = parseFloat(shippingText.replace(/[^\d.,]/g, '').replace(',', '.'));
    if (!shippingAmount || shippingAmount === 0) {
      test.skip(true, 'Sin costo de envio configurado en WooCommerce');
      return;
    }

    // Hay costo de envio — PWOA debe haberlo eliminado via fee negativo
    const discount = await getDiscountAmount(page);
    expect(discount).toBeGreaterThan(0);
  });

  test('NO aplica envio gratis cuando el carrito NO supera el minimo', async ({ page }) => {
    const { productB } = products(); // $50

    campaignId = await createCampaign(page, {
      name:      'TEST envio gratis no aplica',
      objective: 'aov',
      strategy:  'free_shipping',
      config:    { min_amount: '100' },
    });

    await addToCart(page, productB.id, 1); // $50 < $100 minimo
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });
});
