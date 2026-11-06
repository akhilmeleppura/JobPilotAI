import { test, expect } from '@playwright/test';

async function loginUser(page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'candidate@jobpilot.test');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/dashboard|\/$/);
}

test.describe('LinkedIn Studio & Content Studio E2E', () => {
  test('linkedin optimizer displays recruiter score and headline studio', async ({ page }) => {
    await loginUser(page);
    await page.goto('/linkedin-optimizer');

    await expect(page.locator('body')).toContainText(/LinkedIn Profile Optimizer/i);
    await expect(page.locator('body')).toContainText(/Recruiter Search Score/i);
    await expect(page.locator('body')).toContainText(/Headline Studio/i);
  });

  test('content studio renders technical topic ideator and live preview', async ({ page }) => {
    await loginUser(page);
    await page.goto('/content-studio');

    await expect(page.locator('body')).toContainText(/Content & Image Studio/i);
    await expect(page.locator('body')).toContainText(/Technical Topic Ideator/i);
    await expect(page.locator('body')).toContainText(/Live Post Feed Preview/i);
  });

  test('authorized linkedin publishing renders oauth status and review queue', async ({ page }) => {
    await loginUser(page);
    await page.goto('/linkedin/publishing');

    await expect(page.locator('body')).toContainText(/Authorized LinkedIn Publishing/i);
    await expect(page.locator('body')).toContainText(/Human-in-the-Loop Review/i);
  });
});

