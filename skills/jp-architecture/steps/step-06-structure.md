# Step 6: Integration Structure & Boundaries

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🗺️ Map the PRD's capabilities/FRs to concrete files and directories in the *actual* codebase (use the codebase scan).
- ✅ Facilitate; produce a specific tree, not generic placeholders. ⚠️ NO TIME ESTIMATES. 💾 Only save when the user confirms they want to continue.

## YOUR TASK

Define where the integration code lives and how its pieces communicate, mapped to the existing project structure.

## STRUCTURE SEQUENCE

### 1. Map capabilities → locations
For each capability/FR from the PRD, name where it lives in this codebase:
- Session/order creation route(s)
- Webhook handler route
- Order-status / reconciliation utility
- Client SDK init & payment launch (for SDK products) / return-URL handler
- Config & env files
- DB migration/model for order/payment data

### 2. Define boundaries
- **Backend ↔ Juspay**: which calls are server-to-server (session, order status, webhooks in).
- **Client ↔ Backend**: what the client requests and receives (e.g. `sdkPayload`).
- **Data**: where order/payment state is persisted and read.

### 2b. Cross-Side Contract (always for `split`; recommended otherwise)
Author the BE↔FE seam per the schema in `references/split-integration.md` — the session/order endpoint (request/response incl. `sdkPayload`/order id/session token), SDK launch inputs, the payment-result endpoint, return-URL/callback owner, status-reconciliation owner (BE = source of truth), webhook owner (BE), and the env/config each side needs. This is what lets the **other** repo build independently. If an incoming `handoff-<this_side>.md` was ingested, **copy its contract verbatim** (it is fixed) and only fill the parts this side owns. `jp-executor` later finalizes this section as-built into `handoff-<other_side>.md`.

### 3. Concrete tree
Produce the actual files to be created/modified in this project (respect the detected framework's conventions — do not impose a foreign layout). In a `split` run, scope the tree to `this_side` only.

### Content to append

```markdown
## Integration Structure & Boundaries

### Files to Create / Modify
{{concrete tree for THIS codebase — routes, webhook handler, sdk init, return handler, config, migrations; scoped to this_side when split}}

### Capability → Location Mapping
{{FR/capability → file(s)}}

### Boundaries & Data Flow
- Backend ↔ Juspay: {{...}}
- Client ↔ Backend: {{...}}
- Data: {{where order/payment state lives}}

### Cross-Side Contract (BE ↔ FE) *(always for split; omit only for single-side products with no client)*
{{the seam per references/split-integration.md — session/order endpoint + request/response incl. sdkPayload; payment-result endpoint; return URL/callback owner; reconciliation owner (BE); webhook owner (BE); env/config per side; doc-ref URLs. Locked from an incoming handoff when one was ingested.}}
```

## Collaboration Options

Present these as native UI choices when available; otherwise ask a concise direct question:
- **Explore deeper** — alternative organizations / edge cases (inline).
- **Perspectives** — maintainability / ops / developer-experience review (inline).
- **Continue** — append content, set `stepsCompleted: [1,2,3,4,5,6]`, load `./step-07-validation.md`.

FORBIDDEN to load the next step until the user confirms Continue.

## NEXT STEP

After the user confirms Continue, load `./step-07-validation.md`.
