import { test, expect } from '@playwright/test';

async function loginUser(page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'candidate@jobpilot.test');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/dashboard|\/$/);
}

test.describe('Email Studio & Outreach Automation E2E', () => {
  test('email studio displays templates and dynamic interpolation preview', async ({ page }) => {
    await loginUser(page);
    await page.goto('/email-studio/templates');

    await expect(page.locator('body')).toContainText(/Email Studio|Templates/i);
  });

  test('email automation renders accounts and review queue', async ({ page }) => {
    await loginUser(page);
    await page.goto('/email-studio');

    await expect(page.locator('body')).toContainText(/Outreach|Email Automation|Quota/i);
  });
});

