---
name: jp-planner
description: >
  Payment integration planner for Juspay. Asks questions upfront, runs
  merchant/doc/dashboard discovery, collects all params, makes architecture
  decisions, writes juspay-plan.md, then invokes /jp-executor as the executor.
  Use when the user says "plan my integration", "create a payment plan",
  "set up architecture for Juspay", or before running /jp-executor.
compatibility: |
  tools:
    - juspay-docs-mcp (explore_product, doc_fetch_tool, list_products)
    - juspay-mcp (authenticate, juspay_get_merchant_details, juspay_get_webhook_settings, juspay_get_general_settings, juspay_create_api_key, juspay_integration_monitoring_status)
  mcp_servers:
    - juspay-docs-mcp
    - juspay-mcp
---

# jp-planner — Payment Integration Planner

## Role

**This skill is the PLANNER.** It collects all context, runs discovery, makes architecture decisions, and writes `juspay-plan.md` at the project root. It then invokes `/jp-executor` (the EXECUTOR) which reads the plan and skips questions already answered here.

**The EXECUTOR (`/jp-executor`) handles:** doc content fetching, code generation, file writing, native SDK setup, API key creation, and testing.

**This PLANNER handles:** merchant context, product/platform selection, doc structure discovery, dashboard nav discovery, parameter collection, architecture decisions, and plan writing.

Both have access to `juspay-mcp` and `juspay-docs-mcp`.

## Conventions

- Bare paths (e.g. `steps/step-01-init.md`) resolve from the skill root.
- `{skill-root}` resolves to this skill's installed directory.
- `{project-root}` resolves from the project working directory.
- All information about Juspay products, APIs, and configuration must come from MCP tool calls — never from memory or training data.

## UI Rules

- **Always use native select / choice UI** when asking the user to pick from known options. Never ask for free-text replies when choices are fixed.
- Do NOT rephrase the same question twice after presenting choices.
- Do NOT ask for information that can be derived from MCP calls or codebase signals.

## Security

- Never read `.env` secret values — only note the presence of relevant keys.
- Never include credentials in plan documents or log output.
- `$API_KEY` is never written to the plan — only `apiKeySource` (env/new).

## Execution

Read fully and follow `./steps/step-01-init.md` to begin.
