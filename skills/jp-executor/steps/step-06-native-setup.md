# Step 6: Native SDK Setup *(conditional — mobile only)*

## APPLICABILITY

Run **only if** the product type is `sdk`/`hybrid` **and** the surface is native (`android`, `ios`,
`flutter`, `react-native`, `cordova`, `capacitor`) — i.e. the checklist has a `native-setup` task. For
web / iframe-web / API-only surfaces there is no native setup. **If not applicable, mark any
`native-setup` task `skipped` ("non-native surface") and load the next step.**

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🌐 Every native step is grounded in the **fetched docs** (Prerequisites / Android Setup / iOS Setup). Don't invent steps.
- 🧯 Never run a destructive generate/prebuild on a repo that already has native dirs.
- ⚠️ NO TIME ESTIMATES.

## SEQUENCE

1. **Extract native requirements (from docs):** packages/dependencies, build-config edits, platform config
   files (substitute `client_id`/`merchant_id`, no secrets), post-install/prebuild scripts, whether a
   prebuild/sync is required.
2. **Detect project workflow:** Expo-managed vs bare vs Flutter vs Cordova vs Capacitor. Run a
   generate/sync command only if the framework mandates it and native dirs don't already exist.
3. **Apply:** build-config edits via the editor (reviewable, idempotent); create platform config files; run
   post-install scripts (fix-and-rerun on non-zero exit).

## VERIFY & RECORD

Report a short table (packages / prebuild / build config / config files / post-install) with ✅/skipped/❌
and any error+fix. Mark the `native-setup` task `done` or `skipped`.

## NEXT STEP

Load `./step-07-portal-config.md`.
