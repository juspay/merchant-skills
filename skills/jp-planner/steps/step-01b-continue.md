# Step 1b: Resume Planning Session

## Rules

- Use **native select UI** for all choices.
- Do NOT re-run steps that are clearly complete — only ask about genuinely incomplete ones.
- The plan document `juspay-plan.md` is the source of truth for what was done.

## Your Task

A `juspay-plan.md` was found at project root. Determine how far planning got and route the user to the right step to continue.

## Sequence

### 1. Read the Plan Document

Read `{project-root}/juspay-plan.md` completely — frontmatter and body.

### 2. Determine Completion State

Check frontmatter fields and body sections to find the first incomplete step:

| Check | If missing/empty | Resume from |
|---|---|---|
| `product` field | Product not selected | `step-02-merchant-product.md` |
| `platform` field | Platform not selected | `step-03-platform.md` |
| `## Doc Pages` section has no entries | Doc discovery not done | `step-04-doc-discovery.md` |
| `backendBaseUrl` field empty | Config discovery not done | `step-05-dashboard.md` |
| `## Executor Manifest` section is empty or `[]` | Plan not compiled | `step-07-plan.md` |
| All of the above are present | Plan is complete | See step 3b |

> Note: Architecture decisions are no longer part of the plan — they are collected by the executor in Phase 3.5 after doc-fetch.

### 3a. Incomplete Plan — Present Options

Show a brief status summary:

```
Found juspay-plan.md:
  Product:   {{product or "not set"}}
  Platform:  {{platform or "not set"}}
  Doc pages: {{count or "none"}}
  Manifest:  {{step count or "not compiled"}}
```

Present native select:

> "Where would you like to continue?"
>
> - `Resume from where I left off` — (auto-detected step above)
> - `Go back to product selection` — step-02
> - `Go back to platform selection` — step-03
> - `Go back to config discovery` — step-05
> - `Recompile the plan` — step-07
> - `Start over — discard this plan`

On **Start over**: confirm with native select (`[Yes, discard | No, keep it]`), then if confirmed delete `juspay-plan.md` and load `step-01-init.md`.

On any other choice: load the selected step file.

### 3b. Complete Plan — Offer Next Action

If the plan appears complete (manifest is non-empty):

> "Your plan is complete."
>
> Summary card: product, platform, N executor steps planned.
>
> Native select:
> - `Invoke /jp-executor now` — load `step-08-invoke.md`
> - `Review a section` — pick step to revisit
> - `Nothing — I'm done`
