/**
 * FlashSaleStrategy — Descuento valido solo en una ventana de tiempo.
 * La campana se configura con start_time en el pasado y end_time en el futuro.
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount, expectDiscount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

function toMySQLDatetime(date) {
  return date.toISOString().replace('T', ' ').substring(0, 19);
}

test.describe('Pro: FlashSaleStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('aplica 25% dentro del periodo de flash sale', async ({ page }) => {
    const { productA } = products(); // $100
    const now          = new Date();
    const start        = new Date(now.getTime() - 2 * 60 * 60 * 1000); // hace 2 horas
    const end          = new Date(now.getTime() + 2 * 60 * 60 * 1000); // en 2 horas

    campaignId = await createCampaign(page, {
      name:      'TEST flash sale activa',
      objective: 'urgency',
      strategy:  'flash_sale',
      config: {
        start_time:     toMySQLDatetime(start),
        end_time:       toMySQLDatetime(end),
        discount_type:  'percentage',
        discount_value: '25',
      },
    });

    await addToCart(page, productA.id, 1);
    const discount = await getDiscountAmount(page);

    // $100 x 25% = $25
    expectDiscount(discount, 25);
    expect(discount).toBeGreaterThan(0);
  });

  test('NO aplica si la flash sale ya termino', async ({ page }) => {
    const { productA } = products();
    const pastStart    = new Date(Date.now() - 4 * 60 * 60 * 1000); // hace 4h
    const pastEnd      = new Date(Date.now() - 2 * 60 * 60 * 1000); // hace 2h (ya termino)

    campaignId = await createCampaign(page, {
      name:      'TEST flash sale expirada',
      objective: 'urgency',
      strategy:  'flash_sale',
      config: {
        start_time:     toMySQLDatetime(pastStart),
        end_time:       toMySQLDatetime(pastEnd),
        discount_type:  'percentage',
        discount_value: '25',
      },
    });

    await addToCart(page, productA.id, 1);
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });

  test('NO aplica si la flash sale aun no empieza', async ({ page }) => {
    const { productA } = products();
    const futureStart  = new Date(Date.now() + 2 * 60 * 60 * 1000); // en 2h
    const futureEnd    = new Date(Date.now() + 4 * 60 * 60 * 1000); // en 4h

    campaignId = await createCampaign(page, {
      name:      'TEST flash sale futura',
      objective: 'urgency',
      strategy:  'flash_sale',
      config: {
        start_time:     toMySQLDatetime(futureStart),
        end_time:       toMySQLDatetime(futureEnd),
        discount_type:  'percentage',
        discount_value: '25',
      },
    });

    await addToCart(page, productA.id, 1);
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });
});
