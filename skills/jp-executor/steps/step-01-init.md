# Step 1: Initialization & Input Gate

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🛑 HARD GATE: this skill requires BOTH a `jp-prd` PRD and a `jp-architecture` design. Do not implement anything without them.
- 🌐 Code only from docs re-fetched at implementation time; never from memory.
- 🔐 Secrets only in `.env`/secret stores — never in artifacts, logs, or command output.
- ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Load and validate the upstream artifacts, build the execution plan, and confirm the codebase state before any implementation.

## SEQUENCE

### 1. Discover inputs (hard gate)

`{doc_workspace}` is `{project-root}/docs/juspay/`.

- **Execution checklist** — `{doc_workspace}/task-checklist.md` (frontmatter `status: ready`). **Required.**
- **Architecture doc** — `{doc_workspace}/architecture.md` (frontmatter `status: complete` preferred). **Required.**
- **PRD** — `{doc_workspace}/prd.md` (plus `addendum.md` if present) — context.

If the checklist or architecture is **missing** and the user can't point to it:

> "I implement from a completed plan. I'm missing the {execution checklist | architecture}. Run **`jp-prd`** (requirements) then **`jp-architecture`** (design + execution checklist) first, or give me the paths — then I'll build it."

Do **not** proceed without the checklist + architecture. Load them completely; note the authoritative Juspay doc URLs the architecture recorded (you'll re-fetch these at implementation time).

Also pick up the **juspay-mcp mode** and merchant data recorded upstream (see `../references/juspay-mcp.md`). When implementation needs live data the upstream didn't supply — chiefly API-key provisioning and dashboard webhook/return-URL configuration — run the access flow then: **ask access → log in (connected) or manual Q&A**. Never block; secrets go only to `.env`.

Pick up the **topology** (`topology`/`this_side`/`other_side`) from `architecture.md` (or `prd.md`). **Incoming-handoff check:** if `{doc_workspace}/handoff-<this_side>.md` exists (or the user points to one), load it — its **Cross-Side Contract is authoritative**: build this side to its exact endpoint/`sdkPayload`/env shapes and don't redesign the seam (see `../references/split-integration.md`).

### 2. Build the execution queue

Parse `task-checklist.md` into an ordered execution queue, respecting each task's `depends-on`. Use `architecture.md` for the rationale/context behind each task (decisions, product/shape/platform, structure, the **Portal Configuration** block with dashboard nav paths / deep links, and the doc URLs to re-fetch). Confirm the queue with the user. As you implement, write each task's `status` back to `task-checklist.md` (`todo` → `done` | `blocked` | `skipped`) — never put secret values in it.

**Filter by `side` (split runs).** When `topology: split`, the execution queue is only the tasks where `side ∈ {this_side, shared}`. Mark every `side == other_side` task `skipped` with reason `"other side (separate repo) — see handoff-<other_side>.md"` (do **not** delete them — step 9 carries them into the handoff). In a `single-repo` run, all sides execute locally.

### 3. Re-scan the codebase

Confirm the current state matches the architecture's assumptions (stack, framework, existing payment code, env keys present — never read secret values). Surface any drift from what the architecture expected before proceeding.

### 4. Present the plan and proceed

Show the user the ordered execution queue (task ids + titles, grouped by phase) and the juspay-mcp mode. Note any drift surfaced in step 3. Then proceed — checkpoint before each risky/external action (credential provisioning, DB schema changes, portal configuration, running the server/tests).

## NEXT STEP

Load `./step-02-credentials.md`.
