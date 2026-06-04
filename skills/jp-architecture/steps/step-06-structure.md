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

### 3. Concrete tree
Produce the actual files to be created/modified in this project (respect the detected framework's conventions — do not impose a foreign layout).

### Content to append

```markdown
## Integration Structure & Boundaries

### Files to Create / Modify
{{concrete tree for THIS codebase — routes, webhook handler, sdk init, return handler, config, migrations}}

### Capability → Location Mapping
{{FR/capability → file(s)}}

### Boundaries & Data Flow
- Backend ↔ Juspay: {{...}}
- Client ↔ Backend: {{...}}
- Data: {{where order/payment state lives}}
```

## Collaboration Options

Present these as native UI choices when available; otherwise ask a concise direct question:
- **Explore deeper** — alternative organizations / edge cases (inline).
- **Perspectives** — maintainability / ops / developer-experience review (inline).
- **Continue** — append content, set `stepsCompleted: [1,2,3,4,5,6]`, load `./step-07-validation.md`.

FORBIDDEN to load the next step until the user confirms Continue.

## NEXT STEP

After the user confirms Continue, load `./step-07-validation.md`.
