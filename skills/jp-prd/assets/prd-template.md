# Juspay Integration PRD Template

*Expert prior knowledge, not a checklist. Adapt to the integration, the product(s), and the surfaces. Glossary, capability constraints, error surfaces, and integration shapes must be grounded in `docs-mcp` extracts (cited by URL) or the user — never training data.*

## Essential Spine *(almost always present)*

```markdown
---
title: {Integration Name} — Juspay Integration PRD
juspay_products: [{product slug(s) in scope, e.g. hyper-checkout}]
surfaces: [{web | iframe-web | android | ios | flutter | react-native | server | api}]
created: {YYYY-MM-DD}
updated: {YYYY-MM-DD}
status: draft
---

# PRD: {Integration Name}
*Working title — confirm.*

## 0. Document Purpose
[1 paragraph: who this PRD is for (the team integrating Juspay, plus the downstream `jp-architecture` and `jp-executor` runs), how it's structured (Glossary-anchored vocabulary, capabilities grouped with FRs nested, assumptions tagged inline and indexed). Name the Juspay docs this PRD is grounded in and where they live (source URLs). This PRD captures what the integration must do and why — not how (that is `jp-architecture`).]

## 1. Integration Goal
[2-3 paragraphs: what is being integrated and why — which payment outcomes the merchant needs (accept card/UPI payments, run payouts, take recurring mandates, issue refunds), on which surfaces, for whom. Compelling enough to stand alone.]

## 2. Integration Context

### 2.1 Integration Objective
[Bulleted. What the integrating system needs to achieve: accept UPI/card/etc on which surfaces, launch which Juspay product shape, persist which payment/order states, support which callbacks/webhooks/refunds. Keep it concrete.]

### 2.2 Out-of-Scope Methods / Surfaces (v1) *(add when the boundary is non-obvious)*
[Payment methods, surfaces, flows, or environment modes explicitly not served in v1.]

### 2.3 Key Payment Journeys
*Integration flow narratives. Numbered globally UJ-1..UJ-N. Describe the real flow the integration must support. FRs reference flows by ID inline ("realizes UJ-2").*

- **UJ-1. {One-line title — role completing a payment.}**
  - **Trigger + context:** one line, grounded enough to explain when this flow starts.
  - **Entry state:** which surface, authenticated?, coming from where (cart, invoice, app screen)?
  - **Path:** 3-5 concrete beats — session created, SDK launched, method chosen, payment authorized.
  - **Climax:** the moment the payment succeeds and how the payer knows.
  - **Resolution:** order reconciled server-side, receipt/state shown, what's next.
  - **Edge case** *(optional)*: one real failure mode (declined card, dropped UPI, webhook delay) and what happens next.

  *Example:*
  > **UJ-2. A returning shopper pays for groceries with UPI and is back to the cart in seconds.**
  > The shopper checks out on the merchant's mobile web store. The backend creates a session; the hosted payment page opens; they pick UPI, approve in their UPI app, and the page redirects to the return URL. The server reconciles order status via the Order Status API (it does not trust the SDK result alone) and shows "Paid". **Edge case:** if the UPI collect request expires, they see a clear retry option and no duplicate order is created.

**Scope dial:** server-only/API integrations may compress these to a single sentence; UX-heavy checkout flows get full beats + edge cases.

## 3. Glossary
*Downstream skills and readers must use these terms exactly. Sourced from Juspay docs (cite URLs). No synonyms anywhere else in the PRD.*

- **Term** — Definition. Relationship to other terms. *(source URL)*
- [e.g. **Session** — a backend-created order session; the `sdkPayload` from the response launches the client SDK. **Order Status API** — server-to-server reconciliation of final payment state. **Webhook** — async final-status notification; idempotent handling required. **merchant_id / client_id**, **integration type (PP / ec_sdk / ec_api)**, **signature verification** — define each from the fetched docs.]

## 4. Capabilities
*Each subsection is a coherent capability: behavioral description first, FRs nested, optional capability-specific NFRs. FRs numbered globally (FR-1..FR-N). Reference journeys by ID inline.*

### 4.1 {Capability Name — e.g. Session Creation}
**Description:** [Behavioral narrative — what this capability does, who triggers it, the payer/merchant experience, edge cases. Realizes UJ-X. Use Glossary terms exactly. Embed `[ASSUMPTION: ...]` tags where inferred.]

**Functional Requirements:**

#### FR-1: {Short capability name}

[Actor] can [capability] [under conditions]. Realizes UJ-X.

**Consequences (testable):**
- {Specific testable condition, e.g. "After payment callback, the system fetches Order Status server-to-server and never marks an order paid from the SDK result alone."}
- {e.g. "Webhook handling is idempotent: a redelivered event does not double-update the order."}

**Out of Scope:** *(optional)*
- {bound}

#### FR-2: ...

**Capability-specific NFRs:** *(only if unique to this capability)*

**Notes:** *(optional — `[NOTE FOR PM]` callouts)*

### 4.2 {Capability Name}
...

## 5. Non-Goals (Explicit)
[Bulleted. What this integration is *not* and will *not* do in v1 — e.g. "not building a custom payment gateway", "not handling settlement/reconciliation accounting", "no saved-card vault in v1". Prevents scope creep downstream at architecture, executor, and ticket level.]

## 6. MVP Scope

### 6.1 In Scope
[Bulleted, crisp — products, payment methods, surfaces, flows for v1.]

### 6.2 Out of Scope for MVP
[Bulleted. Each item with a one-line reason if it matters; mark v2/v3 deferrals. `[NOTE FOR PM]` where a deferred item is load-bearing.]

## 7. Integration Readiness & Verification
*What downstream architecture/executor must be able to verify before calling this integration complete.*

- **Readiness-1**: In-scope payment methods/surfaces are explicitly listed and each maps to one or more FRs.
- **Readiness-2**: Final payment state comes from the intended source of truth (for example, Order Status reconciliation, webhook, or documented synchronous response), not an ambiguous client signal.
- **Readiness-3**: The environments required for v1 are named (sandbox, production, or both), including any return URL / webhook / callback expectations that affect implementation.
- **Readiness-4**: Error/failure paths that materially affect the integration are called out (declines, expired collect request, webhook delay/failure, refund failure, etc.).
- **Readiness-5**: Open questions that would block architecture or executor are surfaced in §8 instead of being implied.

## 8. Open Questions
[Numbered. Unknowns that become follow-up research or tickets — not silent gaps. E.g. "Is a webhook URL already configured on the merchant account?" "Which environments (sandbox/prod) must v1 support?"]

## 9. Assumptions Index
*Every `[ASSUMPTION]` from the document, surfaced for explicit confirmation:*
- Inline assumption from §X.Y — short description.
```

