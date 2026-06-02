# Step 1: Authenticate & Codebase Scan

## Rules

- This step has NO user questions — it is fully automated.
- Do NOT read `.env` values — only note the presence of relevant files.
- Do NOT proceed to step-02 if MCP authentication fails.

## Your Task

Authenticate with `juspay-mcp` and scan the codebase for signals that inform later steps.

## Sequence

### 1. Authenticate

Attempt `juspay-mcp` authentication using whatever mechanism the agent environment exposes.

**If authentication succeeds** → continue.

**If authentication fails or the tool is unavailable** → stop and tell the user:

> "The `juspay-mcp` server must be authenticated before planning can proceed. Please authenticate it in your agent environment and re-run this skill."

Do NOT proceed to step-02 until authentication is confirmed.

### 2. Scan Codebase

Scan the project working directory for signals. Record findings — they will be used in steps 02–06.

**Backend language signals:**

| Signal | Language |
|---|---|
| `requirements.txt`, `pyproject.toml`, majority `*.py` | `python` |
| `tsconfig.json` + `*.ts` backend files | `typescript` |
| `package.json` with express/fastify/koa/hapi/nest, majority `*.js` | `javascript` |
| `go.mod` or majority `*.go` | `go` |
| `pom.xml`, `build.gradle` (non-Android), majority `*.java` | `java` |
| `Gemfile` or majority `*.rb` | `ruby` |
| `composer.json` or majority `*.php` | `php` |
| `*.csproj` or majority `*.cs` | `csharp` |
| `Cargo.toml` or majority `*.rs` | `rust` |

**Platform signals:**

| Signal | Platform |
|---|---|
| `pubspec.yaml` | `flutter` |
| `package.json` + `react-native` dep | `react-native` |
| `package.json` + `@capacitor/core` | `capacitor` |
| `config.xml` or `cordova` dep | `cordova` |
| `build.gradle` or `AndroidManifest.xml` (no Flutter/RN/Cordova) | `android` |
| `*.xcodeproj` or `Podfile` (no Flutter/RN) | `ios` |
| `package.json` + web framework (`react`, `vue`, `next`, `nuxt`, etc.) | `web` |

**Payment signals:**

- Existing Juspay files: glob for `*juspay*`, `*payment*`, `*checkout*`
- `.env` or `.env.local` presence (do NOT read values, only note existence)
- Existing order/payment DB schemas: `*.prisma`, `*.sql`, migration files containing `order`, `payment`, `transaction`

**Backend base URL signals** (note for step-05):

| Signal | Derived value |
|---|---|
| `PORT=XXXX` in `backend/.env` or `.env` | `http://localhost:XXXX` |
| `EXPO_PUBLIC_API_URL` / `VITE_API_URL` / `NEXT_PUBLIC_API_URL` in frontend `.env` | that value |
| `--port XXXX` in `package.json` dev script | `http://localhost:XXXX` |

Store all findings as:
- `$DETECTED_LANG`
- `$DETECTED_PLATFORM` (or `unknown`)
- `$HAS_EXISTING_PAYMENT` (true/false)
- `$HAS_PERSISTENCE_SCHEMA` (true/false)
- `$BACKEND_BASE_URL` (or `unknown`)
- `$ENV_FILE_EXISTS` (true/false)

### 3. Initialize Plan File

Create `{project-root}/juspay-plan.md` with the frontmatter shell:

```markdown
---
planVersion: "1.0"
createdAt: "{{today's date}}"
product: ""
productType: ""
platform: ""
merchantId: ""
clientId: ""
backendLang: "{{$DETECTED_LANG or unknown}}"
backendBaseUrl: "{{$BACKEND_BASE_URL or unknown}}"
hasPersistenceSchema: {{$HAS_PERSISTENCE_SCHEMA}}
webhookUrl: ""
returnUrl: ""
apiKeySource: ""
hasWebhooks: false
---

# Juspay Integration Plan

_This document is written by jp-planner and read by /jp-executor._
```

## Next Step

Load `./step-02-merchant-product.md`.
