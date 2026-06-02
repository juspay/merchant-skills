# Step 5: Dashboard & Config Discovery

## Rules

- Check existing merchant config via MCP **before** asking the user anything.
- Never read `.env` secret values — only check for key name presence.
- Dashboard configuration needs are driven by what `$DOC_MAP` flags say the product requires — never hardcoded to "webhook + return URL".
- Use **native select or text input UI** only for values that cannot be discovered automatically.
- All dashboard navigation info must come from `juspay-docs-mcp` — never hardcode dashboard URLs.
- **Webhooks are opt-in.** Never assume the merchant wants webhook support. Ask explicitly.

## Your Task

Discover the merchant's current configuration, ask whether they want webhook support, and surface dashboard navigation paths for anything that needs to be configured.

## Sequence

### 1. Build the Config Needs List

Using flags from step-04, determine the base set of things that may need configuration:

| Condition | Candidate item |
|---|---|
| `$HAS_RETURN_URL = true` | `"return-url"` |
| `$PRODUCT = juspay-billing` or `lotuspay` | `"mandate-settings"` |
| `$PRODUCT = payout` | `"payout-settings"` (if applicable per docs) |

Note: `"webhook"` is NOT added automatically — it requires merchant opt-in (step 2 below).

### 2. Webhook Opt-In

Ask the merchant explicitly, regardless of whether the docs describe webhook support:

> "Do you want to implement **webhook support**? Webhooks give you reliable async payment confirmation by having Juspay POST to your server when payment status changes. They require a publicly accessible endpoint.
>
> They are **optional** — you can rely on the order status polling API instead."

Native select:
- `Yes — implement webhook support`
- `No — I'll use the order status API for payment confirmation`

**If "Yes":**
- Set `$HAS_WEBHOOKS = true`
- Add `"webhook"` to the config needs list
- Proceed to fetch available webhook events from the dashboard docs (step 3 below)

**If "No":**
- Set `$HAS_WEBHOOKS = false`
- Do NOT add `"webhook"` to the config needs list
- Do NOT ask for a webhook URL
- Record in plan: `webhookUrl: ""` and `hasWebhooks: false`

### 3. Check Existing Merchant Config via MCP

Call:
```
juspay-mcp:juspay_get_general_settings()
```
Extract any configured `returnUrl` (or equivalent). Note which settings are already present.

If `"webhook"` is in the config needs list (merchant opted in):
```
juspay-mcp:juspay_get_webhook_settings()
```
Extract `$WEBHOOK_URL` (empty if not configured) and `$WEBHOOK_EVENTS` (currently subscribed events).

### 4. Dashboard Nav Discovery (for each unconfigured item)

For each item in `$DASHBOARD_CONFIG_NEEDED` that is **not yet configured** on the merchant account, fetch the relevant dashboard docs page.

**4a. Fetch dashboard product structure (once, shared across items):**
```
juspay-docs-mcp:explore_product({ product: "dashboard" })
```
If this fails, try:
```
juspay-docs-mcp:list_products({ category: "DASHBOARD" })
```
then `explore_product` on the returned slug.

**4b. For each unconfigured item, fetch the relevant page:**

| Item | Page to find |
|---|---|
| `webhook` | Page title contains "Webhook" or "Settings" |
| `return-url` | Page title contains "General Settings" or "Return URL" |
| `mandate-settings` | Page title contains "Billing", "Mandate", or "Settings" |
| `payout-settings` | Page title contains "Payout" or "Settings" |

```
juspay-docs-mcp:doc_fetch_tool({ url: "<matched page URL>" })
```

From each fetched page extract:
- `$NAV_PATH` — navigation path (e.g. "Settings → Webhooks")
- `$DIRECT_LINK` — dashboard deep-link if present
- `$AVAILABLE_WEBHOOK_EVENTS` — for webhook: full list of subscribable events from the docs page
- Any specific instructions for this product

### 5. Webhook Event Selection (only if merchant opted in and `"webhook"` is in config list)

If `$AVAILABLE_WEBHOOK_EVENTS` was extracted from the docs, present a multi-select:

> "Which webhook events do you want to subscribe to?"
>
> [Show the full list of available events from `$AVAILABLE_WEBHOOK_EVENTS`]

