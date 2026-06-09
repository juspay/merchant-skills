# Live merchant data (`juspay-mcp`) — access → login or manual

`juspay-mcp` exposes live Juspay **dashboard** data (merchant details, account settings, webhook config, API keys, integration-stage status). It is **auth-guarded** — it requires the user to log in to their Juspay dashboard. Not every developer has dashboard access, so this is **always optional and must never block**: if they can't (or won't) log in, proceed via manual Q&A.

`docs-mcp` (documentation) is separate and needs no auth — keep using it regardless of the choice here.

## Step 1 — Ask about access (always first)

Before any `juspay-mcp` call, ask:

> "Do you have access to the Juspay dashboard (merchant portal) for this integration?
> 1. **Yes — log in** so I can pull live account details (merchant ID, settings, webhooks) directly.
> 2. **No / skip — manual Q&A**: we proceed without the dashboard and fill these in by hand (or mark them to confirm later)."

Record the choice as the run's **mode** (`connected` | `manual`).

## Step 2a — If "Yes": log in (OAuth)

1. Call `mcp__juspay-mcp__authenticate()` → it returns an authorization URL. Share that URL with the user.
2. The user authorizes in their browser; the browser is redirected to `http://localhost:<port>/callback?code=...&state=...` (on remote sessions the page may fail to load — the URL in the address bar is still valid).
3. Call `mcp__juspay-mcp__complete_authentication(callback_url=<that full URL>)`.
4. **On success → connected mode.** The server's real tools become available; use the ones this skill needs (below).
5. **On failure / unavailable / user gives up → fall back to manual mode.** Never block; tell the user you're switching to manual Q&A.

## Step 2b — If "No": manual mode (Q&A)

Gather the same data points by asking the user, or mark each as *to confirm in the dashboard* so a downstream step (or the developer) fills it. **Do not invent values.**

## Provenance — tag every datum

Tag where each value came from and record it in the artifact and `.decision-log.md`:

- `mcp` — pulled live from juspay-mcp
- `user` — provided manually by the user
- `manual-dashboard` — not known yet; to be set/confirmed in the dashboard later

## Reuse across the chain

The mode and these values flow downstream via the artifacts (`prd.md`, `architecture.md`, `.decision-log.md`). A later skill **reuses** what's already recorded and only runs this flow for what is missing or newly needed — don't re-ask the access question if a prior run already established the mode and the data this skill needs.

## Data points by skill

Expected live tools (names from the existing `integrate` skill; the real set appears only after auth). These per-skill lists are the **known** read tools, **not an exhaustive allowlist** — after auth, enumerate the tools the MCP actually exposes and use any additional relevant *read* tools it offers; don't skip a useful call just because it isn't listed here:

- **jp-prd** *(read)* — `juspay_get_merchant_details` → `merchant_id`, `client_id` (default = `merchant_id`), `integration_type` (helps converge on the product/integration shape). Optional: current settings to know existing state.
- **jp-architecture** *(read)* — `juspay_get_webhook_settings` (configured? events), `juspay_get_general_settings` (return URL configured?), `juspay_integration_monitoring_status` (integration stages / flows to cover).
- **jp-executor** *(write/confirm)* — `juspay_create_api_key` (provision), dashboard webhook/return-URL configuration, `juspay_integration_monitoring_status` (confirm stages after tests).

## Security

Never put secret values (API keys, webhook auth passwords) into `prd.md`, `architecture.md`, `.decision-log.md`, logs, or command output. Secrets live only in `.env`/secret stores and are handled by `jp-executor`. The dashboard `client_id`/`merchant_id` are identifiers, not secrets, and may be recorded.
