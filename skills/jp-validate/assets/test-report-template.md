# Test Report Template — Juspay integration validation

`jp-validate` writes this to `{doc_workspace}/test-report.md`. It records what was **actually** tested and the outcome — no fabricated rows, **no secret values** (env var names only). Omit sections with no content. This report is the tester's artifact; it does not replace `jp-executor`'s `integration-summary.md`.

Frontmatter:

```markdown
---
source_checklist: docs/juspay/task-checklist.md
created: <today>
environment: production   # enforced; a non-production env appears only if the user explicitly required it
juspay_mcp_mode: connected | manual
quality_gate: PASS | CONCERNS | FAIL
---
```

## Sections

```markdown
# Juspay Integration — Test Report

## Run context
- **Environment:** <production> (host) · key/stage alignment: <ok | mismatch>
- **juspay-mcp mode:** <connected | manual>
- **Topology:** <single-repo | split> · this_side: <backend | frontend | fullstack> · other_side: <backend | frontend | none>
- **Test stack detected:** <Playwright / pytest / … or "none — inline curl/bash">
- **Surfaces tested:** <backend | frontend/SDK | native | fullstack>
- **Test files written:** <paths, or "none (inline)">

## Summary
| # | Area | Pri | Test | Mode | Result | Notes |
|---|------|-----|------|------|--------|-------|
| 1 | Order creation | P0 | <name> | <playwright/pytest/curl> | PASS / FAIL / BLOCKED / MANUAL | <…> |
| … |      |     |      |      |        |       |

## Traceability matrix
| Requirement | Source | Covered by | Status |
|-------------|--------|-----------|--------|
| <FR-n / T-id / decision> | prd/architecture/checklist | <test name> | covered / gap / blocked |

*(An in-scope requirement with no covering test is a **gap** — list it explicitly.)*

## Quality gate: <PASS | CONCERNS | FAIL>
<one-paragraph rationale. PASS requires every P0 item passing. CONCERNS = P0 pass but P1/P2 gaps or blocked items. FAIL = a P0 item failed or couldn't be exercised.>

## Payment-NFR checks
- [ ] No PAN/secret in logs, report, or command output
- [ ] Webhook signature verification enforced (rejects tampered payloads)
- [ ] Idempotency proven by duplicate-delivery test
- [ ] Status reconciliation uses server-to-server Order Status (not client result)
- [ ] Environment/key/host aligned

## Cross-side contract conformance *(split runs only — omit otherwise)*
- [ ] This side honors the Cross-Side Contract (endpoint/request/response/`sdkPayload` shapes match)
- **Cross-side E2E:** not runnable here — the `<other_side>` repo is absent. Full end-to-end requires both repos; tracked as a manual/gap below.

## Integration stage *(connected mode only — omit otherwise)*
<juspay_integration_monitoring_status: stages passing / not passing; critical gaps flagged as go-live blockers>

## Blockers & go-live gates
- <what couldn't be run and why — production keys, device-only SDK, CAPTCHA, missing access — each a clear gate>

## Manual test guide *(omit if none)*
<for device-only/native or un-automatable flows: exact steps, test instruments (cards/VPAs from docs), expected callbacks>

## Notes
- <doc source URLs used, non-obvious decisions, caveats>
```
