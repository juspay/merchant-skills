# Task Checklist Template — Juspay integration execution

The granular, ordered execution plan `jp-executor` walks. Derived from the architecture decisions,
structure, and Portal Configuration. The executor walks tasks in dependency order, re-fetches each
task's `doc-refs` for exact code, and writes `status` back as it goes. **No secret values here** — only
the names of the env vars/credentials involved.

Frontmatter:

```markdown
---
source_architecture: docs/juspay/architecture.md
created: <today>
status: ready   # ready -> in-progress -> done (executor updates)
---
```

## Task shape

Each task is a checkbox item with sub-bullets:

```markdown
- [ ] **T<N>. <imperative title>**
  - **type:** install | code | webhook | db | config | env | native-setup | test | manual-dashboard
  - **side:** backend | frontend | shared
  - **depends-on:** [T<i>, …] or —
  - **files:** <files to create/modify> or —
  - **params:** <inputs needed> + provenance (`mcp` | `user` | `manual-dashboard` | `doc-derived`)
  - **acceptance:** <verifiable condition — how the executor knows it's done>
  - **doc-refs:** <authoritative doc URL(s) to re-fetch for exact code> or —
  - **status:** todo | done | blocked | skipped
```

For SDK/headless integrations, method-specific client code should be represented **per payment method** (or
with explicit method-level substeps), and each such task must include that method's `process` payload page in
`doc-refs`. The method set is **doc-derived** (the `process` pages the product exposes) — never a user-asked
list; the integration is method-agnostic. Hosted/redirect products have no per-method client task.

**`side` tagging (always emit it).** `backend` = server/S2S work; `frontend` = client/SDK/web/native work;
`shared` = cross-cutting (env var *names*, status mapping, the contract itself). In a `split` run, `jp-executor`
runs only `side ∈ {this_side, shared}` and carries `side == other_side` tasks into `handoff-<other_side>.md`
(see `references/split-integration.md`). In a `single-repo` run, every side runs locally — the tags still
document ownership.

## Ordering

Default dependency order (drop what doesn't apply, add what the integration needs; emit no task for skipped
categories so the executor self-skips cleanly):

`env/creds → validation layer → SDK install → core integration (session→launch→response) → webhook
handler (+ signature, idempotency) → order-status reconciliation → DB schema → native SDK setup →
portal configuration (manual-dashboard) → tests`.

## Example (illustrative — generate real tasks from the architecture)

```markdown
- [ ] **T1. Add Juspay env vars**
  - type: env · side: shared · depends-on: — · files: `.env`, `.env.example`
  - params: `JUSPAY_MERCHANT_ID` (mcp|user), `JUSPAY_CLIENT_ID` (default = merchant_id)
  - acceptance: vars present; no secret values committed · doc-refs: — · status: todo
- [ ] **T2. Provision API key**
  - type: config · side: backend · depends-on: — · params: api key (mcp `juspay_create_api_key`, or `manual-dashboard`)
  - acceptance: key stored in `.env` only, never logged; target environment/host recorded and consistent · doc-refs: <key/setup page> · status: todo
- [ ] **T4. Implement UPI collect process payload**
  - type: code · side: frontend · depends-on: [T3] · files: `src/payments/juspay/*`
  - params: UPI collect fields/constraints (`doc-derived`)
  - acceptance: UPI collect request shape matches docs exactly; method code references the fetched payload page · doc-refs: <UPI collect process page> · status: todo
- [ ] **T7. Configure webhook URL in dashboard**
  - type: manual-dashboard · side: backend · depends-on: [T5]
  - params: webhook URL + events; **nav path** + **deep link** (from architecture Portal Configuration)
  - acceptance: user confirms the webhook is saved in the dashboard · doc-refs: <dashboard webhook page> · status: todo
```
