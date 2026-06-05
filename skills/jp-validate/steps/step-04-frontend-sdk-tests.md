# Step 4: Frontend / SDK / Native Tests *(conditional)*

## APPLICABILITY

Run **only if** the step-01 inventory has a **web, SDK, or native surface** (web pages, hosted/headless SDK init, Android/iOS/Flutter/RN). If the integration is backend/API-only, **record nothing and load the next step.**

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🌐 Fetch test cards/VPAs from the docs (`docs-mcp`) — never invent PANs/VPAs; never assert on or log a raw PAN.
- 🧪 Persist a framework-native spec when a browser runner exists (Playwright/Cypress); else a guided manual run (+ headless curl where the flow allows).
- 📱 Device-only/native UI can't be driven from the CLI — emit a manual test guide, don't fake a pass.
- 🤝 Confirm before driving a live transaction. ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Exercise the built client surface from the step-02 plan, verifying the payment completes **and** that final state still comes from server-side reconciliation — not the redirect/SDK result alone.

## SEQUENCE (run the parts that apply)

1. **Web (browser runner present)** — drive a real transaction with a doc test card/VPA: launch the page/SDK, complete the flow, verify the **return URL** is reached, then assert final state is resolved by **server-side reconciliation** (not redirect params alone). Persist a Playwright/Cypress spec mirroring repo conventions; honor the test-quality DoD (no hard waits — use the framework's auto-waiting/assertions).
2. **Web (no browser runner)** — guided manual run: exact steps + the doc test instruments + expected outcome; automate any headless portion via curl where the flow allows. Persist nothing browser-specific.
3. **Hosted SDK** (e.g. Hyper Checkout) — verify the modal/overlay launches and the success/failure callback fires, followed by reconciliation.
4. **Headless** — verify each in-scope method's merchant-built UI submits the correct `process` payload (cross-check the fetched payload page).
5. **Mobile / native** — emit a **manual test guide**: exact steps, test instruments (cards/VPAs from docs), and the expected callbacks/state transitions. Mark these `test` tasks as manual, not automated-pass.

## VERIFY & RECORD

Per-item result captured (spec path, or "manual guide", or "inline"); web flows confirm return URL **and** reconciliation; the matching `task-checklist.md` `test` tasks marked `done`/`blocked`/manual; anything device-only is clearly flagged as not CLI-automatable. No PAN/secret in any output.

## NEXT STEP

Load `./step-05-report.md`.
