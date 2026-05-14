---
name: juspay-skills
description: Entry point for integrating Juspay's checkout products. Use when implementing Juspay payments and deciding between HyperCheckout, Express Checkout SDK, or Express Checkout API — routes to the integration-specific skill.
---

# Juspay integration — start here

This skill bank teaches a coding agent to integrate Juspay's checkout products.
Decide which of the three integration shapes the merchant wants, open that
skill, and follow it.

## The three integrations

| Integration | The merchant wants… | Skill |
|-------------|---------------------|-------|
| **HyperCheckout** | Juspay to host the payment-page UI — issue a session on the backend, hand off to Juspay's pre-built checkout. Fastest path. | `integrations/hyper-checkout/` |
| **Express Checkout SDK** | Their own UI, with Juspay's SDK handling payment-method rendering and gateway calls. | `integrations/express-checkout-sdk/` |
| **Express Checkout API** | Pure server-to-server over REST — no Juspay SDK; the merchant orchestrates everything. | `integrations/express-checkout-api/` |

If the merchant hasn't picked a shape, settle that first — everything else
branches on it.

## Before going live

`go-live/production-readiness-checklist/` — validate the integration before
real traffic.

## Documentation

Endpoint, payload, and field detail — plus all cross-cutting concerns (auth,
webhooks, error codes, order-status enum) — is **not** hand-maintained in this
bank. It is fetched live from the **juspay-docs MCP**, per product. See
`mcp/juspay-docs-mcp/` for the server endpoint, its tools, and the fetch
workflow. Skill cards curate *which* docs matter; the MCP serves them. Never
hardcode `juspay.io` URLs and never hallucinate a field — go through the MCP.

## Layer contract

```text
integrations/  →  mcp/
(sequence)        (docs access)
```

An integration orchestrator owns the end-to-end sequence, the decisions, and the
non-negotiables; it fetches every schema and every cross-cutting detail through
`mcp/juspay-docs-mcp/`. Knowledge flows one direction.
