# Step 8: Invoke Executor

## Rules

- Only reached if the user confirmed in step-07.
- The plan file must exist in `.jp-artifacts/` before invoking.
- Use the Skill tool to invoke `integrate` — do not ask the user to run it manually.
- Pass the **canonical artifacts path** to the executor so both plan and summary end up in the same folder.

## Your Task

Invoke the `/jp-executor` executor with the completed plan from the artifacts folder.

## Sequence

### 1. Confirm Plan File Exists

Verify `.jp-artifacts/{$PRODUCT}-{$PLATFORM}-{$DATE}/juspay-plan.md` exists and has non-empty `product` and `platform` frontmatter fields. If missing or incomplete, re-run step-07 to write it.

Store `$ARTIFACTS_FOLDER = {$PRODUCT}-{$PLATFORM}-{$DATE}` (e.g. `hyper-checkout-react-native-2026-06-02`).

### 2. Invoke Executor

Use the Skill tool to invoke the `jp-executor` skill, passing the canonical plan path:

```
Skill({
  skill: "jp-executor",
  args: "--from-plan .jp-artifacts/$ARTIFACTS_FOLDER/juspay-plan.md"
})
```

Before invoking, tell the user:

> "Plan saved to `.jp-artifacts/$ARTIFACTS_FOLDER/juspay-plan.md`.
>
> Invoking `/jp-executor` — it will skip all questions and execute doc fetching, code generation, testing, and summary.
> All artifacts will be written to `.jp-artifacts/$ARTIFACTS_FOLDER/`."

### 3. Hand Off

The `jp-executor` skill takes over from here. This skill's work is complete.

If the Skill tool is unavailable in this environment, tell the user:

> "Your plan is ready. Run:
> ```
> /jp-executor --from-plan .jp-artifacts/$ARTIFACTS_FOLDER/juspay-plan.md
> ```
> to execute the integration."
