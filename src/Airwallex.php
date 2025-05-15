<?php
declare(strict_types=1);
namespace Imranertaza\Airwallex;

use Exception;
use stdClass;

class Airwallex
{
    private string $get_access_token;
    private string $aw_clientID;
    private string $aw_API_key;
    private string $aw_url;
    public string $customer_name;
    public string $customer_email;
    public string $customer_phone;
    private array $productItems;


    /**
     * This is the constructor method of the Airwallex class.
     *
     * @param string $ClientID
     * @param string $API_key
     * @param string $type
     * @throws Exception
     */
    public function __construct(string $ClientID, string $API_key, string $type = 'live')
    {

        $this->aw_url = ($type == 'test') ? 'https://api-demo.airwallex.com/api/v1/' : 'https://api.airwallex.com/api/v1/';

//        define('AW_LOGIN', 'authentication/login');
//        define('AW_CREATE_PAYMENT', 'payments/create');
//        define('AW_CREATE_PINTENT', 'pa/payment_intents/create');
//        define('AW_CREATE_CUSTOMER', 'pa/customers/create');

        $this->aw_clientID = $ClientID;
        $this->aw_API_key = $API_key;
        $this->get_access_token = $this->getAccessToken();
    }


    /**
     * This method generates access token of airwallex payment gateway
     *
     * @return string
     * @throws Exception
     */
    private function getAccessToken(): string
    {

        if (!isset($_SESSION['expiry']) || strtotime($_SESSION['expiry']) < time()) {
            $curl = curl_init();
            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_URL => $this->aw_url . 'authentication/login',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Length: 0',
                        'x-client-id:' . $this->aw_clientID,
                        'x-api-key:' . $this->aw_API_key
                    ),
                )
            );
            $response = curl_exec($curl);
            //                curl_close($curl);
            $res = json_decode($response);
            if (!isset($res->token)) {
                throw new Exception('Unauthorized credentials');
            }
            // Set the new access token and its expiry date
            $_SESSION['expiry'] = $res->expires_at;
            $_SESSION['acc_tok'] = $res->token;
        }

        // Return the access token
        return $_SESSION['acc_tok'];
    }


    /**
     * This method generates salt for the airwallex payment gateway
     *
     * @param int $length
     * @return null|string
     */
    private function generateRandomSalt(int $length = 16): null|string
    {
        try {
            return bin2hex(random_bytes($length));
        } catch (Exception $e) {
            echo "Random Salt did not generated. Problem is: " . $e->getMessage();
            return '!@#ASD)(*'.rand(10, 20);
        }
    }



    /**
     * This method creates a payment intent
     *
     * @param float|null $amount
     * @param string $airwallexCustomerID
     * @return array
     * @throws Exception
     */
    public function createPaymentIntent(?float $amount, string $airwallexCustomerID): array
    {

        // Initialize cURL
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . 'pa/payment_intents/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                'amount' => $amount,
                'currency' => 'USD',
                'customer_id' => $airwallexCustomerID,
                'merchant_order_id' => 'Merchant_Order_' . uniqid(),
                'metadata' => [
                    'my_test_metadata_id' => 'my_test_metadata_id_' . uniqid()
                ],
                //                'order' => [
                //                    'products' => $this->productItems,
                //                    'shipping' => [
                //                        'address' => [
                //                            'city' => 'South Nicoletteland',
                //                            'country_code' => 'CN',
                //                            'postcode' => '25000',
                //                            'state' => 'Maritzaview',
                //                            'street' => '2773 Simonis Hills'
                //                        ],
                //                        'first_name' => 'Orin',
                //                        'last_name' => 'Schowalter',
                //                        'phone_number' => '678-966-3529',
                //                        'shipping_method' => '顺丰快递'
                //                    ],
                //                    'type' => 'Online Mobile Phone Purchases'
                //                ],
                'payment_method_options' => [
                    'card' => [
                        'risk_control' => [
                            'skip_risk_processing' => false,
                            'three_domain_secure_action' => 'FORCE_3DS',
                            'three_ds_action' => 'FORCE_3DS'
                        ]
                    ]
                ],
                'return_url' => 'https://abigale.name'
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        $res = json_decode($response);
        // Output response
        return array(
            "intent_id" => !empty($res->id) ? $res->id : null,
            "client_id" => !empty($res->client_secret) ? $res->client_secret : null
        );
    }


    /**
     * This method returns a package details as an array
     *
     * @param array $packageData
     * @return $this
     */
    public function generatePackageArray(array $packageData): Airwallex
    {
        $package_item = [];

        if (!empty($packageData)) {

            foreach ($packageData as $prodID => $prod) {
                foreach ($prod as $item) {
                    $package_item[] = [
                        'code' => $prodID,
                        'desc' => $item['title'],
                        'name' => $item['title'],
                        'sku' => "",
                        'type' => "",
                        'unit_price' => $item['price'],
                    ];
                }
            }
        }

        $this->productItems = $package_item;
        return $this;
    }


    /**
     * This method is to create a new customer into the airwallex payment gateway
     * @return stdClass
     */
    public function createCustomer(): stdClass
    {
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $this->aw_url . 'pa/customers/create',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                                            "request_id": "' . $salt . '",
                                            "merchant_customer_id": "merchant_' . $salt . '",
                                            "first_name": "' . $this->customer_name . '",
                                            "last_name": "",
                                            "email": "' . $this->customer_email . '",
                                            "phone_number": "' . $this->customer_phone . '" 
                                        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->get_access_token
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);
    }


    /**
     * @description This method is to cancel a payment intent
     *
     * @param string $intentID
     * @return stdClass
     */
    public function cancelPaymentIntent(string $intentID): stdClass
    {
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . 'pa/payment_intents/' . $intentID . '/cancel',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                'cancellation_reason' => "Order cancelled",
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }



