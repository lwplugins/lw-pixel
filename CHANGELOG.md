# Changelog

## [1.2.1] - 2026-08-20

### Changed
- Tested up to WordPress 7.1.

## [1.2.0] - 2026-07-19

### Added
- Phone click tracking: clicking a `tel:` link fires the standard `Contact` event with `contact_method: phone` and the dialled number as `contact_target`. Off by default. (#1)
- Email click tracking: clicking a `mailto:` link fires `Contact` with `contact_method: email`. Off by default. (#1)
- Thank-you page tracking: configure URL fragments (one per line, e.g. `koszonjuk`) and a matching page fires the standard `Lead` event with the page URL. Off by default. (#1)

### Fixed
- Runtime event parameters (the clicked link, scroll depth, downloaded file) now reach every pixel, not just Google Tag Manager — each provider merges them over the server-mapped parameters.

## [1.1.0] - 2026-07-18

### Fixed
- Consent is now evaluated in the browser instead of at render time, so the frontend payload is identical for every visitor and safe to full-page cache. Previously, under nginx/Varnish/CDN HTML caching, the consent state of whoever filled the cache was served to everyone — either dropping tracking for consented visitors or firing pixels for non-consented ones (a GDPR risk). When LW Cookie is active, every configured pixel is emitted with its consent category and `runtime.js` gates per-visitor, re-initialising pixels when consent changes. (#3)

### Changed
- Minimum PHP is now 8.2.

### Added
- PHPStan level 5 static analysis and a PHPUnit test suite in CI.

## [1.0.6] - 2026-05-14

### Changed
- Save Changes button now renders inside every settings tab panel that has editable fields, so it sits with the form fields on the active tab instead of hanging off the bottom of the whole form. Tools (migrator launchers) and System Report (read-only diagnostics) skip the button — they have nothing to save.

## [1.0.5] - 2026-04-29

### Added
- PixelYourSite migrator now imports the cross-cutting auto-event toggles stored in `pys_facebook`: `general_event_enabled` → `event_pageview`, `automatic_event_search_enabled` → `event_search`, `automatic_event_form_enabled` → `event_lead`, plus login / signup / comment / download / scroll / time-on-page
- Migrator imports the WooCommerce event toggles: `woo_view_content_enabled` → `woo_view_product`, `woo_view_category_enabled`, `woo_add_to_cart_enabled`, `woo_initiate_checkout_enabled`, `woo_purchase_enabled`, plus `woo_content_id_prefix`
- Migrator picks up Meta's QA `test_api_event_code` (PYS stores it as a single-element array — handled by the same array-unwrap as `pixel_id`)

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
