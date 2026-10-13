<?php
use PaymentHandler\APIException;
require_once realpath("./PaymentHandler.php");
use PaymentHandler\PaymentHandler;

$orderId = $_POST["order_id"];
// The original order_id from the payment session you want to refund.
// Must match the order_id used when the payment was created in initiatePayment.php.
// A refund is always tied to the original order -- do not generate a new order_id here.

$amount = $_POST["amount"];
// Amount to refund. Must be less than or equal to the amount of the order that is not yet refunded.
// Partial refunds are supported -- you can refund a portion of the original amount.
// If this parameter is not passed, the order is refunded entirely.
// Note: A maximum of 25 refunds are allowed per order by default.

$refundId = $_POST["unique_request_id"];
// Unique ID for this refund request. Must be fewer than 21 characters.
// Cannot be reused for two different refund requests -- each new refund needs its own unique ID.
// Must not equal the txn_id received in the orderStatus API response.
// If the API call times out or fails, retry with the SAME unique_request_id to avoid a duplicate refund.

$paymentHandler = new PaymentHandler("resources/config.json");
try {
    // block:start:refund-function
    $refund = $paymentHandler->refund(["order_id" => $orderId, "amount" => $amount, "unique_request_id" => $refundId]);
    // block:end:refund-function
}  catch (APIException $e ) {
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
<html>
<head>
    <title>Merchant Refund</title>
</head>
<body>
    <h1>Refund status:- <?php $refund["status"]?></h1>
    <p>Here is the stringified map response:- </p>
    <p><?php echo json_encode($refund) ?></p>
    <center>
        <font size="4" color="blue"><b>Response received from order status payment server call</b></font>
        <table border="1">
            <?php
                foreach ($refund as $key => $value) {
                    echo "<tr><td>{$key}</td>";
                    $pvalue = "";
                    if ($value !== null) {
                        $pvalue = json_encode($value);
                    }
                    echo "<td>{$pvalue}</td></tr>";
                }
            ?>
        </table>
    </center>
</body>

</html>
