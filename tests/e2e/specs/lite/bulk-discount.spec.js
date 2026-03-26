/**
 * BulkDiscountStrategy — Descuento a las primeras N unidades de productos especificos.
 * No usa condiciones externas: los productos se definen en bulk_items.
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount, expectDiscount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

test.describe('Lite: BulkDiscountStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('aplica 15% a las primeras 3 unidades de productA ($100)', async ({ page }) => {
    const { productA } = products();

    campaignId = await createCampaign(page, {
      name:      'TEST bulk 15% x3 unidades',
      objective: 'aov',
      strategy:  'bulk_discount',
      config: {
        bulk_items: [{
          product_id:     String(productA.id),
          discount_type:  'percentage',
          discount_value: '15',
          max_quantity:   '5',
        }],
      },
    });

    await addToCart(page, productA.id, 3);
    const discount = await getDiscountAmount(page);

    // $100 x 3 = $300 total, 15% = $45
    expectDiscount(discount, 45);
  });

  test('limita descuento al max_quantity configurado', async ({ page }) => {
    const { productB } = products(); // $50

    campaignId = await createCampaign(page, {
      name:      'TEST bulk max_quantity=2',
      objective: 'aov',
      strategy:  'bulk_discount',
      config: {
        bulk_items: [{
          product_id:     String(productB.id),
          discount_type:  'percentage',
          discount_value: '20',
          max_quantity:   '2',  // solo descuenta las primeras 2 unidades
        }],
      },
    });

    // Agregar 4 unidades pero solo 2 deben recibir descuento
    await addToCart(page, productB.id, 4);
    const discount = await getDiscountAmount(page);

    // $50 x 2 = $100 con descuento, 20% = $20
    expectDiscount(discount, 20);
  });

  test('aplica descuento fijo por unidad', async ({ page }) => {
    const { productA } = products(); // $100

    campaignId = await createCampaign(page, {
      name:      'TEST bulk fijo $10/unidad',
      objective: 'aov',
      strategy:  'bulk_discount',
      config: {
        bulk_items: [{
          product_id:     String(productA.id),
          discount_type:  'fixed',
          discount_value: '10',
          max_quantity:   '10',
        }],
      },
    });

    await addToCart(page, productA.id, 2);
    const discount = await getDiscountAmount(page);

    // $10 fijo x 2 unidades = $20
    expectDiscount(discount, 20);
  });
});
