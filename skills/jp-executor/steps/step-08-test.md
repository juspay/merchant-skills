# Step 8: Smoke Test & Handoff to `jp-validate` *(conditional)*

## APPLICABILITY

Run **only if** `task-checklist.md` has `test` tasks (cross-check the architecture). **If there are no test tasks, record nothing and load the next step.**

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🤝 Thorough testing is owned by **`jp-validate`** — recommend it; only run the inline smoke here if the user declines or it isn't available.
- 🔐 Pass credentials to tests via exported env vars, never inline; never echo them.
- 🚫 Never silently mark a test passed. 🤝 Confirm before starting the dev server. ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Confirm the integration is minimally alive, then hand thorough testing to the dedicated tester.

## SEQUENCE

### 1. Hand off to `jp-validate` (preferred)

The dedicated **`jp-validate`** skill is the thorough tester: it detects the repo's test stack and replicates it (Playwright/Cypress/Jest/Vitest/pytest/…, falling back to curl/bash), prioritizes by payment risk, covers order creation, status reconciliation, webhook signature + idempotency, per-method payloads, constraints and error codes (backend + frontend/SDK), and writes a `test-report.md` with a quality gate. Recommend the user run it after this build.

### 2. Inline smoke (fallback — if the user declines or `jp-validate` isn't available)

Before any live action, confirm the configured key/stage and host/base URL agree (production key ↔ production host); **production is enforced** — never ask which environment to use. A dummy/test gateway returned by the MCP is acceptable for integration testing.

Then run a minimal end-to-end smoke against what was built — detect the start command, confirm, bring the server up; create an order/session (assert 2xx + the documented field) and check its status (server-to-server). Diagnose/fix/re-run on failure. This is a liveness check, **not** full coverage — defer the rest to `jp-validate`.

## VERIFY & RECORD

Either the user is pointed to `jp-validate`, or the inline smoke passed (order create + status). Mark the matching `test` tasks `done`/`blocked`; for the handoff case, leave deeper `test` tasks for `jp-validate` and note it. State anything that couldn't be run and why.

## NEXT STEP

Load `./step-09-summary.md`.
