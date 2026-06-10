# Integration complexity guide (hint)

> Orientation only. The point is to right-size the architecture effort — not to assign a label. The dominant complexity drivers for a Juspay integration are payment-side, not the merchant's business vertical.

## Complexity drivers

| Driver | Lower | Higher |
|---|---|---|
| Per-method client code (headless/SDK only) | hosted page renders methods (no per-method code) | headless: many distinct `process` payloads to wire (doc-derived, not user-chosen) |
| Surfaces | single (web *or* mobile) | multi-surface (web + native mobile) |
| Integration shape | hosted payment page (Juspay renders UI) | headless SDK, or direct API (merchant handles the surface) |
| PCI scope | hosted page (minimal scope) | direct API / card data touching merchant systems |
| Recurring / mandates | one-time payments | subscriptions, mandates, scheduled charges |
| Payouts | none | disbursing funds to payees |
| Tenancy | single merchant | multi-merchant (per-merchant credentials/config/isolation) |
| Reconciliation | simple order status check | high-volume, retries, idempotency-critical, partial refunds |
| Environments | production with a simple go-live gate | staged promotion / extra non-production environments the user explicitly required |

A single high driver (e.g. direct API + cards = real PCI scope) raises the whole integration's complexity even if everything else is simple.

## Merchant vertical (secondary)

The merchant's business domain matters mainly where it raises **compliance/regulatory** load:

- **fintech / lending / banking, government** — high regulatory/PCI sensitivity; treat security, audit, and data-governance decisions as load-bearing.
- **e-commerce, gaming, media, SaaS** — usually standard payment concerns; complexity comes from the drivers above, not the vertical.

Don't over-weight the vertical — a simple e-commerce checkout and a simple fintech checkout share the same payment architecture; the difference is the compliance posture around it.
