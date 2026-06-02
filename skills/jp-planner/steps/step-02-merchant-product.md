# Step 2: Merchant Context & Product Selection

## Rules

- Use **native select UI** for all choices — no free-text product entry.
- All product knowledge comes from `{skill-root}/products/` — never from memory or training data.
- Call `explore_product` only after product is confirmed.
- Write confirmed values to plan before proceeding.

## Your Task

Establish merchant context via MCP, load the product catalog, and confirm which Juspay product to integrate.

## Sequence

### 1. Get Merchant Context

Call:
```
juspay-mcp:juspay_get_merchant_details()
```

Extract and store:
- `$MERCHANT_ID` — from `merchantId` field
- `$INTEGRATION_TYPE` — from `integrationType` array, take first element (e.g. `["PP"]` → `"PP"`)
- `$CLIENT_ID` — default to `$MERCHANT_ID`; ask if different:

  > "Your Client ID is typically the same as your Merchant ID (`$MERCHANT_ID`). Is that correct, or do you use a different Client ID?"
  >
  > Present as native select: `[Yes, use $MERCHANT_ID | No, I'll enter a different one]`

### 2. Load Product Catalog

Read all files from `{skill-root}/products/`. Each file defines: product ID, name, type, platforms, use cases, intent signals.

Store as `$PRODUCT_CATALOG`.

### 3. Infer Recommendation from Integration Type

Map `$INTEGRATION_TYPE` to a recommended product:

| `$INTEGRATION_TYPE` | Recommended product |
|---|---|
| `PP` | `hyper-checkout` |
| `ec_sdk` | `ec-headless` |
| `ec_api` | `ec-api` |
| _(anything else or absent)_ | No inference — show full list |

If a recommendation is found, present it first:

> "Based on your account configuration (`$INTEGRATION_TYPE`), I recommend **[Product Name]**."
>
> Native select: `[Yes, use [Product Name] | No, show me all products]`

If "show all" or no recommendation, go to step 4.

### 4. Present Full Product List

If needed, present all products from `$PRODUCT_CATALOG` as a native select. Group by type for clarity:

**Checkout UI (SDK):** HyperCheckout, EC Headless
**API-only (server-side):** EC API, Payout, Juspay Billing, JusBiz
**UPI:** UPI Plugin SDK, UPI TPAP SDK
**Other:** HyperCredit, LotusPay

Wait for selection.

### 5. Build Doc Map

Call:
```
juspay-docs-mcp:explore_product({ product: $PRODUCT })
```

From the response, extract and store as `$DOC_MAP`:
- Product title and description
- All platforms with their IDs and page lists
- For each page: title, URL (`md content link`)

Classify `$PRODUCT_TYPE`:
- If only API/server pages → `api-only`
- If platform SDK pages present → `sdk`
- If both → `hybrid`

### 6. Update Plan

Update `juspay-plan.md` frontmatter:
```yaml
product: "$PRODUCT"
productType: "$PRODUCT_TYPE"
merchantId: "$MERCHANT_ID"
clientId: "$CLIENT_ID"
```

## Next Step

Load `./step-03-platform.md`.
