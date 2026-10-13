# SmartGateway — Merchant DB Schema Reference

This file tells the model what data to ensure is stored in the merchant's database at each stage of the integration. Use this when setting up the DB schema (Step 5c) and when writing the transaction creation, response handler, and refund endpoints.

---

## Orders / Transactions Table

### Save BEFORE calling the session API

If the redirect fails, the order record must already exist.

| Field                  | Type          | Source               | Notes                                                                                                 |
| ---------------------- | ------------- | -------------------- | ----------------------------------------------------------------------------------------------------- |
| `order_id`           | string        | Merchant-generated   | Max 21 chars, alphanumeric only, non-sequential. Must be unique.                                      |
| `amount`             | DECIMAL(10,2) | Computed server-side | Store as DECIMAL in DB. Sent to API as string `"499.00"` — convert in code. Never from client POST. |
| `currency`           | string        | Config / default     | Default `INR`                                                                                        |
| `customer_id`        | string        | Merchant DB          | Internal user ID. Empty string for guest checkout.                                                    |
| `customer_email`     | string        | Checkout form        | Optional but recommended — reduces payment page drop-off                                             |
| `customer_phone`     | string        | Checkout form        | 10 digits, no country code                                                                            |
| `payment_status`     | string        | Set by code          | Default `NEW` on insert                                                                              |
| `return_url`         | string        | Config               | The callback URL sent to SmartGateway                                                                 |
| `udf1`–`udf5`     | string        | Merchant data        | No special characters. Business metadata: store, promo, channel, etc.                                 |
| `udf6`–`udf10`    | string        | Merchant data        | Special characters allowed. JSON, URLs, rich data.                                                    |
| `billing_address_*`  | string        | Checkout form        | All billing fields if collected at checkout                                                           |
| `shipping_address_*` | string        | Checkout form        | Only if shipping to a separate address                                                                |
| `payment_url`        | string        | Session API response | `payment_links.web` — store in case redirect needs retry                                           |

---

### Update AFTER receiving payment response (from server-side orderStatus call)

Never update from redirect params alone — always from the `orderStatus` API response.

| Field                    | Type          | Source                                  | Notes                                                |
| ------------------------ | ------------- | --------------------------------------- | ---------------------------------------------------- |
| `payment_status`       | string        | `orderStatus.status`                  | Final authoritative status — see status table below |
| `status_id`            | integer       | `orderStatus.status_id`               | Numeric code — see status table below               |
| `txn_id`               | string        | `orderStatus.txn_id`                  | SmartGateway transaction ID                          |
| `txn_uuid`             | string        | `orderStatus.txn_uuid`                | Secondary transaction reference                      |
| `gateway_id`           | integer       | `orderStatus.gateway_id`              | Which backend gateway processed the payment          |
| `gateway_reference_id` | string        | `orderStatus.gateway_reference_id`    | Cross-system reconciliation                          |
| `payment_method`       | string        | `orderStatus.payment_method`          | e.g. `VISA`, `NB_HDFC`, `GOOGLEPAY`             |
| `payment_method_type`  | string        | `orderStatus.payment_method_type`     | `CARD`, `NB`, `UPI`, `WALLET`                |
| `auth_type`            | string        | `orderStatus.auth_type`               | e.g. `THREE_DS`                                     |
| `bank_error_code`      | string        | `orderStatus.bank_error_code`         | Present on failure — store for support/diagnosis    |
| `bank_error_message`   | string        | `orderStatus.bank_error_message`      | Present on failure                                   |
| `resp_code`            | string        | `orderStatus.resp_code`               | Gateway response code                                |
| `rrn`                  | string        | `payment_gateway_response.rrn`        | Bank-side reconciliation reference                   |
| `epg_txn_id`           | string        | `payment_gateway_response.epg_txn_id` | PG transaction ID                                    |
| `amount_refunded`                  | DECIMAL(10,2) | `orderStatus.amount_refunded`         | Running refund total — update on each refund        |
| `maximum_eligible_refund_amount`   | DECIMAL(10,2) | Computed                              | `amount - amount_refunded` — compute and store on every update. Use this to validate refund requests before calling the refund API. |
| `refunded`                         | boolean       | `orderStatus.refunded`                | Whether any refund exists on this order              |
| `sg_response`          | JSON          | Full `orderStatus` response            | Store full response blob for audit trail             |

