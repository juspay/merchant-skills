# Juspay Skills

<!-- SKELETON — content to be filled in. -->

Agent Skills for integrating Juspay payment products. Drop these into your coding
agent and it gets structured, navigable context for **HyperCheckout**,
**Express Checkout SDK**, and **Express Checkout API** — instead of guessing
endpoint shapes from training data.

> **Status:** under construction — skeleton only.

## What this is

A structured **skill bank** organised into layers — a shared documentation-access
skill (the juspay-docs MCP), integration orchestrators, a go-live checklist, and
a bank-level entry point. Each skill is its own folder with a `SKILL.md`, a crisp
activation trigger, a single responsibility, and links to the skills it depends
on.

The full methodology — layers, `SKILL.md` anatomy, naming, splitting heuristics,
authoring quality bar, phasing — lives in [`docs/framework.md`](./docs/framework.md).

## Docs strategy — hybrid

Skill cards own **structure, sequence, decisions, and gotchas**. Exhaustive
endpoint, payload, and field schemas are fetched on demand from the **juspay-docs
MCP server** (hosted at `https://mcp.juspay.in/dashboard/juspay-docs-stream`,
source [`juspay/juspay-mcp`](https://github.com/juspay/juspay-mcp)) — skills go
through the MCP rather than hand-maintaining or hardcoding doc URLs, so
field-level detail stays current.

## Structure

```text
skills/
├── SKILL.md            # bank entry point — navigation
├── mcp/                # juspay-docs-mcp — the shared documentation-access skill
├── integrations/       # hyper-checkout, express-checkout-sdk, express-checkout-api
└── go-live/            # production-readiness-checklist
```

| Layer | What it owns |
|-------|--------------|
| **`mcp/`** | The juspay-docs MCP skill — server, tools, fetch workflow; the shared docs-access dependency for every other skill |
| **`integrations/`** | Per-product orchestrators — the end-to-end sequence, decisions, gotchas, and non-negotiables; fetch every schema and cross-cutting detail via the MCP |
| **`go-live/`** | Production-readiness checklist |
| **Bank `SKILL.md`** | Top-level entry point that orients the agent across the bank |

Cross-cutting concerns — auth, webhooks, error codes, the order-status enum —
are **not** a layer here: each product's MCP docs carry them per-product, so an
integration card just points at the relevant doc pages.

## Layer contract

```text
integrations/  →  mcp/
(sequence)        (docs access)
```

Knowledge flows one direction. An orchestrator owns the sequence, decisions, and
non-negotiables; it fetches every schema and cross-cutting detail through
`mcp/juspay-docs-mcp/`.

## Prerequisites

<!-- TODO: Juspay account + credentials, supported agent tooling, dev environment -->

## Installation

<!-- TODO: setup.sh detects the coding agent and installs skills into the right path -->

## Usage Examples

<!-- TODO: prompt-style examples -->

## Scope

<!-- TODO: decide backend-only vs incl. frontend SDK; in-scope / out-of-scope -->

## Contributing

<!-- TODO -->

## License

<!-- TODO: see LICENSE -->
