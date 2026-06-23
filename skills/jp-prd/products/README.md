# Product catalog — orientation only (NON-AUTHORITATIVE)

These files help match a merchant's goal to a candidate Juspay product. Use them to **shortlist** and
orient — never as the source of truth. Always reconcile the chosen product's slug, integration shape,
platforms, and supported methods against `docs-mcp` (`list_products` → `explore_product` →
`doc_fetch_tool`) before locking. Slugs must match the live catalog.

Each product file has `id` / `category` / `platforms` frontmatter plus: **What it is**, **When to
recommend**, **Key concepts**, **Intent signals**.

## Integration shape → PCI scope (design implication)

- **Hosted payment page** (e.g. `hyper-checkout`) — Juspay renders the UI → **smallest PCI scope**.
  Merchant creates a session, launches the SDK, reconciles server-to-server.
- **Headless SDK** (e.g. `ec-headless`) — merchant owns the UI → **medium scope**; more control, more
  responsibility.
- **Direct API** (e.g. `ec-api`, `payout`) — no client SDK; the merchant's surface handles/forwards card
  data → **largest PCI scope**.

> Live merchant data — which products the account already has, via `juspay_get_merchant_details`'s
> **`integrationType`** array (`PP` → `hyper-checkout`, `EC_API` → `ec-api`, `EC_SDK` → `ec-headless`) —
> comes from `juspay-mcp` (see `references/juspay-mcp.md`) and **drives selection when connected** (confirm
> against it; don't propose products it doesn't list). The knowledge here is documentation-style
> orientation, not account state.
