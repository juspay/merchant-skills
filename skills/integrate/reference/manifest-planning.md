# Reference — Manifest planning (the `plan` step)

Load this when you reach the **PLAN** step. It tells you how to compute the run manifest inline (no sub-agent). Output is `./steps.json` in the project root (where `.integrate-results.json` lives), which you then `register`.

You decide *which steps apply* and *in what order*, drawn **only** from the closed vocabulary. You do not write integration code or talk to the user here.

## Inputs you already have

- `$PRODUCT`, `$PRODUCT_TYPE` (`sdk|api-only|hybrid`), `$PLATFORM`, `$DOC_MAP`.
- `hasExistingOrderSchema` — from a `codebase-signals` scan (mode `existing-schemas`) run just before planning.
- `products/$PRODUCT.md` — read it for product-specific notes (a `## Test Scripts` section, webhook requirements, platform-specific instructions).

## Closed vocabulary — use ONLY these names

Structural / phase: `doc-fetch`, `codegen`, `checklist`, `test`, `summary`, `done`
Native setup (pick the one matching `$PLATFORM`): `android-setup`, `ios-setup`, `react-native-setup`, `flutter-setup`, `cordova-setup`, `capacitor-setup`, `web-setup`, `iframe-web-setup`
Interaction / decision gates: `platform-disambiguation`, `params-collect`, `apikey-provision`, `webhook-config`, `return-url-config`, `db-schema-decision`, `integration-stages`, `stages-confirm`

Do **not** emit `product-select`, `platform-detect`, or `plan` — they are the bootstrap steps already recorded. Never invent a name outside this list (`register` will reject it).

## Selection rules — be conservative

Only register a step that involves **real work for THIS integration**. A registered step that turns out to be a no-op gets "skipped with a reason," which reads as noise. Prefer omitting a genuinely-inapplicable step over registering it to skip.

Always include: `doc-fetch`, `params-collect`, `apikey-provision`, `return-url-config`, `integration-stages`, `codegen`, `checklist`, `test`, `stages-confirm`, `summary`, `done`.

Conditionally include:
- `webhook-config` — only if `$DOC_MAP.hasWebhooks` / the docs require a webhook URL.
- `platform-disambiguation` — **only when `$PLATFORM` is exactly `android` or `ios`** (Java vs Kotlin; Swift vs Obj-C; CocoaPods vs SPM). **Never** for `react-native`, `flutter`, `cordova`, `capacitor`, `web`, `iframe-web`, `api-only`.
- `db-schema-decision` — only if the integration must persist order/payment state **and** `hasExistingOrderSchema` is `false`. If a schema already exists, **omit it**.
- `{$PLATFORM}-setup` — include the one matching `$PLATFORM`; for non-native (`web`/`iframe-web`) set `terminal: ["passed","skipped"]`.

`terminal: ["passed","skipped"]` for steps that can legitimately resolve to "not needed" (`webhook-config`, `return-url-config`, `db-schema-decision`, `{$PLATFORM}-setup`, `test`); else `["passed"]`.
`critical: true` for go-live gates: `doc-fetch`, `params-collect`, `apikey-provision`, `integration-stages`, `codegen`, `test`, `stages-confirm`, and `webhook-config`/`return-url-config` when present.

## Ordering

`doc-fetch` → (`webhook-config`) → `return-url-config` → `apikey-provision` → `params-collect` → (`platform-disambiguation`) → `integration-stages` → `codegen` → (`db-schema-decision`) → (`{$PLATFORM}-setup`) → `checklist` → `test` → `stages-confirm` → `summary` → `done`.

## Output

Write `./steps.json` (pure JSON array, no commentary), each element:

```json
{ "name": "<vocab name>", "guard": "<one-line condition or 'always'>", "terminal": ["passed"], "critical": true }
```

It must contain `doc-fetch`, `codegen`, `test`, `summary`, `done` or `register` rejects it. Then `integrate-results register ./steps.json`.
