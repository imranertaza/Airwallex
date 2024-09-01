<?php
declare(strict_types=1);
namespace Imranertaza\Airwallex;

use Exception;
use stdClass;

class Airwallex
{

    private int $customer_phone;
    private string $customer_name;
    private string $customer_email;
    private array  $productItems;
    private string $AW_URL;
    private string $AW_LOGIN;
    private string $AW_CLIENTID;
    private string $AW_API;

    /**
     * @description This is use set airwallex config data
     * @param array $argument
     * @return $this
     */
    public function config(array $argument) : Airwallex
    {
        $this->AW_URL = $argument["aw_url"];
        $this->AW_LOGIN = $argument["aw_login"];
        $this->AW_CLIENTID = $argument["aw_client_id"];
        $this->AW_API = $argument["aw_api"];

        return $this;
    }
    
    /**
     * @description This method generates access token of airwallex payment gateway
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
                    CURLOPT_URL => $this->AW_URL . $this->AW_LOGIN,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Length: 0',
                        'x-client-id:' . $this->AW_CLIENTID . '',
                        'x-api-key:' . $this->AW_API . ''
                    ),
                )
            );
            $response = curl_exec($curl);
            $res = json_decode($response);
            if (!isset($res->token)){
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
     * @description This method generates salt for the airwallex payment gateway
     * @param int $length
     * @return string
     */
    private function generateRandomSalt(int $length = 16): string
    {
        try {
            return bin2hex(random_bytes($length));
        } catch (Exception $e) {
            echo "Random Salt did not generated. Problem is: " . $e->getMessage();
            return '';
        }
    }

