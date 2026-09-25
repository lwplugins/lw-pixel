# LW Pixel — React admin REST API

Namespace `lw-pixel/v1`, prefix `/admin`. Every route requires
`manage_options`. Authentication uses the WordPress REST cookie with the
`X-WP-Nonce` header (sent by `@wordpress/api-fetch`), so the write routes need
no nonce of their own. Code: `includes/Rest/Admin/*`. The UI reads responses
only through `src/data/shapes.js`.

Errors are `WP_Error` JSON: `{ code, message, data: { status, fields? } }`.

| Code | Status | When |
|---|---|---|
| `lw_pixel_invalid` | 400 | Validation failed. `data.fields` = `{ key: [messages] }`. **Nothing was saved.** |
| `lw_pixel_too_large` | 413 | Body over the route limit (settings 256 KB, custom events 16 KB). |
| `lw_pixel_not_found` | 404 | Custom event ID is not a published/draft `lw_pixel_event`. |
| `lw_pixel_unknown_migrator` | 404 | Import source unknown or not detected. |
| `lw_pixel_save_failed` / `lw_pixel_delete_failed` | 500 | Core post write failed. |

## Settings

### `GET /admin/settings`

```json
{
  "options": { "fb_enabled": false, "fb_pixel_id": "", "fb_capi_token": null, "...": "..." },
  "meta": {
    "pixels": [ { "id": "fb", "label": "Meta (Facebook) Pixel", "enabled": false, "configured": false, "default_category": "marketing" } ],
    "secrets": { "fb_capi_token": { "set": true, "source": "option", "hint": "…wxyz" } },
    "locked": { "chatgpt_api_key": "LW_PIXEL_CHATGPT_API_KEY" },
    "defaults": { "...": "DefaultOptions::all()" },
    "integrations": { "woocommerce": true, "lw_cookie": false, "cf7": true, "...": false },
    "forms_detected": { "form_cf7": true, "...": false },
    "woocommerce_active": true,
    "lw_cookie_active": false,
    "can_unfiltered_html": true,
    "enums": { "compliance_ldu_mode": [ "auto", "force_california" ] },
    "consent_categories": [ "marketing", "analytics", "functional" ],
    "max_code_bytes": 65536,
    "docs_url": "https://github.com/lwplugins/lw-pixel#readme"
  }
}
```

- `options`: every `DefaultOptions` key, cast to its default's type. Keys in
  `Options::SECRET_KEYS` (`fb_capi_token`, `ga4_mp_api_secret`,
  `chatgpt_api_key`) are always `null`. Their state is in `meta.secrets`:
  `source` is `option`, `constant` or `none`, and `hint` is `…` plus the last
  4 characters, or only `…` for secrets under 16 characters.
- `meta.locked`: secrets pinned by a wp-config constant. Only
  `chatgpt_api_key` / `LW_PIXEL_CHATGPT_API_KEY` exists today, the same test as
  `Server\ChatGptCAPI::api_key()`.
- `meta.pixels`: every registered pixel, third-party ones included, with its
  **saved** status. `default_category` is `''` for a pixel with no default
  consent list, which means it is not gated unless a list names it.

### `POST /admin/settings`

The body is a flat, **partial** `{ option_key: value }` object. Only the keys
you send change: a switch that is not sent is never turned off. The save is
atomic: if any key is invalid, the response is a 400 with `fields` and nothing
is stored. On success the response has the same shape as the GET.

Validation per key (`Settings/FieldSchema` + `Settings/ValueParser`):

