# Step 7: Plan Compilation & Confirmation

## Rules

- Compute the executor manifest using only the closed step vocabulary defined below — do NOT invent step names.
- Show the complete plan summary to the user before finalizing.
- Use **native select UI** for the confirm/revise/save choice.
- Only proceed to step-08 if the user confirms.

## Your Task

Assemble the complete `juspay-plan.md`, compute the executor step manifest, present a summary for user confirmation.

## Sequence

### 1. Compute Executor Manifest

Using all collected context (`$PRODUCT`, `$PRODUCT_TYPE`, `$PLATFORM`, `$HAS_WEBHOOKS`, `$HAS_PERSISTENCE_SCHEMA`), compute the step list:

**Always include:**
`doc-fetch`, `arch-decisions`, `apikey-provision`, `return-url-config`, `params-collect`, `integration-stages`, `codegen`, `checklist`, `test`, `stages-confirm`, `summary`, `done`

**Conditionally include:**
- `webhook-config` — only if `$HAS_WEBHOOKS = true`
- `platform-disambiguation` — only if `$PLATFORM = android` or `$PLATFORM = ios`
- `db-schema-decision` — only if `$HAS_PERSISTENCE_SCHEMA = false` (executor decides actual strategy after arch-decisions)
- `{$PLATFORM}-setup` — include the matching platform setup step (e.g. `react-native-setup`); omit for `api-only`

**Ordering:**
`doc-fetch` → `arch-decisions` → (`webhook-config`) → `return-url-config` → `apikey-provision` → `params-collect` → (`platform-disambiguation`) → `integration-stages` → `codegen` → (`db-schema-decision`) → (`{$PLATFORM}-setup`) → `checklist` → `test` → `stages-confirm` → `summary` → `done`

Store as `$MANIFEST_STEPS[]`.

### 2. Assemble juspay-plan.md

**Artifacts folder name:** `{$PRODUCT}-{$PLATFORM}-{$DATE}` (e.g. `hyper-checkout-react-native-2026-06-02`)

Write the complete plan to TWO locations:
1. **Canonical:** `{project-root}/.jp-artifacts/{folder}/juspay-plan.md`
2. **Convenience copy:** `{project-root}/juspay-plan.md`

