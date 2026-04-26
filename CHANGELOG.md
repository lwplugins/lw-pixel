# Changelog

## [1.0.1] - 2026-04-26

### Added
- Tools tab on the Pixel settings page (LW Plugins → Pixel → Tools), keeping all plugin admin under a single page
- PixelYourSite (Free + Pro) migrator — preview-diff UI plus one-click import. Reads from both `wp_options` and the legacy `pys_options` table; maps Meta / GA4 / GTM / Pinterest / Bing / Reddit pixel IDs and core toggles to the matching LW Pixel options

### Changed
- TabEvents extracted EventsAutoSection partial to keep the file under 200 lines

## [1.0.0] - 2026-04-26

### Added
- Initial release
- 10 pixel providers: Meta (Facebook), Google Analytics 4, Google Ads, Google Tag Manager, TikTok, Pinterest, Microsoft Bing UET, Reddit, Snapchat, X (Twitter)
- WooCommerce ecommerce events: ViewProduct, ViewCategory, ViewCart, AddToCart, InitiateCheckout, AddPaymentInfo, Purchase (idempotent)
- 9 form integrations: Contact Form 7, WPForms, Elementor Pro, Forminator, Formidable, Ninja Forms, Fluent Forms, WS Form, Gravity Forms
- Custom Event editor (CPT metabox): name, trigger type (page_load/click/scroll/time), CSS selector, page URL pattern, value/currency, fire-once-per-session
- Auto-tracked frontend events: scroll depth (configurable thresholds), time on page (configurable thresholds), file download (configurable extensions)
- Server-side flagged events: Login, Signup (CompleteRegistration), Comment — replayed on the visitor's next page load
- Meta Conversion API server-side dispatch with Advanced Matching (SHA-256 hashed: email, phone, name, city, state, zip, country, gender, DOB), External ID, Order Enrich
- GA4 Measurement Protocol server-side dispatch
- Compliance: Medical traffic mode (HIPAA-friendly: strips PII from payloads + CAPI user_data)
- Compliance: Limited Data Use mode (auto / force California) for CCPA
- GDPR consent integration via LW Cookie + generic filter API
- System Report admin tab: environment, pixel status, integration detection, redacted options snapshot
- LW Site Manager Abilities API integration (get-options, set-options, list-pixels)
- Standalone smoke test (`composer smoke` / `php bin/smoke-test.php`) — 41 assertions, no WP required
