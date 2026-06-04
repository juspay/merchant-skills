# Step 7: Portal Configuration *(conditional — dashboard)*

## APPLICABILITY

Run **only if** `task-checklist.md` has `manual-dashboard` tasks (cross-check the architecture's Portal
Configuration block). Many integrations need nothing set in the dashboard. **If there are none, record
nothing and load the next step.**

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🧭 Give the user **exact** dashboard navigation — path + deep link — never vague instructions.
- 🤝 The user configures the portal; you guide and **wait for confirmation**.
- ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Get the required settings configured in the dashboard (e.g. webhook URL, return URL) for each
`manual-dashboard` task.

## SEQUENCE

1. **Source the navigation** from the architecture's Portal Configuration block. If absent/stale,
   re-discover via `docs-mcp` ("Dashboard configuration docs" in `../references/juspay-docs-mcp.md`):
   `explore_product("dashboard")` → fetch the Webhook/Settings page → nav path + deep link.
2. **Connected mode:** read current settings (`juspay_get_webhook_settings`, `juspay_get_general_settings`)
   and prompt only for what's missing/wrong.
3. **Present each setting:** compute values (e.g. webhook URL = deployed base + handler route; ask for the
   base, suggest a tunnel if none), then show: *Set `<value>` · Navigate `<path>` · Link `<deep link>` ·
   Events `<list>` · Guide `<docs URL>`*.
4. **Wait** for the user to confirm each is saved.

## VERIFY & RECORD

Each setting confirmed by the user. Mark the `manual-dashboard` tasks `done` (or `blocked` if the user
lacks dashboard access — note it for the summary).

## NEXT STEP

Load `./step-08-test.md`.
