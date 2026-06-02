# Step 3: Platform Selection

## Rules

- If `$PRODUCT_TYPE = api-only` → skip all platform questions, set `$PLATFORM = api-only`.
- Use **native select UI** — show only platforms present in `$DOC_MAP`.
- Pre-select the detected platform from step-01 if it matches a platform in `$DOC_MAP`.
- The `web` vs `iframe-web` choice IS the platform — it is not a sub-question.

## Your Task

Confirm which client platform this integration targets.

## Sequence

### 1. Check Product Type

If `$PRODUCT_TYPE = api-only`:
- Set `$PLATFORM = api-only`
- Update plan: `platform: "api-only"`
- Skip to **Next Step**.

### 2. Check for Single-Side Codebase

Check whether both backend and frontend were detected in step-01.

**If only backend found:**

Present native select:
> "I can only find a **backend** in this project. A complete integration normally requires both sides. How would you like to proceed?"
>
> `[Continue with backend only | Stop — I'll add the frontend first]`

If "Stop" → halt: "Add the frontend to your project and re-run jp-planner when ready."

**If only frontend found:** same prompt with sides swapped.

**If both or neither found:** continue.

### 3. Detect Platform from Codebase

Use the `$DETECTED_PLATFORM` from step-01. Check if it appears in `$DOC_MAP` platforms.

If detected and in doc map → present as pre-selected recommendation:

> "I detected your project is a **[$DETECTED_PLATFORM]** app (found `[signal file]`)."
>
> Native select: `[Yes, use $DETECTED_PLATFORM | No, let me choose]`

If not detected or not in doc map → show platform list from `$DOC_MAP`.

### 4. Platform List (if no auto-detect)

Present as native select showing only platforms in `$DOC_MAP`:

```
Which platform are you integrating for?
> [platform options from $DOC_MAP]
```

### 5. Web vs iframe-web Disambiguation

If the user selects `web` AND both `web` and `iframe-web` are present in `$DOC_MAP`:

> "Which web integration approach do you need?"
>
> Native select:
> - `web` — Direct SDK integration into your page (requires JavaScript SDK setup)
> - `iframe-web` — Hosted payment page embedded via iframe (minimal frontend code)

The selection IS `$PLATFORM`.

### 6. Update Plan

Update `juspay-plan.md` frontmatter:
```yaml
platform: "$PLATFORM"
```

## Next Step

Load `./step-04-doc-discovery.md`.