---

## Refunds Table

### Save BEFORE calling the refund API

The `unique_request_id` must exist in DB before the API call. On timeout, retry with the same ID — never generate a new one.

| Field                 | Type          | Source             | Notes                                                  |
| --------------------- | ------------- | ------------------ | ------------------------------------------------------ |
| `order_id`          | string        | FK to orders table | Links refund to original transaction                   |
| `unique_request_id` | string        | Merchant-generated | Max 21 chars. Save before calling API. Reuse on retry. |
| `refund_amount`     | DECIMAL(10,2) | Request            | Amount to refund — must be ≤ unrefunded order amount |
| `refund_status`     | string        | Set by code        | Default `INITIATED` before API call                   |

### Update AFTER refund API response

| Field                    | Type    | Source                        | Notes                                                 |
| ------------------------ | ------- | ----------------------------- | ----------------------------------------------------- |
| `refund_id`            | string  | `refunds[].id`              | SmartGateway internal refund identifier               |
| `refund_ref`           | string  | `refunds[].ref`             | Gateway's refund reference — for bank reconciliation |
| `refund_status`        | string  | `refunds[].status`          | `SUCCESS`, `PENDING`, `FAILED`                  |
| `refund_type`          | string  | `refunds[].refund_type`     | `STANDARD` or `INSTANT`                           |
| `refund_source`        | string  | `refunds[].refund_source`   | Which gateway processed the refund                    |
| `sent_to_gateway`      | boolean | `refunds[].sent_to_gateway` | False initially — queued, processes within ~15 min   |
| `pg_processed_at`      | string  | `refunds[].pg_processed_at` | Gateway processing timestamp — for settlement        |
| `refund_error_code`    | string  | `refunds[].error_code`      | Present on failure                                    |
| `refund_error_message` | string  | `refunds[].error_message`   | Present on failure                                    |
| `created_at`           | string  | `refunds[].created`         | Refund creation timestamp                             |

---

## Transaction Status Reference

| Status                    | Status ID | Terminal?    | Merchant Action                                        |
| ------------------------- | --------- | ------------ | ------------------------------------------------------ |
| `NEW`                   | 10        | No           | Order created, payment not started. No HMAC to verify. |
| `STARTED`               | 20        | No           | Transaction pending — no gateway assigned yet         |
| `CHARGED`               | 21        | Yes          | Payment successful — mark paid, trigger fulfillment   |
| `JUSPAY_DECLINED`       | 22        | Yes          | Failed — allow retry                                  |
| `PENDING_VBV`           | 23        | **No** | 3DS auth in progress — poll until terminal status     |
| `AUTHORIZED`            | 25        | No           | Pre-auth only — awaiting capture                      |
| `AUTHENTICATION_FAILED` | 26        | Yes          | User did not complete auth — allow retry              |
| `AUTHORIZATION_FAILED`  | 27        | Yes          | Bank declined — allow retry                           |
| `AUTHORIZING`           | 28        | **No** | Bank response pending — poll until terminal status    |
| `VOIDED`                | 31        | Yes          | Pre-auth voided                                        |
| `AUTO_REFUNDED`         | 36        | Yes          | Automatically refunded                                 |

**Non-terminal statuses (23, 28):** Do not update order to failed. Poll `orderStatus` until a terminal status is returned.

---

## Notes for the Model

- Always save order record **before** calling `orderSession()` — if redirect fails, the order still exists.
- Always save refund `unique_request_id` **before** calling `refund()` — if call times out, retry with same ID.
- Always get final status from `orderStatus` API — never trust redirect URL params.
- Store the full `sg_response` JSON on the order — it is needed for disputes, chargebacks, and reconciliation.
- All amount fields (`amount`, `amount_refunded`, `refund_amount`) are stored as `DECIMAL(10,2)` in the DB. When calling the session API, convert to string `"499.00"` in code. API responses return amounts as Double — convert back to DECIMAL before writing to DB.
