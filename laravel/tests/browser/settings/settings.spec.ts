import { test, expect } from '@playwright/test';

async function loginUser(page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'candidate@jobpilot.test');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/dashboard|\/$/);
}

test.describe('Settings & System Health E2E', () => {
  test('settings page displays credential vault and quota monitors', async ({ page }) => {
    await loginUser(page);
    await page.goto('/settings');

    await expect(page.locator('body')).toContainText(/Integrations & Quotas|Credential Vault/i);
    await expect(page.locator('body')).toContainText(/Google AI Studio/i);
  });

  test('system health dashboard displays operational rating and backups table', async ({ page }) => {
    await loginUser(page);
    await page.goto('/system-health');

    await expect(page.locator('body')).toContainText(/System Health, Security/i);
    await expect(page.locator('body')).toContainText(/Health Rating/i);
    await expect(page.locator('body')).toContainText(/Database Backups/i);
  });
});

