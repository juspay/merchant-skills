# Step 1: Initialization & PRD Intake

## MANDATORY RULES (READ FIRST)

- 📖 ALWAYS read this complete step file before acting.
- ✅ Collaborative discovery between peers — you FACILITATE, not generate.
- 🚪 DETECT existing workflow state and handle continuation before any fresh setup.
- 🛑 The jp-prd integration PRD is a REQUIRED input — do not proceed without it.
- 🌐 Ground every Juspay fact in docs-mcp (see `references/juspay-docs-mcp.md`); never assert from memory.
- ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Detect continuation state, discover and load the integration PRD (and any UX/notes), and set up the architecture document for collaborative decision-making.

## INITIALIZATION SEQUENCE

### 1. Check for an existing architecture run

`{doc_workspace}` is `{project-root}/docs/juspay/`. Look for `{doc_workspace}/architecture.md`.

- **If it exists** with frontmatter `stepsCompleted`: this is a continuation. Read the complete file, summarize what's already decided, confirm with the user, then resume by loading the step *after* the highest completed one (e.g. `stepsCompleted: [1,2,3]` → load `./step-04-decisions.md`). Do not re-run completed steps. **Stop here.**
- **If it does not exist or has no `stepsCompleted`:** fresh run — continue below.

### 2. Discover input documents

The integration PRD from `jp-prd` is required. Discover and confirm with the user before loading:

- **Integration PRD** — `{doc_workspace}/prd.md` (preferred). If absent, scan `{project-root}/docs/**/*prd*.md` and ask the user to confirm or point you to one. Also load any sibling `addendum.md` and `.decision-log.md`.
- **UX / design** — `*ux*.md`, `*design*.md` (optional).
- **Notes / product / repo docs the user points to** — anything they offer (optional).

Confirm what you found and ask if the user wants to add anything. Then load ALL confirmed files completely. Track them in frontmatter `inputDocuments`.

From the PRD / `.decision-log.md`, also pick up the **juspay-mcp mode** (`connected` | `manual`) and any recorded merchant data (`merchant_id`, `client_id`, `integration_type`) with their provenance — reuse these and do not re-ask. If the mode wasn't established upstream and a later step needs live settings, run the access flow then (see `references/juspay-mcp.md`).

Also pick up the **topology** (`topology`/`this_side`/`other_side`) from the PRD frontmatter (re-derive from the codebase only if absent — see `references/split-integration.md`). **Incoming-handoff check:** look for `{doc_workspace}/handoff-<this_side>.md` (or a path the user offers) produced by the other repo's run. If present, load it — its **Cross-Side Contract is authoritative**: the architecture must lock the seam from it (don't redesign), and downstream decisions build this side to those exact endpoint/`sdkPayload` shapes.

### 3. Validate the required input (hard gate)

If **no PRD** is found and the user cannot point to one:

> "Architecture design needs a Juspay integration PRD to work from. Run the `jp-prd` skill first (or give me the path to an existing PRD), then come back."

Do **not** proceed without a PRD.

### 4. Create the architecture document

Copy `../architecture-decision-template.md` to `{doc_workspace}/architecture.md` (create the directory). Fill frontmatter: `created` (today's date), `inputDocuments`, `stepsCompleted: [1]`, `juspay_products` (from the PRD frontmatter if present), and `topology`/`this_side`/`other_side` (carried from the PRD).

### 5. Report and offer to continue

> "Architecture workspace set up at `{doc_workspace}`.
>
> **Loaded:** PRD ({path}), {addendum / UX / notes if any}.
> **Products in scope (from PRD):** {list or 'to confirm in Step 3'}.
>
> Ready to design the integration. Anything else to include before we start?
>
> Continue to PRD context analysis

## NEXT STEP

After the user confirms they want to continue and setup is confirmed, load `./step-02-context.md`. Use native choice UI if available; otherwise ask a concise direct question. Do NOT proceed until the architecture doc exists with `stepsCompleted: [1]`.
