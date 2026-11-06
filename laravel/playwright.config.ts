import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/browser',
  timeout: 30000,
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: [
    ['list'],
    ['html', { outputFolder: '../reports/local/browser-report', open: 'never' }]
  ],
  use: {
    baseURL: process.env.APP_URL || 'http://127.0.0.1:8000',
    channel: 'chrome',
    trace: 'retain-on-failure',
    screenshot: 'on',
    video: 'off',
  },
  projects: [
    {
      name: 'Desktop 1080p',
      use: {
        viewport: { width: 1920, height: 1080 },
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 JobPilot-Desktop-E2E',
      },
    },
    {
      name: 'Laptop 1366x768',
      use: {
        viewport: { width: 1366, height: 768 },
      },
    },
    {
      name: 'Tablet iPad',
      use: {
        viewport: { width: 768, height: 1024 },
      },
    },
    {
      name: 'Mobile Phone',
      use: {
        viewport: { width: 390, height: 844 },
        isMobile: true,
      },
    },
  ],
});

