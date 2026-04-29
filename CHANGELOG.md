# Changelog

## [1.0.4] - 2026-04-29

### Fixed
- PixelYourSite migrator was importing only the boolean toggles, leaving Pixel IDs and Measurement IDs blank. PYS stores `pixel_id`, `tracking_id`, `tag_id`, `server_access_api_token` etc. as single-element arrays (`["123…"]`) for multi-pixel support, and `PysMigrator::cast()` fell through to `(string) $default` for any non-scalar input. Cast now unwraps arrays and takes the first non-empty scalar entry

### Added
- PixelYourSite migrator now also maps `pys_facebook.use_server_api` → `fb_capi_enabled` and `pys_facebook.server_access_api_token` → `fb_capi_token`, so the Conversion API setup transfers in the same import

## [1.0.3] - 2026-04-29

### Added
- WP-CLI support — full CLI surface for headless / scripted deployments, sharing the same `Options` + `SettingsSanitizer` layer as the admin UI so writes from CLI go through the exact same validation and head/body/footer protections
- `wp lw-pixel status` — version, configured-pixel count, consent mode, Medical / LDU compliance state, Meta CAPI and GA4 Measurement Protocol toggles
- `wp lw-pixel list [--configured] [--format=table|json|csv|yaml]` — every registered pixel with `enabled` and `configured` flags
- `wp lw-pixel config list [--changed] [--format=…]` / `get <key>` / `set <key> <value>` / `reset [--yes]` — booleans accept `true/false/1/0/yes/no/on/off`, array options accept comma-separated values
- `wp lw-pixel migrate list` / `preview <id> [--format=…]` / `run <id> [--yes]` — drives the existing `MigratorRegistry`, so the PixelYourSite (Free + Pro) importer is now available without opening the Tools tab

## [1.0.2] - 2026-04-26

### Changed
- Brand color updated to `#2b65f6` and title icon replaced with the circle-small mark, matching the other LW plugins
- Settings page header now shows the brand icon next to the "Lightweight Pixel" title (LW Cookie / LW ZenAdmin pattern)
- Active tab left-border uses the new brand color

### Fixed
- Tab navigation no longer shows the browser focus outline when a tab is clicked

### Docs
- README updated with PHP / WordPress / License / Packagist badges and a settings screenshot

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
