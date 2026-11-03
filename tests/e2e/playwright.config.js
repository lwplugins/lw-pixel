/**
 * Browser tests for assets/js/runtime.js.
 *
 * Offline: every vendor library is blocked, so the tests count what the
 * runtime queues for each provider, not what the vendor later sends.
 */
const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: __dirname,
	outputDir: __dirname + '/.results',
	reporter: process.env.CI ? 'github' : 'list',
	use: { browserName: 'chromium', headless: true },
} );