```markdown
---
planVersion: "1.0"
createdAt: "{{date}}"
product: "{{$PRODUCT}}"
productType: "{{$PRODUCT_TYPE}}"
platform: "{{$PLATFORM}}"
entityName: "{{$ENTITY_NAME}}"
merchantId: "{{$MERCHANT_ID}}"
clientId: "{{$CLIENT_ID}}"
backendLang: "{{$DETECTED_LANG}}"
backendBaseUrl: "{{$BACKEND_BASE_URL}}"
apiKeySource: "{{$API_KEY_SOURCE}}"
hasPersistenceSchema: {{$HAS_PERSISTENCE_SCHEMA}}
webhookUrl: "{{$WEBHOOK_URL or $PLANNED_WEBHOOK_URL or empty string}}"
returnUrl: "{{$RETURN_URL or $PLANNED_RETURN_URL or empty string}}"
---

# Juspay Integration Plan

_Written by jp-planner. Read by /jp-executor via `--from-plan`._

## Product & Platform

| Field | Value |
|---|---|
| Product | {{$PRODUCT}} |
| Product Type | {{$PRODUCT_TYPE}} |
| Platform | {{$PLATFORM}} |
| Merchant ID | {{$MERCHANT_ID}} |

## Doc Pages (executor fetches in this order)
{{for each page in $DOC_PAGES}}
- {{page.title}}: {{page.url}}
{{/for}}

## Dashboard Config Hints
{{paste the complete ## Dashboard Config Hints block written in step-05}}

## Executor Manifest
```json
{{$MANIFEST_STEPS as JSON array — each step has: name, description, critical, terminal}}
```

Each step object must include a `description` field. The `terminal` field is an **array** of allowed completion states: `["passed"]` means the step must succeed; `["passed","skipped"]` means it may be legitimately skipped with a reason. Standard entries:
```json
{"name": "doc-fetch", "description": "Fetch all pages from Doc Pages section; build $CONSTRAINTS, $CODE_EXAMPLES, $ERROR_CODES", "guard": "always", "terminal": ["passed"], "critical": true},
{"name": "arch-decisions", "description": "Architect persona reads $CONSTRAINTS; asks ≤6 doc-grounded questions; writes $ARCH_DECISIONS", "guard": "always", "terminal": ["passed"], "critical": true},
{"name": "webhook-config", "description": "Use $DASHBOARD_HINTS.webhook nav/link to guide user to configure webhook in Juspay dashboard", "guard": "hasWebhooks", "terminal": ["passed","skipped"], "critical": false},
{"name": "return-url-config", "description": "Use $DASHBOARD_HINTS.return-url nav/link to guide user to configure return URL", "guard": "always", "terminal": ["passed","skipped"], "critical": false},
{"name": "apikey-provision", "description": "Check .env for JUSPAY_API_KEY; if absent, call juspay_create_api_key and store in memory", "guard": "always", "terminal": ["passed"], "critical": true},
{"name": "params-collect", "description": "Build elicitation list from $CONSTRAINTS; collect required fields not auto-sourced via question gate", "guard": "always", "terminal": ["passed"], "critical": true},
{"name": "integration-stages", "description": "Call juspay_integration_monitoring_status; build $INTEGRATION_STAGES and $SCORE_BASELINE", "guard": "always", "terminal": ["passed","skipped"], "critical": false},
{"name": "codegen", "description": "Apply $ARCH_DECISIONS; generate config module, session endpoint, webhook handler, order-status utility, SDK init; typecheck before close", "guard": "always", "terminal": ["passed"], "critical": true},
{"name": "db-schema-decision", "description": "Scan for existing schemas; generate or extend $entityName table using $CONSTRAINTS field sizes", "guard": "noPersistenceSchema", "terminal": ["passed","skipped"], "critical": false},
{"name": "react-native-setup", "description": "Install SDK packages from prerequisites page; patch build configs; run post-install scripts", "guard": "platform=react-native", "terminal": ["passed","skipped"], "critical": false},
{"name": "checklist", "description": "Emit integration checklist from fetched docs; parameter constraints table; error codes table; integration stages checklist", "guard": "always", "terminal": ["passed"], "critical": false},
{"name": "test", "description": "Start dev server; run session and order-status tests; POST synthetic webhook events; provide manual SDK test guide for mobile", "guard": "always", "terminal": ["passed","skipped"], "critical": false},
{"name": "stages-confirm", "description": "Re-call integration monitoring API; diff stage statuses vs $SCORE_BASELINE; flag critical stages still failing", "guard": "always", "terminal": ["passed","skipped"], "critical": false},
{"name": "summary", "description": "Write summary to .jp-artifacts/<folder>/summary.md — env vars, routes, DB changes, status mappings, webhook config, return URL, notes", "guard": "always", "terminal": ["passed"], "critical": false},
{"name": "done", "description": "Run integrate-results finalize; print timing table; emit FACTS block", "guard": "always", "terminal": ["passed"], "critical": false}
```
```

### 3. Show Summary Card

Present a concise summary to the user:

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Juspay Integration Plan Summary
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Product:      {{$PRODUCT}} ({{$PRODUCT_TYPE}})
  Platform:     {{$PLATFORM or "api-only"}}
  Entity:       {{$ENTITY_NAME}}
  Backend:      {{$DETECTED_LANG or "unknown"}} at {{$BACKEND_BASE_URL or "unknown"}}
  Webhook URL:  {{webhookUrl or "N/A"}}
  Return URL:   {{returnUrl or "N/A"}}
  API Key:      {{apiKeySource == "env" ? "found in .env" : "will provision new"}}
  Steps:        {{$MANIFEST_STEPS count}} executor steps planned
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### 4. Confirmation

Present native select:

> "Plan is ready. What would you like to do?"
>
> - `Confirm — invoke /jp-executor now`
> - `Revise a section — go back to a specific step`
> - `Save plan only — I'll invoke /jp-executor manually later`

**If "Revise":** ask which step to return to via native select (steps 02–05), then reload that step file and re-run from there.

**If "Save plan only":** write the plan, inform the user: "Plan saved. Run `/jp-executor --from-plan .jp-artifacts/{folder}/juspay-plan.md` when ready." Stop here.

**If "Confirm":** proceed to step-08.

## Next Step

If confirmed: load `./step-08-invoke.md`.
