# Testing LW Pixel

Three layers of tests, fastest first.

## 1. Smoke test (PHP CLI, ~1 second)

Exercises the core wiring without WordPress. Catches autoloader, namespace, signature regressions.

```bash
composer smoke
# or
php bin/smoke-test.php
```

Should print `Total: N assertions, all passed.` and exit 0.

## 2. Static analysis

```bash
composer phpcs    # WordPress Coding Standards
composer phpcbf   # auto-fix what's fixable
```

## 3. Manual testing in Docker

The `docker/docker-compose.yml` already mounts the plugin into the WP container.

### Setup

```bash
cd /Users/trueqap/Desktop/Projects/wordpress-plugins-themes/lw-plugins
docker compose -f docker/docker-compose.yml up -d
open http://localhost:9090/wp-admin
# Plugins → activate "LW Pixel"
```

### Settings page checks

Visit **LW Plugins → Pixel** and walk through every tab:

- [ ] Sidebar shows 13 tabs (Meta, Google, Tag Manager, TikTok, Pinterest, Bing, Reddit, Snapchat, X, Events, WooCommerce, Compliance, Advanced, System Report)
- [ ] Each tab loads its content (no blank panels)
- [ ] Saving with at least one pixel ID set persists across reload
- [ ] System Report tab shows JSON with environment + pixels + integrations
- [ ] Custom Event editor: **LW Plugins → Pixel** has no direct link, but `wp-admin/edit.php?post_type=lw_pixel_event` is accessible only to admins (try a non-admin user → should be blocked)

### Frontend payload check

1. Set a Meta Pixel ID on the Meta tab, save.
2. **Make sure "Disable for admins" is OFF on the Advanced tab** (otherwise no pixels load for you).
3. Open the homepage in an incognito window.
4. View source or open DevTools → Elements: there should be a single inline `<script id="lw-pixel-data" type="application/json">` tag with the JSON payload.
5. The payload's `pixels.fb.pixelId` must match the saved value.
6. DevTools → Network → `fbevents.js` should load from `connect.facebook.net`.
7. DevTools → Console: `window.fbq` should be a function.

### Browser-extension verification

| Pixel | Extension | What to look for |
|---|---|---|
| Meta | [Meta Pixel Helper](https://chromewebstore.google.com/detail/meta-pixel-helper/fdgfkebogiimcoedlicjlajpkdmockpc) | Pixel ID + PageView event |
| GA4 | [Google Tag Assistant](https://tagassistant.google.com/) | gtag config + page_view |
| Pinterest | [Pinterest Tag Helper](https://chromewebstore.google.com/detail/pinterest-tag-helper/gnnkjjfgppmnoendoejecjfemdcblain) | Tag ID + pagevisit |
| TikTok | [TikTok Pixel Helper](https://chromewebstore.google.com/detail/tiktok-pixel-helper/aelgobmabdmlfmiblddjfnjodalhidnn) | Pixel + Pageview |
| Bing UET | [UET Tag Helper](https://chromewebstore.google.com/detail/uet-tag-helper-by-microso/naijndjklgmffmpembnkfbcjbognokbf) | Tag firing |

### WooCommerce flow check

(WC must be installed and activated.)

1. Add product → cart: in Network tab, look for the AddToCart event payload going to `fbq`.
2. Visit `/cart` → ViewCart event.
3. Visit `/checkout` → InitiateCheckout event.
4. Place an order → on the thank-you page, **exactly one** Purchase event with `value` and `currency`.
5. Reload the thank-you page → no duplicate Purchase (idempotency).

### Form integration check

If Contact Form 7 is installed:

1. Submit a CF7 form.
2. The redirect / next page-load should fire a Lead event with `source: cf7`.

(The same applies to WPForms / Elementor / Forminator / Formidable / Ninja / Fluent / WS Form / Gravity Forms.)

### Auto-events check

Enable Scroll/Time/Download on the Events tab.

- Scroll: scroll the page down — Network tab fires Scroll events at 25/50/75/100%.
- Time: stay on the page 10s, 30s, 60s, 180s — TimeOnPage events at each threshold.
- Download: click a `<a href="something.pdf">` — Download event with `file_url` and `file_extension`.

### CAPI test

1. Set FB Pixel ID + Access Token + Test Event Code on Meta tab.
2. Open Meta Events Manager → Test Events → enter the test code.
3. Trigger any event on the site.
4. The event should appear in Test Events with `event_source: server` (alongside the browser variant).

### LDU + Medical mode

1. Enable Compliance → Medical mode.
2. Trigger a CAPI event.
3. The CAPI request body's `user_data` should NOT contain `em`, `ph`, `client_user_agent`, `client_ip_address`.

### LW Cookie consent integration

If `lw-cookie` is also active:

1. Open the site in incognito → no pixels load (banner showing).
2. Click "Accept marketing" → Meta / TikTok / Pinterest / Reddit / Snapchat / X / Google Ads load.
3. Click "Accept analytics" → GA4 / Bing load.
4. Refuse all → no marketing/analytics scripts at all.

## What's not tested yet

- PHPUnit unit tests with Brain\Monkey (could be added under `tests/Unit/`).
- End-to-end Playwright tests for the UI.
- CAPI integration test against a real Meta sandbox pixel.

The smoke test covers the critical wiring; everything else is currently manual.
