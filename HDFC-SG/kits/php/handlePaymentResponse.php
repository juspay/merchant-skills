<?php
use PaymentHandler\APIException;
require_once realpath("./PaymentHandler.php");
use PaymentHandler\PaymentHandler;

// block:start:order-status-function
function getOrder($params) {
   try {
        $paymentHandler = new PaymentHandler("resources/config.json");

        if ($params["status"] != "NEW" && $paymentHandler->validateHMAC_SHA256($params) === false) {
            // validateHMAC_SHA256 verifies that the callback was genuinely sent by SmartGateway.
            // It uses the RESPONSE_KEY from config.json to validate the HMAC-SHA256 signature.
            // If this check fails, the callback has been tampered with -- reject it immediately.
            // IMPORTANT: RESPONSE_KEY must be set in config.json or this will silently return false.
            // The "NEW" status is skipped because that state has no signature to verify.
            throw new APIException(-1, false, "Signature verification failed", "Signature verification failed");
        } else {
            $order = $paymentHandler->orderStatus($params["order_id"]);
            // orderStatus() makes a server-to-server call to SmartGateway to fetch the live order status.
            // NEVER trust the "status" field from the redirect POST params alone -- it can be tampered with.
            // Always call orderStatus() after HMAC verification to get the authoritative status.
            // Use the status from this response to update your DB and trigger fulfillment.
            return $order;
        }
   } catch (APIException $e ) {
        http_response_code(500);
        $error = json_encode(["message" => $e->getErrorMessage(), "error_code" => $e->getErrorCode(), "http_response_code" => $e->getHttpResponseCode()]);
        echo "<p> Payment server threw a non-2xx error. Error message: {$error} </p>";
        exit;
     } catch (Exception $e) {
        http_response_code(500);
        echo "<p> Unexpected error occurred, Error message:  {$e->getMessage()} </p>";
        exit;
    }

}
// block:end:order-status-function

function getStatusMessage($order) {
    $message = "Your order with order_id " . $order["order_id"] . " and amount " . $order["amount"] . " has the following status: ";
    $status = $order["status"];

    // Order status values returned by SmartGateway and the recommended DB action for each:
    switch ($status) {
        case "CHARGED":
            // Payment successful. Mark order as PAID in your DB and trigger fulfillment/delivery.
            $message = $message . "order payment done successfully";
            break;
        case "PENDING":
        case "PENDING_VBV":
            // Payment is in progress (PENDING_VBV = awaiting 3D Secure authentication).
            // Mark order as PENDING in your DB. Set up a webhook or polling to catch the final status.
            // Do NOT trigger fulfillment yet.
            $message = $message ."order payment pending";
            break;
        case "AUTHORIZATION_FAILED":
            // Payment was declined by the bank or gateway.
            // Mark order as FAILED in your DB. Allow the customer to retry with a different payment method.
            $message = $message ."order payment authorization failed";
            break;
        case "AUTHENTICATION_FAILED":
            // 3D Secure authentication failed (wrong OTP, timeout, etc.).
            // Mark order as FAILED in your DB. Allow the customer to retry.
            $message = $message . "order payment authentication failed";
            break;
        default:
            // Catch-all for any other status. Log it and investigate.
            $message = $message ."order status " . $status;
            break;
    }
    return $message;
}

// SmartGateway POSTs back to this return_url after the customer completes payment.
// It can also redirect via GET in some flows -- both are handled below.
// Params received: order_id, status, status_id, signature, signature_algorithm

// POST ROUTE
// block:start:construct-params
if (isset($_POST["order_id"])) {
       $params = $_POST;
// block:end:construct-params
       $order = getOrder($params);
       $message = getStatusMessage($order);
} else if (isset($_GET["order_id"])) { // GET ROUTE
   $params = $_GET;
   $order = getOrder($params);
   $message = getStatusMessage($order);
} else {
    http_response_code(400);
    echo "<p>Required Parameter Order Id is missing</p>";
    exit;
}
?>
<html>
<head>
    <title>Merchant payment status page</title>
    <style>
        .section { text-align: center; margin: 20px 0; }
        .section-title { font-size: 1.2em; color: blue; font-weight: bold; }
        table { margin: 0 auto; border-collapse: collapse; }
        td { border: 1px solid #ccc; padding: 4px 8px; }
    </style>
</head>
<body>
    <h1><?php echo $message ?></h1>

    <div class="section">
        <p class="section-title">Return url request body params</p>
        <table>
            <?php
                foreach ($params as $key => $value) {
                    echo "<tr><td>{$key}</td>";
                    $pvalue = "";
                    if ($value !== null) {
                        $pvalue = json_encode($value);
                    }
                    echo "<td>{$pvalue}</td></tr>";
                }
            ?>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Response received from order status payment server call</p>
        <table>
            <?php
                foreach ($order as $key => $value) {
                    echo "<tr><td>{$key}</td>";
                    $pvalue = "";
                    if ($value !== null) {
                        $pvalue = json_encode($value);
                    }
                    echo "<td>{$pvalue}</td></tr>";
                }
            ?>
        </table>
    </div>
</body>
</html>
