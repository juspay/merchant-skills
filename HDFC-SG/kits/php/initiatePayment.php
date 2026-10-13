<?php
use PaymentHandler\APIException;
require_once realpath("./PaymentHandler.php");
use PaymentHandler\PaymentHandler;

$paymentHandler = new PaymentHandler("resources/config.json");

$orderId = "php_sdk_" . uniqid();
$customerId = "php_sdk_customer" . uniqid();

// block:start:session-function
$params = [

    // REQUIRED FIELDS

    "amount" => "10.00",
    // [REQUIRED] Payment amount as a string with exactly 2 decimal places.
    // Valid: "10.00", "1499.50" | Invalid: "10", 10, "10.5", "10.1234"
    // Always compute server-side from your DB. NEVER take from a client form POST.
    // More than 2 decimal places will cause API rejection.

    "order_id" => $orderId,
    // [REQUIRED] Your unique order identifier sent to SmartGateway.
    // Rules: Alphanumeric only, max 21 chars, no special characters, must be non-sequential.
    // Save this in your DB BEFORE calling /session. If redirect fails, you still have the order.

    "customer_id" => $customerId,
    // [REQUIRED] Your internal customer/user identifier.
    // For registered users: use your DB's user_id or UUID.
    // For guest checkout: pass an empty string "".
    // Used by SmartGateway for stored payment methods, EMI, and subscriptions.

    "action" => "paymentPage",
    // [REQUIRED] Fixed value. Tells SmartGateway to show the hosted payment page UI.

    "return_url" => "http://localhost:5000/handlePaymentResponse.php",
    // [REQUIRED] URL where SmartGateway POSTs back after payment (success or failure).
    // DEV:  "http://localhost:5000/handlePaymentResponse.php"
    // PROD: Must be a publicly accessible HTTPS URL. Localhost will NOT work in production.
    // No query parameters or IP addresses allowed in this URL.


    // CUSTOMER CONTACT (CONDITIONAL)

    // "customer_email" => "customer@example.com",
    // [CONDITIONAL] Required if the downstream payment gateway demands it.
    // Pre-fills email on the payment page. Reduces customer drop-off.
    // Recommended: always pass this if you collect email at checkout.

    // "customer_phone" => "9876543210",
    // [CONDITIONAL] Required if the downstream payment gateway demands it.
    // 10-digit mobile number, no country code, no spaces, no dashes.
    // Pre-fills mobile on payment page for UPI / OTP flows.


    // OPTIONAL BASIC FIELDS

    // "currency" => "INR",
    // [OPTIONAL] ISO 4217 currency code. Default: "INR".
    // Other accepted values: "USD", "EUR", "GBP".
    // Most merchants leave this as INR. Only change for international payments.

    // "description" => "Order for 2x Blue Shirt, size L",
    // [OPTIONAL] Short order description. Max 255 chars.
    // Shown on the payment page and forwarded to backend gateways where supported.

    // "product_id" => "PROD_12345",
    // [OPTIONAL] Your product identifier. Useful for single-product or impulse purchase flows.

    // "gateway_id" => 1,
    // [OPTIONAL] Numeric ID to route this order to a specific backend payment gateway.
    // Leave unset to let SmartGateway apply its routing rules automatically.

    // "options.get_client_auth_token" => "true",
    // [OPTIONAL] Set to "true" to get a client_auth_token in the response.
    // Only needed if you are using the Hyper SDK (mobile SDK) integration.
    // Response will then include: juspay.client_auth_token and juspay.client_auth_token_expiry.

    // "order_type" => "TPV_PAYMENT",
    // [CONDITIONAL] Only for TPV (Third Party Validation) mandate flows.
    // Leave unset for standard payment page flows.


    // UDFs: MERCHANT'S CUSTOM BUSINESS TRACKING DATA
    //
    // UDFs (User Defined Fields) let you attach your own business metadata to each payment.
    // They appear in SmartGateway transaction reports, settlement files, and webhooks.
    // All UDFs are optional strings, max 255 chars each.
    // UDF1-UDF5: No special characters allowed.
    // UDF6-UDF10: Special characters supported (JSON, URLs, rich text etc.)
    //
    // MERCHANT DECISION: What data do you need alongside each payment for reconciliation?
    // Save all UDF values in your DB against order_id BEFORE calling /session.

    // "udf1" => "",
    // [OPTIONAL] Suggested: Store / Branch / Location ID.
    // Example values: "STORE_DELHI_01", "BRANCH_MUM_ANDHERI", "WAREHOUSE_BLR"
    // Use when: Multi-location merchants who need to split reports by store.

    // "udf2" => "",
    // [OPTIONAL] Suggested: Internal Order / Invoice / Cart Reference from your DB.
    // Example values: "INV-2026-98123", "CART-8821", "PO-2026-441"
    // Use when: Your internal order ref differs from what you send as order_id to SG.

    // "udf3" => "",
    // [OPTIONAL] Suggested: Customer Loyalty Tier, Segment, or Account Type.
    // Example values: "GOLD_MEMBER", "NEW_USER", "PREMIUM", "B2B_CLIENT"
    // Use when: You run loyalty programs or need segment-wise payment analytics.

    // "udf4" => "",
    // [OPTIONAL] Suggested: Promo Code / Coupon / Offer Applied to this order.
    // Example values: "FESTIVE50", "WELCOME10", "NONE"
    // Use when: You want to track which promotions drive conversions in SG reports.

    // "udf5" => "",
    // [OPTIONAL] Suggested: Sales Channel / Platform / Traffic Source.
    // Example values: "WEB_PORTAL", "IOS_APP", "ANDROID_APP", "POS", "WHATSAPP_BOT"
    // Use when: You sell across multiple channels and need channel-wise revenue reporting.

    // "udf6" => "",
    // [OPTIONAL] UDF6-UDF10 support special characters. Suitable for JSON, URLs, rich text.
    // Example: json_encode(["warehouse" => "BLR", "priority" => "express"])

    // "udf7"  => "", // [OPTIONAL] Free use. Define per your business need.
    // "udf8"  => "", // [OPTIONAL] Free use. Define per your business need.
    // "udf9"  => "", // [OPTIONAL] Free use. Define per your business need.
    // "udf10" => "", // [OPTIONAL] Free use. Define per your business need.


    // BILLING ADDRESS
    //
    // Collect from customer at checkout. Pre-fills the payment page and satisfies
    // compliance requirements for certain card networks.
    // All fields are optional strings unless your downstream gateway requires them.
    // DB Decision: Store billing address in your orders or customers table.

    // "billing_address_first_name"       => "", // [OPTIONAL] Customer's first name. Max 50 chars.
    // "billing_address_last_name"        => "", // [OPTIONAL] Customer's last name. Max 50 chars.
    // "billing_address_line1"            => "", // [OPTIONAL] Flat/building number and street. Max 100 chars. Example: "Flat 4B, Horizon Tower, MG Road"
    // "billing_address_line2"            => "", // [OPTIONAL] Locality / area. Example: "Near Inorbit Mall, Malad West"
    // "billing_address_line3"            => "", // [OPTIONAL] Additional line. Fill only if collected.
    // "billing_address_city"             => "", // [OPTIONAL] City name. Example: "Mumbai"
    // "billing_address_state"            => "", // [OPTIONAL] State name. Example: "Maharashtra"
    // "billing_address_country"          => "", // [OPTIONAL] Country name. Example: "India"
    // "billing_address_postal_code"      => "", // [OPTIONAL] 6-digit PIN code. Example: "400064"
    // "billing_address_phone"            => "", // [OPTIONAL] 10-digit mobile, no country code. Example: "9876543210"
    // "billing_address_country_code_iso" => "IND", // [OPTIONAL] ISO 3166-1 alpha-3. Default: "IND". Use "IND" not "IN".


    // SHIPPING ADDRESS
    //
    // Only needed if you ship physical goods to a delivery address.
    // If billing and shipping are the same, mirror the billing fields above.
    // DB Decision: Store shipping address in your orders table (separate from billing).

    // "shipping_address_first_name"       => "", // [OPTIONAL] Recipient's first name at delivery address.
    // "shipping_address_last_name"        => "", // [OPTIONAL] Recipient's last name.
    // "shipping_address_line1"            => "", // [OPTIONAL] Delivery address: door/flat number and street. Example: "Plot 12, Sector 5, Koramangala"
    // "shipping_address_line2"            => "", // [OPTIONAL] Locality / landmark for delivery.
    // "shipping_address_line3"            => "", // [OPTIONAL] Additional delivery detail. Use only if collected.
    // "shipping_address_city"             => "", // [OPTIONAL] Delivery city. Example: "Bengaluru"
    // "shipping_address_state"            => "", // [OPTIONAL] Delivery state. Example: "Karnataka"
    // "shipping_address_country"          => "", // [OPTIONAL] Country name. Example: "India"
    // "shipping_address_postal_code"      => "", // [OPTIONAL] Delivery PIN code. Example: "560034"
    // "shipping_address_phone"            => "", // [OPTIONAL] Delivery contact number. Can differ from billing phone.
    // "shipping_address_country_code_iso" => "IND", // [OPTIONAL] ISO 3166-1 alpha-3. Default: "IND". "IND" not "IN".


    // METADATA (ADVANCED / SPECIAL USE CASES)

    // "metadata.JUSPAY:gateway_reference_id" => "",
    // [OPTIONAL] Only when a gateway reference ID use case applies.
    // Contact SmartGateway support before using this field.

    // "metadata.auto_refund_post_success" => "true",
    // [CONDITIONAL] For mandate transactions only.
    // Refunds the initial auth amount automatically after mandate registration succeeds.
    // Applicable ONLY during the mandate registration phase, not for regular payments.

];

try {
    $session = $paymentHandler->orderSession($params);
    // block:end:session-function
    $redirect = $session["payment_links"]["web"];
    header("Location: {$redirect}");
    exit;

} catch (APIException $e) {
    http_response_code(500);
    $error = json_encode(["message" => $e->getErrorMessage(), "error_code" => $e->getErrorCode(), "http_response_code" => $e->getHttpResponseCode()]);
    echo "<p> Payment server threw a non-2xx error. Error message: {$error} </p>";
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo " <p> Unexpected error occurred, Error message:  {$e->getMessage()} </p>";
    exit;
}
?>
