/**
 * BuyXPayYStrategy — Llevas X unidades, pagas Y (X > Y).
 * Ej: llevas 3, pagas 2 → 1 gratis.
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount, expectDiscount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

test.describe('Lite: BuyXPayYStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('lleva 3 paga 2 con productB ($50): descuenta 1 unidad = $50', async ({ page }) => {
    const { productB } = products(); // $50

    campaignId = await createCampaign(page, {
      name:      'TEST 3x2 productB',
      objective: 'aov',
      strategy:  'buy_x_pay_y',
      config:    { buy_quantity: '3', pay_quantity: '2' },
      conditions: { product_ids: [productB.id] },
    });

    await addToCart(page, productB.id, 3);
    const discount = await getDiscountAmount(page);

    // 3 items $50 = $150, paga 2 = $100, descuento = $50
    expectDiscount(discount, 50);
  });

  test('lleva 4 paga 3 con productA ($100): descuenta 1 unidad = $100', async ({ page }) => {
    const { productA } = products(); // $100

    campaignId = await createCampaign(page, {
      name:      'TEST 4x3 productA',
      objective: 'aov',
      strategy:  'buy_x_pay_y',
      config:    { buy_quantity: '4', pay_quantity: '3' },
      conditions: { product_ids: [productA.id] },
    });

    await addToCart(page, productA.id, 4);
    const discount = await getDiscountAmount(page);

    // 4 x $100 = $400, paga 3 = $300, descuento = $100
    expectDiscount(discount, 100);
  });

  test('no aplica si la cantidad es menor a buy_quantity', async ({ page }) => {
    const { productB } = products();

    campaignId = await createCampaign(page, {
      name:      'TEST 3x2 sin minimo',
      objective: 'aov',
      strategy:  'buy_x_pay_y',
      config:    { buy_quantity: '3', pay_quantity: '2' },
      conditions: { product_ids: [productB.id] },
    });

    // Solo 2 unidades, no alcanza el minimo de 3
    await addToCart(page, productB.id, 2);
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });
});
