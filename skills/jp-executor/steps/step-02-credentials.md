# Step 2: Credentials

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🔐 Secrets live **only** in `.env`/secret stores — never in `task-checklist.md`, the summary, logs, or command output.
- 🤝 Checkpoint with the user before provisioning anything on their account.
- ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Make the integration's credentials available in the codebase's environment — without ever exposing secret values. Execute the `env` / credential `config` tasks from `task-checklist.md`.

## SEQUENCE

### 1. Reuse what upstream resolved

`merchant_id`, `client_id` (default = `merchant_id`), and the juspay-mcp **mode** were recorded by the planning skills — reuse them. Do not re-ask the access question if the mode is already known.

### 2. Identifiers → env

Write the non-secret identifiers to `.env` / `.env.example` using the env-var names the architecture's
patterns defined (e.g. `JUSPAY_MERCHANT_ID`, `JUSPAY_CLIENT_ID`, environment/host selectors such as
`JUSPAY_BASE_URL` / `JUSPAY_ENV`). `.env.example` carries names only, no values.

### 3. API key

- **Connected mode:** after a one-line confirmation, provision a key with `juspay-mcp` `juspay_create_api_key`. Store the returned value **in memory → `.env` only**. Tell the user: *"A new API key was created and stored in `.env`."* — never echo the value.
- **Manual mode (or provisioning declined):** guide the user to create the key in the dashboard. Use the architecture's **Portal Configuration** block (or discover the dashboard docs via `docs-mcp` — "Dashboard configuration docs" in `../references/juspay-docs-mcp.md`) to give the exact nav path / deep link. Ask them to paste the key, and write it to `.env` only.

### 4. Webhook auth credentials (if used)

If the webhook handler uses Basic Auth or a signing secret, decide the env-var names now (values resolved/read at webhook-handler and test time) — keep them out of every artifact.

### 5. Environment alignment preflight

Before leaving this step, confirm the configured host/environment and key target agree: sandbox key ↔
sandbox host, production key ↔ production host. If the architecture or dashboard docs distinguish key
types/stages, record that in env/config now. Do **not** defer this check to live testing.

## VERIFY & RECORD

`.env` contains the needed keys; `.env.example` lists names only; no secret value appears in any artifact,
log, or command; environment/host selection is recorded and consistent with the key being used. Mark the
matching `task-checklist.md` tasks `done` (or `blocked` if the user couldn't obtain the key).

## NEXT STEP

Load `./step-03-core.md`.
