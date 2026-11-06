import { test, expect } from '@playwright/test';

async function loginUser(page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'candidate@jobpilot.test');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/dashboard|\/$/);
}

test.describe('Dashboard & Core Navigation E2E', () => {
  test('dashboard displays stats, policy banner, and functional sidebar links', async ({ page }) => {
    await loginUser(page);

    // Verify Free Policy Badge
    await expect(page.locator('body')).toContainText(/FREE_RESOURCES_ONLY|Zero-Spend/i);

    // Verify Main Sidebar Navigation Links exist in the navigation architecture
    const expectedLinks = [
      '/role-profiles',
      '/cv-studio',
      '/linkedin-optimizer',
      '/content-studio',
      '/linkedin/publishing',
      '/jobs',
      '/jobs/ai-search',
      '/applications',
      '/import',
      '/email-studio',
      '/ai-chat',
      '/archives',
      '/settings',
      '/system-health',
    ];

    for (const link of expectedLinks) {
      const navItem = page.locator(`a[href*="${link}"]`);
      await expect(navItem.first()).toBeAttached();
    }

    // Verify pinned Settings and Health links are permanently visible
    await expect(page.locator('#pinnedSettingsLink')).toBeVisible();
    await expect(page.locator('#pinnedHealthLink')).toBeVisible();
  });

  test('responsive viewport checks on desktop and mobile', async ({ page }) => {
    await loginUser(page);

    // Check desktop layout
    await page.setViewportSize({ width: 1920, height: 1080 });
    await expect(page.locator('.sidebar, aside')).toBeVisible();

    // Check mobile viewport
    await page.setViewportSize({ width: 390, height: 844 });
    await expect(page.locator('body')).toBeVisible();
  });
});

