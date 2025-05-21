<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once("../vendor/autoload.php");

use Imranertaza\Airwallex\Airwallex;

$airwallex = new Airwallex('UydtLpDuS7ivJMpkc8bxFA', '19e1e4928ab60c81d32c23a4831451a0bb4edb174b7dcb6b0198d81e260349112c30c0638814f3e807ca3a5c172b8422', 'test');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Save Card in Airwallex</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://checkout.airwallex.com/assets/elements.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/constant.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/product_search_script.js"></script>
    <script src="../assets/js/airwallex.js"></script>
    <link rel="stylesheet" href="../assets/css/airwallex.css">
    <style>
        #cardNumber,
        #expiryDate,
        #securityCode {
            border: 1px solid #ccc;
            padding: 10px 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        #cardNumber {
            border: 1px solid #ccc;
            padding: 10px 10px;
            border-radius: 4px;
        }

        .container {
            width: 60%;
            margin: auto;
        }

        #save {
            padding: 15px 48px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Save Card in Airwallex</h1>
        <div id="messageElement"></div>
        <form id="payment-form" method="POST">
            <div id="cardNumber"></div>
            <div id="expiryDate"></div>
            <div id="securityCode"></div>
            <button type="submit" id="save">Save Card</button>
        </form>
    </div>

    <script>
        Airwallex.init({
            env: "demo",
            origin: window.location.origin,
            fonts: [{
                src: "https://checkout.airwallex.com/fonts/CircularXXWeb/CircularXXWeb-Regular.woff2",
                family: "AxLLCircular",
                weight: 400,
            }, ],
        });

        const cardNumberElement = Airwallex.createElement('cardNumber');
        const expiry = Airwallex.createElement('expiry');
        const cvc = Airwallex.createElement('cvc');

        const domElement = cardNumberElement.mount('cardNumber');
        expiry.mount('expiryDate');
        cvc.mount('securityCode');


        const form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Airwallex.confirmPaymentIntent({
                paymentMethod: {
                    card: domElement,
                },
            }).then(function(result) {
                if (result.error) {
                    // Show error in payment form
                    console.error(result.error);
                } else {
                    // Payment succeeded
                    console.log('Payment succeeded:', result);
                }
            });
        });

        const save = document.getElementById("save");
        const messageElement = document.getElementById("messageElement");

        save?.addEventListener('click', async (element) => {
            // const planName = document.getElementById("plan_name").value;
            // const package = document.getElementById('package_name').value;
            const res = await fetch(`http://localhost:8080/aw_create_customer.php`);
            const resData = await res.json();

            //creact payment consent
            Airwallex.createPaymentConsent({
                customer_id: resData.aw_customer, // customer id
                client_secret: resData.client_secret, // Customer client secret
                currency: 'USD',
                element: cardNumberElement,
                next_triggered_by: 'merchant',
                merchant_trigger_reason: 'scheduled',
            }).then((response) => {
                if (response.payment_consent_id) {
                    // save consent id and customer id in DB
                    // (async function() {
                    //     const res = await fetch(`${baseUrl}api_data/subscribe.php`, {
                    //         method: 'POST',
                    //         headers: {
                    //             'Content-Type': 'application/x-www-form-urlencoded',
                    //         },
                    //         body: JSON.stringify({
                    //             consent_ID: response.payment_consent_id,
                    //             customer_id: resData.aw_customer,
                    //             package_id: package,
                    //             plan: planName
                    //         })
                    //     });
                    //     const output = await res.json();
                    //
                    //     if (output.result) {
                    //         // location.reload();
                    //         window.location.href = `${baseUrl}dashboard/checkout/checkout.php`;
                    //     }
                    // })();
                    messageElement.innerHTML = "Card saved successfully!<br/> Your consent ID is: <b>" + response.payment_consent_id + "</b><br/> customer ID is:<b>" + resData.aw_customer + "</b>";
                }
            }).catch(error => {
                messageElement.innerHTML = error.message;
                messageElement.style.marginBottom = "20px";
                messageElement.style.color = "#c0392b";
            });

        })
    </script>
</body>
</html>