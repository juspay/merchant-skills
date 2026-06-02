---
name: jp-executor
description: >
  Juspay integration executor. Use when the user types `/jp-executor` or `/integrate --from-plan`,
  or when jp-planner invokes this skill after writing juspay-plan.md. Reads the plan file,
  registers the executor manifest, fetches docs, generates code, and runs tests.
  Always requires a juspay-plan.md produced by /jp-planner.
compatibility: |
  tools:
    - juspay-docs-mcp (explore_product, doc_fetch_tool, list_products)
    - juspay-mcp (authenticate, juspay_get_merchant_details, juspay_get_webhook_settings, juspay_get_general_settings, juspay_create_api_key, juspay_integration_monitoring_status)
  mcp_servers:
    - juspay-docs-mcp
    - juspay-mcp
---

# /jp-executor — Juspay Integration Executor

> **EXECUTOR ROLE:** This skill is the EXECUTOR. It reads a `juspay-plan.md` written by `jp-planner`, skips all product/platform discovery, and proceeds directly to doc fetching, code generation, and testing.
>
> The planner (`/jp-planner`) handles all questions upfront and writes the plan. This executor trusts the plan completely and never re-asks questions the plan already answered.
>
> **MCP PREFERENCE:** Always prefer `juspay-mcp` tools for live merchant data (credentials, settings, gateway config, integration status). Use `juspay-docs-mcp` only for documentation structure and page content.

---

## PRE-FLIGHT GATES (hard stops — do not proceed past these)

1. **MCP auth** — `juspay-mcp` must be authenticated before any tool call. If it fails, stop and tell the user to authenticate before continuing (see PRE-FLIGHT: MCP AUTHENTICATION).
2. **Plan loaded** — `$PRODUCT`, `$PLATFORM`, and `$DOC_PAGES` must be populated from the plan file before `integrate-results init` is called (see STARTUP Step S.1).
3. **Manifest registered** — `./steps.json` must be registered before any `step-start` call. `step-start` hard-rejects names not in the manifest.

---

## FLAG PARSING

Extract flags before starting:

| Flag                  | Effect                                                                                                            |
| --------------------- | ----------------------------------------------------------------------------------------------------------------- |
| `--from-plan <path>`  | Path to the plan file. Defaults to `./juspay-plan.md` if omitted. This is the primary entry — always required.   |
| `--from <step>`       | Resume from a specific step (see Entry Points). Seed the full task list; mark preceding steps completed.          |

---

## STARTUP — Read Plan and Begin

This executor always reads a plan written by `jp-planner`. Plan search order when `--from-plan` is not provided:
1. `./juspay-plan.md` (convenience copy at project root)
2. Most recently modified `.jp-artifacts/*/juspay-plan.md`

If neither found, stop and tell the user:

> "No `juspay-plan.md` found. Please run `/jp-planner` first to create the integration plan, then re-run `/jp-executor`."

### Step S.1 — Read the plan file

Read the plan file at the `--from-plan` path (or found via search above). Parse the YAML frontmatter and body sections.

Store `$ARTIFACTS_FOLDER` — derive from the plan file path if it's under `.jp-artifacts/`, otherwise derive from `$PRODUCT-$PLATFORM-$CREATEDDATE` fields.

Populate variables from the plan:

| Variable               | Plan field                  | Notes                                                                              |
| ---------------------- | --------------------------- | ---------------------------------------------------------------------------------- |
| `$PRODUCT`             | `product`                   |                                                                                    |
| `$PRODUCT_TYPE`        | `productType`               | `sdk` \| `api-only` \| `hybrid`                                                    |
| `$PLATFORM`            | `platform`                  |                                                                                    |
| `$CLIENT_PLATFORMS`    | `clientPlatforms`           | Comma-separated list for api-only products; empty for SDK products                 |
| `$ENTITY_NAME`         | `entityName`                | e.g. `order`, `payout`, `mandate`                                                  |
| `$MERCHANT_ID`         | `merchantId`                |                                                                                    |
| `$CLIENT_ID`           | `clientId`                  |                                                                                    |
| `$DETECTED_LANG`       | `backendLang`               |                                                                                    |
| `$BACKEND_BASE_URL`    | `backendBaseUrl`            |                                                                                    |
| `$HAS_EXISTING_SCHEMA` | `hasPersistenceSchema`      |                                                                                    |
| `$HAS_WEBHOOKS`        | `hasWebhooks`               | Read directly from plan frontmatter — `true` means merchant opted in during planning |
| `$WEBHOOK_URL`         | `webhookUrl`                |                                                                                    |
| `$WEBHOOK_EVENTS`      | `webhookEventsSelected`     | Comma-separated list of events the merchant selected in the planner                |
| `$RETURN_URL`          | `returnUrl`                 |                                                                                    |
| `$API_KEY_SOURCE`      | `apiKeySource`              | `env` \| `new`                                                                     |
| `$HAS_RETURN_URL`      | derived                     | `returnUrl` is non-empty → `true`; empty string → `false`                          |
| `$DOC_PAGES`           | `## Doc Pages` section      | Ordered list of `title: url` entries — executor fetches these in order in Phase 3  |
| `$DASHBOARD_HINTS`     | `## Dashboard Config Hints` | Nav paths and links for webhook/return-url config; used in Phase 4 instead of re-discovering |
| `$ARCH_DECISIONS`      | (built in Phase 3.5)        | Set by the `arch-decisions` step after doc-fetch — NOT in plan frontmatter         |
| `$API_CAPABILITIES`    | `## Interface Surface`      | List of backend capabilities for api-only products; parsed from plan section       |

### Step S.2 — Extract and register the executor manifest

Extract the `## Executor Manifest` JSON block from the plan → write to `./steps.json` in the project root.

### Step S.3 — Initialize lifecycle and close bootstrap steps

```
integrate-results init
integrate-results set active working
```

The `init` command seeds a bootstrap manifest (`product-select`, `platform-detect`, `plan`). Close these immediately — they were answered by the planner:

```
integrate-results step-start product-select
integrate-results step-end passed "product pre-answered from plan: $PRODUCT"
integrate-results step-start platform-detect
integrate-results step-end passed "platform pre-answered from plan: $PLATFORM"
integrate-results step-start plan
integrate-results register ./steps.json
integrate-results step-end passed "manifest loaded from plan: $(count) steps"
```

### Step S.4 — Seed Wave-1 tasks

Seed one `TaskCreate` per step in the registered manifest (from `./steps.json`), in a single parallel turn.

### Proceed to PHASE 3 — Doc Fetch.

---

## RESULTS TRACKING

Every phase is bookended by two `scripts/lifecycle/integrate-results` calls: `step-start <name>` before the work begins, and `step-end <status> "<verification>"` after verification. This is what keeps per-phase timing accurate — collapsing both into a single end-of-phase call produces zero-second durations.

**Script path** — the script lives at `scripts/lifecycle/integrate-results` **relative to this skill's own directory** (`{skill-root}`), not the user's project root. Resolve the path against wherever this `SKILL.md` is located before invoking.

**Commands:**

- `init` — initialize the workflow lifecycle skeleton and seed the **bootstrap manifest** (`product-select`, `platform-detect`, `plan`). Echoes `init: startedAt=<ts>`. Call once at workflow start. The executor closes these 3 bootstrap steps immediately in STARTUP with `passed "pre-answered from plan"`.
- `register <steps.json>` — merge the planner's run manifest (a JSON array of steps) into the lifecycle file. Echoes `registered: <new> steps (manifest=<total>)`. Called once, by the `plan` step (see RUN MANIFEST & REFERENCE FILES). **Rejects (exit 1)** any step name outside the closed vocabulary, a malformed array, or a manifest missing a required phase step.
- `step-start <name>` — call at the top of each step before any work. Echoes `pending: <name>`. **Rejects (exit 1) any `<name>` not in the manifest** — you cannot start a step the planner didn't register.
- `step-end <status> "<verification>" ["<reason>"]` — call after the step verifies. `<status>` ∈ `passed|failed|skipped`. **A `skipped` status requires a non-empty `<reason>` (3rd arg)** — a silent skip is rejected. Echoes `recorded: <name> <status> (steps=<count>, pending=<none|name>)`.
- `step <name> <status> "<verification>" <startedAt> <completedAt> ["<reason>"]` — single-call form for phases where start time was captured in shell. Same name-in-manifest and skip-reason rules apply.
- `set <field> <value>` — store non-sensitive metadata. Echoes `set: <field>=<value>`. **The script structurally refuses fields matching `*key*`, `*secret*`, `*password*`, `*token*` (case-insensitive) — do not try to work around this.** `set status completed` is **refused (exit 1)** while any registered step has no terminal record.
- `expect <step> "<field,field,…>"` — **question gate:** declare the inputs a step must collect from the user before it can pass. Echoes `expected: <step> needs <fields>`.
- `fulfilled <step> <field>` — record that one expected field was actually collected. Echoes `fulfilled: <step> <field> (<got>/<total>)`.
- `finalize` — completeness check: echoes `complete: all manifest steps recorded` (exit 0) or `incomplete: <names>` (exit 1) when a registered step (other than `done`) was never executed. Run by the DONE phase before declaring success.

**The question gate** makes "collect, don't assume" structural: after `expect <step> "amount,currency"`, a `step-end <step> passed` is **refused (exit 1)** until each field has a matching `fulfilled`. Closing as `skipped "<reason>"` bypasses the gate (a deliberate defer), but you can never `pass` a gate while its declared inputs are unmet.

**Safe fields to `set`:** `product`, `platform`, `productType`, `merchantId`, `active`, `status`.

**Trust the echo.** Every mutation echoes a one-line confirmation. Treat that as proof the write succeeded; do not re-read the JSON to verify.

**If `integrate-results` or `scripts/lifecycle/done` exits with code 2** and prints a line starting with `SKILL_FALLBACK:`, neither `jq` nor Python is available. Skip all remaining `integrate-results` calls without retrying, skip the timing summary in the DONE phase, and inform the user once: "Result tracking is unavailable (no `jq` or Python found). Install either to enable per-phase timing and step-completeness enforcement. The integration itself will proceed normally." Note that **without a JSON processor the manifest gate cannot run**, so step-skip protection is off in that environment — be extra careful to follow every phase in order.

### The run manifest replaces static/dynamic step seeding

The step list is **not** something you maintain in your head or seed ad-hoc as you go. It is a **manifest** written by the planner, read from the plan, and enforced by the script:

1. `init` seeds a **bootstrap manifest**: `product-select`, `platform-detect`, `plan`.
2. STARTUP closes those 3 steps immediately (planner answered them), then reads the `## Executor Manifest` from the plan and `register`s it as `./steps.json`. The manifest now contains every step this run will execute.
3. From then on, `step-start`/`step-end` only accept names that are in the manifest, and `finalize` / `set status completed` fail while any registered step is unaccounted for.

This is what makes a skipped step a **loud failure** instead of a silent omission. See **RUN MANIFEST & REFERENCE FILES** for the full flow and the closed step vocabulary.

**Discipline:**

- Bookend every step with `step-start` at the top and `step-end` at the bottom. Never call `step-end` without a prior `step-start`. Only one step may be pending at a time.
- A step you don't perform must still be **closed explicitly**: `step-end skipped "<verification>" "<reason>"`. Absence is a hard fail; a reasoned skip is fine.
- Never invent step names. If a name isn't in the manifest, `step-start` will reject it — that means the planner didn't register it; do not work around it.
- Never compute timestamps yourself — `integrate-results` resolves UTC internally.
- Verification strings must be credential-free (see SECURITY below).

---

## PROGRESS TRACKING

> **Agent-capability gate:** This entire section is optional UI — applicable only if your agent supports `TaskCreate`/`TaskUpdate` (e.g. Claude Code). If it doesn’t, skip this section entirely. The `integrate-results` lifecycle calls are the source of truth; task tracking is presentation only.

**All task management is specified here — phase bodies contain only lifecycle calls and work instructions.**

**Seeding tasks (Wave 1):** Right after `register` in Step S.3, emit one `TaskCreate` per registered manifest step in a single parallel turn, reading names from `./steps.json`. The 3 bootstrap steps are closed immediately and do not need `TaskCreate` calls.

**State machine:** At every `integrate-results step-start <name>` call → flip that task to `in_progress`. At every `integrate-results step-end passed/skipped` call → flip that task to `completed` (surface the skip reason). One task `in_progress` at a time.

**Auto-skip:** Steps whose runtime guard is false close with `step-end skipped "<reason>"` — flip to `completed` with the reason surfaced.

**Resume (`--from`):** Re-establish manifest; close all steps before the entry point with `step-end passed "resumed"` and flip those tasks to `completed`.

**Failure:** Leave the failing step’s task `in_progress`; call `integrate-results set status failed`.

