# Step 5: Implementation Patterns & Consistency Rules

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 💬 FOCUS on patterns that prevent inconsistent implementation across agents/developers — HOW, not WHAT.
- ✅ Facilitate decisions with the user. ⚠️ NO TIME ESTIMATES. 💾 Only save when the user confirms they want to continue.

## YOUR TASK

Define the consistency rules `jp-executor` (and any agent) must follow so the integration code is uniform and safe.

## PATTERN CATEGORIES

Facilitate a decision for each that applies:

### 1. Configuration & secrets
- Env var naming convention (e.g. `JUSPAY_API_KEY`, `JUSPAY_MERCHANT_ID`, `JUSPAY_CLIENT_ID`, webhook auth).
- Where config is read; never hardcode credentials; never log secrets.

### 2. Webhook & idempotency
- Idempotency key/strategy for redelivered events.
- Signature verification placement (middleware vs handler).
- Webhook response contract (status + body).

### 3. Status mapping & reconciliation
- Canonical Juspay-status → app-status mapping (single source).
- Reconciliation always server-to-server; client result advisory only.

### 4. API & data formats
- Request/response handling, error envelope, JSON field casing.
- For SDK/headless products, per-method client `process` payload handling: one method-specific shape per
  payment method, never one generic `paymentMethods` blob.
- Money/amount representation and currency format.
- DB naming (tables/columns) for order/payment data.

### 5. Error handling & logging
- How errors from `$ERROR_CODES` map to user-facing vs internal.
- Provider/API error handling defaults: preserve the **full provider error body** (`error_code`,
  `error_message`, `developer_message`, and peers the docs define) in logs/internal responses unless the
  architecture explicitly redacts a field; do not collapse to a generic message by default.
- Logging format and what must never appear in logs (credentials, PAN).

### Content to append

```markdown
## Implementation Patterns & Consistency Rules

### Configuration & Secrets
{{env var names + handling rules}}

### Webhook & Idempotency
{{idempotency strategy, signature verification, response contract}}

### Status Mapping & Reconciliation
{{canonical mapping table; reconciliation rule}}

### API & Data Formats
{{response/error envelope, per-method client payload handling, amount/currency, DB naming}}

### Error Handling & Logging
{{error mapping; full provider error-body surfacing defaults; logging do's/don'ts}}

### Enforcement
All implementation MUST: {{mandatory rules}}
```

## Collaboration Options

Present these as native UI choices when available; otherwise ask a concise direct question:
- **Explore deeper** — surface additional conflict points / edge cases (inline).
- **Perspectives** — security / ops / developer-experience review (inline).
- **Continue** — append content, set `stepsCompleted: [1,2,3,4,5]`, load `./step-06-structure.md`.

FORBIDDEN to load the next step until the user confirms Continue.

## NEXT STEP

After the user confirms Continue, load `./step-06-structure.md`.
