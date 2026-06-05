# Payment test matrix — what to test, by risk

The payment-specific coverage `jp-validate` reasons from. It is a **menu, not a mandate**: an item is in scope only when the architecture put it in scope **and** jp-executor built it **and** the environment can exercise it. Ground every shape (fields, status enums, error codes, test cards/VPAs) in re-fetched docs via `docs-mcp` — never hardcode them here.

## Priority matrix (P0–P3)

Order execution **smoke → P0 → P1 → P2 → P3**. A P0 failure blocks the quality gate.

| Pri | What | Why |
|-----|------|-----|
| **P0** | Order/session creation · server-to-server **status reconciliation** · payment authorization · **money movement** (capture/refund/payout) · **webhook signature verification** | Revenue-critical and trust-critical — a bug here loses or mis-moves money, or trusts a forged result. |
| **P1** | Primary in-scope payment-method happy paths (the methods the PRD names) · webhook **idempotency** · return-URL integrity | Core journeys the integration exists to serve. |
| **P2** | Constraint/boundary validation · secondary methods · documented error-code paths · retry/timeout handling | Correctness at the edges; degrades UX if wrong but not silently lossy. |
| **P3** | Polish, optional flows, cosmetic states | Low risk. |

## Coverage areas

### 1. Order & payment lifecycle (P0)
- **Creation** — session/order API called with the doc-exact request shape; 2xx + the documented response field (e.g. `sdkPayload`/order id); DB row created if the integration persists one.
- **Status reconciliation** — the **server-to-server Order Status API is the source of truth**; assert the app fetches it and never persists final state from the client/SDK result alone. Verify the Juspay-status → app-status mapping matches the architecture's table.
- **Duplicate prevention** — a redelivered terminal event/callback does not create a second order or double-apply state.

### 2. Per-method `process` payloads (P1, one per in-scope method)
For each method the architecture scoped and the executor built (UPI collect/intent, card, netbanking, wallet, EMI, BNPL, …): assert the client `process` request matches the fetched payload page **field for field** — required fields present, types/enums correct, method-specific constraints honored. Use **test cards/VPAs from the docs**, never invented PANs/VPAs. Never assert on or log a raw PAN.

### 3. Webhooks (P0 signature / P1 idempotency)
- **Signature verification** — a tampered/invalid-signature payload is **rejected**; a correctly signed one is accepted. (Secret comes from `.env`/juspay-mcp, never inlined.)
- **Idempotency** — the same event delivered twice updates state **once** (no double-credit/double-fulfilment).
- **Parsing & response** — payload structure matches docs; required fields present/typed; the handler returns the documented status/body.

### 4. Constraints & error paths (P2)
- One boundary value per constrained field → assert the **doc-specified** error (not a generic 500).
- For each documented error code in scope, assert the app maps it to the intended handling (transient vs permanent, user-facing message, retry).
- Network-failure-mid-payment → state is resolved by a server-side status fetch, not a client guess.

### 5. Money-movement specifics (P0, where built)
- **Refund** — amount matches; idempotent on re-delivery; order state reflects the refund.
- **Payout** — beneficiary validation (penny-drop/penniless) as built; payout created; status tracked; failure/retry handled.
- **Billing/mandate** — mandate execution on schedule; webhook per cycle; state transitions (active/paused/cancelled).

### 6. Frontend / SDK (step-04)
- **Web** — drive a real transaction with a doc test card/VPA; verify the return URL is reached **and** that final state still comes from server-side reconciliation (not the redirect params alone).
- **Hosted SDK** (e.g. Hyper Checkout) — modal/overlay launches; callback fires on success/failure; reconciliation follows.
- **Headless** — each method's merchant-built UI submits the correct `process` payload.
- **Mobile/native** — emit a manual test guide (CLI can't drive device UI): exact steps, test instruments, expected callbacks.

## Payment NFR checks (lite — assert in step-05)
- **No PAN/secret in logs** — provider error bodies are preserved for debugging but PAN/secrets are redacted; no API key, webhook password, or full card number appears in logs, the report, or command output.
- **Signature enforced** — webhook (and return-URL HMAC where used) verification is actually wired, not stubbed.
- **Idempotency proven** — by an actual duplicate-delivery test, not assumed.
- **Environment alignment** — key/stage ↔ host agree; production by default unless told sandbox; a dummy/test gateway from the MCP is acceptable for integration testing.

## Test-quality Definition of Done
Every **persisted** test must be:
- **Deterministic** — no `sleep`/hard waits, no uncontrolled randomness (seed/control any generated data), no reliance on wall-clock timing.
- **Isolated** — cleans up after itself; passes regardless of run order; no shared mutable state across tests.
- **Explicit** — assertions visible in the test body; no hidden conditionals that can skip the assert.
- **Focused & fast** — one behavior per test; no browser spin-up for a pure API check.

## Traceability
Every test item carries the requirement it covers — its `task-checklist.md` `test` task id, the FR (`FR-n`), or the architecture decision. step-05 turns these into the coverage matrix; an in-scope-but-untested item shows as a gap.