---

## RUN MANIFEST & REFERENCE FILES

This skill is large, and weaker/faster models lose the thread and silently skip steps. Two mechanisms counter that: a **computed run manifest** the script enforces for completeness, and **reference files** that hold the heavy per-phase procedures so SKILL.md stays a lean orchestrator and you load detail only when you need it.

> **No sub-agents.** Everything runs inline in the main context — works identically on Claude Code, OpenCode, Cursor, and weak models. Where a phase needs a heavy read/extract procedure, **load the matching `reference/*.md` file, do the work inline, validate the result, and discard the raw fetched text** to keep context lean. If a retrieval comes back empty/thin, that is a caught error — `step-end failed` the owning step; never paper over it with invented values.

### The run manifest (anti-skip contract)

```
STARTUP        →  read juspay-plan.md → populate variables → register manifest
Phase 3        doc-fetch          → $CONSTRAINTS, $CODE_EXAMPLES, $ERROR_CODES
Phase 3.5      arch-decisions     → persona asks ≤6 doc-grounded questions → $ARCH_DECISIONS
Phase 4        webhook-config, return-url-config, apikey-provision, params-collect
Phase 5        integration-stages, codegen (uses $ARCH_DECISIONS), db-schema, platform-setup
Phase 6-9      checklist, test, stages-confirm, summary, done
               → integrate-results finalize   (HARD FAIL if any step unaccounted for)
```

Bootstrap steps (`product-select`, `platform-detect`, `plan`) are planner-answered and closed in STARTUP. The executor manifest comes from the plan file. A step may resolve to `skipped` **with a reason**; what is forbidden is a registered step being **absent** at `finalize`.

### Closed step vocabulary