//    ///api/v1/pa/customers
//    public function client_secret(string $customerID): string | null
//    {
//
//        $curl = curl_init();
//
//        // Set cURL options
//        curl_setopt_array($curl, [
//            CURLOPT_URL => $this->aw_url . 'pa/customers/' . $customerID . '/generate_client_secret',
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_ENCODING => '',
//            CURLOPT_MAXREDIRS => 10,
//            CURLOPT_TIMEOUT => 0,
//            CURLOPT_FOLLOWLOCATION => true,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//            CURLOPT_CUSTOMREQUEST => 'GET',
//            CURLOPT_HTTPHEADER => [
//                'Content-Type: application/json',
//                'Authorization: Bearer ' . $this->get_access_token,
//            ],
//        ]);
//
//        // Execute cURL request
//        $response = curl_exec($curl);
//
//        // Close cURL session
//        curl_close($curl);
//
//        return json_decode($response)->client_secret ?? null;
//    }


    /**
     * This method is to get payment consents
     * @param string $consents
     * @return stdClass
     */
    public function getPaymentConsents(string $consents) : stdClass
    {
        try {

            $curl = curl_init();

            // Set cURL options
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->aw_url . 'pa/payment_consents/' . $consents,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->get_access_token,
                ],
            ]);

            // Execute cURL request
            $response = curl_exec($curl);

            // Close cURL session
            curl_close($curl);

            return json_decode($response);
        } catch (Exception $e) {
            return json_decode((string)['msg' => $e->getMessage()]);
        }
    }

    /**
     * This method is to create a new customer into the airwallex payment gateway
     * This API only works when you have airwallex native api access enabled
     *
     * @param string $airwallexCustomerID
     * @return stdClass
     */
    public function createPaymentMethod(string $airwallexCustomerID): stdClass
    {

        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        curl_setopt_array(
            $curl,
            array(

                CURLOPT_URL => $this->aw_url . "pa/payment_methods/create",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                                            "request_id": "' . $salt . '",
                                            "customer_id": "' . $airwallexCustomerID . '",
                                            "type": "card",
                                            "card": {
                                                "additional_info": {
                                                  "merchant_verification_value": "A52BD7",
                                                  "token_requestor_id": "50272768100"
                                                },
                                                "billing": {
                                                  "address": {
                                                    "city": "Shanghai",
                                                    "country_code": "CN",
                                                    "postcode": "100000",
                                                    "state": "Shanghai",
                                                    "street": "Pudong District"
                                                  },
                                                  "email": "john.doe@airwallex.com",
                                                  "first_name": "John",
                                                  "last_name": "Doe",
                                                  "phone_number": "13800000000"
                                                },
                                                "cvc": "123",
                                                "expiry_month": "12",
                                                "expiry_year": "2028",
                                                "name": "Syed Imran",
                                                "number": "4242424242424242",
                                                "number_type": "PAN"
                                            }
                                        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->get_access_token
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);
    }


    /**
     * This method is to create a new account into the airwallex payment gateway
     * @return stdClass
     */
    public function createAccount():stdClass
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(

                CURLOPT_URL => $this->aw_url . "pa/payment_methods/create",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '  "account_details": {
                                                "business_details": {
                                                    "business_name":"your_business_name"
                                                }
                                              },
                                              "customer_agreements": {
                                                "agreed_to_data_usage": true,
                                                "agreed_to_terms_and_conditions": true
                                              },
                                              "primary_contact": {
                                                "email": "your_account_name@company.com"
                                              }
                                            }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->get_access_token
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);
    }


    /**
     * This method is to create a payment intent for save card
     * @param string $airwallexCustomerID
     * @param string $price
     * @return stdClass
     */
    public function createPaymentIntentForSaveCard(string $airwallexCustomerID, string $price) : stdClass
    {

        // Initialize cURL
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . 'pa/payment_intents/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                'amount' => $price,
                'currency' => 'USD',
                'customer_id' => $airwallexCustomerID,
                'merchant_order_id' => 'Merchant_Order_' . uniqid(),
                'metadata' => [
                    'my_test_metadata_id' => 'my_test_metadata_id_' . uniqid()
                ],
                'order' => [
                    'shipping' => [
                        'address' => [
                            'city' => 'South Nicoletteland',
                            'country_code' => 'CN',
                            'postcode' => '25000',
                            'state' => 'Maritzaview',
                            'street' => '2773 Simonis Hills'
                        ],
                        'first_name' => 'Orin',
                        'last_name' => 'Schowalter',
                        'phone_number' => '678-966-3529',
                        'shipping_method' => '顺丰快递'
                    ],
                    'type' => 'Online Mobile Phone Purchases'
                ],

                'return_url' => 'https://abigale.name'
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        // Output response
        return json_decode($response);
    }


    /**
     * @description This method confirms the payment intent
     * @param string $id
     * @param string $consent
     * @param string $customer_id
     * @return stdClass
     */
    public function confirmPaymentIntent(string $id, string $consent, string $customer_id):stdClass
    {
        try {
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        curl_setopt_array(
            $curl,
            array(

                CURLOPT_URL => $this->aw_url . "/pa/payment_intents/" . $id . "/confirm",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'customer_id' => $customer_id,
//                    'payment_consent_reference' => [
//                        'id' => $cid,
//                        'cvc' => $cvc
//                    ],
                    "payment_consent_reference"=>[
                        "id" => $consent,
                    ],
//                    "payment_consent_id" => $consent,
//                    'payment_method' => [
//                        'type' => 'card',
//                        'card' => [
//                            'number' => '4035501000000008',
//                            'expiry_month' => '03',
//                            'expiry_year' => '2030',
//                            'name' => 'John Doe',
//                            'cvc' => '737',
//                            'three_ds' => [
//                                'return_url' => 'https://www.airwallex.com'
//                            ],
//                            'billing' => [
//                                'email' => 'john.doe@example.com',
//                                'phone_number' => '+1 1234567890',
//                                'first_name' => 'John',
//                                'last_name' => 'Doe',
//                                'address' => [
//                                    'country_code' => 'US',
//                                    'city' => 'San Francisco',
//                                    'state' => 'CA',
//                                    'street' => '1460 Mission St.#02W101',
//                                    'postcode' => '94103'
//                                ]
//                            ]
//                        ]
//                    ],
                    'request_id' => $salt,
                ]),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->get_access_token
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
        } catch (Exception $e) {
            return json_decode((string)['msg' => $e->getMessage()]);
        }
    }

    /**
     * @description This method creates payment consents
     * @param string $customerID
     * @return stdClass
     */
    public function createPaymentConsent(string $customerID):stdClass
    {
        // Initialize cURL
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . "pa/payment_consents/create",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'customer_id' => $customerID,
                'next_triggered_by' => 'merchant',
                'currency' => 'USD',
//                'amount' => $amount,
                'request_id' => $salt,
                "merchant_trigger_reason" => "scheduled",
                "ignore_payment_method" => true,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        // Output response
        return json_decode($response);
    }


    /**
     * @description This method verifies the payment consents
     * This API only works when you have airwallex native api access enabled
     *
     * @param string $consent_id
     * @return stdClass
     */
    public function verifyPaymentConsents(string $consent_id):stdClass
    {
        // Initialize cURL
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . "pa/payment_consents/" . $consent_id . "/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                "payment_method" => [
                    "type" => "card",
                    "card" => [
                        "number" => "4035501000000008",
                        "expiry_month" => "03",
                        "expiry_year" => "2030",
                        "name" => "John Doe",
                        "cvc" => "737"
                    ],
                  ],
                  "verification_options" => [
                        "card" => [
                            "amount" => 0.01,
                            "currency" => "USD"
                        ]
                  ],
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        // Output response
        return json_decode($response);
    }

    /**
     * @description This method creates subscription
     * @param array $arg this array contains airwallexCustomerID, period, period_unit, price_id, payment_consent
     * @return mixed
     */
    public function createSubscription(array $arg): stdClass
    {

        // Initialize cURL
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . "subscriptions/create",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                'customer_id' => $arg['airwallexCustomerID'],
                'items' => [
                    ['price_id' => $arg['price_id']]
                ],
                'payment_consent_id' => $arg['payment_consent'],
                'recurring' => [
                    'period' => $arg['period'],
                    'period_unit' => $arg['period_unit']
                ],
                'metadata' => [
                    'customer_id' => $arg['customer_id'],
                    'package_id' => $arg['package_id'],
                    'price' => $arg['price']
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        // Output response
        return json_decode($response);
    }


    /**
     * @description This method creates a product
     * @param string $pro_name
     * @param string $unit
     * @return stdClass
     */
    public function create_Product(string $pro_name, string $unit): stdClass
    {

        // Initialize cURL
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . "products/create",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                'active' => true,
                'name' => $pro_name,
                'unit' => 'per ' . $unit
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        // Output response
        return json_decode($response);
    }

    /**
     * @description This method retrieves a product by ID
     * @param string $id
     * @return stdClass
     */
    public function get_product(string $id):stdClass
    {

        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . '/products/' . $id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }

    /**
     * @param string $product_id ID of the Product object this price is associated with
     * @param float $unit_amount
     * @param int $period
     * @param string $period_unit
     * @return stdClass
     */
    public function create_Price(string $product_id, float $unit_amount, int $period, string $period_unit): stdClass
    {

        // Initialize cURL
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . "prices/create",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                'active' => true,
                'currency' => "USD",
                'product_id' => $product_id,
                'unit_amount' => $unit_amount,
                'recurring' => [
                    'period' => $period,
                    'period_unit' => $period_unit
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        // Output response
        return json_decode($response);
    }


    /**
     * @description This method retrieves a subscription by ID
     * @param string $id
     * @return stdClass
     */
    public function get_subscriptions(string $id):stdClass
    {

        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . '/subscriptions/' . $id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }


    /**
     * @description This method retrieves a price by PriceID
     * @param string $price_id
     * @return stdClass
     */
    public function get_price(string $price_id):stdClass
    {
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . '/prices/' . $price_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }

    /**
     * @description This method cancels a subscription by ID
     * @param string $id
     * @return stdClass
     */
    public function cancel_subscription(string $id):stdClass
    {
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . '/subscriptions/' . $id . '/cancel',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                'proration_behavior' => 'NONE'
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }

    public function get_invoices(string $id)
    {
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . '/invoices/' . $id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }


    /**
     * @description This method retrieves a payment intent by ID
     * @param string $id
     * @return stdClass
     */
    public function getPaymentIntent(string $id):stdClass
    {
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . 'pa/payment_intents/' . $id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }

    /**
     * @description This method updates a subscription
     * @param string $id
     * @param string $consent_id
     * @return stdClass
     */
    public function subscriptions_update(string $id, string $consent_id):stdClass
    {
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . '/subscriptions/' . $id . '/update',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                'payment_consent_id' => $consent_id
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }

    /**
     * @description This method updates a price
     * @param string $id
     * @param string $unit_amount
     * @return stdClass
     */
    public function price_update(string $id, string $unit_amount):stdClass
    {
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->aw_url . '/prices/' . $id . '/update',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                'unit_amount' => $unit_amount,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->get_access_token
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }

    /**
     * @description This method retrieves a payment method by ID
     * @param string $consentId
     * @return stdClass
     */
    public function getPaymentMethodIdFromConsent(string $consentId):stdClass
    {
        try {
            $url = $this->aw_url . "pa/payment_consents/$consentId";

            $headers = [
                'Authorization: Bearer ' . $this->get_access_token,
                'Content-Type: application/json'
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return json_decode($response);
        }catch (Exception $e) {
            return json_decode((string)['msg' => $e->getMessage()]);
        }
    }



}