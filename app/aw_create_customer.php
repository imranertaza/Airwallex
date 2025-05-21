<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once("../vendor/autoload.php");

//Called LIBRARY
use Imranertaza\Airwallex\Airwallex;


$airwallex = new Airwallex('UydtLpDuS7ivJMpkc8bxFA', '19e1e4928ab60c81d32c23a4831451a0bb4edb174b7dcb6b0198d81e260349112c30c0638814f3e807ca3a5c172b8422', 'test');

//create customer for airwallex
$airwallex->customer_name = "Syed Imran Ertaza";
$airwallex->customer_email = "syedimranertaza@gmail.com";
$airwallex->customer_phone = "019243293225";

$aw_customer = $airwallex->createCustomer();
$client_secret = $airwallex->getClientSecret($aw_customer->id);
echo json_encode([
    "aw_customer" => $aw_customer->id,
    "client_secret" => $client_secret,
]);
