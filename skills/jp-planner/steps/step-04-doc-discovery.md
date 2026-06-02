# Step 4: Doc Structure Discovery

## Rules

- This step is **read-only and fully automated** — no user questions.
- All information comes from `$DOC_MAP` already built in step-02.
- If `$DOC_MAP` is empty or missing the selected platform, call `explore_product` again.
- Do NOT fetch individual doc page content here — that is the executor's job.

## Your Task

Extract the doc structure flags and ordered page list for the selected platform from `$DOC_MAP`.

## Sequence

### 1. Filter Doc Map to Selected Platform

From `$DOC_MAP`, extract the entry for `$PLATFORM` (or skip if `api-only`).

If the platform entry is missing → call:
```
juspay-docs-mcp:explore_product({ product: $PRODUCT })
```
and rebuild `$DOC_MAP`.

### 2. Extract Flags

From the platform's sections and pages, identify:

- `$HAS_WEBHOOKS` — true if any section is titled "Webhooks", "Webhook Configuration", or similar
- `$HAS_SDK` — true if `$PRODUCT_TYPE` is `sdk` or `hybrid`
- `$HAS_TEST_RESOURCES` — true if any page is titled "Test Cards", "Test Resources", "Simulator", or similar
- `$HAS_PREREQUISITES` — true if any section is titled "Prerequisites", "Getting Started", "Pre-Requisites"
- `$HAS_PLATFORM_SETUP` — true if any section is titled "Android Setup", "iOS Setup", "React Native Setup", etc.

**`$HAS_RETURN_URL` — product-type rule (not doc-title matching):**
- `sdk` or `hybrid` products → **always `true`**. SDK products always have a callback/return flow even if the doc page title doesn't say "Return URL". The SDK hands payment results back to the app which then needs a return path.
- `api-only` products → true only if doc pages explicitly mention "Return URL", "returnUrl", or "redirect". Most api-only products do not use return URLs.

Also derive the **primary entity name** `$ENTITY_NAME` used by this product — this is the concept the merchant's code will track:

| Product | `$ENTITY_NAME` |
|---|---|
| `hyper-checkout`, `ec-headless`, `ec-api`, `hyper-credit` | `order` |
| `payout` | `payout` |
| `juspay-billing`, `lotuspay` | `mandate` |
| `jusbiz` | `card` |
| `upi-plugin-sdk`, `upi-tpap-sdk` | `order` |
| _(any other)_ | `transaction` |

### 3. Extract Ordered Doc Pages

Collect all base integration pages in their numbered order (pages numbered "1. …", "2. …" in the doc map) plus any prerequisite/overview pages.

Store as `$DOC_PAGES[]` — an ordered list of `{ title, url }` objects. This is the fetch order the executor will use in Phase 3.

### 4. Update Plan

Append to `juspay-plan.md`:

```markdown
## Doc Pages (executor fetches in this order)
{{for each page in $DOC_PAGES}}
- {{page.title}}: {{page.url}}
{{/for}}
```

Update frontmatter:
```yaml
entityName: "{{$ENTITY_NAME}}"
```

> `$HAS_WEBHOOKS` and `$HAS_RETURN_URL` are in-memory flags only — they drive step-05 config collection but are NOT written to the plan frontmatter (the executor derives them from `webhookUrl`/`returnUrl` being non-empty).

## Next Step

Load `./step-05-dashboard.md`.
