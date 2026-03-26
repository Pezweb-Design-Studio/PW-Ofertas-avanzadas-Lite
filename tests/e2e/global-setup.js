require('dotenv').config();
const { chromium }    = require('@playwright/test');
const { execSync }    = require('child_process');
const fs   = require('fs');
const path = require('path');

const BASE_URL      = process.env.BASE_URL || 'http://localhost/pw-ofertas';
const FIXTURES_DIR  = path.join(__dirname, 'fixtures');
const WP_ROOT       = process.env.WP_ROOT || '/Applications/XAMPP/xamppfiles/htdocs/pw-ofertas';
const XAMPP_PHP     = process.env.XAMPP_PHP || '/Applications/XAMPP/xamppfiles/bin/php';

module.exports = async function globalSetup() {
  fs.mkdirSync(FIXTURES_DIR, { recursive: true });

  const browser = await chromium.launch();
  const page    = await browser.newPage();

  // ── 1. Login ──────────────────────────────────────────────────────────────
  console.log('[setup] Iniciando sesion en WordPress...');
  await page.goto(`${BASE_URL}/wp-login.php`);
  await page.fill('#user_login', process.env.WP_USER || 'dario');
  await page.fill('#user_pass',  process.env.WP_PASS || 'koroshite2007');
  await page.click('#wp-submit');
  await page.waitForURL('**/wp-admin/**', { timeout: 15000 });

  await page.context().storageState({ path: path.join(FIXTURES_DIR, 'auth.json') });
  console.log('[setup] Sesion guardada.');

  // ── 1b. Limpiar carrito persistente del admin via PHP (restos de ejecuciones anteriores) ──
  // Borra _woocommerce_persistent_cart_1 (user meta) Y la sesion WC en la DB,
  // para que el test runner empiece siempre con el carrito vacio.
  console.log('[setup] Limpiando carrito persistente via PHP...');
  try {
    const clearCartPhp = path.join(__dirname, 'helpers/clear-cart.php');
    const result = execSync(
      `${XAMPP_PHP} ${clearCartPhp} ${WP_ROOT} 1`,
      { timeout: 10000 }
    ).toString().trim();
    console.log(`[setup] ${result}`);
  } catch (e) {
    console.warn('[setup] No se pudo limpiar carrito via PHP:', e.message);
  }

  // ── 2. Obtener nonces ─────────────────────────────────────────────────────
  await page.goto(`${BASE_URL}/wp-admin/`);
  const restNonce = await page.evaluate(() => window.wpApiSettings?.nonce);
  if (!restNonce) throw new Error('[setup] No se encontro wpApiSettings.nonce. Verificar WP admin.');

  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=pwoa-new-campaign`);
  const pwoaNonce = await page.evaluate(() => window.pwoaData?.nonce);
  if (!pwoaNonce) throw new Error('[setup] No se encontro pwoaData.nonce. ¿Esta activo el plugin?');

  console.log('[setup] Nonces obtenidos.');

  // ── 3. Crear productos de prueba via WC REST API ──────────────────────────
  const productDefs = [
    {
      key:            'productA',
      name:           'PWOA Test A - Basico',
      regular_price:  '100',
      manage_stock:   true,
      stock_quantity: 5,   // bajo stock (threshold default=10) para low_stock test
      status:         'publish',
    },
    {
      key:            'productB',
      name:           'PWOA Test B - Precio Medio',
      regular_price:  '50',
      manage_stock:   true,
      stock_quantity: 20,
      status:         'publish',
    },
    {
      key:            'productC',
      name:           'PWOA Test C - Premium',
      regular_price:  '200',
      manage_stock:   true,
      stock_quantity: 15,
      status:         'publish',
    },
    {
      key:            'productD',
      name:           'PWOA Test D - Vencimiento',
      regular_price:  '75',
      manage_stock:   true,
      stock_quantity: 10,
      status:         'publish',
    },
    {
      key:            'productE',
      name:           'PWOA Test E - Con Atributo',
      regular_price:  '60',
      manage_stock:   true,
      stock_quantity: 30,
      status:         'publish',
      attributes: [{
        name:    'Color',
        options: ['Rojo'],
        visible: true,
      }],
    },
  ];

  const products = {};

  for (const { key, ...data } of productDefs) {
    const res     = await page.request.post(`${BASE_URL}/wp-json/wc/v3/products`, {
      headers: { 'X-WP-Nonce': restNonce, 'Content-Type': 'application/json' },
      data,
    });
    if (!res.ok()) {
      const txt = await res.text();
      throw new Error(`[setup] Error creando "${data.name}": ${txt}`);
    }
    const created  = await res.json();
    products[key]  = { id: created.id, name: created.name, price: parseFloat(created.price) };
    console.log(`[setup] Producto creado: ${data.name} (ID: ${created.id})`);
  }

  // ── 4. Poner _expiry_date en productD (para ExpiryBasedStrategy) ──────────
  const expiry = new Date();
  expiry.setDate(expiry.getDate() + 5); // vence en 5 dias
  const expiryStr = expiry.toISOString().split('T')[0];

  // WC REST API soporta meta_data (WP REST API wp/v2 ignora meta no registrada)
  const metaRes = await page.request.put(
    `${BASE_URL}/wp-json/wc/v3/products/${products.productD.id}`,
    {
      headers: { 'X-WP-Nonce': restNonce, 'Content-Type': 'application/json' },
      data:    { meta_data: [{ key: '_expiry_date', value: expiryStr }] },
    }
  );
  if (metaRes.ok()) {
    console.log(`[setup] _expiry_date = ${expiryStr} asignado a productD.`);
  } else {
    const txt = await metaRes.text();
    console.warn(`[setup] No se pudo asignar _expiry_date via WC REST: ${txt}`);
  }

  // ── 5. Crear ordenes completadas para RecurringPurchaseStrategy ───────────
  const meRes    = await page.request.get(`${BASE_URL}/wp-json/wp/v2/users/me`, {
    headers: { 'X-WP-Nonce': restNonce },
  });
  const me       = await meRes.json();
  const adminId  = me.id;

  let ordersCreated = 0;
  for (let i = 0; i < 3; i++) {
    const orderRes = await page.request.post(`${BASE_URL}/wp-json/wc/v3/orders`, {
      headers: { 'X-WP-Nonce': restNonce, 'Content-Type': 'application/json' },
      data: {
        status:      'completed',
        customer_id: adminId,
        line_items:  [{ product_id: products.productA.id, quantity: 1 }],
        billing: {
          first_name: 'PWOA',
          last_name:  'Test',
          email:      'pwoa-test@example.com',
          country:    'CL',
        },
      },
    });
    if (orderRes.ok()) {
      const created = await orderRes.json();
      console.log(`[setup] Orden ${i + 1} creada: ID=${created.id} status=${created.status}`);
      ordersCreated++;
    } else {
      const txt = await orderRes.text();
      console.error(`[setup] ERROR creando orden ${i + 1}: ${orderRes.status()} ${txt.substring(0, 200)}`);
    }
  }
  console.log(`[setup] ${ordersCreated}/3 ordenes completadas creadas para recurring_purchase.`);

  // ── 5b. Restaurar stock de productA (las ordenes lo decrementaron) ────────
  // Las 3 ordenes completadas decrementan stock de 5 a 2.
  // Tests que agregan 3-4 unidades al carrito fallan si no se restaura.
  const restoreStockRes = await page.request.put(
    `${BASE_URL}/wp-json/wc/v3/products/${products.productA.id}`,
    {
      headers: { 'X-WP-Nonce': restNonce, 'Content-Type': 'application/json' },
      data:    { stock_quantity: 5 },
    }
  );
  if (restoreStockRes.ok()) {
    console.log('[setup] Stock de productA restaurado a 5.');
  } else {
    console.warn('[setup] No se pudo restaurar stock de productA.');
  }

  // ── 6. Desactivar campanas existentes para no interferir en tests ─────────
  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=pwoa-new-campaign`);
  await page.waitForLoadState('domcontentloaded');

  const deactivatedIds = await page.evaluate(async (ajaxUrl) => {
    const nonce = window.pwoaData?.nonce;
    const listRes = await fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'pwoa_get_campaigns_paginated', nonce, page: '1', per_page: '100' }).toString(),
    });
    const list = await listRes.json();
    const campaigns = list.data?.campaigns || list.data || [];
    const activeIds = campaigns.filter(c => c.is_active == 1 || c.status === 'active' || c.active == 1).map(c => c.id);

    for (const id of activeIds) {
      await fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'pwoa_toggle_campaign', nonce, campaign_id: String(id), active: '0' }).toString(),
      });
    }
    return activeIds;
  }, `${BASE_URL}/wp-admin/admin-ajax.php`);

  console.log(`[setup] ${deactivatedIds.length} campanas desactivadas temporalmente: [${deactivatedIds.join(', ')}]`);

  // ── 7. Guardar datos ──────────────────────────────────────────────────────
  const testData = { products, restNonce, adminId, deactivatedIds };
  fs.writeFileSync(
    path.join(FIXTURES_DIR, 'test-data.json'),
    JSON.stringify(testData, null, 2)
  );
  console.log('[setup] Setup completado. IDs guardados en fixtures/test-data.json');

  await browser.close();
};
