# Step 5: Traceability, Quality Gate & Test Report

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🧾 The report records what was **actually** tested — no fabricated rows, **no secret values** (env var names only). Omit empty sections.
- 🚫 Do **not** write or alter `integration-summary.md` — that belongs to `jp-executor`. This skill writes `test-report.md`.
- 🚫 THIS IS THE FINAL STEP.

## YOUR TASK

Assemble the coverage matrix, make a quality-gate decision, run the payment-NFR checks, and write the test report.

## SEQUENCE

### 1. Build the traceability matrix

From the step-02 plan + step-03/04 results, map each requirement (`task-checklist.md` `test` task id, `FR-n`, or architecture decision) to its covering test and status (`covered` / `gap` / `blocked`). Any in-scope requirement with no covering test is a **gap** — list it explicitly.

### 2. Decide the quality gate

- **PASS** — every **P0** item passed; no critical gap.
- **CONCERNS** — P0 passed but P1/P2 gaps or blocked items remain.
- **FAIL** — a P0 item failed, or a P0 item couldn't be exercised at all.

State a one-paragraph rationale.

### 3. Payment-NFR checks (lite)

Assert and record: no PAN/secret in logs/report/output; webhook signature verification enforced (rejects tampered payloads); idempotency proven by an actual duplicate-delivery test; reconciliation uses the server-to-server Order Status; environment/key/host aligned. (See `../references/payment-test-matrix.md`.)

### 4. Integration-stage confirmation *(connected mode only)*

Call `juspay_integration_monitoring_status` (per `../references/juspay-mcp.md`); flag any critical stage not passing as a **go-live blocker**. Omit in manual mode.

### 5. Write the report & finalize

Write `{doc_workspace}/test-report.md` from `../assets/test-report-template.md` — run context (env, mode, detected stack, files written), summary pass/fail table, traceability matrix, quality-gate decision, NFR checks, integration-stage status, blockers/go-live gates, manual guide (if any), notes (doc source URLs). Ensure every `test` task in `task-checklist.md` has a terminal `status` (`done`/`blocked`/`skipped`).

### 6. Report

Tell the user the gate decision, what passed, the gaps/blockers and go-live gates, and point them to `test-report.md`.

## WORKFLOW COMPLETE

The integration has been tested against `architecture.md` + `task-checklist.md` — only what was actually built — using the repo's own test stack where present, with results captured in `test-report.md` and a quality-gate decision recorded. `jp-executor`'s `integration-summary.md` is left untouched.