The manifest may contain **only** these names (the script's `register` rejects anything else):

- **Structural / phase:** `product-select`, `platform-detect`, `plan`, `doc-fetch`, `arch-decisions`, `codegen`, `checklist`, `test`, `summary`, `done`
- **Native setup (one, platform-specific):** `android-setup`, `ios-setup`, `react-native-setup`, `flutter-setup`, `cordova-setup`, `capacitor-setup`, `web-setup`, `iframe-web-setup`
- **Interaction / decision gates (first-class, so a dropped question is caught):** `arch-decisions`, `platform-disambiguation`, `params-collect`, `apikey-provision`, `webhook-config`, `return-url-config`, `integration-preview`, `db-schema-decision`, `integration-stages`, `stages-confirm`

A well-formed manifest always contains at least `doc-fetch`, `codegen`, `test`, `summary`, `done` (the script enforces this on `register`).

**`terminal` must be an array — never a boolean.** `["passed"]` = step must succeed; `["passed","skipped"]` = step may be legitimately skipped with reason. The script rejects boolean `true`/`false` values and will error on register.

### Reference files (load on demand)

| Reference file | Load it for | Replaces |
| --- | --- | --- |
| `reference/retrieval.md` | §constraints (Phase 3), §dashboard-nav (Phase 4 fallback), §test-resources (8.3.1), §codebase-signals (4.1/4.4/5.3.1), §integration-stages (5.1/8.5) | (5 read/extract procedures) |

Each retrieval procedure is read-only, returns a strict shape, and the **asking/confirming shell always stays in the orchestrator** — only the fetch/search/extract core lives in the reference file.

> `reference/manifest-planning.md` is no longer used by the executor. The executor reads the manifest from the plan's `## Executor Manifest` section.

### The `plan` step (STARTUP only — handled automatically)

The `plan` bootstrap step is closed during STARTUP (Step S.3). It does **not** compute a manifest inline — the manifest comes from the plan file's `## Executor Manifest` section. The closing sequence is:

```
integrate-results step-start plan
integrate-results register ./steps.json      # steps.json written from plan in Step S.2
integrate-results step-end passed "manifest loaded from plan: $(count) steps for $PRODUCT/$PLATFORM"
```

After `register` succeeds, seed Wave-1 tasks (see PROGRESS TRACKING).

---

## SECURITY

**NEVER include in step verification strings, task descriptions, Bash command arguments, or any terminal output:**

- `$API_KEY` — provisioned in Step 4.2 via `juspay_create_api_key`. Never echoed, never stored in the results file.
- `$WEBHOOK_AUTH_PASSWORD` — read from `.env` in Step 4.4. Same prohibition.
- Any value returned by `juspay_create_api_key`.
- Any credential read from `.env`, `.env.local`, or similar files.

**Correct:**

```
integrate-results step-end passed "API key provisioned and stored in env var; webhook URL configured; return URL set"
```

**Wrong:**

```
integrate-results step-end passed "API key: sk-test-AbCdEf123; webhook=https://..."
```

**The `integrate-results` script structurally rejects sensitive field names on `set` (exits 1).** Never try to work around this by renaming the field.

**When running test scripts (Step 8.2):** pass credentials via shell env vars (`export JUSPAY_API_KEY=...`), not as inline arguments visible in the Bash tool call command string.

**If a script or MCP tool response includes a credential value** (e.g., `juspay_create_api_key` returning the plaintext key), store it in an in-memory variable. Inform the user it was provisioned without echoing the value:

> "A new API key has been created and stored for this session."

---

## UI INTERACTION RULES

Whenever the user must choose between fixed options:

- Use native select / choice UI
- Do NOT ask for free-text replies if options are known
- Wait for a selection before continuing
- Do NOT rephrase the same question again after rendering choices
- **Do NOT ask for information you can derive** — from the codebase, from `juspay-mcp` live data, or from the catalog

Format choices as structured options, not inline prose.

---

## PRE-FLIGHT: MCP AUTHENTICATION

This skill relies on two MCP servers:

- **`juspay-mcp`** — live merchant data (credentials, settings, integration status). Requires authentication before any tool call.
- **`juspay-docs-mcp`** — documentation structure and page content. No authentication required.

### Authentication flow

Before calling any `juspay-mcp` tool, attempt to trigger the authentication flow using whatever mechanism your agent environment exposes (e.g. an `authenticate` tool, an OAuth handshake, or a login prompt provided by the MCP server).

**If authentication succeeds** → proceed directly to Phase 1.

**If authentication fails, the tool is unavailable, or you are unsure how to authenticate in your environment** → first, consult your own agent's documentation on how to authenticate a remote MCP server (e.g. Claude Code docs, OpenCode docs, Cursor docs — whichever agent you are). Then stop and tell the user:

> "The `juspay-mcp` server needs to be authenticated before this integration can proceed.
>
> Please check your agent's documentation on authenticating MCP servers, then re-run `/jp-executor` to continue."

Do **not** attempt to call any `juspay-mcp` tool before authentication succeeds. Do not proceed to Phase 1 until authentication is confirmed.

---

## PHASE 3 — Doc Fetch

Run: `integrate-results step-start doc-fetch`

**Follow `reference/retrieval.md` §constraints** (inline) — the single biggest context win: `doc_fetch_tool` the pages in the order below, read each fully, and build `$CONSTRAINTS`, `$CODE_EXAMPLES`, `$ERROR_CODES`, `$VERSION_CONSTRAINTS`, and `$WARNINGS`. Extract per page and discard raw text so it doesn't pile up in context. Validate `$CONSTRAINTS` and `$CODE_EXAMPLES` are populated before continuing; if a required page failed to load, `step-end failed` and surface the URL rather than proceeding with empty constraints.

**Always use `doc_fetch_tool`. Only fall back to WebFetch if MCP returns an explicit error on a valid URL.**

```
juspay-docs-mcp:doc_fetch_tool({ url: "<url from $DOC_PAGES>" })
```

Fetch order — use the ordered list from `$DOC_PAGES` (the `## Doc Pages` section of the plan), which the planner already sorted:

1. Pre-Requisites / Overview — always first; defines credentials, auth format, version constraints
2. Numbered base integration pages — in the exact order from `$DOC_PAGES`
3. Webhooks, Order Status API
4. Error Codes (resources section)
5. Advanced sections — only if user asks

The exact fields to extract into `$CONSTRAINTS`/`$CODE_EXAMPLES`/`$ERROR_CODES`/`$VERSION_CONSTRAINTS`/`$WARNINGS` and the pattern rules (maxLength/minValue/format/enumValues/warnings) are in **`reference/retrieval.md` §constraints** — follow them exactly.

```
integrate-results step-end passed "fetched $(count) doc pages; $(count) fields in $CONSTRAINTS; $(count) error codes; $(count) warnings"
```


---

## PHASE 3.5 — Architecture Decisions

Run: `integrate-results step-start arch-decisions`

### Persona

You are a **payment infrastructure architect** who has just finished reading the Juspay documentation for `$PRODUCT` on `$PLATFORM`. You have `$CONSTRAINTS`, `$CODE_EXAMPLES`, `$ERROR_CODES`, and a picture of the merchant's tech stack from the codebase scan.

Your role is not to run through a checklist — it is to **understand how this payment integration will fit into the merchant's system as infrastructure**. Payment integrations touch payment state, money movement, and customer trust. Decisions here have long-term consequences: a wrong idempotency strategy means duplicate charges; a wrong state ownership model means reconciliation gaps; a wrong failure surface means silent money loss.

Think through each architectural dimension below. For each one, **derive from the docs whether there is a genuine choice** — if the docs or the merchant's stack prescribe one approach, there is no question to ask. Only ask where alternatives exist and the choice matters. **Ask at most 6 questions total.**

Phrase every question in payment-infrastructure terms. Lead with what you observed in the system or docs — not "which pattern do you prefer?" but "The docs show X and your codebase shows Y — how does your system handle Z?"

**Auto-defaults (never ask about these):**
- Idempotency key field — always `{$ENTITY_NAME}_id` (e.g. `order_id`)
- Retry strategy — `no-retry` unless docs document a specific retryable error class
- Anything the docs prescribe with exactly one valid approach

---

### Architectural Dimensions

Evaluate these in order. Only generate a question when the dimension surfaces a genuine choice given what you read.

**Dimension 1: Payment State Ownership** _(evaluate when docs show both webhook delivery AND a status polling API)_

The merchant's system will need to know the final status of every payment. Two valid paths exist when the docs provide both a webhook delivery mechanism and an order/status polling API.

Ask:
> "The docs show that payment status is delivered both via webhooks (async POST to your server) and via the order status API (on-demand polling). In your system, what is the **source of truth** for a payment's final status?"

Options (derive from what docs actually describe — only present relevant ones):
- `juspay-api-live-query` — your system never stores status locally; always queries Juspay's status API on demand
- `webhook-as-record` — webhook events are the system of record; DB is updated on each webhook event delivery
- `hybrid-with-reconciliation` — webhooks update state in real time; a periodic reconciliation job polls the status API for any payments still in a pending state after a timeout

**Dimension 2: Idempotency Contract** _(evaluate when docs show caller-provided entity IDs)_

Scan `$CODE_EXAMPLES` for the order/payment creation call. If the merchant's code supplies the `order_id` (or equivalent), a double-submission risk exists.

Ask:
> "The docs show that `$ENTITY_NAME` creation requires a caller-provided ID. A double-submission (retry, back-button, network replay) with the same ID would be rejected by Juspay — but your system also needs to be safe. How are `$ENTITY_NAME` IDs scoped in your backend?"

Options:
- `server-uuid-at-checkout` — the backend generates a fresh UUID when checkout is initiated; the client never provides or stores this ID
- `cart-scoped-deterministic` — the ID is derived deterministically from the cart/session (e.g. `order-{cartId}`), enabling safe retries: the same cart always yields the same order ID
- `external-reference-mapped` — your system has its own order reference; the Juspay `$ENTITY_NAME` ID is a stored reference field, not your primary key

**Dimension 3: Interface Surface** _(evaluate for api-only and headless products, or when docs expose multiple endpoints beyond order creation)_

Scan `$CODE_EXAMPLES` and `$DOC_PAGES` for endpoints beyond order session creation. For EC-API and EC-headless, the docs typically expose payment methods, saved cards, UPI VPA validation, etc. Also check `$CLIENT_PLATFORMS` from the plan — multiple client targets may need different response shapes.

If multiple endpoints exist, ask:
> "Beyond order creation, the docs expose these payment service endpoints: [list from docs]. Which should your backend expose as interfaces to your frontend(s)?"

Multi-select (only show options present in the docs):
- `payment-methods` — list available payment methods for a customer/order
- `saved-cards` — fetch and manage a customer's saved cards
- `upi-vpa-validate` — validate a UPI VPA before initiating payment
- `wallet-balance` — fetch wallet balance for enabled wallets
- `emi-plans` — fetch EMI options for a given amount

Follow up if `$CLIENT_PLATFORMS` has multiple entries:
> "You have multiple client platforms ([list $CLIENT_PLATFORMS]). Should these interfaces share a single API contract, or do mobile and web clients need different response shapes?"

Options:
- `unified-contract` — same endpoint, same response shape for all clients
- `platform-versioned` — separate endpoint paths or response variants per platform (e.g. `/api/payment-methods?platform=android`)

**Dimension 4: Async Delivery Resilience** _(evaluate only if `$HAS_WEBHOOKS = true`)_

Webhooks can be delayed, retried, or lost. The docs should describe Juspay's retry behavior — note how many retries and at what intervals.

Ask:
> "Juspay will retry failed webhook deliveries [N times per docs]. What is your system's recovery strategy if a webhook is never successfully delivered — for example, if your server was down during all retry attempts?"

Options:
- `reconciliation-job` — a background job periodically queries the order status API for any payments stuck in a pending state for longer than X minutes
- `rely-on-juspay-retry` — accept Juspay's retry schedule as sufficient; no merchant-side recovery job
- `dual-path-always` — the return URL handler always polls the status API to confirm final state, independent of webhook delivery

**Dimension 5: Credential Boundary** _(always ask — derive the boundary from the detected stack)_

Scan `$CODE_EXAMPLES` for how auth credentials appear (Basic Auth header, API key header, etc.). Scan the codebase for how other third-party credentials are currently managed.

Ask:
> "Juspay API calls use [auth pattern from docs, e.g. 'Basic Auth with base64(apiKey:)']. Where is the credential managed in your backend — and who can access it?"

Options (present only those relevant to `$DETECTED_LANG` and codebase patterns):
- `config-module` — a dedicated payment config module (`lib/juspay/config.{ext}`) reads env vars and exports typed values; no other code touches env vars directly
- `env-inline` — `process.env` / `os.environ` accessed directly at each call site
- `di-container` — injected via a framework DI container (NestJS `@Injectable`, Spring `@Service`, etc.); credentials never accessed directly in business logic

**Dimension 6: Failure Observability** _(evaluate when the integration is non-trivial: multi-service signals detected in codebase, or merchant already has existing payment code)_

Scan the codebase for logging libraries, error tracking integrations (Sentry, Datadog, etc.), or existing observability setup.

Ask:
> "When a payment fails or stalls, how should your team know? [If you detected an existing logging/alerting setup: 'I see you're already using [tool] — how should payment events fit in?']"

Options:
- `structured-logs-only` — every payment event logged with order ID, amount, status, and error code in your existing log stream
- `alert-on-critical-errors` — trigger your existing alerting (Sentry, PagerDuty, etc.) for specific Juspay error codes that indicate system-level failures
- `metrics-emit` — emit payment success/failure counters and latency to your existing metrics stack

---

### Store Decisions

Write all answers to `$ARCH_DECISIONS` — a keyed object. Keys not applicable to this product are `null`.

```
$ARCH_DECISIONS = {
  paymentStateOwnership: "...",         // or null if not applicable
  idempotencyContract: "...",           // or null if API returns ID
  idempotencyKey: "{$ENTITY_NAME}_id",  // always auto-set
  interfaceSurface: [...],              // array of selected capabilities, or null
  clientContractShape: "...",           // or null if single platform
  asyncResilienceStrategy: "...",       // or null if $HAS_WEBHOOKS = false
  credentialBoundary: "...",
  failureObservability: "..."           // or null if simple/greenfield
}
```

```
integrate-results step-end passed "arch decisions collected: [list of non-null keys]"
```


---

## PHASE 4 — Parameter Collection

Phase 4 is **several registered gate steps**, not one — each gets its own `step-start`/`step-end` bookend so a dropped question becomes a hard fail at `finalize`. The steps the planner registered from this phase's vocabulary: `webhook-config` (only if `$HAS_WEBHOOKS = true`), `return-url-config`, `apikey-provision`, `params-collect`. Run each that is in the manifest; for any whose work turns out unnecessary at runtime (e.g. already configured), close it with `step-end skipped "<reason>"` — never leave it unstarted.

Tell the user:

> "I've read the documentation. I'll collect what I need."

### Step 4.1 — Auto-resolve merchant context via MCP (shared prep)

`$MERCHANT_ID` and `$CLIENT_ID` were read from the plan in Step S.1 — reuse those values. Do not call `juspay_get_merchant_details()` again.

Always call:

```
juspay-mcp:juspay_get_general_settings()
```

From general settings, extract:

- `$RETURN_URL` — existing return URL if configured (check if non-empty)

**Webhook check — only if `$HAS_WEBHOOKS = true` (set by the merchant in the planner).** The planner asked the merchant whether they want webhook support. If `$HAS_WEBHOOKS = false`, skip all webhook-related MCP calls and configuration. Do not ask again.

If `$HAS_WEBHOOKS = true`, call:

```
juspay-mcp:juspay_get_webhook_settings()
```

From webhook settings, extract:

- `$WEBHOOK_URL` — existing webhook URL if configured (check if non-empty)
- `$WEBHOOK_EVENTS` — currently subscribed events

**If `$HAS_WEBHOOKS = false` and `webhook-config` is in the manifest:**

```
integrate-results step-start webhook-config
integrate-results step-end skipped "merchant opted out of webhook support during planning; relying on order status API for payment confirmation"
```

Skip to the `return-url-config` step.

**If `$HAS_WEBHOOKS = true` AND `$WEBHOOK_URL` is empty or not configured:**

First, scan the codebase for an existing webhook handler (e.g. `api/juspay/webhook`, `api/webhook`, `webhooks` route). If one exists, note its path as `$WEBHOOK_PATH`.

> **Greenfield note:** On a first-time integration the webhook handler does not exist yet — it is generated in Phase 5. If the scan finds no handler, set `$WEBHOOK_PATH` to the route the codegen step _will_ create (the conventional path for this product/framework, e.g. `/api/juspay/webhook`) and treat it as the planned path. Never emit a URL containing an empty or undefined path segment. When you reach Phase 5, generate the webhook handler at exactly this path so the dashboard value the user configured stays correct.

Begin the registered `webhook-config` step:

```
integrate-results step-start webhook-config
```

> If `juspay_get_webhook_settings` reported `$WEBHOOK_URL` already configured, close the step now — `step-end skipped "webhook already configured: $WEBHOOK_URL"` — and skip the rest of this block. The planner registered the step; resolve it, don't drop it.

**Dashboard nav lookup — plan-first:** If `$DASHBOARD_HINTS` has a `webhook` entry (from the plan's `## Dashboard Config Hints` section), use it directly:

- `$WEBHOOK_DASHBOARD_NAV` ← `$DASHBOARD_HINTS.webhook.nav`
- `$WEBHOOK_DASHBOARD_LINK` ← `$DASHBOARD_HINTS.webhook.link`
- `$WEBHOOK_EVENTS_REQUIRED` ← `$DASHBOARD_HINTS.webhook.eventsRequired`

**Only if `$DASHBOARD_HINTS` has no webhook entry**, fall back to live discovery per `reference/retrieval.md` §dashboard-nav (`target=webhook`): call `explore_product("dashboard")`, find the webhook/settings page, fetch it with `doc_fetch_tool`, and extract `$WEBHOOK_DASHBOARD_NAV`, `$WEBHOOK_DASHBOARD_LINK`, `$WEBHOOK_EVENTS_REQUIRED`. Validate non-empty before presenting; if empty, `step-end failed` and surface the raw docs URL.

Ask the user for their deployed base URL (needed to compute the full webhook URL):

> "No webhook URL is configured on your account. Please provide your deployed base URL so I can show you exactly what to set in the dashboard.
>
> If you don't have a deployed URL yet, you can use `ngrok http <port>` or `cloudflared tunnel` to get a temporary public URL."

Once the user provides a base URL, compute `$CONFIGURED_WEBHOOK_URL` = `<user-provided base URL>` + `$WEBHOOK_PATH` (the planned or existing path from the scan above — never empty), normalising so there is exactly one `/` at the join. Present the configuration instructions:

> "**Configure your webhook in the Juspay dashboard:**"

> **URL to set:** `$CONFIGURED_WEBHOOK_URL`
>
> **Navigation:** $WEBHOOK*DASHBOARD_NAV *(from docs)_
> **Direct link:** $WEBHOOK_DASHBOARD_LINK _(if available)\_
>
> **Events to enable:**
>
> - _(list each event from `$WEBHOOK_EVENTS_REQUIRED`)_
>
> 📖 Configuration guide: $WEBHOOK_DOCS_URL
>
> Let me know once you've saved the settings and I'll continue."

Wait for the user to confirm the webhook is configured. Store the confirmed URL as `$WEBHOOK_URL`.

```
integrate-results step-end passed "webhook URL configured manually via dashboard: $WEBHOOK_URL; navigation instructions provided"
```


Begin the registered `return-url-config` step:

```
integrate-results step-start return-url-config
```

> If `$RETURN_URL` came back already configured from `juspay_get_general_settings`, close it now — `step-end skipped "return URL already configured: $RETURN_URL"` — and skip the rest of this block.

**If `$RETURN_URL` is empty or not configured:**

First, scan the codebase (inline, per `reference/retrieval.md` §codebase-signals mode `return-handler-path`) for an existing return URL page or handler that can receive the Juspay redirect and handle order status response. If one exists, note its path as `$RETURN_PATH`.

**Dashboard nav lookup — plan-first:** If `$DASHBOARD_HINTS` has a `return-url` entry, use it directly:

- `$SETTINGS_DASHBOARD_NAV` ← `$DASHBOARD_HINTS["return-url"].nav`
- `$SETTINGS_DASHBOARD_LINK` ← `$DASHBOARD_HINTS["return-url"].link`

**Only if `$DASHBOARD_HINTS` has no `return-url` entry**, fall back to live discovery per `reference/retrieval.md` §dashboard-nav (`target=general-settings`): reuse `$DASHBOARD_DOCS` from the webhook step if set, otherwise call `explore_product("dashboard")`, find the general settings page, and fetch it with `doc_fetch_tool`. Extract `$SETTINGS_DASHBOARD_NAV` and `$SETTINGS_DASHBOARD_LINK`. Fall back to `list_products({ category: "DASHBOARD" })` if `explore_product` fails.

Ask the user for the return URL:

> "No return URL is configured for your account. Please provide the URL customers should be redirected to after payment completes. This must be a real route in your app that handles the payment return/order status response."

After the user provides a URL, check whether its path matches an existing route or handler in the codebase. Two cases:

- **Greenfield (no matching route yet):** this is expected on a first-time integration — the return handler is generated in Phase 5. Do **not** warn. Record the path as `$RETURN_PATH` and generate the handler at exactly that path in Phase 5 so the configured value stays correct.
- **Existing codebase with a conflicting route:** if the codebase already has return/redirect handlers and the provided URL matches none of them, warn:

  > "The URL you provided doesn't match any existing return handler in your codebase, and you already have other routes defined. I'll create a handler at this path in the code-generation step — confirm this is the path you want, or give me one that matches an existing route."

Once confirmed, present the configuration instructions:

> "**Configure your return URL in the Juspay dashboard:**
>
> **URL to set:** `<user-provided URL>`
>
> **Navigation:** $SETTINGS*DASHBOARD_NAV *(from docs)_
> **Direct link:** $SETTINGS_DASHBOARD_LINK _(if available)\_
>
> 📖 Configuration guide: $SETTINGS_DOCS_URL
>
> Let me know once you've saved the settings."

Wait for the user to confirm. Store the confirmed URL as `$RETURN_URL`.

```
integrate-results step-end passed "return URL configured via dashboard: $RETURN_URL"
```


**Environment is always production.** Do not ask the user. Use production host URLs from the docs. Juspay registers integration-checklist stages only when real transactions flow through a live account, so testing (Phase 8) runs against production using Juspay's Dummy PG simulator (test cards/VPAs).

**Disclose this posture once**, before provisioning the API key:

> "Heads up: this integration runs against your **production** Juspay account. I'll provision a production API key and run test transactions through Juspay's Dummy PG simulator (no real money moves, but the key and activity are on your live account). Continue?"

Wait for confirmation before Step 4.2.

### Step 4.2 — Provision API key (only if one isn't already available)

Begin the registered `apikey-provision` step: `integrate-results step-start apikey-provision`.

**Check for an existing key first — do not create a new key on every run.** Calling `juspay_create_api_key` unconditionally proliferates keys on the merchant's production account, especially across `--from` re-runs. In order:

1. If `$API_KEY` is already set in memory this session → reuse it; skip creation.
2. Otherwise scan the project's `.env` / `.env.local` for an existing `JUSPAY_API_KEY` (or the env-var name this product's docs use). If present and non-empty → load it into `$API_KEY` in memory and inform the user: _"Using the existing API key from your `.env`."_ Skip creation.
3. Only if neither exists, create a new one:

```
juspay-mcp:juspay_create_api_key({ description: "jp-executor-<product>-<date>" })
```

Store the returned plaintext value as `$API_KEY` **in memory only — never log it, never store it in the results file, never echo it**. Inform the user:

> "A new API key has been created for your account for this integration."

**Write the API key to `.env` immediately** (only if not already present — check before appending):

```bash
grep -q "JUSPAY_API_KEY" .env 2>/dev/null || echo "JUSPAY_API_KEY=" >> .env
```

The key value itself must be written by the user or by the codegen step — do not embed a plaintext key value in a shell command. The line `JUSPAY_API_KEY=` acts as a placeholder that codegen fills.

**Never display the key value to the user. Never pass it to `integrate-results`.**

Close the step (credential-free verification only):

```
integrate-results step-end passed "API key resolved (provisioned or reused from env); env placeholder written to .env"
```


### Step 4.3 — Collect remaining required params

Begin the registered `params-collect` step: `integrate-results step-start params-collect`.

**Do not assume or invent param values, and do not proceed with only the merchant/client ID.** The transcript failure mode is closing this step having collected almost nothing. The **question gate** makes this structural — you literally cannot close `params-collect` as `passed` until every declared field is recorded:

1. **Build the elicitation list from `$CONSTRAINTS`.** Take every field where `required = true`, then remove the ones with an automatic source (`$MERCHANT_ID`, `$CLIENT_ID`, `$API_KEY`, `$WEBHOOK_URL`, `$RETURN_URL`, and anything you generate per-transaction like `order_id`/`amount` that the _app_ supplies at runtime). What remains is the set of values **the user must provide now** (e.g. business config, fixed identifiers, currency, environment-specific IDs the docs call for).
2. **Declare them to the gate:**

   ```
   integrate-results expect params-collect "<field1>,<field2>,…"
   ```

3. **Ask for each field via the native question UI**, one structured prompt — never free-text guesses, never silent defaults. If a field has a documented default or `enumValues`, present those as choices. As each value comes back, record it AND **write it to `.env` immediately**:

   ```
   integrate-results fulfilled params-collect <field>
   # then immediately:
   echo "JUSPAY_<FIELD_NAME_UPPER>=<value>" >> .env
   ```

   **Write env vars as each value is confirmed — do not defer to codegen.** Env vars deferred to codegen risk being lost on context compaction. The only exception is `$API_KEY` (handled in Step 4.2) and dynamic per-transaction values (order IDs, amounts) which are never env vars.

4. **Platform version check** (SDK path only) — if docs specify a minimum version.
5. **Backend language** — if not already detected from codebase.

**Closing rule:** `step-end params-collect passed` is **refused by the script** until every `expect`ed field has a matching `fulfilled`. If the user genuinely declines a required value, record `step-end skipped "<which fields the user deferred>"` instead — never fabricate. The verification string must name what was collected (e.g. `"collected: currency=INR, businessId=…; env vars written to .env"`), not a vague "confirmed".

### Step 4.4 — Resolve backend base URL and webhook auth credentials

**`$BACKEND_BASE_URL`** — scan the codebase for the backend's listening port and base URL:

| Signal                                                                            | Derived value           |
| --------------------------------------------------------------------------------- | ----------------------- |
| `PORT=XXXX` in backend `.env` or `.env.local`                                     | `http://localhost:XXXX` |
| `EXPO_PUBLIC_API_URL` / `VITE_API_URL` / `NEXT_PUBLIC_API_URL` in frontend `.env` | use that value directly |
| `--port XXXX` in `package.json` dev script                                        | `http://localhost:XXXX` |
| `ports: - "XXXX:XXXX"` in `docker-compose.yml`                                    | `http://localhost:XXXX` |

If detected with confidence, confirm in one line, e.g.:

> "Backend detected at `http://localhost:3001` (from `backend/.env: PORT=3001`)."

If not determinable, ask:

> "What URL is your backend running on? (e.g., `http://localhost:3001`)"

Store as `$BACKEND_BASE_URL`.

**`$WEBHOOK_AUTH_USERNAME` / `$WEBHOOK_AUTH_PASSWORD`** — if the webhook handler uses Basic Auth (check the generated code or backend `.env`), read these values from `.env` rather than asking. Store them **in memory only**. Never log them or include them in step verification strings.

**Sandbox note:** The test scripts in Step 8.2 call your local backend, which in turn calls Juspay's API with your configured credentials. To test with Juspay's Dummy PG (simulator), no extra setup is needed — the test resources page (fetched in Step 8.3.1) lists test cards and UPI VPAs that route to the simulator automatically.

```
integrate-results step-end passed "params collected; backend URL: $BACKEND_BASE_URL; webhook auth read from env"
```


---

## PHASE 4.5 — Integration Preview & Approval

Run: `integrate-results step-start integration-preview`

**Purpose:** Show the developer exactly what the executor is about to write to their system. Nothing is generated until this step is approved. This is the last chance to course-correct before code generation.

### What to present

**1. Files to be created** — derive from `$ARCH_DECISIONS`, `$DETECTED_LANG`, `$PRODUCT`, `$PLATFORM`, `$HAS_WEBHOOKS`, and `$API_CAPABILITIES` (if api-only):

```
Files to be created:
  lib/juspay/config.{ext}          — credential config module
  lib/juspay/session.{ext}         — order session creation
  lib/juspay/order-status.{ext}    — order status lookup
  lib/juspay/webhook-handler.{ext} — (only if $HAS_WEBHOOKS = true)
  lib/juspay/payment-methods.{ext} — (only if payment-methods in $API_CAPABILITIES)
  lib/juspay/saved-cards.{ext}     — (only if saved-cards in $API_CAPABILITIES)
  [frontend/SDK files — only if $PRODUCT_TYPE = sdk or hybrid]
```

Use actual file extensions for `$DETECTED_LANG`. Use actual paths as they will be written.

**2. Routes/endpoints to be wired** — list each endpoint and the merchant's existing router file where it will be registered:

```
Routes to be registered in {{detected router file}}:
  POST /api/juspay/session          → lib/juspay/session.{ext}
  GET  /api/juspay/status/:id       → lib/juspay/order-status.{ext}
  POST /api/juspay/webhook          → lib/juspay/webhook-handler.{ext} (if $HAS_WEBHOOKS)
  GET  /api/juspay/payment-methods  → lib/juspay/payment-methods.{ext} (if applicable)
  GET  /api/juspay/saved-cards      → lib/juspay/saved-cards.{ext} (if applicable)
```

**3. Merchant system touches** — a callout about changes to EXISTING files:

```
⚠️  CHANGES TO YOUR EXISTING SYSTEM

  ROUTER / SERVER FILE
  └── {{detected router file}}
      Juspay route registrations will be added here.
      ({{N}} lines of registration code — no logic moved or deleted)

  ENV FILE
  └── .env
      Keys already written in Steps 4.2–4.3: {{list}}
      Keys codegen will fill in: {{list if any}}

  DATABASE  (only if db-schema-decision will create/modify schema)
  └── {{schema file or ORM file}}
      ⚠️  Explicit approval required before this change is made (Step 5.3)

  NOTHING ELSE is modified — no existing handlers, models, business
  logic, or services will be changed without your explicit approval.
```

**4. Payment flow** — one plain-English paragraph describing how a payment flows through this integration from end to end, using the actual values collected.

**5. Approval gate:**

> "This is the complete set of changes the integration will make. Ready to generate code?"

Native select:
- `Yes — generate the code`
- `I want to adjust something`
- `Stop`

**If "I want to adjust something":** ask what to change (freetext). Update `$ARCH_DECISIONS` if the adjustment is an arch decision. Re-render the preview. Only proceed after approval.

**If "Stop":** tell the user "Integration stopped. No code has been generated. Env vars written so far have been preserved." Halt.

**If "Yes":**

```
integrate-results step-end passed "integration preview approved; {{N}} files to create; merchant-system touches confirmed"
```

---

## PHASE 5 — Code Generation

Phase 5 is **four registered steps**, run in order, each its own bookend (never nest one inside another — only one step may be pending at a time): `platform-disambiguation` (native platforms only, if registered), `integration-stages`, `codegen`, `db-schema-decision`.

### Step 5.0 — Platform disambiguation (only if the manifest registered it)

The planner registers `platform-disambiguation` **only when `$PLATFORM` is exactly `android` or `ios`** — the native language/toolchain choice. It is **not** registered for `react-native`, `flutter`, `cordova`, `capacitor`, `web`, `iframe-web`, or `api-only` (a cross-platform framework has no Java-vs-Kotlin question, and the `web` vs `iframe-web` distinction is already resolved in `$PLATFORM` from the plan). If `platform-disambiguation` is **not** in the manifest, skip straight to Step 5.1 — do not invent it.

```
integrate-results step-start platform-disambiguation
```

Ask the variant questions that apply, then store the resolved variant:

- Android: Java vs Kotlin
- iOS: Swift vs Objective-C, CocoaPods vs SPM

```
integrate-results step-end passed "platform variant resolved: <e.g. android/kotlin>"
```


### Step 5.1 — Fetch integration stages

This is the registered `integration-stages` step. Begin it: `integrate-results step-start integration-stages`.

**Follow `reference/retrieval.md` §integration-stages** (inline) — call `juspay_integration_monitoring_status`, walk the nested response per the rules below, and build `$INTEGRATION_STAGES[]` + `$SCORE_BASELINE`. Validate the result is non-empty before continuing; if the call fails, `step-end failed` and surface the error rather than proceeding with no stage contract.

`juspay_integration_monitoring_status` discovers which payment flows Juspay expects this integration to cover. The result becomes `$INTEGRATION_STAGES` — a second constraint source that drives codegen alongside `$CONSTRAINTS` from Phase 3. The exact `product_integrated`/`platform` mappings, the nested-response traversal, the include filter, and the per-stage fields + `$SCORE_BASELINE` are in **`reference/retrieval.md` §integration-stages** — follow them exactly.

**These stages are the integration contract.** Every stage in `$INTEGRATION_STAGES` must have corresponding code coverage in the steps below. Stages where `criticalResult: true` must be covered before non-critical ones.

```
integrate-results step-end passed "fetched $(count) integration stages; baseline $(critical)/$(total) critical passing"
```


### Step 5.2 — Code generation

Now begin the registered `codegen` step: `integrate-results step-start codegen`.

**Rules:**

- Use code examples and method names from fetched docs as the base. Substitute collected values. Do not use method or class names you did not see in the docs.
- Every visible, non-disabled stage in `$INTEGRATION_STAGES` (Step 5.1) must have corresponding code coverage. Cover critical stages first.
- Every constrained field in `$CONSTRAINTS` (Phase 3) must pass through the validation layer before it reaches the API. No parameter bypasses its documented bounds.
- **Before generating frontend/SDK code**, re-read `products/$PRODUCT.md` for any platform-specific or integration-type-specific code instructions. Every such instruction section must be applied exactly as written when generating code for the matching platform or integration type.
- **Interface-first principle:** All Juspay-specific logic lives in dedicated interface files (e.g. `lib/juspay/`, `services/juspay/`). The only change to the merchant's existing code is registering the new routes in their router file. Do NOT modify existing business logic, models, or API handlers. The integration stitches in — it does not rewrite.
- **Webhook is conditional:** Only generate a webhook handler if `$HAS_WEBHOOKS = true`. When `$HAS_WEBHOOKS = false`, omit the webhook handler entirely and ensure the order status endpoint is sufficient for payment confirmation.
- **API capabilities:** For api-only products, generate one interface file per capability in `$API_CAPABILITIES` (e.g., `payment-methods.{ext}`, `saved-cards.{ext}`). Each file contains exactly the interface for that capability — no bundling multiple capabilities into one file.

#### Step 5.2.0 — Apply architecture decisions from plan

Before writing any code, read `$ARCH_DECISIONS` and apply each decision to the generated code:

**T1 — Credential Management (`accessPattern`):**
- `typed-config-module` → generate a dedicated config module (e.g. `config/juspay.ts` or `config/juspay.py`) that reads env vars and exports typed constants; import this module at all usage sites
- `direct-env-access` → read `process.env.*` / `os.environ[...]` inline at each usage site
- `dependency-injection` → inject config via constructor or DI container; never access env vars directly in business logic

**T2 — Entity Identifier Strategy (`idGeneration`, `prefixPattern`):**
- `server-generated-prefixed` → generate IDs as `` `${prefixPattern}${Date.now()}-${Math.random().toString(36).slice(2)}` `` on the server
- `server-generated-uuid` → use `uuid()` / `nanoid()` on the server; never accept an ID from the client
- `passthrough-client` → accept the ID from the client request body; validate non-empty

**T3 — Persistent State Design (`storageStrategy`, `statusHistory`):**
- `no-persistence` → no DB calls; return the Juspay API response directly to the caller
- `new-table` → generate DB schema in the `db-schema-decision` step; add DB write calls in the webhook handler and session creator
- `extend-existing` → add Juspay fields to the existing schema in the `db-schema-decision` step; same DB writes
- `app-layer-only` → track state in application cache / session; no persistent DB writes
- `full-history` → each webhook event appends a new row (INSERT) — never update existing rows
- `latest-only` → each webhook event upserts the single status row (INSERT ON CONFLICT DO UPDATE or equivalent)

**T4 — Error Handling & Retry (`retryStrategy`):**
- `exponential-backoff` → wrap every Juspay API call in a retry helper: max 3 attempts, delay doubles each time (1s → 2s → 4s), retry only on transient errors (5xx, timeout)
- `fixed-retry-3` → retry up to 3 times with a fixed 1 s delay between attempts
- `no-retry` → throw / return the error immediately on the first failure; do not retry

**T5 — Async Delivery Resilience (`asyncResilienceStrategy`) — only if `$HAS_WEBHOOKS = true`:**
- `reconciliation-job` → generate a reconciliation utility that queries the Juspay order status API for any `$ENTITY_NAME` records in a pending state; the merchant wires this to their job scheduler
- `rely-on-juspay-retry` → no reconciliation code; webhook handler includes idempotency guard but no recovery fallback
- `dual-path-always` → the return URL handler always calls the order status API to confirm final state, regardless of webhook delivery; webhook handler updates DB idempotently from either path

**T5b — Webhook Idempotency (`idempotencyKey` = `{$ENTITY_NAME}_id` — always auto-set):**
- `db-unique-constraint` → define a UNIQUE constraint on the `$ENTITY_NAME}_id` column; catch duplicate-key DB errors in the webhook handler and return HTTP 200
- `redis-lock` → acquire a Redis lock keyed on `{$ENTITY_NAME}_id` before processing; release after commit; skip if lock already held
- `none` → no idempotency guard (acceptable for dev/low-volume only)

**T6 — SDK Integration (`sdkInitLocation`, `sdkPayloadDelivery`) — only if `$PRODUCT_TYPE = sdk or hybrid`:**
- `per-flow-screen` → call SDK `init()` / `open()` inside the payment screen component; tear down on unmount
- `app-entry-point` → call SDK `init()` once in `App.tsx` / `main.dart` / `AppDelegate`; expose the SDK instance via context/provider
- `lazy-first-use` → call `init()` on the first SDK usage; guard subsequent calls with an initialized flag
- `backend-endpoint` → fetch the SDK session payload from the backend (`/api/juspay/session`) immediately before calling `open()`; never store the payload client-side
- `client-env-config` → pass the SDK payload from env/build-time config; no backend round-trip for payload

**T7 — Return / Callback Handling (`returnHandlerBehavior`) — only if `$PRODUCT_TYPE = sdk or hybrid`:**
- `show-status-from-query` → on the return URL page, read `order_id` and `status` from query params; display to the user immediately
- `poll-backend` → on the return URL page, poll `GET /api/juspay/order-status/:id` every 2 s (max 10 attempts) until status is terminal; show result
- `webhook-driven` → on the return URL page, show a "Payment processing…" state; update the UI only when the backend signals the webhook-confirmed status (via WebSocket, SSE, or polling a DB-backed status endpoint)

**T8 — Interface Surface (`interfaceSurface`) — api-only and headless products:**
- For each capability in `$ARCH_DECISIONS.interfaceSurface`, generate a dedicated interface file in `lib/juspay/`. Apply the `clientContractShape` decision if `$CLIENT_PLATFORMS` has multiple entries: generate a unified endpoint or platform-versioned variants as decided.

#### Step 5.2.1 — Emit validation layer from $CONSTRAINTS

Before writing any integration code, generate a validation helper/function derived from `$CONSTRAINTS`:

- For each field with `maxLength`: add a length check that throws/returns an error if the value exceeds it. Add a `// docs: max N chars` inline comment.
- For each field with `minValue`: add a numeric floor check with a `// docs: min N` comment.
- For each field with `type = Integer` or `Decimal`: enforce correct type casting; never pass a string where the docs declare numeric.
- For each field with `enumValues`: define a constant set (enum, frozen object, or literal union type) and reference it instead of raw strings.
- For each field where `warnings` is non-empty: add a `// ⚠️ WARNING: <warning text>` comment before that field's assignment.

This validation layer is written once and referenced throughout the integration code.

#### Step 5.2.2 — Install SDK dependencies (SDK / hybrid products only)

Before writing any code that imports the SDK, install the packages the docs require.
Use the exact package names and versions stated in the Prerequisites / Getting Started page — do not install anything not mentioned there.

| Platform                               | Command                                      |
| -------------------------------------- | -------------------------------------------- |
| `react-native`, `cordova`, `capacitor` | `npm install <packages>` in the project root |
| `flutter`                              | `flutter pub add <packages>`                 |
| Native Android                         | Add to `build.gradle` `dependencies` block   |
| Native iOS                             | Add to `Podfile` then run `pod install`      |

#### Step 5.2.3 — Generate integration code

Generate in this order, placing ALL Juspay logic in the `lib/juspay/` (or `services/juspay/`) interface directory:

1. **Config module** — `lib/juspay/config.{ext}` — reads env vars, exports typed constants; no other file accesses env vars directly
2. **Session / order creation** — `lib/juspay/session.{ext}` — creates a Juspay order and returns the session payload; uses the config module
3. **Order status** — `lib/juspay/order-status.{ext}` — queries Juspay's order status API; always present (even when webhooks are enabled, this is the reconciliation tool)
4. **Webhook handler** — `lib/juspay/webhook-handler.{ext}` — **only if `$HAS_WEBHOOKS = true`**; includes HMAC/signature verification per the docs; idempotency per `$ARCH_DECISIONS.asyncResilienceStrategy`
5. **Payment method interfaces** — one file per capability in `$ARCH_DECISIONS.interfaceSurface` (e.g. `lib/juspay/payment-methods.{ext}`, `lib/juspay/saved-cards.{ext}`)
6. **SDK integration files** — frontend/SDK components only if `$PRODUCT_TYPE = sdk or hybrid`
7. **Route registration** — add route registrations to the merchant's existing router file (the only existing file touched); each route points to the corresponding interface file

**Never modify the merchant's existing business logic, model files, or API handlers.** If a capability requires interaction with merchant data (e.g., looking up a customer's cart to build the order), generate a thin interface that accepts the required data as parameters — the merchant wires their data to the interface, not the other way around.

