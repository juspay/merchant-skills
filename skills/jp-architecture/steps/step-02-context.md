# Step 2: PRD Context Analysis

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- ✅ Analyze the loaded PRD — do not assume or invent requirements.
- 📋 You FACILITATE; no technology decisions yet — pure analysis.
- ⚠️ NO TIME ESTIMATES.
- 💾 Only save when the user confirms they want to continue; update frontmatter before loading the next step.

## YOUR TASK

Analyze the integration PRD to understand the payment scope, flows, and constraints that will drive the architecture.

## ANALYSIS SEQUENCE

### 1. Extract from the PRD

- **Capabilities & FRs** — the payment capabilities and their testable consequences.
- **Key Payment Journeys (UJ-N)** — the flows the integration must support (checkout, UPI collect/intent, refund, payout, reconciliation).
- **NFRs & concerns** — PCI-DSS scope, webhook/signature security, **idempotency**, **server-to-server reconciliation**, environments (sandbox/prod), payment-method coverage, platform/SDK surfaces.
- **Non-Goals & MVP scope** — what is explicitly out.
- **Products in scope** — the Juspay product(s) the PRD named (confirm/lock in Step 3).

### 2. Assess scale & domain

Use `../data/integration-complexity.md` to gauge the integration's complexity. The dominant drivers are payment-side: number of payment methods, number of surfaces (web + mobile), integration shape, multi-merchant/tenancy, recurring/mandates, payouts, and regulatory/PCI load — plus the merchant's business vertical where it raises compliance.

### 3. Reflect understanding back

> "Reviewing the PRD. I see {{fr_count}} functional requirements across {{capability_list}}, targeting {{surfaces}} with {{methods}}.
>
> **Architecturally significant:**
> - Flows to support: {{flows}}
> - Critical NFRs: {{PCI / idempotency / reconciliation / signature security}}
> - Environments: {{sandbox/prod}}
> - Open constraints from the PRD: {{...}}
>
> Does this match your understanding?"

### 4. Content to append

```markdown
## PRD Context Analysis

### Payment Scope
{{capabilities, FRs, flows in scope}}

### Non-Functional Drivers
{{PCI scope, security, idempotency, reconciliation, environments}}

### Scale & Domain
- Merchant domain: {{domain}} · complexity: {{low/medium/high}}
- Surfaces: {{web/mobile/server}} · methods: {{cards/upi/...}}

### Constraints & Dependencies
{{existing payment code, env, provider notes from PRD/codebase}}

### Cross-Cutting Concerns
{{concerns spanning multiple capabilities}}
```

## Collaboration Options

Present these as native UI choices when available; otherwise ask a concise direct question:
- **Explore deeper** — probe architectural implications, edge cases, hidden constraints (inline).
- **Perspectives** — weigh from security / operations / developer-experience angles (inline).
- **Continue** — append content, set `stepsCompleted: [1,2]`, load `./step-03-product-sdk.md`.

On Explore deeper/Perspectives: do the inline work, ask for confirmation, then re-show the options. FORBIDDEN to load the next step until the user confirms Continue.

## NEXT STEP

After the user confirms Continue, load `./step-03-product-sdk.md`.
