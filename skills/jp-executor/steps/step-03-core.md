# Step 3: Validation Layer & Core Integration

## MANDATORY RULES (READ FIRST)

- 📖 Read this complete step file before acting.
- 🌐 **Re-fetch the docs** (the URLs the architecture recorded) via `docs-mcp` `doc_fetch_tool` before writing code. Use the **exact** method/class/field names from the fetched pages — never from memory or training.
- ⛔ For SDK/headless products, **no payment-method code is written** until that method's client `process`
  payload page is fetched in-session and its exact request shape is extracted. Backend session docs are not
  enough.
- 🧱 Every constrained field passes through the validation layer before it reaches the API.
- 🔎 Provider/API errors keep the **full provider error body** by default for logs/internal handling
  (`error_code`, `error_message`, `developer_message`, and peers the docs define), with only
  secrets/PAN-level redaction.
- ⚠️ NO TIME ESTIMATES.

## YOUR TASK

Build the validation layer and the core payment integration. Execute the `install` and `code` tasks from `task-checklist.md`.

## SEQUENCE

### 1. Re-fetch authoritative docs

For the tasks in scope, `doc_fetch_tool` the base integration pages recorded in the architecture
(`doc-refs`). Extract the exact request/response shapes, method/class names, and field constraints. If a
page won't load, say so and offer the URL — do not invent.

For SDK/headless integrations, treat each in-scope payment method as its own grounding unit: fetch the
method's `process` payload page in-session and extract the exact fields/types/enums/constraints before
writing that method's code. If a method's payload page is missing from `doc-refs` or won't load, **stop that
method's implementation and mark it blocked** rather than guessing from another method or a generic
`paymentMethods` object.

### 2. Emit the validation layer *(only if there are constrained fields)*

If the architecture lists field constraints, generate one validation helper: length/min/max checks, type casting (never pass a string where docs declare numeric), enum sets, and `// docs: …` comments citing the constraint. Written once, referenced everywhere a constrained field is sent. If the integration has no constrained request fields, skip this.

### 3. Install the SDK / packages

Install **exactly** the packages and versions the Prerequisites/Getting-Started page names (npm / pub / gradle / pod, per platform). Install nothing not named in docs.

### 4. Generate core integration

Using doc-sourced names, in the files the architecture's structure assigned:
1. **Auth/config** — read credentials from env (from step 2); never hardcode.
2. **Session / order creation** — backend call returning the client payload (e.g. `sdkPayload`).
3. **Client launch / method handling** — SDK init → open → response handler (for SDK products), or redirect
   handling (hosted/API). For SDK/headless flows, wire each payment method from its own fetched `process`
   payload shape; never invent or generalize a method payload from another method.
4. **Response handling** — treat the client/SDK result as advisory only (reconciliation below is the source
   of truth).
5. **Error handling** — preserve the full provider error body by default in logs/internal responses
   (subject to secret/PAN redaction). User-facing messages may be simplified, but the raw provider fields
   should remain available for debugging unless the architecture explicitly says otherwise.

### 5. Order-status reconciliation

If the integration tracks order/payment status, implement the server-to-server Order Status call as the **authority**: after callback/redirect (and again on webhook, if any), fetch order status and update state. Map Juspay status → app status per the architecture. Never mark an order paid from the client/SDK result alone. (If the architecture defines no status to reconcile — rare — skip.)

## VERIFY & RECORD

Code compiles/builds; constrained fields (if any) route through the validation layer; every implemented
payment method is grounded in its fetched `process` payload page; reconciliation updates persist; provider
error bodies are preserved for debugging; method names match the fetched docs. Mark the matching
`task-checklist.md` tasks `done`.

## NEXT STEP

Load `./step-04-webhook.md`.
