require('dotenv').config();
const { chromium } = require('@playwright/test');
const fs   = require('fs');
const path = require('path');

const BASE_URL     = process.env.BASE_URL || 'http://localhost/pw-ofertas';
const FIXTURES_DIR = path.join(__dirname, 'fixtures');

module.exports = async function globalTeardown() {
  const testDataPath = path.join(FIXTURES_DIR, 'test-data.json');
  const authPath     = path.join(FIXTURES_DIR, 'auth.json');

  if (!fs.existsSync(testDataPath)) {
    console.log('[teardown] No hay test-data.json, nada que limpiar.');
    return;
  }

  const { products, restNonce, adminId, deactivatedIds = [] } = JSON.parse(fs.readFileSync(testDataPath, 'utf8'));

  const browser  = await chromium.launch();
  const context  = await browser.newContext({ storageState: authPath });
  const page     = await context.newPage();

  // Refrescar nonce (el guardado puede haber expirado si se corre al dia siguiente)
  await page.goto(`${BASE_URL}/wp-admin/`);
  const freshNonce = await page.evaluate(() => window.wpApiSettings?.nonce) || restNonce;

  // ── Eliminar ordenes de prueba ────────────────────────────────────────────
  const ordersRes = await page.request.get(
    `${BASE_URL}/wp-json/wc/v3/orders?customer=${adminId}&per_page=50&status=completed`,
    { headers: { 'X-WP-Nonce': freshNonce } }
  );
  if (ordersRes.ok()) {
    const orders = await ordersRes.json();
    for (const order of orders) {
      if (order.billing?.email === 'pwoa-test@example.com') {
        await page.request.delete(
          `${BASE_URL}/wp-json/wc/v3/orders/${order.id}?force=true`,
          { headers: { 'X-WP-Nonce': freshNonce } }
        );
      }
    }
    console.log('[teardown] Ordenes de prueba eliminadas.');
  }

  // ── Eliminar productos ────────────────────────────────────────────────────
  for (const [key, product] of Object.entries(products)) {
    const res = await page.request.delete(
      `${BASE_URL}/wp-json/wc/v3/products/${product.id}?force=true`,
      { headers: { 'X-WP-Nonce': freshNonce } }
    );
    const ok  = res.ok();
    console.log(`[teardown] ${ok ? '✓' : '✗'} Producto ${product.name} (ID: ${product.id}) ${ok ? 'eliminado' : 'ERROR'}`);
  }

  // ── Reactivar campanas que estaban activas antes de los tests ────────────
  if (deactivatedIds.length > 0) {
    await page.goto(`${BASE_URL}/wp-admin/admin.php?page=pwoa-new-campaign`);
    await page.waitForLoadState('domcontentloaded');

    await page.evaluate(
      async ({ ajaxUrl, ids }) => {
        const nonce = window.pwoaData?.nonce;
        for (const id of ids) {
          await fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'pwoa_toggle_campaign', nonce, campaign_id: String(id), active: '1' }).toString(),
          });
        }
      },
      { ajaxUrl: `${BASE_URL}/wp-admin/admin-ajax.php`, ids: deactivatedIds }
    );
    console.log(`[teardown] ${deactivatedIds.length} campanas reactivadas: [${deactivatedIds.join(', ')}]`);
  }

  fs.unlinkSync(testDataPath);
  console.log('[teardown] Limpieza completada.');

  await browser.close();
};
