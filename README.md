# Juspay Agent Skills

A three-skill pipeline — `/jp-prd` → `/jp-architecture` → `/jp-executor` — that takes a Juspay payment integration from requirements to working code in any project.

**Pairs with [`juspay-mcp`](https://github.com/juspay/juspay-mcp) to work.** The skills are the workflow; the MCP servers are the data. Every Juspay fact (product names, API/SDK fields, error codes, webhooks) comes from the **[Docs MCP](https://github.com/juspay/juspay-mcp/tree/main/juspay_docs_mcp)**, and merchant data (IDs, settings, configuration) from the **[Dashboard MCP](https://github.com/juspay/juspay-mcp/tree/main/juspay_dashboard_mcp)**. Register both, or the skills can't ground their output properly.

Plain `SKILL.md` files — agent-agnostic. Works with any MCP-aware LLM agent: point it at `skills/` and register the two MCP servers.

## The pipeline

Each skill hands off through a shared workspace at `docs/juspay/`. Run end-to-end or jump in anywhere.

```
/jp-prd  ──▶  prd.md  ──▶  /jp-architecture  ──▶  architecture.md  ──▶  /jp-executor  ──▶  integration code
                                                  task-checklist.md
```

| Skill | What it does | Writes |
|-------|--------------|--------|
| **`/jp-prd`** | Scope the integration — *what* it must do (products, methods, flows, constraints). | `prd.md` |
| **`/jp-architecture`** | Turn the PRD into doc-grounded *decisions* + an implementation checklist. | `architecture.md`, `task-checklist.md` |
| **`/jp-executor`** | Build it in your codebase — credentials, SDK, webhooks, DB, tests — re-fetching exact doc shapes at write time. | integration code |

Each `SKILL.md` is a stable decision engine; product catalogs live in `products/`, exhaustive schemas stay in the Docs MCP (fetched on demand).

## Adding a product

Drop a markdown file into each skill's `products/` catalog — no `SKILL.md` changes. The product must be reachable via the Docs MCP's `explore_product`. See `products/README.md`.

```yaml
---
id: <kebab-case-id>
category: CHECKOUT | PAYOUTS | BILLING | UPI SOLUTIONS
platforms: [android, ios, web, flutter, react-native, ...]
---
## What it is / When to recommend / Key concepts / Intent signals
```

## Source


- **MCP servers** — [juspay/juspay-mcp](https://github.com/juspay/juspay-mcp): [Docs MCP](https://github.com/juspay/juspay-mcp/tree/main/juspay_docs_mcp) · [Dashboard MCP](https://github.com/juspay/juspay-mcp/tree/main/juspay_dashboard_mcp)
- **License** — [LICENSE](./LICENSE)
