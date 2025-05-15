<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once("../vendor/autoload.php");
use Imranertaza\Airwallex\Airwallex;

$airwallex = new Airwallex('UydtLpDuS7ivJMpkc8bxFA', '19e1e4928ab60c81d32c23a4831451a0bb4edb174b7dcb6b0198d81e260349112c30c0638814f3e807ca3a5c172b8422', 'test');


// This is how to create customer
$airwallex->customer_name = 'Syed Imran Ertaza';
$airwallex->customer_email = 'imranertaza12@gmail.com';
$airwallex->customer_phone = '01924329315';
$createCustomer = $airwallex->createCustomer();
print "<pre>";
var_dump($createCustomer);