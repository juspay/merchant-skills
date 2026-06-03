# Step 9: Completion & Handoff to jp-executor

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🎯 Provide clear next steps for implementation. ⚠️ NO TIME ESTIMATES.
- 🚫 THIS IS THE FINAL STEP.

## COMPLETION SEQUENCE

### 1. Summarize what was built together

Recap: product/shape, key decisions, patterns, structure, Portal Configuration, the readiness status from
Step 7, and the execution checklist. Congratulate the user.

### 2. Update `architecture.md` frontmatter

```yaml
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8, 9]
workflowType: 'jp-architecture'
lastStep: 9
status: 'complete'
completedAt: '<today>'
```

### 3. Handoff

> "Planning complete. Handoff artifacts in `{doc_workspace}` (`{project-root}/docs/juspay/`):
> - `architecture.md` — the why/how (decisions, patterns, structure, portal config)
> - `task-checklist.md` — the ordered, minute execution steps
>
> **Next:** run **`jp-executor`** to implement. It gates on both files, re-fetches the recorded doc pages
> via `docs-mcp` for exact code, walks the checklist, and writes task statuses back.
>
> Live merchant data (credential provisioning, dashboard webhook/return-URL config, stage checks) is
> handled by jp-executor against `juspay-mcp`, with a manual fallback if you can't authenticate."

Offer to answer any questions about the architecture or checklist.

## WORKFLOW COMPLETE

`architecture.md` + `task-checklist.md` are the single source of truth for implementation by `jp-executor`.
