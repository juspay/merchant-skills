# Step 3: Juspay Product & SDK Selection (doc-grounded)

> Replaces the generic "starter template" step. Here we lock the exact Juspay product, integration shape, and SDK/platform variant — grounded in the live docs, not guessed.

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🌐 Ground EVERYTHING in docs-mcp (`references/juspay-docs-mcp.md`): `list_products` → `explore_product` → `doc_fetch_tool`. Never construct doc URLs; never assert product facts from memory.
- 🧭 `../products/` is a NON-AUTHORITATIVE catalog (per-product *Key concepts* / platforms) — read the chosen product's entry for orientation, but confirm every slug/shape/platform against the live catalog via docs-mcp.
- ✅ Collaborative; confirm the choice with the user.
- ⚠️ NO TIME ESTIMATES. 💾 Only save when the user confirms they want to continue.

## YOUR TASK

Confirm the Juspay product(s), integration shape, and platform/SDK variant for this integration, with doc citations.

## SELECTION SEQUENCE

### 1. Start from the PRD + codebase

The PRD named the product(s) in scope and the surfaces; the codebase scan (from jp-prd / re-confirm here) tells you the platform. Reconcile both.

### 2. Confirm via docs-mcp

- If the product isn't certain: `list_products(category?)` and converge with the user.
- `explore_product(<slug>)` to get the authoritative doc index — extract: product title, **integration shape** (hosted page / headless SDK / direct API), supported **platforms**, and the **base integration pages** (in order).
- Classify `$PRODUCT_TYPE` (`sdk` | `api-only` | `hybrid`) from the doc structure.
- **Split the integration pages into two tracks** so neither gets shortchanged downstream:
  - **Backend (S2S) pages** — session/order creation, order status, refunds, webhooks.
  - **Client SDK pages** — `initiate` plus the **per-payment-method `process` payload** pages (UPI collect/intent, card, netbanking, wallet, …). For SDK/headless products these are the most non-standard, error-prone surface; enumerate one entry per in-scope method. They are NOT optional appendix reading to be deferred to the executor.

### 3. Resolve platform / SDK variant

- For SDK products: confirm the platform (web/iframe-web/android/ios/flutter/react-native/cordova/capacitor) against the codebase, then disambiguation the docs require (e.g. Android Java vs Kotlin; iOS Swift vs Obj-C; web vs iframe-web).
- For UPI specialized SDKs: ask `issuing_psp` (e.g. YBL, ICICI, AXIS) and `auth_type` (cat | rsa | direct-jws) — these select the SDK variant.
- For api-only: no platform question; confirm backend language from the codebase.

### 4. Single-side check

If the product needs both backend and client and only one side exists in the codebase, surface it and ask whether to proceed single-side (with an other-side TODO recorded later) or stop.

### 5. Content to append

```markdown
## Juspay Product & Integration Shape

### Selected Product
- **Product:** {{title}} (`{{slug}}`) — *(source URL)*
- **Integration shape:** {{hosted page | headless SDK | direct API}}
- **Product type:** {{sdk | api-only | hybrid}}

### Platform / SDK Variant
- **Platform(s):** {{...}} — *(source URL)*
- **Variant details:** {{language; issuing_psp/auth_type for UPI; web vs iframe-web}}

### Base Integration Pages (authoritative, in order)
**Backend (S2S):** {{numbered list of doc page titles + URLs — session/order, order status, refunds, webhooks}}
**Client SDK:** {{`initiate` page + URL}}
**Per-method `process` payloads (SDK/headless):** {{one entry per in-scope method — method name → page title + URL}}

### Rationale
{{why this product/shape fits the PRD's goal, methods, and surfaces}}
```

## Collaboration Options

Present these as native UI choices when available; otherwise ask a concise direct question:
- **Explore deeper** — compare alternative products/shapes/variants and their trade-offs (inline, doc-grounded).
- **Perspectives** — weigh from integration shape / developer-experience / operations angles (inline).
- **Continue** — append content, set `stepsCompleted: [1,2,3]`, update `juspay_products` frontmatter, load `./step-04-decisions.md`.

FORBIDDEN to load the next step until the user confirms Continue.

## NEXT STEP

After the user confirms Continue, load `./step-04-decisions.md`.
