/**
 * ExpiryBasedStrategy — Descuento segun dias para vencimiento del producto.
 * Requiere que productD tenga _expiry_date con vencimiento en ~5 dias (configurado en global-setup).
 * Tier: hasta 7 dias → 20% descuento.
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount, expectDiscount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

test.describe('Lite: ExpiryBasedStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('aplica 20% a producto que vence en 5 dias (tier <=7 dias)', async ({ page }) => {
    const { productD } = products(); // $75, vence en 5 dias

    campaignId = await createCampaign(page, {
      name:      'TEST expiry tiers',
      objective: 'liquidation',
      strategy:  'expiry_based',
      config: {
        discount_type: 'percentage',
        tiers: [
          { days: '7',  discount: '20' },
          { days: '30', discount: '10' },
        ],
      },
    });

    await addToCart(page, productD.id, 1);
    const discount = await getDiscountAmount(page);

    // $75 x 20% = $15
    // NOTA: este test puede fallar si _expiry_date no se pudo asignar via REST en global-setup.
    // En ese caso, setear _expiry_date manualmente en el producto PWOA Test D.
    expectDiscount(discount, 15);
    expect(discount).toBeGreaterThan(0);
  });

  test('no aplica a producto sin _expiry_date configurado', async ({ page }) => {
    const { productA } = products(); // $100, sin _expiry_date

    campaignId = await createCampaign(page, {
      name:      'TEST expiry sin fecha',
      objective: 'liquidation',
      strategy:  'expiry_based',
      config: {
        discount_type: 'percentage',
        tiers: [{ days: '30', discount: '15' }],
      },
    });

    await addToCart(page, productA.id, 1);
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });
});
