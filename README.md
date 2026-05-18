# Juspay Skills

Agent skills that give your coding agent structured, doc-driven context for integrating [Juspay](https://juspay.io) payment products.

## What this is

A skill bank that adds a `/integrate` command to Claude Code. When invoked, the agent runs a fully guided wizard that:

1. Reads your merchant account configuration live (via the Juspay MCP).
2. Recommends the right product for your use case.
3. Detects your tech stack from the codebase.
4. Fetches authoritative documentation on demand (via the Juspay Docs MCP).
5. Generates complete integration code using only doc-sourced method names and field names.
6. Configures webhooks and return URLs on your account.
7. Provisions an API key and runs live tests against your dev server.



## Prerequisites

- [Claude Code](https://claude.ai/code) or [Open Code](https://opencode.ai/) installed
- A Juspay merchant account.
- Both Juspay MCP servers configured — refer to their [GitHub](https://github.com/juspay/juspay-mcp) repository for setup instructions.

## Installation

Claude Code automatically discovers skills placed in the right directory — no config commands needed.

**Global (available in all projects)**

```bash
mkdir -p ~/.claude/skills
cp -r skills/integrate ~/.claude/skills/
```

**Project-scoped (current project only)**

```bash
mkdir -p .claude/skills
cp -r skills/integrate .claude/skills/
```

## Usage

Open Claude Code in your project directory and run:

```
/integrate
```

Or ask naturally:

```
Help me integrate Juspay payments into this app
```

```
Set up Hyper Checkout for my React Native app
```

```
I need to add payouts to my backend
```

### Flags

| Flag | Effect |
|------|--------|
| `--product <id>` | Skip the recommendation step and go straight to integrating a specific product |
| `--platform <id>` | Hint the platform — the agent still verifies against your codebase |

Example:

```
/integrate --product hyper-checkout --platform flutter
```

## How it works

The skill runs a 7-phase workflow:

| Phase | What happens |
|-------|-------------|
| **0 — Intent & product selection** | Reads your merchant account, infers the recommended product from your integration type, confirms with you |
| **1 — Doc structure** | Calls `explore_product` to get the full documentation map for the chosen product |
| **2 — Platform detection** | Scans your codebase for platform signals (pubspec.yaml, AndroidManifest.xml, package.json, etc.) before asking |
| **3 — Doc fetch** | Fetches each documentation page in the required order — prerequisites first, then numbered integration steps |
| **4 — Parameter collection** | Auto-resolves merchant ID, client ID, webhook URL, and return URL from your account; provisions an API key; asks only for what it can't derive |
| **5 — Code generation** | Generates auth setup, core integration, webhook handler, status verification, DB schema, and error handling — using only method names from the fetched docs |
| **6 — Checklist & error reference** | Produces a per-product integration checklist (pulled from live integration monitoring) and an error code reference |
| **7 — Live testing** | Starts your dev server, sends real HTTP requests to each generated endpoint, and reports a pass/fail table |

## Docs strategy

Skill files own **structure, sequence, decisions, and gotchas**. Exhaustive endpoint schemas and field-level detail are fetched on demand from the **docs MCP server** — so the skill stays current without hand-maintaining doc copies.

## Repository structure

```
skills/
└── integrate/
    ├── SKILL.md          # /integrate command — the decision engine
    └── products/         # One file per product — ID, type, platforms, intent signals
        ├── hyper-checkout.md
        ├── ec-headless.md
        ├── ec-api.md
        ├── hyper-credit.md
        ├── lotuspay.md
        ├── payout.md
        ├── jusbiz.md
        ├── juspay-billing.md
        ├── upi-plugin-sdk.md
        └── upi-tpap-sdk.md
```

`SKILL.md` contains no product knowledge — all product facts come from `products/` files or live MCP responses. This keeps the orchestrator stable while products evolve independently.

## Contributing

Product entries live in `skills/integrate/products/`. Each file follows this schema:

```yaml
---
id: <kebab-case-id>
category: CHECKOUT | PAYOUTS | BILLING | UPI SOLUTIONS
platforms: [android, ios, web, flutter, react-native, ...]
---

## What it is
## When to recommend
## Key concepts
## Intent signals
```

To add a new product, create a file in `products/` and ensure the product is accessible via the `docs-mcp-server` `explore_product` tool. No changes to `SKILL.md` are needed.

## License

See [LICENSE](./LICENSE).
