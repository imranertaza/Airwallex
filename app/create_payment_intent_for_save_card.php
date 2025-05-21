<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once("../vendor/autoload.php");

//use Imranertaza\Airwallex;
use Imranertaza\Airwallex\Airwallex;

// Instantiate the Airwallex class with your API key and secret
$airwallex = new Airwallex('UydtLpDuS7ivJMpkc8bxFA', '19e1e4928ab60c81d32c23a4831451a0bb4edb174b7dcb6b0198d81e260349112c30c0638814f3e807ca3a5c172b8422', 'test');

$consent_id = "cst_hkdmdxn6nh7jyhe9u9d";
$customer_id = "cus_hkdmfcpksh7jyhdy0ks";

// When the form is submitted, create a payment intent and confirm the payment.
// You will get success or failure message based on the payment status.
if(isset($_POST['submit'])) {
    $amount = $_POST['amount'];
    // Create a new payment from a customer saved card
    $paymentIntent = $airwallex->createPaymentIntentForSaveCard($customer_id, $amount);
    $confirmPaymentIntent = $airwallex->confirmPaymentIntent($paymentIntent->id, $consent_id, $customer_id);
    if ($confirmPaymentIntent->status != "SUCCEEDED") {
        echo "<p style='text-align: center'>Payment not successful</p>";
    } else {
        echo "<p style='text-align: center'>Payment successful </p>";
    }
}


// Get the existing card details from the payment consent of the customer.
$payment_method = ($airwallex->getPaymentConsents($consent_id)->payment_method->card) ?? null;
if(isset($payment_method)) {
    $existingCard = $payment_method->bin .'••••••••'. $payment_method->last4;
} else {
    $existingCard = 'No card is set.';
}
?>

<form action="" method="post" style="width: 60%; margin: auto;text-align: center;">
    <h1>Payment with saved card</h1>
    <p>Existing card: <?php echo $existingCard; ?></p>
    <input type="text" name="amount" placeholder="Enter amount">
    <input type="submit" name="submit" value="Submit">
</form>
