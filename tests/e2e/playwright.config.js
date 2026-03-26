require('dotenv').config();
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  globalSetup:    require.resolve('./global-setup'),
  globalTeardown: require.resolve('./global-teardown'),
  testDir:        './specs',
  workers:        1, // secuencial: carrito compartido en WooCommerce
  timeout:        40000,
  use: {
    baseURL:      process.env.BASE_URL || 'http://localhost/pw-ofertas',
    storageState: './fixtures/auth.json',
    screenshot:   'only-on-failure',
    video:        'retain-on-failure',
    locale:       'es-CL',
  },
  reporter: [
    ['list'],
    ['html', { open: 'never', outputFolder: 'playwright-report' }],
  ],
});
