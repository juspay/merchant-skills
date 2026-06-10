# Split-repo integrations (FE / BE in separate repos)

A Juspay integration usually has two sides:

- **backend (BE / S2S)** — creates the session/order, reconciles via the Order Status API (source of truth), handles webhooks.
- **frontend (FE / client)** — launches the SDK (web / hosted / headless / native), drives the payment, returns the user.

These two sides often live in **separate repositories** that are not open in the same folder. This reference defines how every skill in the pipeline (`jp-prd`, `jp-architecture`, `jp-executor`, `jp-validate`) detects that situation, works on the side that **is** present, and hands the other side off to an independent agent — without ever assuming both repos are co-located.

## Vocabulary

- **`topology`** — `single-repo` (both sides are in this folder, *or* the product only needs one side) · `split` (this repo holds one side; the other side is a separate repo handled by another run/agent).
- **`this_side`** — `backend | frontend | fullstack` (what this folder contains).
- **`other_side`** — `backend | frontend | none` (what lives in the *other* repo; `none` when not split).

Record these three values once (in `prd.md` frontmatter) and **reuse** them downstream — do not re-derive or re-ask if a prior skill already set them.

## Detecting topology

Classify the surfaces present in `{project-root}` from codebase signals — do not ask for what you can detect:

- **Backend signals** — server framework / route files, `requirements.txt`, `go.mod`, server-side `package.json` (Express/Nest/etc.), `pom.xml`, `Gemfile`, controllers/handlers, DB/ORM.
- **Frontend signals** — `index.html`, web SDK usage, `AndroidManifest.xml`, `*.xcodeproj`/`*.xcworkspace`, `pubspec.yaml` (Flutter), React-Native / Expo config.

Then:

1. If the chosen Juspay product needs **both** sides but only **one** is present → this is a **candidate `split`**. Surface it and confirm with the user — it could be (a) genuinely split repos, (b) a monorepo where the other side is a sibling folder, or (c) the other side simply hasn't been started yet. All three resolve to `topology: split` for *this* run; only the present side is built here.
2. If both sides are present → `single-repo`, `this_side: fullstack`, `other_side: none`.
3. If the product only needs one side (e.g. a pure server-to-server API like `ec-api`/`payout` with no client SDK) → `single-repo` with `this_side` = that side; there is no other side to hand off.

Never invent the other repo's contents or path. The other side is described by the **contract**, not by reaching into another folder.

## The Cross-Side Contract (the BE ↔ FE seam)

The only thing the two sides must agree on is the seam between them. `jp-architecture` designs it; `jp-executor` finalizes it **as-built**. Schema:

```markdown
## Cross-Side Contract (BE ↔ FE)
- **Topology:** split · this repo = {backend|frontend} · other repo = {frontend|backend}
- **Session / order creation (BE exposes → FE calls):**
  - Endpoint: {METHOD /path}
  - Request (FE → BE): {fields + types}
  - Response (BE → FE): {fields + types — incl. `sdkPayload` / order id / session token}
- **SDK launch (FE):** which response fields the FE feeds the SDK; per-method `process` payloads (see architecture)
- **Payment result (FE → BE):**
  - Endpoint: {METHOD /path}
  - Request (FE → BE): {order id / client status signal}
  - Response (BE → FE): {final app-level status}
- **Return URL / callback:** {url or route} · owner: {BE | FE}
- **Status reconciliation:** server-to-server Order Status API · owner: **BE** (source of truth; FE never trusts the client/SDK result alone)
- **Webhooks:** owner: **BE** · events: {…}
- **Env / config per side:** BE: {var names} · FE: {var names}
- **Doc refs:** {authoritative Juspay doc URLs}
```

## Emit — handing the other side off (`jp-executor`, as-built)

When `topology: split`, after building this side `jp-executor` writes **one portable file** the user gives to the other repo's agent:

`{doc_workspace}/handoff-<other_side>.md` (e.g. `handoff-frontend.md` when this repo built the backend).

```markdown
---
handoff_for: {frontend | backend}      # the side the OTHER agent must build
this_repo_side: {backend | frontend}
produced_by: jp-executor
created: <today>
source: docs/juspay/{architecture.md, task-checklist.md, integration-summary.md}
---
# Juspay Integration Handoff → <other_side>

## 1. Cross-Side Contract (as-built — treat as fixed)
{the schema above, filled with REAL values this side determined: real endpoint paths, real
request/response shapes incl. the actual `sdkPayload` structure returned, real env var names,
real return route. This is authoritative for the other agent.}

## 2. What's already done (this side: <this_side>)
{built routes / SDK init / DB / env names / packages — drawn from integration-summary.md}

## 3. What you need to build (<other_side>)
{the `side == other_side` tasks from task-checklist.md — per task: title, type, files hint,
params, acceptance, doc-refs. The other agent re-fetches doc-refs via docs-mcp for exact code.}

## 4. How to use this
Run `jp-prd` → `jp-architecture` → `jp-executor` in the <other_side> repo and provide this file
as an input. Its Cross-Side Contract is **authoritative** — build to it; do not redesign the seam.
```

No secret values ever appear in the handoff — env var **names** only.

## Ingest — building the second side against the contract (every skill)

At the start of each skill, look for an **incoming** handoff for the side you are about to build: `{doc_workspace}/handoff-<this_side>.md`, or a path the user supplies. If present:

- Load it and treat its **Cross-Side Contract as authoritative/fixed**. Do **not** redesign the seam — `jp-prd` writes requirements that conform to it, `jp-architecture` locks the contract from it, `jp-executor` builds this side to the real endpoints/shapes it specifies.
- `jp-executor` additionally **verifies this side honors the contract** and notes any unavoidable deviation back in its own handoff/summary so the first side can reconcile.

## Side tags on tasks

`jp-architecture` tags every task in `task-checklist.md` with `side: backend | frontend | shared`. `jp-executor` executes only `side ∈ {this_side, shared}`; it marks `side == other_side` tasks `skipped` with reason `"other side (separate repo) — see handoff-<other_side>.md"` (it does not delete them) and carries them into the handoff's section 3.
