# Juspay Integration PRD Quality Rubric

A judgment rubric for the validator subagent. Walk the PRD with these dimensions in mind and write substantive findings — not box-ticking. The goal is a review that tells the user whether this integration PRD is *good*, not whether it has the right section headers.

Most PRDs do not need every dimension scrutinized equally. Calibrate to the integration's shape (consumer checkout, internal tool, server-only API integration, technical capability spec), downstream usage, and what the PRD itself is trying to do. Be specific — cite locations, quote phrases, name what's missing. Abstract criticism is failure of nerve.

## How to use this rubric

1. Read the full PRD (and addendum.md if present) before writing anything.
2. For each dimension below, form a judgment — *strong / adequate / thin / broken* — backed by specifics from the PRD.
3. Write findings only where they add information. A `strong` dimension may need no findings; a `broken` one needs concrete, fixable ones.
4. Severity ranks impact on the PRD's usefulness, not how easy the fix is. A vague Vision statement is *critical* even though it's a one-paragraph fix; a glossary drift might be *low* even though it appears in many places.
5. The overall verdict is your synthesis — 2–3 sentences that name what holds up and what's at risk. Earn it with the dimension judgments.

## Output format

Write findings to `{doc_workspace}/review-rubric.md`:

```markdown
# PRD Quality Review — {prd_name}

## Overall verdict
[2–3 sentences. What holds up, what's at risk. Earned by the dimension judgments below.]

## Decision-readiness — [strong | adequate | thin | broken]
[1–3 paragraphs of judgment with specific PRD locations.]

### Findings
- **[critical|high|medium|low]** [Title] (§ location) — [Note]. *Fix:* [suggested fix].

## Substance over theater — [verdict]
...

(repeat for each dimension)

## Mechanical notes
[Glossary drift, ID continuity, broken cross-refs, Assumptions Index roundtrip. Lighter weight — these matter for downstream but don't drive the overall verdict.]
```

## The dimensions

### 1. Decision-readiness

Can a decision-maker act on this PRD? Are the trade-offs surfaced honestly, or has the PRD smoothed everything to neutral? Would someone pushing back find their objection acknowledged or dodged?

Look for:
- Decisions that are stated as decisions, not buried as "considerations."
- Trade-offs named with what was given up, not just what was chosen.
- Open Questions that are actually open — not rhetorical questions with an answer in the next sentence.
- `[NOTE FOR PM]` callouts at real tensions, not at safe checkpoints.

Red flag: a PRD where every choice "balances" everything and every NFR is "important."

### 2. Substance over theater

Is the content earned, or is it furniture? Distinguish:

- **Innovation theater** — claimed novelty that isn't novel. Differentiation sections written because the template had one, not because Discovery surfaced something.
- **NFR theater** — copied boilerplate ("system must be scalable / secure / reliable") without product-specific thresholds.
- **Vision theater** — a Vision statement that could swap into any PRD in this category without change.

Flag what reads like furniture, even if it's well-written furniture.

### 3. Strategic coherence

Does the PRD have a thesis? Do the features serve a unified arc, or is it a list of capabilities someone wanted?

Look for:
- A stated thesis the PRD bets on (problem framing, user insight, market move).
- Feature prioritization that follows from the thesis — not from "what's easy first."
- Integration-readiness criteria that validate the PRD's thesis and scope, not generic delivery aspirations.
- Coherent MVP scope kind — problem-solving, experience, platform, or revenue — with scope logic that matches.

Red flag: a PRD that reads as a backlog with section headings.

### 4. Done-ness clarity

Would an engineer reading this PRD know what "done" looks like for each FR?

Look for:
- FRs with at least one testable consequence per FR — verifiable condition, measurable outcome.
- "System handles X gracefully," "reasonable performance," "user-friendly" — flag every one.
- Acceptance criteria implied or explicit. Sometimes the FR's consequences carry this; sometimes the PRD genuinely needs an Acceptance section.
- For non-functional sections (UX, performance, security): bounds, not adjectives.

