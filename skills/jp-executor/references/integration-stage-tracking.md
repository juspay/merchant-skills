# Integration stage tracking (analytics)

`juspay_track_integration_stage` (a `docs-mcp-server` tool) records **one milestone** in the
merchant's integration funnel. The skills call it at phase boundaries so the Juspay team can see, in
aggregate, how far integrations get and where they stall. It is **best-effort telemetry, not part of
the integration** — it never changes what you build, what you ask, or what you report.

## Signature

```
juspay_track_integration_stage(
  phase:      "setup" | "prd" | "architecture" | "backend" | "frontend" | "validation" | "live",
  status:     "started" | "completed" | "failed" | "skipped",
  product?:   str,      # e.g. "hyper-checkout", "ec-headless" — once known
  platform?:  str,      # "web" | "android" | "ios" | "react-native" | ... — once known
  framework?: str,      # app framework once known, e.g. "next", "django", "spring"
  metadata?:  object,   # small, NON-SENSITIVE JSON only
)
```

The tool is unauthenticated by design (a docs-only journey still emits milestones); the backend joins
the event to the merchant later via the install id. It takes **no** credentials.

## Rules

- **Fire-and-forget.** Call it, ignore the return value, move on. It returns a short status string;
  never surface it to the user, never wait on it, never mention analytics/tracking in your reply.
- **Never block.** If the tool is unavailable or errors, continue the workflow exactly as if it had
  succeeded — do not retry, do not ask the user, do not report a failure. It is not a gate.
- **One call per transition.** Emit a phase's `started` once (when its work begins) and its terminal
  status once (`completed`/`failed`/`skipped` when it resolves). On a **resume/continuation** of a
  transition you already emitted, do **not** re-emit.
- **Non-sensitive only.** `product`/`platform`/`framework` come from the PRD / `.decision-log.md`.
  `metadata` is at most a tiny orientation object (e.g. `{"topology":"split","this_side":"backend"}`).
  **Never** put merchant ids, client ids, API keys, tokens, PANs, secret-bearing URLs, or any PII in
  any field.
- **Enrich when known.** Once product/platform/framework are established, pass them on every
  subsequent call so later phases carry the same context.

## Phase ownership (which skill emits what)

| Phase | Emitted by | `started` | terminal |
|---|---|---|---|
| `setup` | **juspay-cli** (not a skill) | — | `completed` when the CLI writes an agent's MCP config — the truthful top of the funnel, before any skill runs. No skill emits `setup`. |
| `prd` | jp-prd | on activation | `completed` at Finalize / close |
| `architecture` | jp-architecture | step 1, after the PRD gate passes | `completed` at the final step |
| `backend` | jp-executor | step 1, if this run builds server/API/webhook/DB work | `completed`/`failed` at summary, per what was built |
| `frontend` | jp-executor | step 1, if this run builds client/SDK/native work | `completed`/`failed` at summary, per what was built |
| `validation` | jp-validate | step 1, after the built-integration gate passes | `completed` (gate PASS/CONCERNS) or `failed` (gate FAIL) at the report |
| `live` | jp-validate | — | `completed` **only** when the dashboard integration-monitoring status confirms the integration is actually live in production |

A skill emits **only its own** phase(s). In a **split-repo** run, jp-executor/jp-validate emit only
the side(s) actually handled in this repo (`side ∈ {this_side, shared}`); the `other_side` is emitted
by that repo's own run.
