/**
 * TieredDiscountStrategy — Descuento escalonado segun cantidad total de items.
 * Tier mayor que se cumpla es el que se aplica.
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount, expectDiscount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

const TIERS = [
  { quantity: '3', discount: '10' },
  { quantity: '5', discount: '20' },
  { quantity: '10', discount: '30' },
];

test.describe('Pro: TieredDiscountStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('3 items → aplica tier 10%', async ({ page }) => {
    const { productB } = products(); // $50

    campaignId = await createCampaign(page, {
      name:      'TEST tiered porcentaje',
      objective: 'aov',
      strategy:  'tiered_discount',
      config:    { discount_type: 'percentage', tiers: TIERS },
    });

    await addToCart(page, productB.id, 3);
    const discount = await getDiscountAmount(page);

    // $50 x 3 = $150, 10% = $15
    expectDiscount(discount, 15);
  });

  test('5 items → aplica tier 20% (no el de 10%)', async ({ page }) => {
    const { productB } = products(); // $50

    campaignId = await createCampaign(page, {
      name:      'TEST tiered 5 items',
      objective: 'aov',
      strategy:  'tiered_discount',
      config:    { discount_type: 'percentage', tiers: TIERS },
    });

    await addToCart(page, productB.id, 5);
    const discount = await getDiscountAmount(page);

    // $50 x 5 = $250, 20% = $50
    expectDiscount(discount, 50);
  });

  test('2 items → no alcanza ningun tier, sin descuento', async ({ page }) => {
    const { productB } = products();

    campaignId = await createCampaign(page, {
      name:      'TEST tiered sin tier',
      objective: 'aov',
      strategy:  'tiered_discount',
      config:    { discount_type: 'percentage', tiers: TIERS },
    });

    await addToCart(page, productB.id, 2);
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });

  test('descuento fijo: 5 items → $20 fijo', async ({ page }) => {
    const { productB } = products();
    const fixedTiers = [
      { quantity: '3', discount: '10' },
      { quantity: '5', discount: '20' },
    ];

    campaignId = await createCampaign(page, {
      name:      'TEST tiered fijo',
      objective: 'aov',
      strategy:  'tiered_discount',
      config:    { discount_type: 'fixed', tiers: fixedTiers },
    });

    await addToCart(page, productB.id, 5);
    const discount = await getDiscountAmount(page);

    expectDiscount(discount, 20);
  });
});
