/**
 * RecurringPurchaseStrategy — Descuento a clientes que ya compraron N veces el producto.
 * En global-setup se crearon 3 ordenes completadas para el admin (productA).
 * La estrategia con required_purchases=3 debe aplicar al usuario logueado.
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount, expectDiscount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

test.describe('Pro: RecurringPurchaseStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('aplica 20% a usuario con 3 compras previas de productA', async ({ page }) => {
    const { productA } = products(); // $100, con 3 ordenes completadas del admin

    campaignId = await createCampaign(page, {
      name:      'TEST recurring 3 compras previas',
      objective: 'loyalty',
      strategy:  'recurring_purchase',
      config: {
        required_purchases: '3',
        discount_type:      'percentage',
        discount_value:     '20',
      },
      conditions: { product_ids: [productA.id] },
    });

    await addToCart(page, productA.id, 1);
    const discount = await getDiscountAmount(page);

    // $100 x 20% = $20
    expectDiscount(discount, 20);
    expect(discount).toBeGreaterThan(0);
  });

  test('NO aplica si el usuario no tiene suficientes compras previas', async ({ page }) => {
    const { productC } = products(); // $200, sin historial de compras

    campaignId = await createCampaign(page, {
      name:      'TEST recurring sin historial',
      objective: 'loyalty',
      strategy:  'recurring_purchase',
      config: {
        required_purchases: '3',
        discount_type:      'percentage',
        discount_value:     '20',
      },
      conditions: { product_ids: [productC.id] },
    });

    // productC no tiene ordenes completadas previas
    await addToCart(page, productC.id, 1);
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });
});
