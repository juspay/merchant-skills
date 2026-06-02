# Step 5: Dashboard & Config Discovery

## Rules

- Check existing merchant config via MCP **before** asking the user anything.
- Never read `.env` secret values — only check for key name presence.
- Dashboard configuration needs are driven by what `$DOC_MAP` flags say the product requires — never hardcoded to "webhook + return URL".
- Use **native select or text input UI** only for values that cannot be discovered automatically.
- All dashboard navigation info must come from `juspay-docs-mcp` — never hardcode dashboard URLs.

## Your Task

Discover the merchant's current configuration for whatever the selected product requires, and surface dashboard navigation paths for anything that needs to be configured.

## Sequence

### 1. Build the Config Needs List

Using flags from step-04, determine `$DASHBOARD_CONFIG_NEEDED[]` — the list of things this product requires to be configured:

| Condition | Add to list |
|---|---|
| `$HAS_WEBHOOKS = true` | `"webhook"` |
| `$HAS_RETURN_URL = true` | `"return-url"` |
| `$PRODUCT = juspay-billing` or `lotuspay` | `"mandate-settings"` |
| `$PRODUCT = payout` | `"payout-settings"` (if applicable per docs) |

### 2. Check Existing Merchant Config via MCP

Call:
```
juspay-mcp:juspay_get_general_settings()
```
Extract any configured `returnUrl` (or equivalent). Note which settings are already present.

If `"webhook"` is in `$DASHBOARD_CONFIG_NEEDED`:
```
juspay-mcp:juspay_get_webhook_settings()
```
Extract `$WEBHOOK_URL` (empty if not configured) and `$WEBHOOK_EVENTS`.

### 3. Dashboard Nav Discovery (for each unconfigured item)

For each item in `$DASHBOARD_CONFIG_NEEDED` that is **not yet configured** on the merchant account, fetch the relevant dashboard docs page to get navigation instructions and direct links.

**3a. Fetch dashboard product structure (once, shared across items):**
```
juspay-docs-mcp:explore_product({ product: "dashboard" })
```
If this fails, try:
```
juspay-docs-mcp:list_products({ category: "DASHBOARD" })
```
then `explore_product` on the returned slug.

**3b. For each unconfigured item, fetch the relevant page:**

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
- `$EVENTS_REQUIRED` — for webhook: recommended events to subscribe
- Any specific instructions for this product

### 4. Collect Unconfigured Values from User

For each unconfigured item that requires a URL or path from the user:

**Webhook URL** (if `"webhook"` in list and `$WEBHOOK_URL` is empty):

> "No webhook URL is configured. What path will your webhook handler use?"
>
> Text input, suggested default: `/api/juspay/webhook`

Compute full URL once `$BACKEND_BASE_URL` is known (step 6 below): `$BACKEND_BASE_URL + webhook-path`.

**Return URL** (if `$HAS_RETURN_URL = true` and `$RETURN_URL` is empty from general settings):

> "After a payment completes, Juspay redirects the user to a return URL in your app.
> For SDK products the SDK hands the payment result back to the app — this URL is where that handoff lands.
> What path in your app should handle the payment return?"
>
> Text input, suggested default: `/payment-return`

Store the path as `$RETURN_PATH`. Compute `$PLANNED_RETURN_URL = $BACKEND_BASE_URL + $RETURN_PATH` once the backend URL is confirmed.

**For other items** (mandate-settings, payout-settings): surface the dashboard nav instructions and ask the user to confirm they've reviewed the docs. Do NOT ask for URL values unless the docs say a URL is required.

### 5. API Key Check

Scan `.env`, `.env.local`, `.env.example` for the API key env var name used by this product (typically `JUSPAY_API_KEY`). Note presence only.

Set `$API_KEY_SOURCE`:
- `"env"` — key name found in env file
- `"new"` — not found (executor will provision)

### 6. Backend Base URL

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
- `$PLANNED_WEBHOOK_URL` = `$BACKEND_BASE_URL` + webhook path (if webhook was needed)
- `$PLANNED_RETURN_URL` = `$BACKEND_BASE_URL` + return path (if return URL was needed)

### 7. Update Plan

Update `juspay-plan.md` frontmatter:
```yaml
backendBaseUrl: "{{$BACKEND_BASE_URL}}"
apiKeySource: "{{$API_KEY_SOURCE}}"
webhookUrl: "{{$WEBHOOK_URL if already configured, else $PLANNED_WEBHOOK_URL, else empty}}"
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
- eventsRequired: {{$EVENTS_REQUIRED}}
{{/if}}
{{/for}}
```

## Next Step

Load `./step-07-plan.md`.
