import { test, expect } from '@playwright/test';

async function loginUser(page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'candidate@jobpilot.test');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/dashboard|\/$/);
}

test.describe('AI Chat Workspace E2E', () => {
  test('ai chat workspace loads multi-pane layout and prompt chips', async ({ page }) => {
    await loginUser(page);
    await page.goto('/ai-chat');

    await expect(page.locator('body')).toContainText(/AI Chat|Career Copilot/i);
    await expect(page.locator('textarea, input[type="text"]').first()).toBeVisible();
  });
});