This is the dimension downstream story creation will lean on hardest. Be unforgiving here.

### 5. Scope honesty

Are omissions explicit, or is the reader meant to infer them?

Look for:
- A Non-Goals section where it would do real work — and `[NON-GOAL for MVP]` callouts where omissions could be silently assumed.
- `[ASSUMPTION: …]` tags on inferences the user didn't directly confirm, indexed at the end.
- `[NOTE FOR PM]` callouts at deferred decisions and unresolved tensions.
- De-scoping proposed honestly, not done silently.

Open-items density: count Open Questions + `[ASSUMPTION]` + `[NOTE FOR PM]` callouts relative to how implementation-ready the PRD claims to be. High counts on an early draft can be fine; high counts on a handoff-ready PRD is a blocker.

### 6. Downstream usability

If this PRD feeds UX, architecture, or story creation, can those workflows source-extract from it cleanly?

Look for:
- Glossary present; every domain noun used identically across FRs, UJs, and readiness criteria.
- FR / UJ / readiness IDs contiguous, unique, and cross-references that resolve.
- Each section makes sense pulled out alone — cross-references via Glossary terms, not "see above."
- UJs each have a concrete role-based protagonist (never a fictional name); no floating UJs.

For standalone PRDs (no downstream), this dimension matters less — say so. For this skill, downstream is `jp-architecture` and `jp-executor` — judge whether they can source-extract the integration cleanly.

### 7. Shape fit

Has the PRD been forced into a shape that doesn't match the product?

- Consumer product / meaningful UX → UJs or flow narratives are load-bearing.
- Internal tool, single-operator role, or server/API integration → capability spec shape; UJs may be overhead.
- Hobby / solo → rigor light, substance bar still applies.
- Brownfield → existing-code references must be accurate; new UJs and existing UJs must be distinguished.
- Chain-top (feeds UX → architecture → stories) → downstream usability matters more; standalone PRDs can be lighter on traceability.

Flag PRDs that are over-formalized (UJ density for a single-operator tool) or under-formalized (consumer product with no UJs).

### 8. Integration soundness (Juspay)

The payment-domain dimension. Calibrate to integration complexity and handoff needs.

Look for:
- **Doc-grounding.** Product names, integration shape, field/credential vocabulary, error codes, and webhook structure are grounded in `docs-mcp` extracts and **cited by source URL** — not asserted from memory. Flag any Juspay-specific claim with no source.
- **Integration shape stated.** Hosted page vs headless SDK vs direct API is named and matches the surfaces — this drives which capabilities and handoff sections apply.
- **Reconciliation as source of truth.** The PRD requires server-to-server order-status reconciliation and does **not** treat the SDK/client result as authoritative.
- **Idempotency.** Webhook/event handling is required to be idempotent; duplicate-order prevention is addressed.
- **Security & secrets.** Credential handling (no keys in code/logs), webhook **signature verification**, and redirect/return-URL integrity appear where the integration requires them.
- **Environments.** Sandbox vs production is distinguished, with a go-live gate.
- **Method/flow coverage.** The payment methods and flows in scope are explicit, and the integration stages each implies are acknowledged.

Red flag: an implementation-handoff PRD that trusts the client result, has no signature verification where webhooks/callbacks are in scope, has no idempotency, or invents Juspay field names not in the fetched docs.

## Mechanical notes

Cover these as a tail section, not a primary dimension. They matter for downstream but don't drive the verdict on whether the PRD is good.

- Glossary drift (case, plural, synonyms across the PRD).
- ID continuity (gaps, duplicates, unresolved cross-references).
- Assumptions Index roundtrip (every inline `[ASSUMPTION]` indexed; index entries all appear inline).
- UJ/flow clarity (each UJ has a concrete trigger/context and completion path).
- Required sections present for the integration shape and downstream handoff needs.
