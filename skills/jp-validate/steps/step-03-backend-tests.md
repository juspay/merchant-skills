# Step 3: Backend / API Tests *(conditional)*

## APPLICABILITY

Run **only if** the step-01 inventory has a **backend / API surface** (server routes, webhook handler, server-side reconciliation, DB). If the integration is frontend/SDK-only, **record nothing and load the next step.**

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🌐 Re-fetch the architecture's `doc-refs` via `docs-mcp` for the exact request/response shape **before** asserting on it — never from memory.
- 🧪 Persist tests in the detected runner (mirroring repo conventions); inline curl/bash only where no runner exists.
- 🔐 Credentials via exported env vars; never inline a secret in a test, never echo one. Never assert on or log a raw PAN.
- 🚫 Never silently mark a test passed — diagnose/fix/re-run on failure; state what can't be run.
- 🤝 Confirm before starting the dev server or driving anything that moves money. ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Implement and run the backend test items from the step-02 plan, in priority order, grounded in re-fetched docs.

## SEQUENCE (run the items that apply, P0 first)

1. **Dev server / harness** — detect the start command (or the in-process test client, e.g. `supertest`/`TestClient`); confirm; bring it up only as needed.
2. **Order / session creation (P0)** — doc-exact request → 2xx + the documented response field (e.g. `sdkPayload`/order id); assert the DB row if the integration persists one.
3. **Status reconciliation (P0)** — assert the app fetches the **server-to-server Order Status** as source of truth and maps Juspay status → app status per the architecture's table; the client/SDK result alone never sets final state.
4. **Webhook (P0 signature / P1 idempotency)** — a tampered-signature payload is rejected; a correctly signed one is accepted and returns the documented response; the **same event delivered twice** updates state once.
5. **Money movement (P0, where built)** — refund amount matches + idempotent; payout/beneficiary validation + status tracking; billing mandate execution — only for what was built.
6. **Constraint boundaries (P2)** — one boundary value per constrained field → assert the **doc-specified** error.
7. **Error-code paths (P2)** — for each in-scope documented error code, assert the app's intended handling (transient vs permanent, message, retry).

For each: **persist** a framework-native test following repo conventions when a runner exists, else run **inline curl/bash**. Honor the test-quality DoD (deterministic, isolated, explicit). On failure, diagnose → fix → re-run; never leave a silent pass.

## VERIFY & RECORD

Per-item pass/fail captured (with the file path or "inline"); the matching `task-checklist.md` `test` tasks marked `done`/`blocked`; anything un-runnable noted with the reason (carried to the report). No secret value appeared in any test, log, or output.

## NEXT STEP

Load `./step-04-frontend-sdk-tests.md`.