DB schema is **not** part of this step — it is the separate `db-schema-decision` step (Step 5.3 below), run after `codegen` closes, so the two never overlap as pending steps.

#### Step 5.2.4 — Build gate (mandatory before closing codegen)

**`codegen` is not `passed` until the generated code actually compiles.** Every file you write must be complete, valid code — never a stub, a single character, or a fragment. Before `step-end`, run the project's typecheck/build and require it to succeed:

| Stack               | Gate command                                                            |
| ------------------- | ----------------------------------------------------------------------- |
| TypeScript backend  | `npx tsc --noEmit` (or the project's `build`/`typecheck` script)        |
| JS/Node             | `node --check <file>` on each generated file, or the project lint/build |
| Expo / React Native | `npx tsc --noEmit` for the app code                                     |

If it fails, read the errors, fix the generated code, and re-run until clean. Only then:

```
integrate-results step-end passed "code generated and typechecks clean: <files>; packages installed; tsc --noEmit exit 0"
```

If you genuinely cannot get a clean build (e.g. native toolchain unavailable in this environment), do **not** record `passed` — record `failed` with the actual error, or `skipped "<reason the build can't run here>"`. **Never record `passed` for code you did not verify compiles.**


### Step 5.3 — Database schema (the `db-schema-decision` step)

**Only run this step if `db-schema-decision` is in the manifest.** The planner omits it when the app already has an order/payment schema (`hasExistingOrderSchema = true`) or the flow never touches a DB — in that case there is nothing to do here, and `step-start db-schema-decision` would be rejected as not-in-manifest. Skip straight to Phase 6.

If it _is_ registered: `integrate-results step-start db-schema-decision`. Follow this flow strictly; **do not write or suggest schema changes without explicit merchant approval**. Every DB change modifies the merchant's existing system — it requires informed consent, not just a passive scan. If, after scanning, you find the existing schema already covers everything, close it honestly — `step-end skipped "existing schema already stores order_id/status; no changes needed"`.

**Before writing or modifying any schema, present a callout:**

> "⚠️ **Database change — your approval is required**
>
> I'm about to make the following change to your database:
>
> [Describe precisely: new table name and columns, OR which existing table and which columns will be added]
>
> This is a change to your **existing system** — not just a new interface file.
>
> Please confirm you want to proceed, or choose 'Skip' to handle this manually."
>
> Native select: `[Yes, make this change | Skip — I'll handle DB changes myself]`

Only generate schema after explicit "Yes". If "Skip": `step-end skipped "merchant chose to handle DB schema manually"`.

#### Step 5.3.1 — Scan for existing schemas

Search the codebase (inline, per `reference/retrieval.md` §codebase-signals mode `existing-schemas`) for payment- or order-related DB definitions:

- Migration files (`migrations/`, `db/migrate/`, `*.sql`, `*.prisma`, `schema.rb`, `typeorm/*.entity.*`, `mongoose` model files, etc.)
- ORM model files that contain fields like `order_id`, `payment_status`, `transaction_id`, `amount`, or similar

Collect every match as `$EXISTING_SCHEMAS`.

#### Step 5.3.2 — Branch on findings

_If `$EXISTING_SCHEMAS` is non-empty:_

Present a summary of what was found:

> "I found existing payment/order-related schemas:
>
> - `<file>`: `<table/model name>` — fields: `<list relevant fields>`
> - _(repeat for each)_
>
> Would you like me to:
>
> 1. **Extend these** — add Juspay-specific fields (`juspay_order_id`, `payment_status`, etc.) to the existing schema
> 2. **Create a separate table** — add a new `juspay_orders` table alongside the existing ones
> 3. **Skip DB changes** — I'll handle order correlation in the application layer only"

Wait for a selection. Apply only what the user confirms.

_If `$EXISTING_SCHEMAS` is empty:_

Ask permission before creating anything:

> "No existing payment or order schemas found. The docs require storing: `<fields derived from $CONSTRAINTS and the product's status/webhook docs>`.
>
> Shall I create a DB schema for this? If yes, which format?
>
> 1. Raw SQL migration
> 2. Prisma schema
> 3. TypeORM entity
> 4. Mongoose model
> 5. Skip — I'll handle this manually"

Wait for a selection. If the user picks 1–4, generate the schema using field names and constraints from the fetched docs. If they pick 5, skip entirely.

#### Step 5.3.3 — Generate the agreed schema

The `db-schema-decision` step is already in progress (started at Step 5.3). Use field names, lengths, and constraints from `$CONSTRAINTS` and the product's order/webhook docs. Apply `maxLength` values from `$CONSTRAINTS` as column size constraints (e.g., `VARCHAR(20)` for a field with `maxLength: 20`). Do not add fields that don't appear in the docs or that the user didn't request.

```
integrate-results step-end passed "db schema generated: <table/model name>; N columns; constraints from $CONSTRAINTS applied"
```


---

## PHASE 6 — Native SDK Setup (Mobile Platforms Only)

**Trigger**: `$PRODUCT_TYPE` is `sdk` or `hybrid` AND `$PLATFORM` is any of: `android`, `ios`, `react-native`, `flutter`, `cordova`, `capacitor`.

The step name is the platform-specific name the planner registered: `{$PLATFORM}-setup` (e.g., `react-native-setup`, `android-setup`, `web-setup`).

**If NOT triggered**, the handling depends on whether the planner registered `{$PLATFORM}-setup` in the manifest:

**Case A — step NOT in the manifest** (`$PRODUCT_TYPE = api-only`, or `$PRODUCT_TYPE = hybrid` with a non-mobile platform such as `web` or `iframe-web`):
No lifecycle calls are needed — the step was never registered. Skip directly to Phase 7.

**Case B — step IS in the manifest but native setup is not applicable** (`$PRODUCT_TYPE = sdk` on a non-mobile platform such as `web` or `iframe-web`, where the docs show no native SDK setup is needed):

```
integrate-results step-start {$PLATFORM}-setup
integrate-results step-end skipped "not applicable: $PLATFORM is not a native mobile platform; no native SDK setup required"
```

Flip `{$PLATFORM}-setup` task to `completed`. Skip to Phase 7.

---

Run: `integrate-results step-start {$PLATFORM}-setup`

**Rule**: Every action in this phase is grounded in the docs fetched in Phase 3. Do not invent steps.

### Step 6.1 — Extract native setup requirements from docs

Re-scan the pages already fetched in Phase 3 (Prerequisites / Overview / Getting Started, plus any "Android Setup" or "iOS Setup" named sections) and extract:

- `$NATIVE_PACKAGES` — packages/dependencies to install (npm/pub/gradle/cocoapods)
- `$BUILD_TOOL_CHANGES` — edits required to build config files (`build.gradle`, `pubspec.yaml`, etc.)
- `$PLATFORM_CONFIG_FILES` — config files that must be created (e.g. a `*.txt` or `*.json` with SDK credentials)
- `$POST_INSTALL_SCRIPTS` — scripts that must run after package install (e.g. Podfile hooks, asset fuse scripts)
- `$PREBUILD_REQUIRED` — whether a prebuild/generate/sync step is needed before native directories are accessible

If a docs section is explicitly labelled "Android Setup" or "iOS Setup", treat its full contents as authoritative for that platform — do not skip any step it lists.

### Step 6.2 — Check project structure

Detect the project workflow from the codebase before running anything:

| Signal                                                     | Workflow                                     |
| ---------------------------------------------------------- | -------------------------------------------- |
| `app.json` with `"expo"` key, no `android/` or `ios/` dirs | Expo managed — native dirs must be generated |
| `android/` and `ios/` present                              | Bare / native — no prebuild needed           |
| `pubspec.yaml`                                             | Flutter — use Flutter toolchain              |
| `config.xml`                                               | Cordova — use `cordova platform add`         |
| `capacitor.config.*`                                       | Capacitor — use `npx cap sync`               |

For managed workflows where native directories do not yet exist: run the framework's generate/sync command (e.g. `npx expo prebuild` for Expo managed, `npx cap sync` for Capacitor, `flutter pub get` for Flutter) **only if `$PREBUILD_REQUIRED` is true or the framework mandates it**. Never run a destructive generate command on a repo that already has native directories.

### Step 6.3 — Execute build tool changes

For each item in `$BUILD_TOOL_CHANGES`:

- Read the target file first.
- Apply the change using the Edit tool (not shell sed/awk) so the diff is reviewable.
- Check idempotency — if the value is already present, skip it.

### Step 6.4 — Create platform config files

For each item in `$PLATFORM_CONFIG_FILES`:

- Write the file using the Write tool.
- Substitute `$CLIENT_ID`, `$MERCHANT_ID`, or any other resolved credential from Phase 4.

### Step 6.5 — Run post-install scripts and dependency sync

For each item in `$POST_INSTALL_SCRIPTS`:

- Run via Bash.
- Capture stdout/stderr; if it exits non-zero, read the error, diagnose, fix, and re-run.
- Do not continue to the next step until this one exits 0.

### Step 6.6 — Summary table

Report results before recording the step:

```
## Native SDK Setup Complete

| Step | Action | Result |
|------|--------|--------|
| packages installed | <names from docs> | ✅ / ❌ |
| prebuild / generate | <command, or "skipped — dirs existed"> | ✅ / skipped / ❌ |
| build config patched | <files changed> | ✅ / ❌ |
| config files created | <files created> | ✅ / ❌ |
| post-install scripts | <commands run> | ✅ / ❌ |
```

Any ❌ row must include the captured error output and the fix attempted.

```
integrate-results step-end passed "{$PLATFORM}-setup complete: packages installed, build configs patched, config files created, post-install scripts run"
```


---

## PHASE 7 — Checklist and Error Reference


Run: `integrate-results step-start checklist`

### Checklist

Generate a checklist from what you actually fetched — every item must reflect something real in this product's docs.

````
## Integration Checklist — [Product] on [Platform or API]

### Credentials
- [ ] [credential from docs] stored as env var
- [ ] API key generated via dashboard or juspay-mcp

### [Backend / API]
[items derived from API doc pages]

### [Frontend / SDK] (if applicable)
[items derived from SDK doc pages]

### Testing
- [ ] Successful sandbox transaction
- [ ] Error case from $ERROR_CODES tested
- [ ] Webhooks verified end-to-end

### Integration Stages (from Juspay)

`$INTEGRATION_STAGES` was already fetched in Step 5.1 — render it directly, no second MCP call needed here.

For each stage in `$INTEGRATION_STAGES`, emit:

```
### [stage.section]   ← the section dict key is the display name, e.g. "Payments Flow Checklist"

- [ ] **[stage.stageDisplayName]** ⚠️ Critical ← only if stage.criticalResult is true
      [stage.stageDescription]
```

- Do **not** show `status` — it reflects past activity, not a live gate; developers check stages off as they test
- Mark stages where `criticalResult: true` as ⚠️ Critical
- Group stages by `stage.section` (the section dict key)

### Go-Live
- [ ] Switched to production environment and keys
- [ ] Production end-to-end test passed
````

### Parameter Constraints

Emit this table only for fields in `$CONSTRAINTS` where at least one constraint column is non-null. Omit fully unconstrained fields to keep it scannable.

```
## Parameter Constraints

| Field | Type | Required | Max Length | Min Value | Format | Warnings |
|-------|------|----------|------------|-----------|--------|---------|
[one row per constrained field from $CONSTRAINTS — populate only non-null columns; use — for nulls]
```

### Error Reference

```
## Error Codes

| Code / Status | Meaning | Recommended action |
|---------------|---------|-------------------|
[from $ERROR_CODES]
```

### What's next

Briefly offer to go deeper on sections from `$DOC_MAP` that weren't part of the base integration — but only mention things you actually saw in the doc map.

```
integrate-results step-end passed "checklist generated from docs; $(count) integration stages from monitoring API; error reference table built"
```


---

## PHASE 8 — Live Testing

Run: `integrate-results step-start test`

**Always attempt to run the server and test the integration yourself. Do not tell the user to test manually if you can do it.**

> **`test passed` means tests actually ran.** Record `passed` only if real requests executed and you observed the HTTP status / body / DB effect (see GUARDRAIL 19). Calling a metrics endpoint or merely listing test cards is **not** a test. If nothing testable can run in this environment, record `step-end skipped "<why: e.g. mobile build requires a device>"` and provide the manual guide — never a fabricated pass.

### Step 8.1 — Start the dev server

Scan the codebase for the start command:

| Signal                              | Command                       |
| ----------------------------------- | ----------------------------- |
| `package.json` with `"dev"` script  | `npm run dev` (or `yarn dev`) |
| `pubspec.yaml`                      | Cannot run — skip to Step 8.3 |
| Mobile-only project (no web server) | Cannot run — skip to Step 8.3 |

**Before starting** the server, call:

```
integrate-results set active waiting
```

Run the server in the background, wait for it to be ready, then:

```
integrate-results set active working
```

> **Important:** Shell environment variables override `.env` files in Vite/Node. Before starting the server, check if any required env vars (e.g. `JUSPAY_API_KEY`) are already set in the shell and would conflict with the project's `.env`. Unset them if they don't belong to this project.

### Step 8.2 — Run backend API tests

If the product file (in `products/$PRODUCT.md`) defines a `## Test Scripts` section, use those scripts for Steps 8.2.2 and 8.2.3 — the exact invocation, arguments, and verification criteria are specified there. Each script prints the HTTP status, response body, and an explicit ✅ / ❌. If a script exits non-zero, read the output, diagnose the root cause (wrong env var, bad header, type mismatch, server log), fix, and re-run until it passes.

If no product-specific test scripts are defined, test Steps 8.2.2 and 8.2.3 with inline curl against `$SESSION_ENDPOINT` and `$ORDER_STATUS_ENDPOINT`.

**SECURITY: Pass credentials to test scripts via exported env vars, not inline in the command string.**

```bash
export JUSPAY_API_KEY="$API_KEY"  # set in env, do not inline in command
```

**Step 8.2.1 — Generate test parameters**

Auto-generate these — do not ask the user:

- `TEST_ORDER_ID` = `test-$(date +%s)` (unique per run)
- `TEST_AMOUNT` = `1.00`
- `TEST_CUSTOMER_ID` = `test-customer-001`
- `TEST_CUSTOMER_EMAIL` = `test@juspay.in`
- `TEST_CUSTOMER_PHONE` = `9999999999`
- `TEST_FIRST_NAME` = `Test`, `TEST_LAST_NAME` = `User`
- `FAIL_ORDER_ID` = `fail-$(date +%s)` (separate ID for the failure-case webhook test)

Derive endpoint URLs from the generated backend routes and `$BACKEND_BASE_URL`:

- `SESSION_ENDPOINT` = `$BACKEND_BASE_URL` + session route (e.g. `/api/juspay/session`)
- `ORDER_STATUS_ENDPOINT` = `$BACKEND_BASE_URL` + order-status route prefix (e.g. `/api/juspay/order-status`)
- `WEBHOOK_ENDPOINT` = `$BACKEND_BASE_URL` + webhook route (e.g. `/api/juspay/webhook`)

**Step 8.2.2 — Session creation**

Run the session test as specified in the product file's `## Test Scripts` section (if defined), or use inline curl to POST to `$SESSION_ENDPOINT` with the test parameters from Step 8.2.1.

**Step 8.2.3 — Order status**

Run the order-status test as specified in the product file's `## Test Scripts` section (if defined), or use inline curl to GET `$ORDER_STATUS_ENDPOINT/$TEST_ORDER_ID`.

**Step 8.2.4 — Webhook tests (inline curl)**

Webhook tests use inline curl since payload structure and event names vary per product — derive both from the webhook section of the docs fetched in Phase 3:

```bash
# SUCCESS — credentials passed via env, not inline
curl -s -w "\n%{http_code}" -X POST "$WEBHOOK_ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "Authorization: Basic $(printf '%s:%s' "$WEBHOOK_AUTH_USERNAME" "$WEBHOOK_AUTH_PASSWORD" | base64)" \
  -d '<payload constructed from docs — substitute $TEST_ORDER_ID and $TEST_JUSPAY_ORDER_ID>'
```

Assert: HTTP 200 AND body contains `{"status":"ok"}` AND DB row `payment_status` = success status from docs.

Repeat for the failure event type using `$FAIL_ORDER_ID` (create a session for it first with `test-session.sh`).

Assert: HTTP 200 AND DB row `payment_status` = failure status from docs.

**Step 8.2.5 — Constraint edge-case tests (inline curl)**

For each field in `$CONSTRAINTS` where `maxLength`, `minValue`, or `type` is defined, generate one boundary test — **cap at 10 tests total**, prioritising `required` fields first, then `critical`-path fields. Skip client-only SDK fields (not part of backend API requests).

- **maxLength test**: send the field with a value of exactly `maxLength + 1` characters → expect the doc-specified error code for that field (from `$CONSTRAINTS[field].errors`). Assert HTTP 4xx AND response body contains expected error code.
- **minValue test**: send the field with value `minValue - 1` → expect the doc-specified error. Assert HTTP 4xx AND expected error code in body.
- **type test**: send a string where `type = Integer` or `Decimal` → expect the doc-specified error. Assert HTTP 4xx AND expected error code in body.
- **enumValues test**: send a value not in `enumValues` → expect rejection. Assert HTTP 4xx.

Report in Step 8.4 table with type `Constraint`.

### Step 8.3 — Frontend / SDK tests

**This step applies when `$PRODUCT_TYPE = sdk` or `$PRODUCT_TYPE = hybrid`.**

**Step 8.3.1: Fetch test credentials** (all SDK/hybrid products)

**Follow `reference/retrieval.md` §test-resources** (inline) — `doc_fetch_tool` the test-resources page and extract `$TEST_CARDS`, `$TEST_UPI_VPA`, `$DUMMY_PG_FLOWS`:

```
juspay-docs-mcp:doc_fetch_tool({ url: "<test-resources md content link from $DOC_MAP>" })
```

Extract:

- `$TEST_CARDS` — card numbers, expiry, CVV for the Dummy PG / simulator
- `$TEST_UPI_VPA` — VPA values for UPI success/failure (e.g. `success@upi`, `failure@upi`)
- `$DUMMY_PG_FLOWS` — how to trigger success vs failure for each payment method on the simulator

**Step 8.3.2: Branch on platform**

#### Web / iframe-web

The integration checklist stages (New Card, UPI Collect, UPI Intent, Wallet, etc.) are registered on Juspay's servers only when real transactions flow through the hosted payment page. For each stage:

- Navigate to the payment page URL from the session response
- Complete a transaction using `$TEST_CARDS` / `$TEST_UPI_VPA`
- Verify the callback / redirect lands on `$RETURN_URL`
- If the payment page blocks headless browsers or requires CAPTCHA, state this explicitly — do not silently skip

#### Mobile (react-native, flutter, android, ios, cordova, capacitor)

The SDK UI runs on a device/emulator and cannot be driven from the CLI. State this explicitly before providing the guide:

> "Backend tests (Step 8.2) are complete. The client-side SDK requires manual verification on a device or emulator."

Produce a ready-to-run manual test guide from `$TEST_CARDS`, `$TEST_UPI_VPA`, `$DUMMY_PG_FLOWS`, and the integration stages from the Phase 7 checklist:

```
## Manual SDK Test Guide

### Setup
- Build and install: `npx expo run:android` / `npx expo run:ios` / `flutter run` / etc.
- Backend must be running at: $BACKEND_BASE_URL

### Test flows

| # | Flow | Test input | Expected result |
|---|------|------------|-----------------|
| 1 | New Card — success | [success card from $TEST_CARDS] | Payment succeeds, app shows success |
| 2 | New Card — failure | [decline card from $TEST_CARDS] | Payment fails, error shown gracefully |
| 3 | UPI Collect — success | [success VPA from $TEST_UPI_VPA] | UPI request sent, payment succeeds |
| 4 | UPI Collect — failure | [failure VPA from $TEST_UPI_VPA] | Failure handled gracefully |
| 5 | Back / cancel | Tap hardware/software back on payment screen | App returns to checkout, no order corruption |
| [other stages from Phase 7 checklist] | | | |

### After each test
- DB: verify `payment_status` updated (via webhook)
- App: success/failure screen matches the payment outcome
```

### Step 8.4 — Report results

After all testing, report a unified pass/fail table:

```
| Test | Type | Result |
|------|------|--------|
| POST {session endpoint} → sdkPayload + DB row | Backend | ✅ / ❌ |
| GET {order-status endpoint} → status field | Backend | ✅ / ❌ |
| POST {webhook endpoint} <success event> → DB updated | Backend | ✅ / ❌ |
| POST {webhook endpoint} <failure event> → DB updated | Backend | ✅ / ❌ |
| {field} maxLength exceeded → {expected error code} | Constraint | ✅ / ❌ / — |
| {field} below minValue → {expected error code} | Constraint | ✅ / ❌ / — |
| {field} wrong type → {expected error code} | Constraint | ✅ / ❌ / — |
| New Card — success | Web / ⏭ manual on device | ✅ / ❌ / ⏭ |
| New Card — failure | Web / ⏭ manual on device | ✅ / ❌ / ⏭ |
| UPI Collect — success | Web / ⏭ manual on device | ✅ / ❌ / ⏭ |
| UPI Collect — failure | Web / ⏭ manual on device | ✅ / ❌ / ⏭ |
| [other stages from Phase 7] | Web / ⏭ manual on device | ✅ / ❌ / ⏭ |
```

— = no constraint-testable fields found for this category; Constraint rows are expanded once per constrained field.

⏭ = not automatable from CLI; manual test guide provided in Step 8.3.2.

If any automated test cannot be completed (payment page blocks headless, CAPTCHA, etc.), state the reason explicitly — never silently mark as passed.

```
integrate-results set active working
integrate-results step-end passed "backend tests: session ✅ order-status ✅ webhook-success ✅ webhook-failure ✅; SDK: manual guide provided"
```


### Step 8.5 — Confirm integration stages (the `stages-confirm` step)

This is the registered `stages-confirm` step — a sibling of `test`, not part of it. Begin it: `integrate-results step-start stages-confirm`.

**Follow `reference/retrieval.md` §integration-stages** (inline) with the **same arguments as Step 5.1**, to re-read stage status and verify test activity was recorded against the merchant account:

```
juspay-mcp:juspay_integration_monitoring_status({
  platform: <same mapping as Step 5.1>,
  product_integrated: <same mapping as Step 5.1>,
  merchant_id: $MERCHANT_ID,
  start_time: <30 days ago in YYYY-MM-DDTHH:MM:SSZ>,
  end_time: <now in YYYY-MM-DDTHH:MM:SSZ>
})
```

Walk the same traversal path as Step 5.1 and compare `status` for each `stageKey` against the baseline in `$INTEGRATION_STAGES`. Also compare the new `queryData.scoreData` against `$SCORE_BASELINE`.

Report a diff table — one row per stage from `$INTEGRATION_STAGES`:

```
## Integration Stage Confirmation

| Stage | Section | Critical | Before | After | Change |
| ----- | ------- | -------- | ------ | ----- | ------ |
| [stageDisplayName] | [section] | ⚠️ / — | [baseline status] | [new status] | ✅ / ⚠️ / ❌ |
```

- **Before / After** values: `PASSED` | `FAILED` | `NOT_ATTEMPTED`
- **Change** column:
  - ✅ — status moved to `PASSED` (was `NOT_ATTEMPTED` or `FAILED`)
  - ⚠️ — no change in status
  - ❌ — status regressed (was `PASSED`, now `FAILED` or `NOT_ATTEMPTED`)

Below the table, print the score delta:

```
Critical stages: [new baseCriticalSuccessCount] / [baseCriticalTotalCount] passing
                 (was [baseline baseCriticalSuccessCount] / [baseCriticalTotalCount])
All stages:      [new baseTotalSuccessCount] / [baseTotalCount] passing
```

If any critical stage (`criticalResult: true`) is still `NOT_ATTEMPTED` or `FAILED` after testing, flag it as a go-live blocker:

> "⚠️ Stage **[stageDisplayName]** is critical and still not passing. This must be resolved before go-live."

```
integrate-results step-end passed "stage confirmation: $(passed)/$(total) critical passing; diff vs baseline reported"
```


---

## PHASE 9 — Integration Summary

Run: `integrate-results step-start summary`

Write a persistent, developer-facing summary of every change made during this integration run. This file is the single place a future developer can look to understand what was added, what was configured, and what to expect from the integration.

### Step 9.1 — Locate the summary directory

Always write the summary to the same artifacts folder as the plan:

```
.jp-artifacts/$ARTIFACTS_FOLDER/summary.md
```

`$ARTIFACTS_FOLDER` was set in Step S.1 from the plan file path (or derived as `$PRODUCT-$PLATFORM-$CREATEDDATE`). Create the directory if it does not yet exist. If `summary.md` already exists from a prior run, append a counter suffix (`-2`, `-3`, …) rather than overwriting it.

### Step 9.2 — Write the summary file

Use the Write tool to create the file. Populate each section from what was actually performed during this workflow run — **omit any section where no changes were made**. Never fabricate rows.

```markdown
# Juspay Integration Summary — [Product] on [Platform or API]

**Date:** [YYYY-MM-DD]  
**Merchant ID:** [MERCHANT_ID]  
**Docs base:** [first page URL from $DOC_MAP, for reference]

---

## Environment Variables

Variables added to `.env` or `.env.local` during this integration (names only — never values).

| Variable                            | File | Purpose                     |
| ----------------------------------- | ---- | --------------------------- |
| JUSPAY_API_KEY                      | .env | API authentication          |
| JUSPAY_MERCHANT_ID                  | .env | Merchant account identifier |
| JUSPAY_CLIENT_ID                    | .env | SDK client identifier       |
| JUSPAY_WEBHOOK_USERNAME             | .env | Webhook Basic Auth username |
| JUSPAY_WEBHOOK_PASSWORD             | .env | Webhook Basic Auth password |
| [any others written during codegen] |      |                             |

---

## API Routes Created / Modified

| Method | Path | File | Description |
| ------ | ---- | ---- | ----------- |

[one row per route generated in Phase 5 — e.g., POST /api/juspay/session, GET /api/juspay/order-status/:orderId, POST /api/juspay/webhook]

---

## Database Changes

| Change | Table / Model | Migration file | Columns added / modified |
| ------ | ------------- | -------------- | ------------------------ |

[one row per table or model created or altered in Phase 5 DB work]

---

## Order / Payment Status Mapping

Juspay statuses mapped to app-internal statuses in the webhook handler and order-status utility.

| Juspay status | App status | Mapped in |
| ------------- | ---------- | --------- |

[rows derived from status values used in the generated webhook handler and order-status code]

---

## Frontend Routes / Pages

| Route | File | Description |
| ----- | ---- | ----------- |

[rows for any frontend pages, redirect handlers, or return-URL routes created in Phase 5]

---

## SDK / Packages Installed

| Package | Version pinned | Platform |
| ------- | -------------- | -------- |

[rows from packages installed in Step 5.3 or Phase 6 — names and versions from the docs]

---

## Config Files Created or Modified

| File | Change type | Notes |
| ---- | ----------- | ----- |

[rows for build configs, platform config files, .env, gradle files, Podfile, pubspec.yaml, etc. touched during the integration]

---

## Webhook Configuration

| Setting           | Value                                                                           |
| ----------------- | ------------------------------------------------------------------------------- |
| Webhook URL       | [WEBHOOK_URL]                                                                   |
| Events subscribed | [comma-separated list of event names where value is true in the updated config] |

---

## Return URL

[RETURN_URL]

---

## Other-Side TODO _(only present when `$SINGLE_SIDE_MODE = true`)_

> This section is written **only** when the planner detected a single-side codebase and proceeded with one side only. Omit it entirely if both backend and frontend were present.
>
> **Do not hardcode any steps here.** Every checklist item must be derived from the docs fetched in Phase 3. Walk `$DOC_MAP` for `$MISSING_SIDE`-facing sections (e.g. sections labelled "Backend", "Server", "API" if `$MISSING_SIDE = backend`; sections labelled "Frontend", "Client", "SDK", platform names if `$MISSING_SIDE = frontend`). For each page in those sections, extract the required actions and emit one checklist item per distinct action, using the exact names from `$CODE_EXAMPLES` and `$CONSTRAINTS`.

### What must be implemented on the [$MISSING_SIDE] before go-live

[One checklist item per required action from the $MISSING_SIDE-facing doc sections in $DOC_MAP. Group items under the same section headings used in the docs. Use exact method/field/file names from $CODE_EXAMPLES and $CONSTRAINTS — no invented names.]

---

## Notes

[Non-obvious decisions, workarounds, known caveats, or anything a future developer would need to know. Do not include credential values. Examples: why a particular status mapping was chosen, a version constraint from the docs, a quirk of the SDK init flow, etc.]
```

**Do not close `summary` until the file is written.** Use the Write tool to create it, then record the close naming the real path (GUARDRAIL 20):

```
integrate-results step-end passed "summary written to .jp-artifacts/$ARTIFACTS_FOLDER/summary.md; N env vars, N routes, N DB changes, N status mappings documented"
```

Leave any section empty if no changes were made in that category (e.g., if this product's docs didn't require any environment variables, omit the Environment Variables section entirely rather than writing "None").


---

## ENTRY POINTS

| ID                 | Starts at    | Use case                                           |
| ------------------ | ------------ | -------------------------------------------------- |
| `default`          | `doc-fetch`  | Fresh executor run (always reads from plan)        |
| `--from doc-fetch` | `doc-fetch`  | Re-fetch docs (e.g. after docs updated)            |
| `--from codegen`   | `codegen`    | Regenerate code (docs already fetched)             |
| `--from test`      | `test`       | Re-run live tests only                             |
| `--from summary`   | `summary`    | Re-write the integration summary only              |

When using `--from <step>`, always **read the plan file first** (same STARTUP sequence: read plan → init → close bootstrap steps → register manifest from plan). Then re-derive in-memory state and close skipped-over steps:

1. Always run the full STARTUP sequence to register the manifest from the plan.
2. Mark every manifest step **before** the entry point as terminal — `step-start <name>` / `step-end passed "resumed from --from flag"`.

### Resume state reconstruction (mandatory before running the entry-point step)

Closing earlier steps only satisfies the completeness gate and the progress UI — it does **not** restore the in-memory variables those phases produced. On a fresh invocation, `$CONSTRAINTS`, `$API_KEY`, endpoint routes, etc. are all empty. **Before executing the entry-point step, silently re-derive the state it depends on.** Variables from the plan (`$PRODUCT`, `$PLATFORM`, `$MERCHANT_ID`, etc.) are always available via Step S.1 — they don't need to be re-fetched.

Per entry point, reconstruct at minimum:

| `--from`    | Re-derive before running                                                                                                                                                                                                                                                                                                                                                                          |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `doc-fetch` | Plan variables already populated — no extra work. Confirm `$DOC_PAGES` is non-empty.                                                                                                                                                                                                                                                                                                             |
| `codegen`   | Re-fetch docs (`reference/retrieval.md` §constraints / Phase 3) to rebuild `$CONSTRAINTS`, `$CODE_EXAMPLES`, `$ERROR_CODES`, `$VERSION_CONSTRAINTS`, `$WARNINGS`; re-resolve `$RETURN_URL`/`$WEBHOOK_URL` (Step 4.1); resolve `$BACKEND_BASE_URL` + webhook-auth creds (Step 4.4); load `$API_KEY` per Step 4.2 (reuse existing — do **not** mint a new one); re-fetch `$INTEGRATION_STAGES` (Step 5.1) |
| `test`      | All of `codegen` above **plus** the generated endpoint routes (`$SESSION_ENDPOINT`, `$ORDER_STATUS_ENDPOINT`, `$WEBHOOK_ENDPOINT`) by scanning the already-generated code, and `$TEST_CARDS`/`$TEST_UPI_VPA` (`reference/retrieval.md` §test-resources / Step 8.3.1) for SDK/hybrid                                                                                                               |
| `summary`   | `$PRODUCT`, `$PLATFORM`, `$MERCHANT_ID`, `$WEBHOOK_URL`, `$RETURN_URL` from plan (already populated); plus the actual set of files/routes/DB changes present in the working tree (read them from disk)                                                                                                                                                                                           |

If a value cannot be re-derived (e.g. no docs reachable, no generated code found for `--from test`), stop and tell the user which prerequisite is missing rather than proceeding with empty state.

---

## DONE

Run: `integrate-results step-start done`

### Timing summary

Run the timing summary script (same path-resolution rule as `integrate-results` — resolve `scripts/lifecycle/done` relative to this skill's directory, not the project root):

```
<skill-dir>/scripts/lifecycle/done
```

It emits:

1. An **INCOMPLETE** warning line if any registered step (other than `done`) was never executed — this is the manifest completeness check surfacing.
2. A markdown timing table (step, status, duration, verification), with any step taking ≥30% of total time in **bold**.
3. A total wall-clock line.
4. A `**Slowest step**:` line.
5. A `<<<FACTS ... FACTS` block with machine-readable data (`totalSeconds`, `slowestStep`, `dominantSteps`, `skippedSteps`, `failedSteps`, `incompleteSteps`, `product`, `platform`, `productType`).

Print the table to the user. Do not compute durations yourself.

**If `incompleteSteps` is non-empty**, the run skipped registered work. Do **not** declare success: go back and execute (or explicitly `step-end skipped "<reason>"`) each listed step before completing. `set status completed` will refuse while any step is unaccounted for.

### Optimization suggestions

Read the `FACTS` block and generate 2–3 concrete suggestions based on the actual timing:

- If `doc-fetch` dominated: suggest `--from codegen` for re-runs when only regenerating code
- If `test` failed: note which test failed and how to re-run with `--from test` after fixing
- If `{platform}-setup` was slow: note the specific build step and suggest `--from codegen` for re-runs
- If `params-collect` was slow: user interaction was the bottleneck — note which fields took longest so future runs can pre-configure them in the plan
- If `codegen` was slow: suggest incremental changes via `--from codegen` rather than full re-runs

Be specific to this run's data, not generic advice.

### Completion

First confirm completeness, then close out:

```
integrate-results finalize                # must print "complete: ..." — if it prints "incomplete: <names>", resolve those steps first
integrate-results set status completed    # refused by the script while any step is unaccounted for; use "failed" if a phase failed without recovery
integrate-results set active false
integrate-results step-end passed "integration complete: $PRODUCT on $PLATFORM; timing table printed"
```


`set status completed` is structurally refused unless every registered step (except `done`) has a terminal record (`passed`/`skipped`). A `failed` step or an unaccounted step blocks completion — resolve it or call `integrate-results set status failed` instead.

---

## TOOL CALL REFERENCE

| When          | Tool                                                                                                                         | Purpose                                                                                             |
| ------------- | ---------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| Step S.1      | Read `juspay-plan.md`                                                                                                        | Parse plan frontmatter + body sections; populate all `$` variables                                  |
| Phase 3       | `doc_fetch_tool(url)`                                                                                                        | Fetch individual doc pages from `$DOC_PAGES`; build `$CONSTRAINTS` table                            |
| Step 4.1      | `juspay_get_webhook_settings()`                                                                                              | Check if webhook URL is already configured on the merchant account                                  |
| Step 4.1      | `juspay_get_general_settings()`                                                                                              | Check if return URL is already configured on the merchant account                                   |
| Step 4.1      | `explore_product("dashboard")`                                                                                               | Fallback only (when `$DASHBOARD_HINTS` is empty): get dashboard doc structure                       |
| Step 4.1      | `doc_fetch_tool(url)`                                                                                                        | Fallback only: fetch webhook or general-settings page; store as `$DASHBOARD_DOCS`                   |
| Step 4.1      | `list_products({ category: "DASHBOARD" })`                                                                                   | Fallback if `explore_product("dashboard")` also fails                                               |
| Step 4.2      | `juspay_create_api_key(...)`                                                                                                 | Provision a new API key; store in memory only — never log or store in results file                  |
| Step 5.1      | `juspay_integration_monitoring_status(...)`                                                                                  | Fetch integration stage contract into `$INTEGRATION_STAGES`; drives which flows codegen must cover  |
| Step 8.5      | `juspay_integration_monitoring_status(...)`                                                                                  | Re-call after tests to confirm stage activity recorded; diff against `$INTEGRATION_STAGES` baseline |
| Step 5.2.1    | (code output)                                                                                                                | Emit validation layer from `$CONSTRAINTS` — length/value/type guards before API calls               |
| Step 5.2.2    | Bash                                                                                                                         | Install SDK packages (npm/flutter/gradle/pod) — names and versions from docs                        |
| Phase 6       | Bash                                                                                                                         | Run prebuild/generate/sync, run post-install scripts — steps derived from docs                      |
| Phase 6       | Edit / Write                                                                                                                 | Patch build config files or create platform config files — content derived from docs                |
| Step 4.4      | Bash / Read                                                                                                                  | Confirm or detect `$BACKEND_BASE_URL`; read webhook auth credentials from `.env`                    |
| Step 8.2      | Bash (product test scripts or inline curl)                                                                                   | POST session endpoint; verify HTTP 200 + product-specific response fields + DB row                  |
| Step 8.2      | Bash (product test scripts or inline curl)                                                                                   | GET order-status endpoint; verify HTTP 200 + status field                                           |
| Step 8.2      | Bash (inline curl)                                                                                                           | POST synthetic webhook events; payload from docs; assert HTTP 200 + DB updated                      |
| Phase 9       | Bash / Read                                                                                                                  | Detect summary directory (`docs/`, `memory-bank/`, `notes/`, or project root)                       |
| Phase 9       | Write                                                                                                                        | Write `juspay-integration/[product]-[date].md` with env vars, routes, DB changes, mappings          |
| STARTUP       | `integrate-results init`                                                                                                     | Initialize lifecycle skeleton + bootstrap manifest                                                  |
| STARTUP       | `integrate-results register steps.json`                                                                                      | Register manifest from plan; rejects unknown names / malformed / missing required steps             |
| Each step     | `integrate-results step-start/step-end`                                                                                      | Bookend every registered step; names must be in the manifest                                        |
| Metadata      | `integrate-results set`                                                                                                      | Store product/platform/productType/merchantId — never credentials                                   |
| Done          | `integrate-results finalize`                                                                                                 | Completeness gate — non-zero if a registered step was never executed                                |
| Done          | `<skill-dir>/scripts/lifecycle/done`                                                                                         | Generate timing table + FACTS block (incl. `incompleteSteps`)                                       |
| Retrieval     | `reference/retrieval.md` (§constraints / §dashboard-nav / §test-resources / §codebase-signals / §integration-stages)        | Read/extract procedures done inline (see RUN MANIFEST & REFERENCE FILES)                            |
| Fallback only | WebFetch                                                                                                                     | Only if `doc_fetch_tool` returns an error on a valid URL                                            |

**Never construct doc URLs yourself.** All URLs come from the `md content link` field in `explore_product` responses.

---

## Output

Each phase produces structured output in the conversation:

- **STARTUP** — Silent. Plan read, variables populated, manifest registered, tasks seeded.
- **Phase 3** — Coverage line: "fetched N pages; M fields in $CONSTRAINTS; P error codes; Q warnings"
- **Phase 4** — One-line confirmation per auto-resolved setting; questions only for unconfigured values
- **Phase 5** — Generated code blocks: validation layer, API routes, webhook handler, order-status utility, optional DB schema
- **Phase 6** (mobile only) — Native setup summary table with ✅ / ❌ / skipped per step
- **Phase 7** — Markdown integration checklist + parameter constraints table + error reference table
- **Phase 8** — Unified pass/fail test results table; manual SDK test guide for mobile platforms
- **Phase 9** — Summary file written to `[docs|memory-bank|notes|juspay-integration]/[product]-[YYYY-MM-DD].md`
- **Done** — Timing table with per-phase durations and 2–3 concrete optimization suggestions

---

## GUARDRAILS

1. **The plan is the source of truth for product/platform/URLs.** Never re-ask questions the plan already answered (`$PRODUCT`, `$PLATFORM`, `$WEBHOOK_URL`, `$RETURN_URL`, etc.). Architecture decisions (`$ARCH_DECISIONS`) are collected in Phase 3.5 after doc-fetch — they are NOT in the plan.

2. **Always read the plan before touching the lifecycle.** STARTUP must complete (plan read, all `$` variables populated) before `integrate-results init` is called.

3. **Never construct doc URLs.** All URLs come from `$DOC_PAGES` (from the plan's `## Doc Pages` section). Use them exactly as provided.

4. **Never fabricate.** If a page didn't load or a section wasn't in the docs, say so. Offer the raw URL for the user to check manually.

5. **Code examples come from the docs.** Use the exact method names, class names, and code structure from the fetched documentation pages as your source of truth.

6. **Parameters and constraints come from the docs.** The actual required fields, types, maxLength, minValue, and enumValues are what the fetched pages say — populate `$CONSTRAINTS` from them.

7. **Code uses doc-sourced names only.** If a method or class name doesn't appear in the fetched pages, do not use it.

8. **Error codes come from the docs.** Collect them from every page you fetch. Do not invent them.

9. **Architecture decisions come from `$ARCH_DECISIONS`.** Apply every decision from Phase 3.5 exactly as collected. Do not re-ask a question that Phase 3.5 already answered. Do not substitute your own judgment for a decision already made.

10. **Credentials never leave memory.** `$API_KEY`, `$WEBHOOK_AUTH_PASSWORD`, and any other secret must not appear in verification strings, task descriptions, `integrate-results` calls, Bash command arguments, or any terminal output. The `integrate-results` script enforces this for stored fields; the caller is responsible for verification strings and command arguments.

11. **The manifest is the step contract — never skip a registered step silently.** Every step the planner registered must reach a terminal record (`passed`, or `skipped` with a reason) before `done`. `finalize` and `set status completed` hard-fail otherwise. If a step truly doesn't apply, close it `skipped "<reason>"`; never just move past it.

12. **`register` rejects unknown names.** The plan's manifest uses only closed-vocabulary step names. If `register` rejects, the plan has a malformed manifest — do not invent step names to work around it; tell the user to re-run the planner.

13. **Validate every retrieval result before proceeding.** A retrieval procedure that returns empty/thin output is a caught error: `step-end failed` the owning step and surface the cause. Never paper over a missing `$CONSTRAINTS` or stage list with invented values.

14. **Retrieval procedures never interact with the user.** They are read-only and return data; all questions, confirmations, and dashboard-config hand-offs stay in the orchestrator.

15. **Verification strings describe what actually happened — never fabricate.** Recording `step-end passed` is a claim that the step's work was _really done and observed_. Do not write "fetched N pages; 50+ fields in constraints" unless you actually extracted those fields; do not record `test passed` unless real requests ran and you saw the responses; do not record `summary passed` unless the summary file was written. The completeness gate proves a step was _recorded_, not that it was _done_ — that integrity is on you. If the work could not be done, record `failed` (with the real error) or `skipped "<honest reason>"`, never a fake `passed`.

16. **`test passed` requires real evidence.** A test step is `passed` only when actual requests ran and you observed the HTTP status / response body / DB row. Calling an unrelated metrics endpoint, or "documenting test cards," is not testing. If the app cannot be run here (mobile build, no server), record `skipped "<why>"` and hand over the manual test guide — do not claim a pass.

17. **`summary passed` requires the file on disk.** Phase 9 writes a summary markdown file. Do not close `summary` until the file exists; name its path in the verification string.

18. **Settings responses carry secrets — never persist or echo them.** `juspay_get_general_settings` / `juspay_get_webhook_settings` return `cardEncodingKey`, `internalHashKey`, `paymentResponseHashKey`, `webHookPassword`, SSL certs, and more. Use only the specific non-secret values you need (`returnUrl`, `webHookurl`, subscribed events). **Never copy any of these secret fields into `.env`, generated code, verification strings, or chat.** Read webhook-auth credentials from the project's own `.env` (Step 4.4), not from the settings API response.
