# Step 5: Data Model *(conditional)*

## APPLICABILITY

Run **only if** `task-checklist.md` has `db` tasks (cross-check the architecture). Skip when order/payment
state lives elsewhere, is handled by an existing schema the architecture chose not to change, or the
integration is stateless. **If there is no `db` task, record nothing and load the next step.**

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🤝 **Checkpoint with the user** before creating or altering schema.
- 🧱 Field names/sizes come from the docs/constraints the architecture recorded — add nothing extra.
- ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Create/alter the order/payment schema per the architecture's decision (extend existing vs new).

## SEQUENCE

1. Confirm the architecture's finding by scanning the codebase for existing order/payment schemas.
2. **Checkpoint** before writing. Then apply the decided change (migration/model), using field names and
   sizes from docs/constraints (e.g. `maxLength: 20` → `VARCHAR(20)`).

## VERIFY & RECORD

Schema matches the decision and the codebase migrates cleanly. Mark the `db` tasks `done` (or `skipped`
if the user opts out).

## NEXT STEP

Load `./step-06-native-setup.md`.
