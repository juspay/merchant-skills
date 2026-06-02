# Reference — Retrieval procedures (done inline, no sub-agents)

Load the section you need when a phase calls for it. Each procedure is **read-only**, returns a strict shape, and **must not be faked** — if the data can't be retrieved, say so and fail/skip the owning step rather than inventing values. To protect context on weak models: fetch lean, extract each page immediately, and don't hold many raw pages at once.

**Never construct doc URLs** — copy them verbatim from `explore_product` / `md content link`.

---

## §doc-map  (Step 2.1)

Input: a product id. Call `juspay-docs-mcp:explore_product({ product })`; if it fails, `list_products` to resolve the slug, then retry. Build `$DOC_MAP`:

- `title`, `platforms[] {id,title}`, `sections[] {platform, sectionTitle, pages[]{pageTitle, mdContentLink, order}}` (preserve numbered-page order via `order`).
- `productType`: runtime platform IDs → `sdk`; only `docs` → `api-only`; mix → `hybrid`.
- `hasWebhooks`, `hasStatusApi`, `hasTestResources`: whether such a section/page exists.

Validate `$DOC_MAP` is non-empty before proceeding.

---

## §constraints  (Phase 3 — doc-fetch)

Input: the ordered `md content link` URLs (Pre-Requisites/Overview first, then numbered base pages, then webhooks/status, then error codes). `doc_fetch_tool({ url })` each in order (fall back to WebFetch only on a valid-URL error). Read each fully and build:

- `$CONSTRAINTS[]` — per field: `name`, `type` (`String|Integer|Decimal|Boolean|Array|Object`), `required`, `maxLength`, `minLength`, `minValue`, `maxValue`, `format`, `enumValues[]`, `warnings[]`, `errors[]`.
  - `maxLength` from "max N chars / up to N / (max: N)"; `min/maxValue` from "minimum/maximum N"; `format` from ISO 4217 / E.164 / UUID / YYYY-MM-DD / alphanumeric; `enumValues` from "one of: …" or a values table; `warnings` from callouts near the field; `errors` from error codes referencing the field.
- `$CODE_EXAMPLES` — exact method/class/identifier names from the docs.
- `$ERROR_CODES` — `{code, meaning, action}` from every page.
- `$VERSION_CONSTRAINTS` — min SDK / platform version.
- `$WARNINGS` — global callouts not tied to a field.

**Validate `$CONSTRAINTS` and `$CODE_EXAMPLES` are populated.** If a required page failed to load, surface the URL and `step-end failed` — never record a count you didn't actually extract.

---

## §dashboard-nav  (Phase 4 — webhook-config / return-url-config)

Input: `target` = `webhook` or `general-settings`. `explore_product({ product: "dashboard" })` (fall back via `list_products({ category: "DASHBOARD" })`); find the page whose title matches the target; `doc_fetch_tool` it. Return:

- `docsUrl`, `dashboardNav` (e.g. "Settings → Webhooks"), `dashboardLink` (direct deep-link or null — don't invent), `eventsRequired[]` (webhook target only).

If no matching page, fall back to navigation-only instructions.

---

## §test-resources  (Step 8.3.1)

Input: the test-resources `md content link`. `doc_fetch_tool` it (fall back to WebFetch). Return verbatim (never invent card numbers/VPAs):

- `$TEST_CARDS[] {number, expiry, cvv, outcome}`, `$TEST_UPI_VPA[] {vpa, outcome}`, `$DUMMY_PG_FLOWS[]`.

---

## §codebase-signals  (Steps 2.2, 2.2.5, 2.3, 4.1, 4.4, plan, 5.3.1)

Read-only fan-out scans with Glob/Grep/Read. Return a verdict + the signal files matched; never guess — `null` when nothing is found.

- `backend-lang`: `requirements.txt`/`pyproject.toml`→python; `tsconfig.json`+`*.ts`→typescript; `package.json` w/ express|fastify|koa|hapi|nest or majority `*.js`→javascript; `go.mod`→go; `pom.xml`/`build.gradle`(non-Android)→java; `Gemfile`→ruby; `composer.json`→php; `*.csproj`→csharp; `Cargo.toml`→rust. Conflict → count files under `server/`,`backend/`,`api/`,root; majority wins.
- `platform`: `pubspec.yaml`→flutter; `package.json` w/ react-native→react-native; @capacitor/core→capacitor; cordova/`config.xml`→cordova; `build.gradle`/`AndroidManifest.xml`→android; `*.xcodeproj`/`Podfile`→ios; else web/iframe-web.
- `single-side`: presence of backend signals vs frontend signals (`react`/`vue`/`svelte`/`next`/`index.html`/`src/*.tsx`/`pubspec.yaml`/`AndroidManifest.xml`). Returns `{hasBackend, hasFrontend}`.
- `existing-schemas`: migrations (`migrations/`, `db/migrate/`, `*.sql`, `*.prisma`, `schema.rb`, `typeorm/*.entity.*`, mongoose models) or ORM models with `order_id`/`payment_status`/`transaction_id`/`amount`. Returns the matches (drives `hasExistingOrderSchema`).
- `backend-base-url`: `PORT=` in backend `.env`→`http://localhost:PORT`; `EXPO_PUBLIC_API_URL`/`VITE_API_URL`/`NEXT_PUBLIC_API_URL`→that value; `--port` in dev script; docker-compose ports.
- `webhook-handler-path` / `return-handler-path`: existing route files (`api/juspay/webhook`, `api/webhook`, redirect/return handlers).

---

## §integration-stages  (Step 5.1 and Step 8.5)

Call `juspay-mcp:juspay_integration_monitoring_status({ platform, product_integrated, merchant_id, start_time, end_time })` with the mappings below, walk the nested response, and return the filtered stages — not the raw dump.

**Mappings.** `product_integrated`: hyper-checkout→`Payment Page Session`, ec-headless→`EC + SDK`, ec-api→`EC Only`, others→`Payment Page Session`. `platform`: web/iframe-web→`Web`, android→`Android`, ios→`IOS`, flutter/react-native/cordova/capacitor→`Android`, api-only→`Backend`. Window: 30 days ago → now (`YYYY-MM-DDTHH:MM:SSZ`).

**Traversal:** `queryData.responseData.features → {featureKey} → .sections → {sectionKey} → .stages → {stageKey}`. Include a stage only if `visibilityResult==true && disableStage==false && onlyReportResult==false`.

Each `$INTEGRATION_STAGES[]` entry: `section` (the sectionKey = display name), `stageKey`, `stageDisplayName`, `stageDescription`, `criticalResult`, `status` (`PASSED|FAILED|NOT_ATTEMPTED`). Also store `queryData.scoreData` as `$SCORE_BASELINE` (`baseCriticalSuccessCount/baseCriticalTotalCount`, `baseTotalSuccessCount/baseTotalCount`). If the call fails or returns no stages, `step-end failed` — don't proceed with no stage contract.
