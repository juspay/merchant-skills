# Detecting & mirroring the repo's test stack

`jp-validate` **replicates the repo's existing way of testing** rather than imposing one. Detect what is already there, classify the surface, and write tests in that framework's conventions. Inline curl/bash is the fallback for cases with **no** runner — not the default.

## 1. Scan for runners (evidence, not assumption)

Read manifests and config files; never guess from file names alone. Treat a runner as "present" only when both a dependency/binary **and** a config or existing test files back it up.

**JS / TS** — read `package.json` (`devDependencies`, `scripts`) + config files:
- **Playwright** — `@playwright/test`, `playwright.config.{ts,js,mjs}`, `e2e/`/`tests/` `*.spec.ts` using `page`. → E2E/UI + API (`request` fixture).
- **Cypress** — `cypress`, `cypress.config.{ts,js}`, `cypress/`. → E2E/UI + API (`cy.request`).
- **Jest** — `jest`, `jest.config.*` or `"jest"` key. → unit/integration; API via `supertest`.
- **Vitest** — `vitest`, `vitest.config.*`/`vite.config.*`. → unit/integration.
- **supertest** — `supertest` dep → in-process HTTP assertions against the app (pairs with Jest/Vitest/Mocha).
- **Mocha/Chai**, **node:test** — config or `.mocharc`, or `test` script invoking `node --test`.

**Python** — read `pyproject.toml`/`setup.cfg`/`pytest.ini`/`tox.ini`, `requirements*.txt`:
- **pytest** — `pytest` dep, `[tool.pytest.ini_options]`, `tests/test_*.py`. → unit/integration; HTTP via `httpx`/`requests`/`TestClient`.
- **unittest** — `tests/` using `unittest.TestCase` and no pytest.

**Other** — **Go** (`*_test.go` + `go test`), **Ruby** (`rspec`, `spec/`), **Java/Kotlin** (`src/test/`, JUnit, `pom.xml`/`gradle`), **PHP** (`phpunit.xml`).

**HTTP collections** — `*.postman_collection.json`, `*.http`/`*.rest` files, Bruno/Hoppscotch — usable for API checks if the user prefers them.

If several runners coexist (common in fullstack repos: Playwright for E2E + Jest/pytest for unit), keep **each** and route items to the right one.

## 2. Classify the surface

- **Backend / API** — server routes, webhook handler, DB, server-side reconciliation exist → backend test items (step-03).
- **Frontend / SDK** — web pages, SDK init, headless per-method UI, or native (Android/iOS/Flutter/RN) exist → frontend/SDK items (step-04).
- **Fullstack** — both.

Cross-check the classification against jp-executor's `integration-summary.md` (routes, SDK init, native setup) and the architecture's surfaces — don't infer a surface the integration didn't build.

## 3. Conditional profile (keep it lean)

- **API-only** target (backend, or no browser-driving runner found): use the API-capable runner (`supertest`/`request`/`httpx`/`TestClient`) or inline curl/bash. Do **not** pull in a browser framework just to hit an endpoint.
- **UI + API** target (a browser runner exists and a web surface was built): use Playwright/Cypress for the transaction-drive E2E, and the same tool's request API (or the backend runner) for API assertions.

## 4. Mirror existing conventions when persisting

When a runner exists, write tests that look like the repo's own:
- Put files where the repo puts them (`tests/`, `e2e/`, `__tests__/`, `spec/`, co-located `*.test.ts`).
- Match naming (`*.spec.ts` vs `*.test.ts` vs `test_*.py`) and the assertion/fixture style already in use.
- Reuse existing helpers/fixtures/factories rather than inventing parallel ones.
- Wire into the existing test command (`npm test`, `pytest`, `npx playwright test`) so the suite runs in CI the same way.

## 5. Fallback: no runner detected

Run **inline** curl/bash (or the project's own ad-hoc runner). Ephemeral checks — assert HTTP status + documented response field, persist nothing into the repo. Say so explicitly in the report. Offer to scaffold a real suite only if the user asks; otherwise don't introduce a framework the repo doesn't use.

## 6. Credentials in tests

Always via exported env vars read from `.env`/secret store (`process.env.JUSPAY_*`, `os.environ[...]`). Never inline a key into a spec, a fixture, a Postman variable committed to the repo, or command output. Never echo a secret value.
