import { test, expect } from '@playwright/test';

async function loginUser(page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'candidate@jobpilot.test');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/dashboard|\/$/);
}

test.describe('Application Registry & Spreadsheet Import E2E', () => {
  test('application registry loads applications data table', async ({ page }) => {
    await loginUser(page);
    await page.goto('/applications');

    await expect(page.locator('main h3, main h4, main .fw-bold, .content-area h3').first()).toContainText(/Application/i);
    await expect(page.locator('.card').first()).toBeVisible();
  });

  test('spreadsheet import page renders dropzone and template download', async ({ page }) => {
    await loginUser(page);
    await page.goto('/import');

    await expect(page.locator('body')).toContainText(/Spreadsheet|Import/i);
    const templateLink = page.locator('a[href*="/import/template"]');
    await expect(templateLink).toBeVisible();
  });
});

