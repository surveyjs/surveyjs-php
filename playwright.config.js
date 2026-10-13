// Browser tests for the step pages (tests/browser): what PHPUnit and the contract test can't see,
// because they never run shared/client/host.js or resources/js/page-tabs.js. Chromium only.
//
//   npm run test:browser                     against composer dev (or composer start) on port 8000
//   BASE_URL=http://127.0.0.1:9000 npm run test:browser
//
// Without a running app, Playwright starts PHP's built-in server on port 8000 itself.
import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.BASE_URL ?? 'http://127.0.0.1:8000';

export default defineConfig({
    testDir: 'tests/browser',
    workers: 1,   // PHP's built-in server answers one request at a time on Windows
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['github']] : 'list',
    use: {
        baseURL,
        trace: 'retain-on-failure',
    },
    projects: [{
        name: 'chromium',
        use: {
            ...devices['Desktop Chrome'],
            viewport: { width: 1440, height: 900 },
            // Under composer dev, a page the test fulfils itself loads its assets from Vite on [::1]:5173;
            // Chrome's Local Network Access checks would block that. Built assets (CI) don't need it.
            launchOptions: { args: ['--disable-features=LocalNetworkAccessChecks'] },
        },
    }],
    webServer: {
        command: 'php -d upload_max_filesize=10M -d post_max_size=12M -S 127.0.0.1:8000 -t public server.php',
        url: `${baseURL}/up`,
        reuseExistingServer: true,
        timeout: 30_000,
    },
});
