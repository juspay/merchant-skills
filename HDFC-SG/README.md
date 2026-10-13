# HDFC SmartGateway

A standalone skill for integrating **HDFC SmartGateway** payments into a merchant's application. Unlike the `jp-*` pipeline, it needs **no MCP servers** — the integration kit in this folder is its source of truth.

| Path | What it is |
|------|------------|
| [`hdfc-sg-integration/`](./hdfc-sg-integration) | The skill — scans the codebase, collects the merchant's business answers, and writes the integration (session, response handling, refunds). |
| [`kits/php/`](./kits/php) | The PHP integration kit the skill reads at each step (`PaymentHandler.php`, `initiatePayment.php`, `handlePaymentResponse.php`, `initiateRefund.php`, `resources/config.json`). |

## Install

```bash
npx juspay hdfc-sg agent-setup
```

Or directly with the [`skills`](https://github.com/vercel-labs/skills) CLI:

```bash
npx skills add juspay/merchant-skills/HDFC-SG/hdfc-sg-integration
```

Then ask your agent to integrate HDFC SmartGateway.
