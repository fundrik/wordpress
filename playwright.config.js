const { defineConfig } = require('@playwright/test');

process.env.WP_BASE_URL ??= 'http://localhost:8889';
process.env.STORAGE_STATE_PATH ??= '.tmp/playwright/storage-states/admin.json';

module.exports = defineConfig({
  testDir: './tests/e2e',
  workers: 1,
  outputDir: '.tmp/playwright/test-results',
  globalSetup: require.resolve('./tests/e2e/global-setup.js'),
  use: {
    baseURL: process.env.WP_BASE_URL,
    storageState:
      process.env.STORAGE_STATE_PATH || '.tmp/playwright/storage-states/admin.json',
  },
  webServer: {
    command: 'npm run wp-env:start',
    port: 8889,
    reuseExistingServer: true,
  },
});