    /**
     * @description This method returns a package details as an array
     * @param array $packageData
     * @return $this
     */
    public function generatePackageArray(array $packageData): Airwallex
    {
        $package_item = [];

        if (!empty ($packageData)) {

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
     *@description This method generates a product array list
     * @return array
     */
    public function generateProductsArray(): array
    {
        $productItems = [];

        if (!empty ($_SESSION['cart_item'])) {

            foreach ($_SESSION['cart_item'] as $prodID => $prod) {
                foreach ($prod as $item) {
                    $productItems[] = [
                        'code' => $prodID,
                        'desc' => $item['title'],
                        'name' => $item['title'],
                        'quantity' => $item['qty'],
                        'sku' => "",
                        'type' => "",
                        'unit_price' => $item['price'],
                        'url' => $item['thumb_image']
                    ];
                }
            }
        }

        return $productItems;
    }


    /**
     * @description This method sets the customer data before creating a customer.
     * @param int $customerID
     * @return $this
     */
    public function setCustomerData(int $customerID): Airwallex
    {
        $customer = new Customer(($customerID));
        $this->customer_phone = (int)$customer->customer_info()['phone'];
        $this->customer_name = $customer->customer_info()['name'];
        $this->customer_email = $customer->customer_info()['email'];
        return $this;
    }


    /**
     * @description This method is to create a new customer into the airwallex payment gateway
     * @return string
     */
    public function createCustomer(): string
    {
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Get access token
        $accessToken = $this->getAccessToken();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $this->AW_URL . AW_CREATE_CUSTOMER,
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
                    'Authorization: Bearer ' . $accessToken
                ),
            )
        );

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response)->id;
    }


    /**
     * @param string $intentID
     * @return stdClass
     */
    public function cancelPaymentIntent(string $intentID): stdClass
    {
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . 'pa/payment_intents/' . $intentID . '/cancel',
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
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }


    /**
     * @description This method saves the card to ariwallex by using the tokenization (following the rules of PCI-DSS)
     * @param array $cardDetails
     * @return string
     * @throws Exception
     */
    public function tokenizeCardToSave(array $cardDetails): string
    {
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.airwallex.com/v1/card/tokenize',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($cardDetails),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer' . $accessToken
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $responseObject = json_decode($response);

        if ($responseObject instanceof stdClass && isset($responseObject->token)) {
            return $responseObject->token;
        } else {
            throw new Exception("Failed to tokenize card details");
        }
    }


    /**
     * @description This method creates a payment using the saved token (for saved card)
     * @param string $token
     * @param float $amount
     * @return stdClass
     * @throws Exception
     */
    public function createPaymentWithSavedCard(string $token, float $amount): stdClass
    {
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL.'payments',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'token' => $token,
                'amount' => $amount,
                'currency' => 'USD',
                'merchant_order_id' => 'your_order_id',
                'customer_id' => 'customer_id'
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $responseObject = json_decode($response);

        if ($responseObject instanceof stdClass && isset($responseObject->id)) {
            return $responseObject;
        } else {
            throw new Exception("Failed to create payment");
        }
    }

    /**
     * @description This method retrieves card details using token
     * @param string $token
     * @return stdClass
     * @throws Exception
     */
    public function getCardDetails(string $token): stdClass
    {
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.airwallex.com/v1/card/details?token=$token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $responseObject = json_decode($response);

        if ($responseObject instanceof stdClass) {
            return $responseObject;
        } else {
            throw new Exception("Failed to retrieve card details");
        }
    }

    /**
     * @description this is using create consent
     * @return stdClass
     * @throws Exception
     */
    public function createPaymentConsent(): stdClass
    {
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . 'pa/payment_consents/create',
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
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }

    /**
     * @description This is used to get client secret
     * @param string $customerID
     * @return string|null
     * @throws Exception
     */
    public function client_secret(string $customerID) : string | null
    {

        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . 'pa/customers/' . $customerID . '/generate_client_secret',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response)->client_secret ?? null;
    }

    /**
     * @description This is used to get individual consents
     * @param string $consents
     * @return stdClass|string
     */
    public function get_payment_consents(string $consents) : stdClass | string
    {
        try {
            $curl = curl_init();

            // Get access token
            $accessToken = $this->getAccessToken();

            // Set cURL options
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->AW_URL . 'pa/payment_consents/' . $consents,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ],
            ]);

            // Execute cURL request
            $response = curl_exec($curl);

            // Close cURL session
            curl_close($curl);

            return json_decode($response);

        }catch (Exception $e){

            return $e->getMessage();

        }
    }

    /**
     * @description This method is to create a new customer into the airwallex payment gateway
     * @return string
     */
    public function createPaymentMethod(string $airwallexCustomerID): stdClass
    {

        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Get access token
        $accessToken = $this->getAccessToken();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $this->AW_URL.'pa/payment_methods/create',
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
                    'Authorization: Bearer ' . $accessToken
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);

    }

    /**
     * @description This is using for airwallex create account
     * @return stdClass
     * @throws Exception
     */
    public function createAccount() : stdClass
    {
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Get access token
        $accessToken = $this->getAccessToken();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $this->AW_URL.'pa/payment_methods/create',
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
                    'Authorization: Bearer ' . $accessToken
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);

    }

    /**
     * @description This is using for create payment intent for save card
     * @param string $airwallexCustomerID
     * @param string $price
     * @return mixed
     * @throws Exception
     */
    public function createPaymentIntent(string $airwallexCustomerID, string $price) : stdClass
    {

        // Initialize cURL
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . AW_CREATE_PINTENT,
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
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);
        $res = json_decode($response);

        // Output response
        return $res;
    }

    /**
     * @description This use for confirm payment intent
     * @param $id
     * @param $cid
     * @param $cvc
     * @param $customer_id
     * @return mixed
     * @throws Exception
     */
    public function confirmPaymentIntent(string $id, string $cid, string $cvc, string $customer_id) : stdClass
    {
        $curl = curl_init();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Get access token
        $accessToken = $this->getAccessToken();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $this->AW_URL.'pa/payment_intents/' . $id . '/confirm',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'customer_id' => $customer_id,
                    'payment_consent_reference' => [
                        'id' => $cid,
                        'cvc' => $cvc
                    ],
                    'request_id' => $salt,
                ]),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken
                ),
            ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }

    /**
     * @description This is use for create consents
     * @param int $amount
     * @return mixed
     * @throws Exception
     */
    public function createConsents(int $amount) : stdClass
    {
        // Initialize cURL
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL.'pa/payment_consents/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'customer_id' => 'cus_hkdm5j76tgxaa4enfbw',
                'next_triggered_by' => 'merchant',
                'currency' => 'USD',
                'amount' => $amount,
                'request_id' => $salt,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        $res = json_decode($response);

        // Output response
        return $res;
    }

    /**
     * @description This is for payment consents verify
     * @param string $id
     * @return mixed
     * @throws Exception
     */
    public function payment_consents_verify(string $id) : stdClass
    {
        // Initialize cURL
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL.'pa/payment_consents/'.$id.'/verify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);
        $res = json_decode($response);

        // Output response
        return $res;
    }

    /**
     * @description This method creates subscription
     * @param array $arg this array contains airwallexCustomerID, period, period_unit, price_id, payment_consent
     * @return mixed
     */
    public function createSubscription(array $arg) : stdClass
    {

        // Initialize cURL
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . "subscriptions/create",
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
                    'period_unit'=> $arg['period_unit']
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);
        $res = json_decode($response);

        // Output response
        return $res;
    }

    /**
     * @description this is using for create product
     * @param string $pro_name
     * @param string $unit
     * @return stdClass
     * @throws Exception
     */
    public function create_Product(string $pro_name, string $unit) : stdClass
    {

        // Initialize cURL
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();


        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . "products/create",
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
                'unit' => 'per '.$unit
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);
        $res = json_decode($response);

        // Output response
        return $res;
    }

    /**
     * @description This is using for create price
     * @param string $product_id
     * @param float $unit_amount
     * @param int $period
     * @param string $period_unit
     * @return stdClass
     * @throws Exception
     */
    public function create_Price(string $product_id, float $unit_amount, int $period, string $period_unit) : stdClass
    {

        // Initialize cURL
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();


        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . "prices/create",
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
                'product_id'=> $product_id,
                'unit_amount' => $unit_amount,
                'recurring' => [
                    'period' => $period,
                    'period_unit'=> $period_unit
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);
        $res = json_decode($response);

        // Output response
        return $res;
    }

    /**
     * @description This is using for gat subscription data to base on subscription id
     * @param string $id
     * @return stdClass
     * @throws Exception
     */
    public function get_subscriptions(string $id) : stdClass
    {

        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . '/subscriptions/'.$id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }

    /**
     * @description This is using for get price to base on get price id
     * @param string $price_id
     * @return stdClass
     * @throws Exception
     */
    public function get_price(string $price_id) : stdClass
    {
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt


        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . '/prices/'.$price_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);
        $res = json_decode($response);

        return $res;
    }

    /**
     * @description This is using for cancel subscription to base on subscription id
     * @param string $id
     * @return stdClass
     * @throws Exception
     */
    public function cancel_subscription(string $id) : stdClass 
    {
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . '/subscriptions/'.$id.'/cancel',
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
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        $res = json_decode($response);

        return $res;
    }

    /**
     * @description This is using for get invoice to base on invoice id
     * @param string $id
     * @return stdClass
     * @throws Exception
     */
    public function get_invoices(string $id) : stdClass
    {
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . '/invoices/'.$id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }


    /**
     * @description This using for get intent to base on id
     * @param string $id
     * @return stdClass
     * @throws Exception
     */
    public function get_intent(string $id) : stdClass 
    {
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . '/payment_intents/'.$id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);

        return json_decode($response);
    }

    /**
     * @description This is using for subscriptions update to base on subscription id
     * @param string $id
     * @param string $cst_id
     * @return stdClass
     * @throws Exception
     */
    public function subscriptions_update(string $id, string $cst_id) : stdClass
    {
        $curl = curl_init();

        // Get access token
        $accessToken = $this->getAccessToken();

        // Generate random salt
        $salt = "pm" . $this->generateRandomSalt();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->AW_URL . '/subscriptions/'.$id.'/update',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'request_id' => $salt,
                'payment_consent_id' => $cst_id,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Close cURL session
        curl_close($curl);
        $res = json_decode($response);

        return $res;
    }

}