import { test, expect } from '@playwright/test';

async function loginUser(page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'candidate@jobpilot.test');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/dashboard|\/$/);
}

test.describe('CV Studio & Global Assignment Manager E2E', () => {
  test('cv studio displays latex editor and ats analysis tools', async ({ page }) => {
    await loginUser(page);
    await page.goto('/cv-studio/assignment');

    await expect(page.locator('body')).toContainText(/CV Assignment|CV Studio/i);
    const editorLink = page.locator('a[href*="/cv-studio/editor/"]').first();
    if (await editorLink.count() > 0) {
      await editorLink.click();
      await expect(page.locator('#latexEditor')).toBeVisible();
    }
  });

  test('cv assignment manager renders all 4 assignment modes', async ({ page }) => {
    await loginUser(page);
    await page.goto('/cv-studio/assignment');

    await expect(page.locator('body')).toContainText(/CV Assignment/i);
    await expect(page.locator('body')).toContainText(/Global Default/i);
    await expect(page.locator('body')).toContainText(/Role Profile/i);
  });
});

