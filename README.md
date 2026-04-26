# LW Pixel

Lightweight tracking pixel manager for WordPress.

## What it does

Loads tracking pixels from the major ad networks and dispatches the standard ecommerce / conversion events for them. No upsell, no tracking, no bloat.

## Supported pixels

- **Meta (Facebook) Pixel** — `fbq` browser pixel + Conversion API server-side (Advanced Matching, External ID, Order Enrich)
- **Google Analytics 4** — `gtag.js` + Measurement Protocol server-side
- **Google Ads** — conversion tracking + remarketing
- **Google Tag Manager** — container + `dataLayer`
- **TikTok Pixel** — `ttq`
- **Pinterest Tag** — `pintrk`
- **Microsoft Bing UET** — `uetq`
- **Reddit Pixel** — `rdt`
- **Snapchat Pixel** — `snaptr`
- **X (Twitter) Pixel** — `twq`

## Supported events

- `PageView` (auto, every page)
- `ViewContent` — single posts/pages and WooCommerce products
- `Search` — search results pages
- `Lead` / `Contact` — form submissions
- `AddToCart`, `InitiateCheckout`, `AddPaymentInfo`, `Purchase`, `ViewCart`, `ViewCategory` — WooCommerce
- `Login`, `Signup` (CompleteRegistration), `Comment` — server-side, replayed on the next page load
- `Scroll`, `TimeOnPage`, `Download` — auto-tracked frontend events with configurable thresholds
- Custom events — define your own with the editor (page_load / click / scroll / time triggers, URL patterns)

## Integrations

### Form plugins

Lead events fire automatically when any of these forms are submitted:

- **Contact Form 7**
- **WPForms**
- **Elementor Pro Forms**
- **Gravity Forms**
- **Forminator**
- **Formidable Forms**
- **Ninja Forms**
- **Fluent Forms**
- **WS Form**

### Other

- **WooCommerce** — full ecommerce funnel (ViewProduct, ViewCategory, ViewCart, AddToCart, InitiateCheckout, AddPaymentInfo, Purchase) with idempotent thank-you-page Purchase
- **LW Cookie** — granular consent-based pixel loading via `lw_cookie_is_category_allowed`
- **LW Site Manager** — managed via the Abilities API (get-options, set-options, list-pixels)

## Compliance

- **Medical traffic mode** — strips PII from event payloads + CAPI user_data (HIPAA-friendly)
- **Limited Data Use (LDU)** — adds Meta data_processing_options for California / CCPA (auto-resolve or force California)

## Migration

Built-in **PixelYourSite (Free / Pro) migrator** under **LW Plugins → Pixel → Tools** — preview-diff UI, one-click import. Reads from both `wp_options` and the legacy `pys_options` table.

## Installation

```bash
composer require lwplugins/lw-pixel
```

Or download a release ZIP from the [GitHub releases page](https://github.com/lwplugins/lw-pixel/releases).

## Configuration

After activation, go to **LW Plugins → Pixel** and add your pixel IDs. Each pixel has its own tab with provider-specific options.

## License

GPL-2.0-or-later
