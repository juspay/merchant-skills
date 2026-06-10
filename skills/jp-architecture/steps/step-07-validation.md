# Step 7: Architecture Validation & Readiness

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- ✅ Validate coherence, PRD coverage, and implementation readiness for `jp-executor`.
- ⚠️ NO TIME ESTIMATES. 💾 Only save when the user confirms they want to continue.

## YOUR TASK

Validate the complete architecture for coherence, payment-flow coverage, and go-live readiness.

## VALIDATION SEQUENCE

### 1. Coherence
- Do product/shape, decisions, patterns, and structure agree? Any contradictions?
- Are all Juspay facts doc-cited (no memory-based claims)?

### 2. PRD coverage
- Every capability/FR and Key Payment Journey has architectural support?
- Every payment **flow** in MVP scope is covered? (Methods are doc-derived/dashboard-enabled, not an MVP scoping question.)
- NFRs addressed: PCI scope, signature verification, idempotency, reconciliation, environments?

### 3. Implementation readiness (for jp-executor)
- Are decisions concrete enough to implement without re-deciding?
- Are file locations, status mapping, env vars, and error handling specified?
- Are the authoritative doc page URLs recorded so executor can re-fetch exact code?
- **Is the client `process` payload extracted to field level for every method the product's docs expose** (not just listed as a URL to fetch later)? A method whose payload is deferred is a critical readiness gap, not a minor one. *(The set is doc-derived, never user-asked; hosted products have none.)*
- Does the architecture specify environment/key alignment and full provider-error surfacing defaults clearly
  enough that the executor won't guess?
- Is **every task tagged with `side`**, and (for `split`) are both `this_side` and `other_side` tasks present?
- Is the **Cross-Side Contract** complete enough that the other repo could build its side from it alone — session/order endpoint + request/response (incl. `sdkPayload`), payment-result endpoint, return-URL/reconciliation/webhook ownership, env/config per side? (When a handoff was ingested, the contract must match it.)

### 4. Go-live readiness checklist

```markdown
### Go-Live Readiness Checklist
Mark [x] only when confirmed; any [ ] must appear in Gap Analysis.

**Payment flows**
- [ ] Every in-scope flow has a covering decision
- [ ] Client `process` payload extracted to field level for every method the docs expose (not deferred; doc-derived, never user-asked; none for hosted)
- [ ] Reconciliation (server-to-server) is the source of truth
- [ ] Idempotent webhook handling specified

**Security & compliance**
- [ ] Signature/auth verification specified
- [ ] Secrets handling specified (no keys in code/logs)
- [ ] PCI scope understood for the chosen integration shape
- [ ] Return-URL / redirect integrity addressed

**Cross-side (split repos)** *(omit if `topology: single-repo`)*
- [ ] Every task tagged `side`; both `this_side` and `other_side` tasks present
- [ ] Cross-Side Contract complete (session/order + result endpoints, `sdkPayload` shape, return-URL/reconciliation/webhook ownership, env per side)
- [ ] If a handoff was ingested, the contract matches it (seam not redesigned)

**Environments**
- [ ] Production host/credentials configured (production enforced; non-production env present only if the user explicitly required it)
- [ ] Key/stage and host alignment specified
- [ ] Go-live gate defined

**Readiness**
- [ ] Decisions doc-grounded (source URLs present)
- [ ] File locations + status mapping + env vars specified
- [ ] Full provider-error surfacing defaults specified
```

### 5. Content to append

```markdown
## Architecture Validation

### Coherence
{{assessment}}

### PRD Coverage
{{flows/FRs/NFRs covered; gaps}}

### Gap Analysis
{{critical / important / minor}}

### Go-Live Readiness Checklist
{{the checklist above, filled}}

### Readiness Assessment
**Status:** {{READY FOR IMPLEMENTATION | READY WITH MINOR GAPS | NOT READY}}
(READY only when all checklist items are [x] and no critical gaps; NOT READY if any critical security/reconciliation gap is open.)
```

## Collaboration Options

Present these as native UI choices when available; otherwise ask a concise direct question:
- **Explore deeper** — resolve complex gaps (inline).
- **Perspectives** — security / ops / developer-experience review (inline).
- **Continue** — append content, set `stepsCompleted: [1,2,3,4,5,6,7]`, load `./step-08-checklist.md`.

FORBIDDEN to load the next step until the user confirms Continue.

## NEXT STEP

After the user confirms Continue, load `./step-08-checklist.md`.
