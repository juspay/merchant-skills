# Step 6: Architecture Review & Approval

## Rules

- This step is **read-only from the merchant's system** — do NOT make any file changes here.
- Every piece of information in this step must come from what was already collected in steps 01–05.
- **Do not proceed to step-07 until the merchant explicitly approves.**
- This step produces the `## Architecture` and `## Merchant System Touches` sections written into the plan.

## Your Task

Present the full integration architecture and every planned touch to the merchant's own system. Surface this clearly so the developer can review, correct, or stop before any code is generated.

## Sequence

### 1. Render Architecture Diagram

Build a text-based integration architecture from collected context:

```
┌─────────────────────────────────────────────────────────────────┐
│  Integration Architecture: {{$PRODUCT}} — {{date}}              │
└─────────────────────────────────────────────────────────────────┘

  CLIENT(S)                  MERCHANT BACKEND              JUSPAY
  ─────────                  ─────────────────             ──────
  {{$CLIENT_PLATFORMS or     {{$BACKEND_BASE_URL}}   →→→  Juspay API
   $PLATFORM if SDK}}                │                     ({{$PRODUCT}})
        │                            │
        │ calls payment              │ server-to-server
        │ interfaces                 │ REST calls
        ↓                            ↓
  e.g. POST /api/juspay/session   lib/juspay/  ←── new interface layer
       GET  /api/juspay/status/:id
{{if $HAS_WEBHOOKS}}
                                     ↑
                            Juspay Webhook POST
                            → POST {{$PLANNED_WEBHOOK_URL or $WEBHOOK_URL}}
{{/if}}
{{if $HAS_PERSISTENCE_SCHEMA or arch-decision includes db}}
                                     ↓
                              Merchant DB (existing or new schema)
{{/if}}
```

Fill every placeholder with the actual resolved value. Present this as a labeled block to the developer.

### 2. Interface Surface — What the Executor Will Create

List every file and endpoint the executor will create from scratch. This is additive only — new files, new routes:

**New backend interface files** (in `lib/juspay/`, `services/juspay/`, or equivalent for `$DETECTED_LANG`):
- Derive the file list from `$PRODUCT`, `$PRODUCT_TYPE`, `$HAS_WEBHOOKS`, and `$API_CAPABILITIES` (if api-only)
- Examples: `juspay-client.{ext}`, `session.{ext}`, `order-status.{ext}`, `webhook-handler.{ext}`, `payment-methods.{ext}`, `saved-cards.{ext}`

**New backend routes/endpoints** (wired into the merchant's existing router):
- List each endpoint: method + path + handler file
- Example: `POST /api/juspay/session → lib/juspay/session.ts`
- For api-only with multiple `$CLIENT_PLATFORMS`: note if any interfaces differ across platforms (e.g., mobile clients may need a different response shape)

**New frontend/SDK files** (SDK and hybrid products only):
- Payment screen component or SDK initialisation file
- Return/callback handler page

**New environment variables** to be appended to `.env`:
- `JUSPAY_API_KEY` — Juspay API key (resolved in executor)
- `JUSPAY_MERCHANT_ID` — `$MERCHANT_ID`
- `JUSPAY_CLIENT_ID` — `$CLIENT_ID`
- Any product-specific vars indicated by the docs

Present this as a formatted list. Be specific — use actual paths and env var names.

### 3. Merchant System Touches — What the Executor Will Modify

This section is a **callout** about changes to the merchant's EXISTING system. Every item here requires the developer's awareness and implicit consent:

```
⚠️  CHANGES TO YOUR EXISTING SYSTEM

The following and ONLY the following changes will be made to your
existing codebase. Nothing else will be modified without your
explicit approval during implementation.

  ROUTER / SERVER FILE
  └── {{detected router file, e.g. src/routes/index.ts}}
      Juspay payment routes will be registered here.
      (3–5 lines of route registration code added)

  ENV FILE
  └── {{.env or .env.local}}
      New keys will be appended:
      JUSPAY_API_KEY, JUSPAY_MERCHANT_ID, JUSPAY_CLIENT_ID
      {{+ any product-specific vars}}

  DATABASE  {{only if arch-decision involves DB}}
  └── {{migration path or ORM file}}
      {{describe: new table, or columns added to existing table}}
      ⚠️  You will be asked to confirm this separately before any
          schema change is written.

  NOTHING ELSE — no changes to existing models, business logic,
  API handlers, or internal services without your explicit approval.
```

If no DB changes are anticipated, omit the DATABASE section entirely.

### 4. Payment Flow Narrative

Write a 3–5 sentence plain-English description of how a payment will flow through this integration:

> "A payment begins when [client] calls `POST /api/juspay/session`. Your backend creates a Juspay order and returns [sdk payload / redirect URL] to the client. The client [launches SDK / redirects to checkout / calls Juspay API directly]. When payment completes, Juspay [sends a webhook to `$WEBHOOK_URL` | returns the result to `$RETURN_URL`]. Your system confirms the final status via `GET /api/juspay/status/:id` [and updates the DB]."

Adapt every bracketed part to the actual product and collected decisions.

### 5. Approval Gate

Present a summary card:

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Architecture Review
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Product:     {{$PRODUCT}} ({{$PRODUCT_TYPE}})
  Platform:    {{$PLATFORM / $CLIENT_PLATFORMS}}
  New files:   {{count of new files}} to be created
  New routes:  {{count}} endpoints
  Env vars:    {{count}} keys added to .env
  DB changes:  {{"None" or brief description}}
  Webhooks:    {{$HAS_WEBHOOKS ? "Yes — events: " + $WEBHOOK_EVENTS_SELECTED : "No — using order status API"}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

Ask for approval:

> "This is exactly what the integration will change in your system. During implementation, anything outside this list will require your explicit approval. Ready to proceed?"

Native select:
- `Yes — looks good, proceed to plan`
- `I want to change something`
- `Stop — cancel the integration`

**If "I want to change something":**
- Ask: "What would you like to adjust?" (freetext)
- Based on their answer, return to the appropriate earlier step (02 for product, 03 for platform/capabilities, 05 for webhooks/return URL) and re-run from there.
- After re-running, return to this step and re-render the architecture review.

**If "Stop":**
- Tell the user: "Integration planning stopped. No files have been modified. Run `/jp-planner` again when ready."
- Halt.

**If "Yes":** proceed.

### 6. Write Architecture to Plan

Append to `juspay-plan.md`:

```markdown
## Architecture

[paste the rendered architecture diagram from section 1]

## Interface Surface

### New Files
[paste the file list from section 2]

### New Routes
[paste the endpoint list from section 2]

### New Env Vars
[paste the env var list from section 2]

## Merchant System Touches

[paste the callout block from section 3]

## Payment Flow

[paste the narrative from section 4]
```

## Next Step

Load `./step-07-plan.md`.
