import { test, expect } from '@playwright/test';

test.describe('Authentication & Access Control E2E', () => {
  test('unauthenticated visitors are redirected to login', async ({ page }) => {
    await page.goto('/applications');
    await expect(page).toHaveURL(/.*login/);
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('login page validates empty or invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('h3, h4, h2, .fw-bold')).toContainText(/Log In|Sign In|JobPilot/i);

    // Attempt login with invalid credentials
    await page.fill('input[name="email"]', 'wrong@example.com');
    await page.fill('input[name="password"]', 'InvalidPass123!');
    await page.click('button[type="submit"]');

    // Should remain on login page with error
    await expect(page).toHaveURL(/.*login/);
  });

  test('valid candidate user can log in and reach dashboard', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'candidate@jobpilot.test');
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');

    // Should successfully redirect to dashboard
    await expect(page).toHaveURL(/\/dashboard|\/$/);
    await expect(page.locator('body')).toContainText(/Dashboard|JobPilot AI|Overview/i);
  });
});