---

## Adapt-In Menu *(add the clusters the integration calls for)*

### Cross-cutting quality and shape *(most non-trivial integrations)*
- **Cross-Cutting NFRs** — system-wide non-functional requirements not tied to one capability (latency, availability, observability/logging, retry/backoff).
- **Constraints and Guardrails** — operational boundaries, storage boundaries, cost/performance constraints, rollout constraints.
- **Why Now** — add when timing is load-bearing (a launch deadline, a provider migration, a method rollout). Drop when incidental.

### Payment & integration concerns *(baked in for Juspay)*
- **Security** — credential/secret handling (no keys in code or logs), webhook **signature verification**, return-URL/redirect integrity, replay protection.
- **Idempotency & Reconciliation** — order-status reconciliation as source of truth, idempotent webhook handling, duplicate-order prevention, retry semantics.
- **Environments** — the environments in play (production by default, plus sandbox and any staging/pre-production the merchant uses — not necessarily just two), test credentials/cards/VPAs, environment promotion and go-live gating.
- **Payment Methods & Coverage** — which methods are in scope (cards, UPI collect/intent, netbanking, wallets, EMI, BNPL/PayLater, NACH, virtual/gift cards, QR, … — whatever the product supports, not limited to these), and the integration stages/flows each implies.
- **Platform / SDK Surface** — web / iframe-web / native mobile / cross-platform; SDK vs hosted page vs direct API; per-surface requirements.
- **API Contracts** — request/response field tables for the endpoints in scope (field, type, required, constraints), error-code surface — all doc-derived and URL-cited. Heavy detail can move to `addendum.md`.

### Merchant-platform concerns
- **Multi-Merchant / Tenancy** — per-merchant credentials, configuration, isolation.
- **Operational Requirements** — SLAs, monitoring/alerting on payment failures, on-call expectations.
- **Rollout & Change Management** — phased rollout, fallback to existing provider, comms.
- **Data Governance** — what payment data is stored, retention, classification.

### Small-scope all-inclusive *(use when the integration is 1-2 stories' worth and the user wants a single captured artifact)*
- **Stories** — story-level specs listed inline at the end. Each: *"The system/user can [action] [under conditions]. Acceptance: [testable criteria]."* Numbered Story-1.. Pair with a lean §1 Goal, §2 Integration Context (one key flow), §3 Glossary (handful of terms), §4 Capabilities (often one), §6 MVP Scope (tight).
