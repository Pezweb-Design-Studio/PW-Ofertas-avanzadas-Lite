/**
 * BasicDiscountStrategy — Descuento simple % o fijo a productos seleccionados.
 * Escenarios: porcentaje, monto fijo, sin productos en carrito (no aplica).
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount, expectDiscount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

test.describe('Lite: BasicDiscountStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('aplica 20% de descuento sobre producto $100', async ({ page }) => {
    const { productA } = products(); // $100

    campaignId = await createCampaign(page, {
      name:      'TEST basic 20%',
      objective: 'basic',
      strategy:  'basic_discount',
      config:    { discount_type: 'percentage', discount_value: '20' },
      conditions: { product_ids: [productA.id] },
    });

    await addToCart(page, productA.id, 1);
    const discount = await getDiscountAmount(page);

    // $100 x 20% = $20
    expectDiscount(discount, 20);
    expect(discount).toBeGreaterThan(0);
  });

  test('aplica descuento fijo $15 sobre producto $50', async ({ page }) => {
    const { productB } = products(); // $50

    campaignId = await createCampaign(page, {
      name:      'TEST basic fijo $15',
      objective: 'basic',
      strategy:  'basic_discount',
      config:    { discount_type: 'fixed', discount_value: '15' },
      conditions: { product_ids: [productB.id] },
    });

    await addToCart(page, productB.id, 1);
    const discount = await getDiscountAmount(page);

    expectDiscount(discount, 15);
  });

  test('no aplica descuento a producto no incluido en condiciones', async ({ page }) => {
    const { productA, productC } = products();

    campaignId = await createCampaign(page, {
      name:      'TEST basic solo productA',
      objective: 'basic',
      strategy:  'basic_discount',
      config:    { discount_type: 'percentage', discount_value: '20' },
      conditions: { product_ids: [productA.id] },
    });

    // Agregar productC (no incluido en condiciones)
    await addToCart(page, productC.id, 1);
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });
});
