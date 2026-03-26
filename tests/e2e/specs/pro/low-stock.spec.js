/**
 * LowStockStrategy — Descuento automatico a productos con stock <= umbral.
 * productA tiene stock=5 (bajo el umbral default de 10).
 * productC tiene stock=15 (sobre el umbral).
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount, expectDiscount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

test.describe('Pro: LowStockStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('aplica 15% a productA (stock=5, bajo umbral=10)', async ({ page }) => {
    const { productA } = products(); // $100, stock=5

    campaignId = await createCampaign(page, {
      name:      'TEST low stock 15%',
      objective: 'liquidation',
      strategy:  'low_stock',
      config: {
        stock_threshold: '10',
        discount_type:   'percentage',
        discount_value:  '15',
      },
    });

    await addToCart(page, productA.id, 1);
    const discount = await getDiscountAmount(page);

    // $100 x 15% = $15
    expectDiscount(discount, 15);
    expect(discount).toBeGreaterThan(0);
  });

  test('NO aplica a productC (stock=15, sobre umbral=10)', async ({ page }) => {
    const { productC } = products(); // $200, stock=15

    campaignId = await createCampaign(page, {
      name:      'TEST low stock no aplica',
      objective: 'liquidation',
      strategy:  'low_stock',
      config: {
        stock_threshold: '10',
        discount_type:   'percentage',
        discount_value:  '15',
      },
    });

    await addToCart(page, productC.id, 1);
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });

  test('umbral personalizado: productC (stock=15) bajo umbral=20', async ({ page }) => {
    const { productC } = products(); // $200, stock=15

    campaignId = await createCampaign(page, {
      name:      'TEST low stock umbral 20',
      objective: 'liquidation',
      strategy:  'low_stock',
      config: {
        stock_threshold: '20',  // stock=15 esta bajo 20
        discount_type:   'percentage',
        discount_value:  '10',
      },
    });

    await addToCart(page, productC.id, 1);
    const discount = await getDiscountAmount(page);

    // $200 x 10% = $20
    expectDiscount(discount, 20);
  });
});
