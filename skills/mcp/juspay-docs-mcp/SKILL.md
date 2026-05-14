---
name: juspay-docs-mcp
description: How to fetch Juspay documentation through the juspay-docs MCP server. Use whenever a Juspay skill needs endpoint, payload, or field detail — it is the shared documentation-access dependency for every integration and foundation skill.
---

# juspay-docs MCP — documentation access

Every integration and foundation skill in this bank defers endpoint, payload,
and field detail to the **juspay-docs MCP** — a hosted Model Context Protocol
server that serves Juspay's official docs as clean Markdown. This skill is how
the agent reaches it. The MCP is the **final source of truth** for schemas; skill
cards never hand-maintain or hallucinate them.

## The server

- **Endpoint (streamable HTTP):** `https://mcp.juspay.in/dashboard/juspay-docs-stream`
- **Source:** `https://github.com/juspay/juspay-mcp`

It must be registered as an MCP server in the agent before these skills are
usable. Once registered, the agent has the three tools below, and the MCP's own
`server_instructions` are auto-injected into context — follow them; this bank
does not repeat them.

## Tools

| Tool | Use for | Key arguments |
|------|---------|---------------|
| `list_doc_sources` | Discover the doc index for a specific SDK/API integration | `merchant_id`, `client_id`, `integration_type`, `platform` |
| `browse_doc_sources` | Browse cross-cutting product topics not tied to an SDK integration (outages, dashboard, refunds as a feature, etc.) | none required (optional `category`) |
| `fetch_docs` | Fetch one doc page (a URL from an index) as Markdown | `url` |

`integration_type` values used by this bank: `payment-page-cat`,
`payment-page-signature` (HyperCheckout), `express-checkout` (Express Checkout
SDK), `api` (Express Checkout API). Each integration skill states its own.

## Fetch workflow

1. **Gather context** — `merchant_id`, `client_id`, `platform`, and the
   `integration_type` from the integration skill in play. Use **real values from
   the merchant** — never placeholders. (The MCP's server instructions detail
   this; follow them.)
2. **`list_doc_sources`** with that context → returns the doc index for that
   configuration.
3. **`fetch_docs`** the index → discover the page URLs inside it.
4. **`fetch_docs`** the specific pages the integration skill's *documentation
   map* names — that map is the curated core path.

For a cross-cutting product/feature question not anchored to an SDK integration,
skip step 1–2 and use **`browse_doc_sources`** directly.

## Rules

- **Always go through the MCP.** Never hardcode `juspay.io` doc URLs into code
  or skill cards — resolve them via `list_doc_sources` / `fetch_docs`.
- **Never hallucinate** an endpoint, field, or payload. If it is not in the
  fetched docs, fetch it or ask.
- **Don't re-narrate the docs.** Skill cards own *sequence, decisions, gotchas*
  and curate *which* pages matter; the MCP owns the *schemas*.

## Used by

Every `integrations/*` skill — for its integration's doc pages, and for the
cross-cutting detail (auth, webhooks, error codes, order-status enum) carried
per-product in those same docs.
