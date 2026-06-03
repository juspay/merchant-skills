# Grounding in Juspay docs (`docs-mcp-server`)

The `docs-mcp-server` MCP exposes the official Juspay documentation. It needs **no authentication**. Use it to ground the PRD in real product facts — vocabulary, capabilities, constraints, error surfaces, integration shapes. Never assert a Juspay product name, field, endpoint, error code, or behavior from memory.

## Tools (call in this order)

1. **`list_products(category?)`** — browse the product catalog. Use first when the right product isn't obvious. Optional `category` filter (e.g. `CHECKOUT`, `BILLING`, `DASHBOARD`, `PAYOUTS`, `UPI  SOLUTIONS`). Returns title, slug, category, URL, short description per product.
2. **`explore_product(product)`** — fetch the `llms.txt` index for one product **slug** (e.g. `hyper-checkout`, `ec-headless`). The index lists `.md` page links and the doc structure (platforms, sections, pages).
3. **`doc_fetch_tool(url)`** — fetch one allowed Juspay docs URL (returns markdown). Use **after** `explore_product`, on the specific pages you need.

## Rules

- **Never construct doc URLs yourself.** Every URL comes from an `explore_product` result (its `md content link` fields) or a `list_products` result.
- **Allowed domains only:** `*.juspay.io`, `*.juspay.in`, `dth95m2xtyv8v.cloudfront.net`. `doc_fetch_tool` rejects others.
- **Cite sources.** Tag every doc-derived fact in the PRD (Glossary terms, FR constraints, error codes, integration shape) with its source URL.
- **Don't fabricate.** If a page didn't load or a fact isn't in the docs, say so and offer the raw URL — do not invent.

## Integration shapes (high level)

- **Payment Page / Express Checkout** — hosted UI loaded by the Juspay SDK.
- **Headless Express Checkout** — SDK with merchant-built UI.
- **API Integration** — direct server-to-server REST calls.
- Specialized SDKs exist for UPI (TPAP, Plugin, Bank Integration), BNPL, etc.

These shapes determine the PRD's surfaces, the PCI/security posture, and which capabilities/FRs apply. Confirm the shape with the user and the docs before locking the PRD.

## What to extract for the PRD (grounding, not codegen)

- **Glossary vocabulary** — the exact domain nouns the docs use (Session, Order Status, webhook, sdkPayload, merchant_id, client_id, integration type, signature verification).
- **Capability constraints** — required request fields, types, limits, enums that shape FR consequences.
- **Error / edge surface** — documented error codes and failure modes that FRs and journeys must handle.
- **Integration shape & surfaces** — hosted vs headless vs API; which platforms/SDKs are supported.

## Dashboard configuration docs (portal setup)

Some integration steps require configuration in the Juspay **dashboard** (merchant portal) — e.g. setting the webhook URL, the return URL, or creating an API key. When the plan needs portal configuration, discover the dashboard docs and surface exact navigation for the user:

1. `explore_product("dashboard")` → in the returned index find the page whose title matches **"Webhook"** or **"Settings"** → `doc_fetch_tool(url)`.
2. **Fallback** if that fails: `list_products(category: "DASHBOARD")` → pick the dashboard product slug → `explore_product` → `doc_fetch_tool` the relevant page.

Extract and record (cite the source URL):
- the **navigation path** (e.g. "Settings → Webhooks"),
- a **direct deep link** to the setting, if the page provides one,
- the **recommended events / fields** to set.

Trigger this only when portal config is actually needed (webhook/return URL not configured, API key required) — it matters most in **manual** mode, where the dashboard can't be read live.

> For **live merchant data** (credentials, settings, webhook config, API-key provisioning), use `juspay-mcp` — see `references/juspay-mcp.md` for the access → login-or-manual flow. `docs-mcp` (this file) is documentation only and needs no auth.
