# Step 3: Platform Selection

## Rules

- Use **native select UI** — show only platforms present in `$DOC_MAP`.
- Pre-select the detected platform from step-01 if it matches a platform in `$DOC_MAP`.
- The `web` vs `iframe-web` choice IS the platform — it is not a sub-question.
- **Never skip this step for api-only products.** API-only products (EC-API, EC-headless) have their own clients (web, Android, iOS) that call backend interfaces. The integration must expose the right interfaces for each target.

## Your Task

Confirm which client platform(s) this integration targets.

## Sequence

### 1. Branch on Product Type

#### A. SDK / Hybrid products (`$PRODUCT_TYPE = sdk` or `hybrid`)

Use the standard SDK platform flow (sections 2–5 below).

#### B. API-only products (`$PRODUCT_TYPE = api-only`)

API-only products expose REST endpoints that the merchant's own clients call. The integration creates server-side interface files — but the *shape* of those interfaces depends on what clients will consume them.

Ask:

> "Your backend will call Juspay's APIs server-to-server. Which client platforms will call your payment backend? This determines which interfaces we generate."

Present as multi-select native UI:
- `web` — browser-based frontend (React, Vue, plain JS, etc.)
- `android` — native Android app
- `ios` — native iOS app
- `react-native` — React Native mobile app
- `flutter` — Flutter mobile app
- `backend-only` — server-to-server only; no frontend client needs interfaces from this backend

Store selected platforms as `$CLIENT_PLATFORMS[]`.

**For EC-API and EC-headless:** After the client platform selection, note:

> "For EC-API and EC-headless, your backend will need to expose interfaces beyond just order creation. We'll include interfaces for the capabilities you need based on what the docs expose."

Ask which capabilities are needed (multi-select, derived from `$DOC_MAP` endpoints — only show options present in the docs):

- `payment-methods` — fetch available payment methods for a customer/order
- `saved-cards` — fetch and manage a customer's saved cards
- `upi-vpa-validate` — validate a UPI VPA before initiating payment
- `wallet-balance` — fetch wallet balance for enabled wallets
- `emi-plans` — fetch EMI options for a given order amount
- `order-create-only` — only order creation (minimal integration)

Store selected capabilities as `$API_CAPABILITIES[]`.

Set `$PLATFORM = api-only`. Update plan:
```yaml
platform: "api-only"
clientPlatforms: {{$CLIENT_PLATFORMS as comma-separated string}}
```

Skip to **Next Step**.

---

### 2. Check for Single-Side Codebase (SDK/hybrid only)

Check whether both backend and frontend were detected in step-01.

**If only backend found:**

Present native select:
> "I can only find a **backend** in this project. A complete integration normally requires both sides. How would you like to proceed?"
>
> `[Continue with backend only | Stop — I'll add the frontend first]`

If "Stop" → halt: "Add the frontend to your project and re-run jp-planner when ready."

**If only frontend found:** same prompt with sides swapped.

**If both or neither found:** continue.

### 3. Detect Platform from Codebase (SDK/hybrid only)

Use the `$DETECTED_PLATFORM` from step-01. Check if it appears in `$DOC_MAP` platforms.

If detected and in doc map → present as pre-selected recommendation:

> "I detected your project is a **[$DETECTED_PLATFORM]** app (found `[signal file]`)."
>
> Native select: `[Yes, use $DETECTED_PLATFORM | No, let me choose]`

If not detected or not in doc map → show platform list from `$DOC_MAP`.

### 4. Platform List (SDK/hybrid — if no auto-detect)

Present as native select showing only platforms in `$DOC_MAP`:

```
Which platform are you integrating for?
> [platform options from $DOC_MAP]
```

### 5. Web vs iframe-web Disambiguation (SDK/hybrid only)

If the user selects `web` AND both `web` and `iframe-web` are present in `$DOC_MAP`:

> "Which web integration approach do you need?"
>
> Native select:
> - `web` — Direct SDK integration into your page (requires JavaScript SDK setup)
> - `iframe-web` — Hosted payment page embedded via iframe (minimal frontend code)

The selection IS `$PLATFORM`.

### 6. Update Plan

For SDK/hybrid, update `juspay-plan.md` frontmatter:
```yaml
platform: "$PLATFORM"
```

## Next Step

Load `./step-04-doc-discovery.md`.
