/**
 * MinAmountStrategy — Descuento cuando el total del carrito >= monto minimo.
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount, expectDiscount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

test.describe('Pro: MinAmountStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('aplica 10% cuando el carrito supera $150 (productC $200)', async ({ page }) => {
    const { productC } = products(); // $200

    campaignId = await createCampaign(page, {
      name:      'TEST min amount $150',
      objective: 'aov',
      strategy:  'min_amount',
      config: {
        min_amount:     '150',
        discount_type:  'percentage',
        discount_value: '10',
      },
    });

    await addToCart(page, productC.id, 1); // $200 >= $150
    const discount = await getDiscountAmount(page);

    // $200 x 10% = $20
    expectDiscount(discount, 20);
    expect(discount).toBeGreaterThan(0);
  });

  test('NO aplica si el carrito no supera el minimo', async ({ page }) => {
    const { productB } = products(); // $50

    campaignId = await createCampaign(page, {
      name:      'TEST min amount no cumple',
      objective: 'aov',
      strategy:  'min_amount',
      config: {
        min_amount:     '150',
        discount_type:  'percentage',
        discount_value: '10',
      },
    });

    await addToCart(page, productB.id, 1); // $50 < $150
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });

  test('descuento fijo $30 cuando supera $100', async ({ page }) => {
    const { productC } = products(); // $200

    campaignId = await createCampaign(page, {
      name:      'TEST min amount fijo $30',
      objective: 'aov',
      strategy:  'min_amount',
      config: {
        min_amount:     '100',
        discount_type:  'fixed',
        discount_value: '30',
      },
    });

    await addToCart(page, productC.id, 1);
    const discount = await getDiscountAmount(page);

    expectDiscount(discount, 30);
  });

  test('cumple exactamente el monto minimo', async ({ page }) => {
    const { productA } = products(); // $100

    campaignId = await createCampaign(page, {
      name:      'TEST min amount exacto',
      objective: 'aov',
      strategy:  'min_amount',
      config: {
        min_amount:     '100',
        discount_type:  'percentage',
        discount_value: '10',
      },
    });

    await addToCart(page, productA.id, 1); // exactamente $100
    const discount = await getDiscountAmount(page);

    // $100 >= $100 → aplica → $10
    expectDiscount(discount, 10);
  });
});
