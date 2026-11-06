import { test, expect } from '@playwright/test';

async function loginUser(page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'candidate@jobpilot.test');
  await page.fill('input[name="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/dashboard|\/$/);
}

test.describe('Job Discovery & AI Search E2E', () => {
  test('job discovery grid loads zero-cost sources', async ({ page }) => {
    await loginUser(page);
    await page.goto('/jobs');

    await expect(page.locator('main h3, main h4, main .fw-bold, .content-area h3').first()).toContainText(/Job Discovery/i);
    await expect(page.locator('body')).toContainText(/Adzuna|Jooble|RemoteOK|Himalayas|Remotive/i);
  });

  test('natural language ai job search performs conversational queries', async ({ page }) => {
    await loginUser(page);
    await page.goto('/jobs/ai-search');

    await expect(page.locator('body')).toContainText(/Natural Language AI Job Search/i);
    
    // Fill prompt and submit
    const promptInput = page.locator('input[name="prompt"]');
    await expect(promptInput).toBeVisible();
    await promptInput.fill('Senior Remote Laravel Engineer $140k+ with TiDB');
    await page.locator('button:has-text("Search with AI")').click();

    await expect(page).toHaveURL(/.*prompt=.*/);
    await expect(page.locator('body')).toContainText(/Match|Ranking|Results|Remote|Laravel|Discover/i);
  });
});

