# LW Pixel

Lightweight tracking pixel manager for WordPress.

## What it does

Loads tracking pixels from the major ad networks (Meta, Google, TikTok, Pinterest, Bing) and dispatches the standard ecommerce / conversion events for them. No upsell, no tracking, no bloat.

## Supported pixels

- Meta (Facebook) Pixel — `fbq` browser pixel + Conversion API server-side
- Google Analytics 4 — `gtag.js`
- Google Ads — conversion tracking + remarketing
- Google Tag Manager — container + `dataLayer`
- TikTok Pixel — `ttq`
- Pinterest Tag — `pintrk`
- Microsoft Bing UET — `uetq`

## Supported events

- `PageView` (auto, every page)
- `ViewContent` — single posts/pages and WooCommerce products
- `Search` — search results pages
- `Lead` / `Contact` — form submissions
- `AddToCart`, `InitiateCheckout`, `AddPaymentInfo`, `Purchase` — WooCommerce
- Custom events — define your own with the editor

## Integrations

- **WooCommerce** — full ecommerce funnel events
- **Contact Form 7, WPForms, Elementor Forms** — auto-detected lead events
- **LW Cookie** — granular consent-based pixel loading
- **LW Site Manager** — managed via the Abilities API

## Installation

```bash
composer require lwplugins/lw-pixel
```

Or download a release ZIP from the [GitHub releases page](https://github.com/lwplugins/lw-pixel/releases).

## Configuration

After activation, go to **LW Plugins → Pixel** and add your pixel IDs. Each pixel has its own tab with provider-specific options.

## License

GPL-2.0-or-later
