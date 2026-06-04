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
- Every payment method/flow in MVP scope is covered?
- NFRs addressed: PCI scope, signature verification, idempotency, reconciliation, environments?

### 3. Implementation readiness (for jp-executor)
- Are decisions concrete enough to implement without re-deciding?
- Are file locations, status mapping, env vars, and error handling specified?
- Are the authoritative doc page URLs recorded so executor can re-fetch exact code?
- **Is the client `process` payload extracted to field level for every in-scope method** (not just listed as a URL to fetch later)? A method whose payload is deferred is a critical readiness gap, not a minor one.
- Does the architecture specify environment/key alignment and full provider-error surfacing defaults clearly
  enough that the executor won't guess?

### 4. Go-live readiness checklist

```markdown
### Go-Live Readiness Checklist
Mark [x] only when confirmed; any [ ] must appear in Gap Analysis.

**Payment flows**
- [ ] Every in-scope method/flow has a covering decision
- [ ] Client `process` payload extracted to field level for every in-scope method (not deferred)
- [ ] Reconciliation (server-to-server) is the source of truth
- [ ] Idempotent webhook handling specified

**Security & compliance**
- [ ] Signature/auth verification specified
- [ ] Secrets handling specified (no keys in code/logs)
- [ ] PCI scope understood for the chosen integration shape
- [ ] Return-URL / redirect integrity addressed

**Environments**
- [ ] Sandbox vs production hosts/credentials distinguished
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
