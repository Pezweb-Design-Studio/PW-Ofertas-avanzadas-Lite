/**
 * Helpers para crear/eliminar campanas PWOA.
 * Usa page.goto() al wizard para obtener un nonce FRESCO de la sesion actual,
 * luego page.evaluate() para la llamada AJAX (garantiza cookies correctas).
 */
const BASE_URL = process.env.BASE_URL || 'http://localhost/pw-ofertas';
const WIZARD_URL = `${BASE_URL}/wp-admin/admin.php?page=pwoa-new-campaign`;
const AJAX_URL   = `${BASE_URL}/wp-admin/admin-ajax.php`;

/**
 * Crea una campana y devuelve su ID.
 * Navega al wizard para obtener nonce fresco antes de llamar el AJAX.
 *
 * @param {import('@playwright/test').Page} page
 */
async function createCampaign(page, { name, objective, strategy, config, conditions = {} }) {
  await page.goto(WIZARD_URL);
  await page.waitForLoadState('domcontentloaded');

  const result = await page.evaluate(
    async ({ ajaxUrl, params }) => {
      const nonce = window.pwoaData?.nonce;
      if (!nonce) throw new Error('[campaigns] pwoaData.nonce no encontrado en la pagina wizard');

      const body = new URLSearchParams({ ...params, nonce });
      const res  = await fetch(ajaxUrl, {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    body.toString(),
      });
      return res.json();
    },
    {
      ajaxUrl: AJAX_URL,
      params: {
        action:        'pwoa_save_campaign',
        name,
        objective,
        strategy,
        config:        JSON.stringify(config),
        conditions:    JSON.stringify(conditions),
        stacking_mode: 'priority',
        priority:      '10',
        start_date:    '',
        end_date:      '',
      },
    }
  );

  if (!result.success) {
    throw new Error(`[campaigns] Error creando "${name}": ${JSON.stringify(result.data)}`);
  }
  return result.data.campaign_id;
}

/**
 * Elimina (soft-delete) una campana por ID.
 */
async function deleteCampaign(page, campaignId) {
  // Si no estamos en el wizard, navegar para tener el nonce disponible
  if (!page.url().includes('/wp-admin/')) {
    await page.goto(WIZARD_URL);
    await page.waitForLoadState('domcontentloaded');
  }

  await page.evaluate(
    async ({ ajaxUrl, campaignId }) => {
      const nonce = window.pwoaData?.nonce;
      const body  = new URLSearchParams({
        action:      'pwoa_delete_campaign',
        nonce,
        campaign_id: String(campaignId),
      });
      await fetch(ajaxUrl, {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    body.toString(),
      });
    },
    { ajaxUrl: AJAX_URL, campaignId }
  );
}

/**
 * Desactiva todas las campanas activas y devuelve sus IDs para restaurarlas despues.
 * Llama a pwoa_get_campaigns_paginated + pwoa_toggle_campaign.
 */
async function deactivateAllCampaigns(page) {
  await page.goto(WIZARD_URL);
  await page.waitForLoadState('domcontentloaded');

  const activeIds = await page.evaluate(async (ajaxUrl) => {
    const nonce = window.pwoaData?.nonce;

    // Obtener todas las campanas activas
    const listRes = await fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'pwoa_get_campaigns_paginated',
        nonce,
        page: '1',
        per_page: '100',
        status: 'active',
      }).toString(),
    });
    const list = await listRes.json();
    if (!list.success) return [];

    const campaigns = list.data?.campaigns || list.data || [];
    const ids = campaigns.map(c => c.id).filter(Boolean);

    // Desactivar cada una
    for (const id of ids) {
      await fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'pwoa_toggle_campaign',
          nonce,
          campaign_id: String(id),
          active: '0',
        }).toString(),
      });
    }

    return ids;
  }, AJAX_URL);

  return activeIds;
}

/**
 * Reactiva campanas previamente desactivadas.
 */
async function reactivateCampaigns(page, ids) {
  if (!ids || ids.length === 0) return;

  await page.goto(WIZARD_URL);
  await page.waitForLoadState('domcontentloaded');

  await page.evaluate(
    async ({ ajaxUrl, ids }) => {
      const nonce = window.pwoaData?.nonce;
      for (const id of ids) {
        await fetch(ajaxUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action:      'pwoa_toggle_campaign',
            nonce,
            campaign_id: String(id),
            active:      '1',
          }).toString(),
        });
      }
    },
    { ajaxUrl: AJAX_URL, ids }
  );
}

module.exports = { createCampaign, deleteCampaign, deactivateAllCampaigns, reactivateCampaigns };
