# Step 8: Execution Checklist (the "minute steps" for jp-executor)

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🧩 Translate the architecture into GRANULAR, ordered execution steps — what `jp-executor` walks.
- 🔐 No secret *values* in the checklist — only the names of env vars / credentials.
- ⚠️ NO TIME ESTIMATES. 💾 Only save when the user confirms they want to continue.

## YOUR TASK

Produce `{doc_workspace}/task-checklist.md` from the architecture decisions, structure, and Portal Configuration, using `../assets/task-checklist-template.md`. Converge with the user before handoff.

## SEQUENCE

### 1. Derive tasks

Walk `architecture.md` and emit one task per concrete action, in dependency order:
`env/creds → validation layer → SDK install → core integration → webhook (+ signature, idempotency) →
order-status reconciliation → DB schema → native setup → portal configuration → tests`.

**Emit tasks only for work the architecture actually decided is needed.** If a category was skipped or
doesn't apply (no webhook, no DB change, web/API surface with no native setup, nothing to configure in the
dashboard), emit **no** task of that type — the executor's matching step self-skips. Don't pad the checklist
with inapplicable tasks.

For SDK/headless products, the client-side method work must stay **per payment method**: emit either one
task per method **the product's docs expose** (a doc-derived set — never a user-asked method list; the
integration is method-agnostic) or explicit method-level substeps, and include that method's `process`
payload page in `doc-refs`. Never emit one generic payment-method task with no per-method payload refs.
*(Hosted/redirect products have no per-method client task.)*

For each task fill: `id`, `type`, **`side`** (`backend` | `frontend` | `shared`), `depends-on`, `files`,
`params` (+ provenance), `acceptance`, `doc-refs`, `status: todo`. Pull `manual-dashboard` tasks from the
**Portal Configuration** block (navigation path + deep link + events).

**Tag every task with `side`** so `jp-executor` can partition work in a `split` run (it runs `side ∈ {this_side,
shared}` and carries `other_side` tasks into the handoff). Emit tasks for **both** sides even when this is a
split run — the `other_side` tasks are the spec the other repo's agent will execute. See
`references/split-integration.md`.

### 2. Map coverage

Every capability/FR and payment flow from the PRD must be covered by ≥1 task; every file in the
architecture's structure must appear in some task. Note any gaps before continuing.

### 3. Write the checklist

Write `{doc_workspace}/task-checklist.md` (frontmatter: `source_architecture`, `created` = today's date,
`status: ready`).

### 4. Present & converge

Show the checklist so the user can refine granularity and ordering. Present these as native UI choices when
available; otherwise ask a concise direct question:
- **Explore deeper** — break down or reorder specific tasks (inline).
- **Perspectives** — security / ops / developer-experience review of the steps (inline).
- **Continue** — finalize the checklist, set `architecture.md` `stepsCompleted: [1,2,3,4,5,6,7,8]`, load `./step-09-complete.md`.

FORBIDDEN to load the next step until the user confirms Continue.

## NEXT STEP

After the user confirms Continue, load `./step-09-complete.md`.
