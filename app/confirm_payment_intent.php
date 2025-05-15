<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once("../vendor/autoload.php");

use Imranertaza\Airwallex\Airwallex;

$airwallex = new Airwallex('UydtLpDuS7ivJMpkc8bxFA', '19e1e4928ab60c81d32c23a4831451a0bb4edb174b7dcb6b0198d81e260349112c30c0638814f3e807ca3a5c172b8422', 'test');


// This is how to confirm a payment intent
$confirmPaymentIntent = $airwallex->confirmPaymentIntent('int_hkdmrkbb4h7d1w6gogz', "cst_hkdmq8q2zh77wclsftr", "cus_hkdm7mv4hgxoxrkii3w");
print "<pre>";
var_dump($confirmPaymentIntent);
//exit();