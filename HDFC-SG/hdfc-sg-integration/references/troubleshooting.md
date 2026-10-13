# SmartGateway Integration — Troubleshooting

Check this file when an error occurs after the integration is complete.

---

## Config & Setup

### `config.json` Not Found

**Symptom:** `PaymentHandler` throws a file-not-found or JSON parse error on initialisation.

**Fix:** The PHP SDK has the path `resources/config.json` hardcoded. The file must sit at exactly that path relative to where `PaymentHandler.php` runs — not the project root.

---

### Wrong BASE_URL

**Symptom:** API calls fail or return unexpected errors.

**Fix:** Check `BASE_URL` in `config.json`:

- UAT / sandbox: `https://smartgateway.hdfcuat.bank.in`
- Production: `https://smartgateway.hdfc.bank.in`

Credentials are environment-specific — a UAT key will not work against the production BASE_URL.

---

### RESPONSE_KEY

**Symptom:** HMAC verification always returns false even though `config.json` is present.

**Fix:** Check that `RESPONSE_KEY` is present in `config.json`. It is a required field — if missing, HMAC verification will always return false even if all other credentials are correct. Add it from the SmartGateway Dashboard → Settings → Security → API Keys.

---

## SDK

### Kit Version Mismatch

**Symptom:** Terminal throws errors like `Call to undefined method`, `Class not found`, or a method exists in the code but fails at runtime — pointing to `PaymentHandler.php`.

**Fix:** Read `PaymentHandler.php` from the kit GitHub now. Replace the merchant's existing `PaymentHandler.php` with it.
