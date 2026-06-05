# Step 2: Risk-Based Test Design & Environment Preflight

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🎯 Test only the **intersection**: in-scope (architecture) ∩ built (inventory) ∩ env-supported. Never test un-built work or out-of-scope methods.
- 🧭 Prioritize by payment risk — P0 first. Avoid duplicate coverage across levels.
- 🤝 Checkpoint the plan with the user before writing or running anything.
- ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Turn the test-target inventory into a prioritized, traceable test plan — deciding for each item its priority, level, execution mode (framework-native file vs inline), and the requirement it covers — then confirm the plan and the environment before execution.

## SEQUENCE

### 1. Enumerate candidate test items (the intersection)

From `../references/payment-test-matrix.md`, list candidate items, keeping only those that satisfy **all three**:

- **In scope** — the architecture put it in scope (method, webhook, reconciliation, constraint field, error code, surface).
- **Built** — step-01's inventory shows jp-executor actually implemented it.
- **Env-supported** — the environment can exercise it (a runner exists or curl/bash suffices; live keys present; connected mode where a live check is needed).

Drop anything failing any leg, and note why (e.g. "refund — in scope but not built → skip").

### 2. Assign priority (P0–P3)

Use the matrix: **P0** = order/session creation, server-to-server status reconciliation, payment auth, money movement, webhook signature verification; **P1** = primary per-method happy paths, webhook idempotency, return-URL integrity; **P2** = constraint boundaries, secondary methods, documented error-code paths; **P3** = polish.

### 3. Choose level + execution mode per item (no duplicate coverage)

- **Level** — API/integration vs E2E/UI; prefer the lowest level that proves the behavior. Don't re-prove at E2E what an API test already covers.
- **Mode** — **persist a framework-native test** in the detected runner (mirroring repo conventions, per `../references/test-stack-detection.md`) when one exists for that surface; otherwise **inline curl/bash** (ephemeral). Honor the test-quality DoD for anything persisted.

### 4. Build the traceability matrix

For each item record the requirement it covers — the `task-checklist.md` `test` task id, the `FR-n`, or the architecture decision. Every in-scope `test` task should map to at least one item; flag any that can't (it becomes a gap in step-05).

### 5. Environment preflight

Confirm the configured key/stage and host/base URL agree (sandbox key ↔ sandbox host, production key ↔ production host). **Default to production** when no environment was named. A **dummy/test gateway** returned by the MCP is acceptable — this is integration testing. Block (don't silently pass) any item whose environment can't be satisfied; mark its `test` task `blocked` with the reason.

### 6. Checkpoint the plan

Present the ordered plan — **smoke → P0 → P1 → P2 → P3** — showing per item: area, priority, level, mode (which runner or inline), files to be written, and covered requirement. Confirm with the user (especially any item that drives a live transaction or moves money) before proceeding.

## VERIFY & RECORD

A prioritized, deduplicated plan exists; every item ties to a requirement; environment is aligned (or offending items blocked); the user approved the plan and any live/money-moving actions.

## NEXT STEP

Load `./step-03-backend-tests.md`.
