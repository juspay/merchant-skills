# Juspay for Claude Code

One command to give Claude Code (and any MCP-aware agent) full Juspay context: signed-in merchant data, live documentation, and a guided `/integrate` wizard for adding Juspay payments to any project.

## Install

```bash
npm install -g https://github.com/sahyll/juspay-skills/releases/download/cli-v0.2.1/juspay-claude-code-skill-0.2.1.tgz
juspay-claude
```

That's it. First run opens a browser for sign-in, registers two MCP servers, installs the skills, and launches Claude. Subsequent runs just launch Claude with everything wired up.

> Requires [Claude Code](https://claude.ai/code) (`npm install -g @anthropic-ai/claude-code`) and Node.js 18+.

## What gets installed

| Component | Path | Purpose |
|---|---|---|
| **CLI** | `juspay-claude` on PATH | Sign-in, setup, launch wrapper |
| **OAuth tokens** | `~/.config/juspay/oauth.json` (mode 0600) | 90-day session, reused across runs |
| **Dashboard MCP** | user-scope in Claude Code config | Live merchant data: orders, gateways, settings, API keys |
| **Docs MCP** | user-scope in Claude Code config | Live Juspay documentation lookup |
| **Integrate skill** | `~/.claude/skills/integrate/` | The `/integrate` wizard + per-product reference files |

## Commands

```
juspay-claude              Launch Claude with Juspay context
juspay-claude init         Sign in, register MCPs, install skills (idempotent)
juspay-claude init --force Force re-authentication
juspay-claude update       Pull latest skills from this repo
juspay-claude list         List installed Juspay skills
juspay-claude uninstall    Remove MCPs, skills, config, OAuth tokens
juspay-claude help         Show all commands
```

## How `/integrate` works

Open Claude in a project, run `/integrate` (or just describe what you want):

```
/integrate
Help me integrate Juspay payments into this app
Set up Hyper Checkout for my React Native app
I need to add payouts to my backend
```

The skill runs a 7-phase, doc-driven workflow:

| Phase | What happens |
|-------|-------------|
| **0** | Reads your merchant account, infers the right product, confirms with you |
| **1** | Calls `explore_product` to get the full doc map for the chosen product |
| **2** | Detects your platform from the codebase (pubspec.yaml, AndroidManifest.xml, package.json, etc.) |
| **3** | Fetches each doc page in order — prerequisites first, then integration steps |
| **4** | Auto-resolves merchant ID, webhook URL, return URL; provisions an API key; asks only for what it can't derive |
| **5** | Generates auth setup, core integration, webhook handler, status verification, DB schema, error handling — using only doc-sourced method names |
| **6** | Produces a per-product integration checklist + error code reference |
| **7** | Starts your dev server, sends real HTTP requests, reports a pass/fail table |

### Flags

```
/integrate --product hyper-checkout --platform flutter
```

| Flag | Effect |
|------|--------|
| `--product <id>` | Skip recommendation, integrate a specific product |
| `--platform <id>` | Hint the platform — agent still verifies against your codebase |

## Architecture

```
skills/
└── integrate/
    ├── SKILL.md        # The /integrate decision engine — contains no product
    │                   # knowledge, only the workflow and decision rules.
    └── products/       # One file per product: id, category, platforms,
        ├── hyper-checkout.md      # intent signals. Read on demand by the
        ├── ec-headless.md         # agent when the SKILL.md routes it there.
        ├── ec-api.md
        ├── hyper-credit.md
        ├── lotuspay.md
        ├── payout.md
        ├── jusbiz.md
        ├── juspay-billing.md
        ├── upi-plugin-sdk.md
        └── upi-tpap-sdk.md
```

**The split is deliberate:** `SKILL.md` stays stable; product details evolve in `products/`. Exhaustive endpoint schemas live in the docs MCP — fetched on demand, never copied here.

## Test it

A demo merchant app lives in [`test_app/`](./test_app/). Clone, install the CLI, run `juspay-claude` in any project, and try `/integrate`.

You don't need this repo to use the CLI — the install command above is sufficient. Clone only if you want to inspect the skills source or contribute new product entries.

## Adding a new product

Product entries live in `skills/integrate/products/`. Each file is markdown with a frontmatter block:

```yaml
---
id: <kebab-case-id>
category: CHECKOUT | PAYOUTS | BILLING | UPI SOLUTIONS
platforms: [android, ios, web, flutter, react-native, ...]
---

## What it is
## When to recommend
## Key concepts
## Intent signals
```

To add a product, drop a new file in `products/` — no changes to `SKILL.md` needed. The product must be reachable via the docs MCP's `explore_product` tool.

## Source

- **CLI source** — private repo: `ssh://git@ssh.bitbucket.juspay.net/~madhan.k_juspay.in/juspay-cli.git`
- **Skills + releases** — this repo
- **MCP servers** — [juspay/juspay-mcp](https://github.com/juspay/juspay-mcp)

## Releases

CLI tarballs are attached to GitHub Releases tagged `cli-vX.Y.Z`. The latest:

- **v0.2.1** — Fix product file install + auto-sweep legacy skill dirs
- **v0.2.0** — OAuth + dual MCP registration

## License

See [LICENSE](./LICENSE).
