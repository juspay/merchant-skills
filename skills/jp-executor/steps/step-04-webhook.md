# Step 4: Webhook Handler *(conditional)*

## APPLICABILITY

Run **only if** `task-checklist.md` has `webhook` tasks (cross-check the architecture's decisions). Many
integrations don't need a webhook — synchronous/redirect-only flows that rely on order-status
reconciliation alone, or merchants that poll. **If there is no webhook task, record nothing and load the
next step.**

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🌐 Webhook payload shapes, event names, and the **signature/auth scheme** come from the **re-fetched docs** — never memory.
- 🔒 Signature/auth verification is **mandatory**; handling is **idempotent**.
- ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Implement the webhook handler for the `webhook` tasks in the checklist.

## SEQUENCE

1. Fetch the webhook docs page (URL from the architecture's `doc-refs`).
2. Implement the handler in the file the architecture assigned:
   - **Verify** the signature/auth on every request before trusting the payload; reject on failure.
   - **Idempotent**: a redelivered event must not double-update the order (dedupe on event/order id).
   - Update order state via the status mapping from the architecture (and trigger reconciliation from
     step 3 where appropriate); respond with the documented success contract.

## VERIFY & RECORD

Handler verifies signatures and is idempotent; a synthetic event updates state correctly. Mark the
`webhook` tasks `done` (or `blocked`).

## NEXT STEP

Load `./step-05-data-model.md`.
