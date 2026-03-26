/**
 * AttributeQuantityDiscountStrategy — Descuento cuando el carrito tiene >=N unidades
 * de productos con un atributo especifico.
 * Usa productE que tiene atributo "Color: Rojo" (slug: color, termino: rojo).
 *
 * NOTA: los slugs de atributos WC son pa_<nombre> para atributos globales.
 * Si el atributo se creo como personalizado (no global), el slug es solo el nombre en minusculas.
 * Ajustar attribute_slug si es necesario segun como WC registro el atributo.
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });
const { test, expect }  = require('@playwright/test');
const { createCampaign, deleteCampaign } = require('../../helpers/campaigns');
const { addToCart, clearCart, getDiscountAmount, expectDiscount } = require('../../helpers/cart');
const fs = require('fs'), path = require('path');

function products() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '../../fixtures/test-data.json'))).products;
}

test.describe('Lite: AttributeQuantityDiscountStrategy', () => {
  let campaignId;

  test.afterEach(async ({ page }) => {
    await clearCart(page);
    if (campaignId) {
      await deleteCampaign(page, campaignId);
      campaignId = null;
    }
  });

  test('aplica 10% cuando hay >=2 unidades del color "Rojo"', async ({ page }) => {
    const { productE } = products(); // $60, atributo Color=Rojo

    campaignId = await createCampaign(page, {
      name:      'TEST atributo color rojo x2',
      objective: 'aov',
      strategy:  'attribute_quantity_discount',
      config: {
        min_quantity:    '2',
        discount_type:   'percentage',
        discount_value:  '10',
        max_applications: '0', // 0 = sin limite
        // El atributo se envia como condicion
        attribute_slug:  'color',
        attribute_value: 'rojo',
      },
      conditions: {
        attribute_slug:  'color',
        attribute_value: 'rojo',
      },
    });

    await addToCart(page, productE.id, 2);
    const discount = await getDiscountAmount(page);

    // $60 x 2 = $120, 10% = $12
    expectDiscount(discount, 12);
    expect(discount).toBeGreaterThan(0);
  });

  test('no aplica si hay menos de min_quantity unidades', async ({ page }) => {
    const { productE } = products();

    campaignId = await createCampaign(page, {
      name:      'TEST atributo sin minimo',
      objective: 'aov',
      strategy:  'attribute_quantity_discount',
      config: {
        min_quantity:    '3',
        discount_type:   'percentage',
        discount_value:  '10',
        max_applications: '0',
        attribute_slug:  'color',
        attribute_value: 'rojo',
      },
      conditions: {
        attribute_slug:  'color',
        attribute_value: 'rojo',
      },
    });

    // Solo 2 unidades, requiere 3
    await addToCart(page, productE.id, 2);
    const discount = await getDiscountAmount(page);

    expect(discount).toBe(0);
  });
});
