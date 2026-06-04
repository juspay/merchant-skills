# Step 8: Live Testing *(conditional)*

## APPLICABILITY

Run **only if** `task-checklist.md` has `test` tasks (cross-check the architecture). Only run the kinds of
tests the integration supports (e.g. no backend tests for a client-only integration; SDK UI gets a manual
guide, not CLI automation). **If there are no test tasks, record nothing and load the next step.**

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🧪 Test **inline** (curl / bash / the project's own runner) — no bundled scripts.
- 🔐 Pass credentials to tests via exported env vars, never inline; never echo them.
- 🚫 Never silently mark a test passed — if something can't be automated (CAPTCHA, device-only SDK), say so.
- 🤝 Confirm before starting the dev server. ⚠️ NO TIME ESTIMATES.

## SEQUENCE (run the parts that apply)

1. **Environment preflight** — before starting anything live, confirm the configured key/stage and host/base
   URL agree (sandbox key ↔ sandbox host, production key ↔ production host). If they don't, stop and mark
   the affected test tasks `blocked` until corrected.
2. **Dev server** — detect the start command; confirm; run in background until ready. (Skip for mobile-only.)
3. **Backend tests** — for the endpoints that exist: session/order creation (HTTP 200 + documented field +
   DB row if any), order status (HTTP 200 + status), webhook success/failure (only if a webhook exists →
   signed payload → documented response + state update). Diagnose/fix/re-run on failure.
4. **Constraint edge cases** — only if a validation layer exists: one boundary value per constrained field →
   assert the doc-specified error.
5. **Frontend / SDK** — web: drive a transaction with test cards/VPAs, verify the return URL. Mobile: emit a
   **manual test guide** (can't drive device UI from CLI).
6. **Integration-stage confirmation** — connected mode only: `juspay_integration_monitoring_status`; flag
   any critical stage not passing as a go-live blocker.

## VERIFY & RECORD

Report a unified pass/fail table (only the rows that apply), including the environment preflight result.
Mark the `test` tasks `done`/`blocked`. State anything that couldn't be run and why.

## NEXT STEP

Load `./step-09-summary.md`.
