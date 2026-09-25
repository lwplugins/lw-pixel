=== LW Pixel ===
Contributors: lwplugins
Tags: pixel, conversion tracking, chatgpt ads, facebook, google analytics
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.2.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ChatGPT Ads conversion tracking (browser + server), Meta, GA4, TikTok and 7 more ad networks in one lightweight pixel manager.

== Description ==

LW Pixel manages all your tracking pixels from a single, clean settings page, and sends conversions from the browser and from your server.

**ChatGPT Ads conversion tracking**

Measure the results of your ChatGPT Ads campaigns with the official measurement pixel and the Conversions API:

* Browser pixel and server-side Conversions API, deduplicated with a shared event ID
* WooCommerce: product views, add to cart, checkout started and orders (sent once per order, amounts in the currency's minor units)
* Form submissions as leads, signups as registrations, page views, and your own custom events
* Consent-aware: fires only after the visitor accepts marketing cookies (LW Cookie)
* Optional hashed advanced matching, debug mode and a one-click connection test

**Features:**

* 11 pixel providers — ChatGPT Ads, Meta (Facebook), Google Analytics 4, Google Ads, Google Tag Manager, TikTok, Pinterest, Microsoft Bing UET, Reddit, Snapchat, X (Twitter)
* Server-side events — ChatGPT Ads Conversions API, Meta Conversion API for every standard event, GA4 Measurement Protocol; browser and server copies share one event ID so they are counted once
* WooCommerce integration — ViewProduct, ViewCategory, ViewCart, AddToCart, InitiateCheckout, AddPaymentInfo, Purchase (sent once per order, from the browser and the server)
* 9 form integrations — Contact Form 7, WPForms, Elementor Pro, Forminator, Formidable, Ninja Forms, Fluent Forms, WS Form, Gravity Forms
* Custom Event editor — page_load / click / scroll / time triggers, URL patterns, fire-once-per-session
* Auto-tracked events — scroll depth, time on page, file download, login, signup, comment, phone clicks, email clicks, thank-you pages
* Advanced Matching (SHA-256 hashed) for Meta and ChatGPT Ads, External ID, Order Enrich
* Compliance — Medical traffic + LDU (California / CCPA)
* GDPR-compliant — works with LW Cookie out of the box, for browser and server-side events
* Settings importer from a previous pixel plugin, with a preview before anything changes
* No bloat, no upsell, no tracking of your data

**Why LW Pixel?**

Most pixel plugins are bloated with upsells, premium features, and tracking. LW Pixel does one thing well: it loads your pixels efficiently and tracks the right events. That's it.

== Installation ==

1. Upload `lw-pixel` to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to LW Plugins → Pixel and configure your pixel IDs

== Frequently Asked Questions ==

= How do I set up ChatGPT Ads? =

1. Copy your pixel ID from the ChatGPT Ads Manager.
2. In LW Plugins → Pixel, open the ChatGPT Ads settings, enable the pixel, paste the pixel ID and save.
3. For server-side tracking, create a Conversions API key in the Ads Manager, paste it (or define `LW_PIXEL_CHATGPT_API_KEY` in `wp-config.php`), turn on "Send events server-side" and use the connection test.
4. While testing, turn on debug mode to see every pixel call in the browser console. Turn it off on a live site.

The pixel is in the marketing consent category: with LW Cookie it loads only after the visitor accepts marketing cookies. If your site sends a Content Security Policy, allow `https://bzrcdn.openai.com` (script-src, connect-src) and `https://bzr.openai.com` (connect-src, img-src).

= Does this work with WooCommerce? =

Yes. LW Pixel auto-detects WooCommerce and fires ecommerce events.

= Is it GDPR-compliant? =

Yes. It integrates with LW Cookie and any plugin that exposes the `lw_cookie_is_category_allowed` filter. Server-side events respect the same consent: nothing is sent for a provider the visitor did not allow.

= Can I use server-side events? =

Yes. Turn on the server-side option of a provider:

* Meta Conversion API — every standard event (PageView, ViewContent, Search, Lead, Contact, AddToCart, InitiateCheckout, AddPaymentInfo, Purchase, CompleteRegistration).
* ChatGPT Ads Conversions API — the same events as the ChatGPT Ads pixel.
* GA4 Measurement Protocol — the purchase; other events too when GA4 is loaded by something else (for example Tag Manager), because GA4 would otherwise count them twice.

The browser and the server copy of an event share one event ID, so each platform counts it once. Requests are sent after the page was delivered (or in the background through Action Scheduler) and never slow down the visitor. Page views are sent server-side only on pages that a page cache cannot serve to other visitors (for example for logged-in users); cart, checkout, form, signup and purchase events are always sent.

== Screenshots ==

1. Settings page
2. Pixel configuration
3. Event configuration

== Changelog ==

= 1.2.4 =
* Fix: notices from themes and other plugins (for example a theme's purchase-code or recommended-plugins notice) could show on the LW Pixel screen. They are now kept off every LW Plugins screen, whatever their markup.

= 1.2.3 =
* Fix: pixels never fired with LW Cookie consent — the runtime read the base64-encoded consent cookie as plain JSON, so every analytics/marketing pixel stayed blocked even after the visitor accepted all cookies

= 1.2.2 =
* Fix: the release package and Composer dist no longer ship tests, docs or development configuration

= 1.2.1 =
* Update: Tested up to WordPress 7.1.

= 1.2.0 =
* New: Phone click tracking — a click on a tel: link fires the standard Contact event (off by default) (#1)
* New: Email click tracking — a click on a mailto: link fires the standard Contact event (off by default) (#1)
* New: Thank-you page tracking — list URL fragments (e.g. koszonjuk) and a matching page fires the standard Lead event (off by default) (#1)
* Fix: Runtime event parameters (clicked link, scroll depth, downloaded file) now reach every pixel, not just Google Tag Manager

= 1.1.0 =
* Fix: Consent is now evaluated in the browser, so consent-filtered pixels are no longer baked into full-page-cached HTML. Under page caching this previously dropped tracking for consented visitors or fired pixels for non-consented ones. With LW Cookie active, all configured pixels are output and runtime.js gates each one per visitor (#3)
* Update: Minimum PHP is now 8.2; added PHPStan level 5 and a PHPUnit test suite to CI

= 1.0.6 =
* Change: Save Changes button now renders inside every settings tab that has editable fields, not just below the entire form. Tools (migrator launchers) and System Report (read-only diagnostics) still skip it.

= 1.0.5 =
* New: PixelYourSite migrator now also imports auto-event toggles (PageView, Search, Lead/form, Login, Signup, Comment, Download, Scroll, Time on Page) and WooCommerce event toggles (ViewProduct, ViewCategory, AddToCart, InitiateCheckout, Purchase, content_id_prefix), so a full PYS → LW Pixel switch keeps your existing tracking surface
* New: Migrator picks up the Meta `test_api_event_code` so QA event codes don't get lost in the migration

= 1.0.4 =
* Fix: PixelYourSite migrator now imports the actual Pixel ID, GA4 Measurement ID and Bing/Pinterest/Reddit IDs — PYS stores those as single-element arrays (`["123…"]`) and the migrator was discarding non-scalar values. The fix unwraps any array, taking the first non-empty entry
* New: Migrator also imports the Meta Conversion API toggle (`use_server_api` → `fb_capi_enabled`) and access token (`server_access_api_token` → `fb_capi_token`)

= 1.0.3 =
* New: WP-CLI support — full CLI surface for headless / scripted setups, sharing the same Options + SettingsSanitizer layer as the admin UI
* New: `wp lw-pixel status` — version, configured-pixel count, consent mode, compliance flags, Meta CAPI / GA4 MP state
* New: `wp lw-pixel list [--configured] [--format=…]` — every registered pixel with enabled/configured flags
* New: `wp lw-pixel config list|get|set|reset` — booleans accept true/false/1/0/yes/no/on/off, array options accept comma-separated values
* New: `wp lw-pixel migrate list|preview|run` — runs the PixelYourSite (Free + Pro) importer (and any future migrators) from the CLI

= 1.0.2 =
* Update: Brand color updated to #2b65f6 and title icon replaced with the circle-small mark, matching the other LW plugins
* Update: Settings page header now shows the brand icon next to the "Lightweight Pixel" title (LW Cookie / LW ZenAdmin pattern)
* Fix: Tab navigation no longer shows the browser focus outline when a tab is clicked
* Update: Active tab left-border uses the new brand color
* Docs: README updated with PHP / WordPress / License / Packagist badges and a settings screenshot

= 1.0.1 =
* New: Tools tab on the Pixel settings page (LW Plugins → Pixel → Tools)
* New: PixelYourSite (Free + Pro) migrator — preview diff with one-click import. Reads from both wp_options and the legacy pys_options table. Maps Meta / GA4 / GTM / Pinterest / Bing / Reddit pixel IDs and core toggles to LW Pixel options.

= 1.0.0 =
* New: Initial release
* New: 10 pixel providers — Meta, Google Analytics 4, Google Ads, Google Tag Manager, TikTok, Pinterest, Microsoft Bing UET, Reddit, Snapchat, X (Twitter)
* New: WooCommerce ecommerce events — ViewProduct, ViewCategory, ViewCart, AddToCart, InitiateCheckout, AddPaymentInfo, Purchase
* New: 9 form integrations — Contact Form 7, WPForms, Elementor Pro, Forminator, Formidable, Ninja Forms, Fluent Forms, WS Form, Gravity Forms
* New: Custom Event editor — page_load / click / scroll / time triggers, page URL patterns, fire-once-per-session
* New: Auto-tracked frontend events — scroll depth, time on page, file download
* New: Server-side replayed events — Login, Signup, Comment
* New: Meta Conversion API with Advanced Matching (SHA-256), External ID, Order Enrich
* New: GA4 Measurement Protocol server-side dispatch
* New: Compliance modes — Medical traffic + LDU (California / CCPA)
* New: GDPR consent integration via LW Cookie
* New: System Report admin tab for diagnostics
* New: LW Site Manager Abilities API integration
