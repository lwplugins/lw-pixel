=== LW Pixel ===
Contributors: lwplugins
Tags: pixel, analytics, facebook, google analytics, conversion tracking
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.3
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight tracking pixel manager for WordPress — Meta, GA4, Ads, GTM, TikTok, Pinterest, Bing, Reddit, Snapchat, X in one minimal plugin.

== Description ==

LW Pixel is a lightweight, no-bloat alternative to PixelYourSite. Manage all your tracking pixels from a single, clean settings page.

**Features:**

* 10 pixel providers — Meta (Facebook), Google Analytics 4, Google Ads, Google Tag Manager, TikTok, Pinterest, Microsoft Bing UET, Reddit, Snapchat, X (Twitter)
* WooCommerce integration — ViewProduct, ViewCategory, ViewCart, AddToCart, InitiateCheckout, AddPaymentInfo, Purchase (idempotent)
* 9 form integrations — Contact Form 7, WPForms, Elementor Pro, Forminator, Formidable, Ninja Forms, Fluent Forms, WS Form, Gravity Forms
* Custom Event editor — page_load / click / scroll / time triggers, URL patterns, fire-once-per-session
* Auto-tracked events — scroll depth, time on page, file download, login, signup, comment
* Meta Conversion API with Advanced Matching (SHA-256), External ID, Order Enrich
* GA4 Measurement Protocol for server-side dispatch
* Compliance — Medical traffic + LDU (California / CCPA)
* GDPR-compliant — works with LW Cookie out of the box
* No bloat, no upsell, no tracking of your data

**Why LW Pixel?**

Most pixel plugins are bloated with upsells, premium features, and tracking. LW Pixel does one thing well: it loads your pixels efficiently and tracks the right events. That's it.

== Installation ==

1. Upload `lw-pixel` to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to LW Plugins → Pixel and configure your pixel IDs

== Frequently Asked Questions ==

= Does this work with WooCommerce? =

Yes. LW Pixel auto-detects WooCommerce and fires ecommerce events.

= Is it GDPR-compliant? =

Yes. It integrates with LW Cookie and any plugin that exposes the `lw_cookie_is_category_allowed` filter.

= Can I use server-side events? =

Yes. Meta Conversion API and GA4 Measurement Protocol are supported out of the box.

== Screenshots ==

1. Settings page
2. Pixel configuration
3. Event configuration

== Changelog ==

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