| Keys | Rule |
|---|---|
| booleans (`*_enabled`, `event_*`, `woo_*`, `form_*`, …) | `true`/`false`, `1`/`0`, `"1"`/`"0"`. Anything else is an error (e.g. `"false"`). |
| `fb_pixel_id` | `^\d{5,20}$` |
| `ga4_measurement_id` / `gads_conversion_id` / `gtm_container_id` | upper-cased, then `^G-[A-Z0-9]{4,20}$` / `^AW-\d{5,15}$` / `^GTM-[A-Z0-9]{4,12}$` |
| `snapchat_pixel_id` | lower-cased UUID |
| other IDs (`tiktok_`, `pinterest_`, `bing_`, `reddit_`, `x_`, `chatgpt_pixel_id`, `fb_test_event_code`, `gads_conversion_label`) | `^[A-Za-z0-9_-]{1,64}$` |
| Any ID | `""` clears it. |
| `event_scroll_thresholds` | comma list of integers from 1 to 100 (max. 20), stored sorted and unique: `"25,50,75,100"` |
| `event_time_thresholds` | comma list of integers from 1 to 86400 |
| `event_download_extensions` | comma list of `[a-z0-9]{1,10}`, lower-cased, leading dot dropped |
| `event_thankyou_urls` | one fragment per line, max. 50 lines of 200 characters, blank lines dropped |
| `woo_content_id_prefix` | text, max. 64 characters |
| `compliance_ldu_mode` | `auto` or `force_california` |
| `consent_marketing_pixels`, `consent_analytics_pixels`, `consent_unclassified_pixels` | arrays of registered pixel IDs. After the save, a pixel may appear in only one list. Send all three when you move a pixel. |
| `head_code`, `body_open_code`, `footer_code` | raw, max. 64 KB, trimmed, backslashes kept. **Requires `unfiltered_html`**, otherwise a field error. |
| secrets | `null` keeps the stored value, `""` removes it, and any other string (printable ASCII, no spaces, max. 512) replaces it. A locked key is a field error. |
| removed in 1.3.0: `debug_mode`, `gads_remarketing`, `pinterest_em_enabled`, `consent_mode` | rejected as unknown |
| unknown keys | field error `Unknown setting.` |

The validated keys are merged over the stored options and passed through
`Admin\SettingsSanitizer::sanitize()`. That is the same sanitizer the CLI and
the Site Manager abilities use.

## Custom events

The storage has not changed: one `lw_pixel_event` post per event (`publish`
means on, `draft` means off) plus the `_lw_pixel_custom_event` meta record.

Event shape:

```json
{ "id": 12, "title": "Label", "enabled": true, "modified": "2026-09-26 10:00:00",
  "data": { "event_name": "SignupClick", "trigger_type": "click", "selector": ".cta",
            "scroll_pct": 50, "time_seconds": 30, "page_pattern": "/pricing*",
            "value": "9.99", "currency": "HUF", "fire_once": false } }
```

| Method | Route | Body / result |
|---|---|---|
| GET | `/admin/custom-events` | `{ events: [event], meta: { defaults, triggers, run_limit: 100, max_listed: 500 } }` (newest first) |
| POST | `/admin/custom-events` | Fields → 201 + event. Created on unless `enabled: false` is sent. |
| GET | `/admin/custom-events/{id}` | event |
| POST | `/admin/custom-events/{id}` | Partial fields → event |
| DELETE | `/admin/custom-events/{id}` | Moves it to the trash → `{ deleted: true, id }` |

Fields: `title` (max. 200; empty means the event name), `enabled`,
`event_name` (`^[A-Za-z][A-Za-z0-9_]{0,39}$`, required on create),
`trigger_type` (`page_load|click|scroll|time`), `selector` (max. 500, no
`{ } <`; the UI also parses it with `querySelector`), `scroll_pct` (1–100),
`time_seconds` (1–86400), `page_pattern` (empty, or a path starting with `/`
or `*` without spaces), `value` (empty or `\d{1,9}(\.\d{1,4})?`), `currency`
(3 letters, upper-cased), `fire_once`. Only the fields you send are validated.
A click trigger needs a selector whenever you send `trigger_type` or
`selector`, and always on create.

## Tools

| Method | Route | Result |
|---|---|---|
| GET | `/admin/migrators` | `{ migrators: [ { id, label, available, preview: [ { key, secret, from, to } ] } ] }`. For secret keys, `from`/`to` are booleans (set or not). |
| POST | `/admin/migrators/{id}/run` | `{ id, updated: [keys], skipped: [source keys] }`. Replaces the old GET + nonce link. |
| GET | `/admin/system-report` | `{ report: { environment, pixels, integrations, options } }`. Secrets and custom code appear only as `***REDACTED*** (N chars)`. |
| POST | `/admin/test/chatgpt` | Always 200: `{ ok, status, message }` from `Server\ChatGptCAPI::test_connection()`, a validate-only request built from the **saved** pixel ID and key. The message never contains the key. |

## Screen

`SettingsPage` renders `<div id="lw-pixel-root">` outside `.wrap` and loads
`build/index.js` + `build/index.css`, with translations from
`languages/lw-pixel-hu_HU-*.json`. Boot data comes from
`window.lwPixelAdmin = { version, namespace, docsUrl }`. The body class is
`lw-pixel-screen`. Classic hash links (`#facebook`, `#gtm`, `#tiktok`,
`#system-report`, …) map to the new tabs.
