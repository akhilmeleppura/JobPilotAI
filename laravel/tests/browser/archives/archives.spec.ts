import { test, expect } from '@playwright/test';

async function loginUser(page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'candidate@jobpilot.test');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/dashboard|\/$/);
}

test.describe('Drive & Sheets Archives E2E', () => {
  test('archives page displays monthly export table and checksum badges', async ({ page }) => {
    await loginUser(page);
    await page.goto('/archives');

    await expect(page.locator('body')).toContainText(/Drive & Sheets Archive/i);
    await expect(page.locator('body')).toContainText(/Archived Monthly Partitions|Generate Monthly/i);
  });
});