Allow the merchant to select all relevant events. Store selected events as `$WEBHOOK_EVENTS_SELECTED[]`.

If `$AVAILABLE_WEBHOOK_EVENTS` could not be fetched, ask in freetext:

> "Which webhook events do you want to enable? (List event names, or type 'all' for all available events)"

### 6. Collect Unconfigured Values from User

**Webhook URL** (only if `"webhook"` is in config list and `$WEBHOOK_URL` is empty):

> "No webhook URL is configured. What path will your webhook handler use?"
>
> Text input, suggested default: `/api/juspay/webhook`

Compute full URL once `$BACKEND_BASE_URL` is known (step 7 below): `$BACKEND_BASE_URL + webhook-path`.

**Return URL** (if `$HAS_RETURN_URL = true` and `$RETURN_URL` is empty from general settings):

> "After a payment completes, Juspay redirects the user to a return URL in your app.
> For SDK products the SDK hands the payment result back to the app — this URL is where that handoff lands.
> What path in your app should handle the payment return?"
>
> Text input, suggested default: `/payment-return`

Store the path as `$RETURN_PATH`. Compute `$PLANNED_RETURN_URL = $BACKEND_BASE_URL + $RETURN_PATH` once the backend URL is confirmed.

**For other items** (mandate-settings, payout-settings): surface the dashboard nav instructions and ask the user to confirm they've reviewed the docs. Do NOT ask for URL values unless the docs say a URL is required.

### 7. API Key Check

Scan `.env`, `.env.local`, `.env.example` for the API key env var name used by this product (typically `JUSPAY_API_KEY`). Note presence only.

Set `$API_KEY_SOURCE`:
- `"env"` — key name found in env file
- `"new"` — not found (executor will provision)

### 8. Backend Base URL

If `$BACKEND_BASE_URL` was detected in step-01 → confirm in one line:

> "Backend detected at `$BACKEND_BASE_URL`. Is that correct?"
>
> Native select: `[Yes | No, let me enter it]`

If not detected or user says no → text input with example: `http://localhost:3001`

**URL parsing:** The user's response may contain non-URL text (e.g., "https://x.com tunneling localhost:3001"). Always extract the first valid HTTPS/HTTP URL from their response, then confirm:
> "I'll use `<extracted URL>` as your backend base URL. Is that correct?"
Wait for explicit confirmation before storing. Only then compute `$BACKEND_BASE_URL`.

If `$PRODUCT_TYPE = api-only` with no client SDK → backend base URL may not apply; ask if relevant.

**After confirming `$BACKEND_BASE_URL`**, compute any planned URLs that depend on it:
- `$PLANNED_WEBHOOK_URL` = `$BACKEND_BASE_URL` + webhook path (only if `$HAS_WEBHOOKS = true`)
- `$PLANNED_RETURN_URL` = `$BACKEND_BASE_URL` + return path (if return URL was needed)

### 9. Update Plan

Update `juspay-plan.md` frontmatter:
```yaml
backendBaseUrl: "{{$BACKEND_BASE_URL}}"
apiKeySource: "{{$API_KEY_SOURCE}}"
hasWebhooks: {{$HAS_WEBHOOKS}}
webhookUrl: "{{$WEBHOOK_URL if already configured, else $PLANNED_WEBHOOK_URL if hasWebhooks=true, else empty string}}"
webhookEventsSelected: "{{$WEBHOOK_EVENTS_SELECTED as comma-separated string, or empty string}}"
returnUrl: "{{$RETURN_URL if already configured, else $PLANNED_RETURN_URL, else empty}}"
```

Append to `juspay-plan.md`:
```markdown
## Dashboard Config Hints
{{for each item in $DASHBOARD_CONFIG_NEEDED where nav was discovered}}
### {{item}}
- nav: "{{$NAV_PATH}}"
- link: "{{$DIRECT_LINK or 'not available'}}"
{{if item == "webhook"}}
- eventsSelected: {{$WEBHOOK_EVENTS_SELECTED}}
{{/if}}
{{/for}}
```

## Next Step

Load `./step-06-arch-review.md`.
