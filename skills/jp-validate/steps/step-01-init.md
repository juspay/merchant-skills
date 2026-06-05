# Step 1: Initialization, Gate & Stack Detection

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🛑 GATE: this skill tests a **built** integration. Don't write tests for something that wasn't implemented.
- 🌐 Ground request/response shapes in docs re-fetched at test time; never from memory.
- 🔐 Never read or echo secret values — credentials reach tests only via exported env vars.
- ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Load the upstream artifacts and confirm an integration was actually built, detect the repo's test stack, and inventory what there is to test — before designing any tests.

## SEQUENCE

### 1. Gate on a built integration

`{doc_workspace}` is `{project-root}/docs/juspay/`. Accept **either**:

- **Post-executor (normal):** `{doc_workspace}/task-checklist.md` exists with `test` tasks and/or in-progress/done implementation tasks, or `{doc_workspace}/integration-summary.md` is present.
- **Standalone:** `{doc_workspace}/architecture.md` exists **and** integration code is present in the repo.

If neither holds:

> "I test an integration that's already built. I don't see a completed jp-executor run (no `task-checklist.md`/`integration-summary.md`) or integration code. Run **`jp-executor`** first, or point me at the built integration and its `docs/juspay/` artifacts."

Do **not** fabricate tests without a built integration.

### 2. Load the artifacts

Read `architecture.md` (decisions, in-scope methods, surfaces, reconciliation rule, webhook events/signature approach, constraint fields, error-code surface, environments) and `prd.md` (FRs/journeys for traceability). Read `task-checklist.md` (the `test` tasks and their `acceptance` + `doc-refs`) and `integration-summary.md` if present (routes, SDK init, DB, native setup, packages — the ground truth of what was built). Note the authoritative Juspay doc URLs to re-fetch at test time.

Pick up the **juspay-mcp mode** the upstream skills recorded (see `../references/juspay-mcp.md`) — reuse it; **don't re-ask** the access question. Only run the access flow later if a test genuinely needs live data (e.g. integration-stage status) the artifacts don't carry.

### 3. Detect the test stack

Follow `../references/test-stack-detection.md`: scan manifests/config (`package.json`, `playwright.config.*`, `cypress.config.*`, jest/vitest config, `pytest.ini`/`pyproject.toml`, Go/Ruby/Java/PHP equivalents, Postman/`.http` collections). Record each runner present (with evidence — a dep **and** config/existing tests), the repo's test conventions (dir, naming, fixtures), and where **no** runner exists → inline curl/bash fallback.

### 4. Re-scan what was actually built

Confirm against the codebase (never read secret values): which routes/endpoints exist, whether a webhook handler exists, DB/persistence, SDK/headless init, per-method code, native surfaces. Classify the surface (backend / frontend-SDK / native / fullstack). Surface any drift from what the architecture/summary claims.

### 5. Produce the test-target inventory

Emit a concise inventory: surfaces present, endpoints/handlers found, in-scope methods that were built, detected runner(s) per surface (or "inline fallback"), and the juspay-mcp mode. This is the input to test design.

## VERIFY & RECORD

The gate passed; artifacts loaded; the test stack is identified per surface (or fallback noted); the test-target inventory lists only built, in-scope work. No secret value was read or printed.

## NEXT STEP

Load `./step-02-test-design.md`.
