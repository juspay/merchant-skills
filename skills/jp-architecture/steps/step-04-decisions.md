# Step 4: Core Integration Decisions

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🌐 Ground API/SDK shapes in docs-mcp; fetch the base integration pages, the webhooks/order-status pages, **and the per-method client `process` payload pages** before deciding (`doc_fetch_tool`). Cite URLs. Never invent field names.
- ⚖️ **Equal rigor for client and backend.** The client-side `process` payload is extracted to field level for EVERY in-scope payment method — not deferred to the executor with "re-fetch later." Deferring it is precisely how method-payload bugs incubate.
- ✅ Facilitate decisions WITH the user; present options and trade-offs, not just recommendations.
- ⚠️ NO TIME ESTIMATES. 💾 Only save when the user confirms they want to continue, category by category.

## YOUR TASK

Make the integration decisions that downstream implementation depends on. Walk the categories; for each, present doc-grounded options, decide together, record with rationale and source URLs.

For account-specific facts (is a webhook/return URL already configured? which events? which integration stages to cover?), use what was recorded from `juspay-mcp` upstream. If it's missing and you need it: in **connected** mode read it now (`juspay_get_webhook_settings`, `juspay_get_general_settings`, `juspay_integration_monitoring_status`); in **manual** mode ask the user or mark it *to confirm in the dashboard*. Never block (see `references/juspay-mcp.md`). Tag provenance.

**Portal configuration discovery.** Whenever a setting must be configured in the Juspay dashboard (webhook URL, return URL, API-key creation) — especially in **manual** mode where it can't be read live — discover the dashboard docs via `docs-mcp` and capture the exact **navigation path + deep link + events** (see "Dashboard configuration docs" in `references/juspay-docs-mcp.md`). Record these as the Portal Configuration block below; they become `manual-dashboard` tasks in the execution checklist (step 8).

## DECISION CATEGORIES

Address **only the categories this integration actually needs** — don't manufacture decisions for things the product/flows don't require. For example: skip **webhooks** for synchronous/redirect-only flows with no async notification (rely on order-status reconciliation); skip the **data model** if order state lives elsewhere or an existing schema suffices unchanged; skip **portal configuration** if nothing must be set in the dashboard; native setup is decided later (step 6) and only for native surfaces. For each category that *does* apply, present the decision, the doc-grounded options, and capture the choice. Note which categories you deliberately skipped and why.

### 1. Session / order creation
- Backend session/order creation flow and the request fields required (from docs).
- What the client receives (e.g. `sdkPayload`) and how it launches the payment.

### 1b. Client `process` payload — per payment method *(SDK/headless products)*
- For **each** in-scope method (UPI collect/intent, card, netbanking, wallet, …), fetch the method's `process` page and extract the **exact `process` request shape**: required fields, field types, enums, and method-specific constraints. Record per method, with the source URL.
- This carries the same field-level rigor as the backend APIs. Do not collapse "all methods" into one generic payload, and do not push the extraction to the executor.

### 2. Credentials & secrets handling
- Which credentials are needed (api key, `merchant_id`, `client_id`) and how they're stored — env vars, never in code or logs. (Provisioning happens in `jp-executor`; here, decide the *strategy*.)
- `client_id` default and override policy.

### 3. Webhook handling & signature verification
- Webhook endpoint shape, event types, and payload structure (from docs).
- **Signature/auth verification** approach (do not skip).
- **Idempotency**: how redelivered events are de-duplicated.

### 4. Order-status reconciliation (source of truth)
- Server-to-server Order Status API as the authority; the client/SDK result is never trusted alone.
- Status mapping: Juspay statuses → app-internal order statuses.

### 5. Data model
- Order/payment schema (extend existing vs new table) and the fields to persist (from docs constraints). Decide format only; executor implements.

### 6. Environments & error handling
- Sandbox vs production hosts and credential sets; go-live gating; how key type/stage is kept aligned with
  the configured host. **Default to production** for the integration unless the PRD or user explicitly
  selects sandbox.
- Error-code surface (from docs) and how each class is handled/surfaced; default provider-error handling
  must preserve the full provider error body for debugging unless a field is explicitly redacted.

### Per-decision record

```markdown
- **Decision:** {{what}}
- **Choice:** {{decision}} — *(source URL)*
- **Rationale:** {{why}}
- **Affects:** {{FRs / components}}
```

Check cascading implications after each major decision.

### Content to append

```markdown
## Core Integration Decisions

### Session / Order Creation
{{decisions + source URLs}}

### Client Process Payloads — per method *(SDK/headless only)*
{{one block per in-scope method: method name → exact `process` request fields, types, enums, constraints → source URL}}

### Credentials & Secrets
{{strategy}}

### Webhooks & Signature Verification *(omit if not needed)*
{{endpoint, events, signature approach, idempotency}}

### Order-Status Reconciliation
{{reconciliation flow + Juspay→app status mapping}}

### Data Model *(omit if not needed)*
{{schema decisions}}

### Environments & Error Handling
{{sandbox/prod, error mapping}}

### Portal Configuration (dashboard)
{{one entry per setting that must be configured in the dashboard — what to set, navigation path, deep link (if any), events — each with its docs source URL. Omit the section if no portal config is needed.}}

### Decision Impact
{{implementation sequence + cross-dependencies}}
```

## Collaboration Options

Present these as native UI choices when available; otherwise ask a concise direct question:
- **Explore deeper** — explore alternatives/edge cases for any decision (inline, doc-grounded).
- **Perspectives** — security / ops / developer-experience review of trade-offs (inline).
- **Continue** — append content, set `stepsCompleted: [1,2,3,4]`, load `./step-05-patterns.md`.

FORBIDDEN to load the next step until the user confirms Continue.

## NEXT STEP

After the user confirms Continue, load `./step-05-patterns.md`.
