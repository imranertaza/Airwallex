# Airwallex Payment Gateway PHP Library

This is a simple open-source Airwallex payment gateway PHP library that provides a basic implementation of airwallex payment gateway to any php application easily.

## Features

- **Configure:** Configure Airwallex payment gateway.
- **Create Payment:** Retrieve records from the database.
- **Create Payment Intent:** Modify existing records in the database.
- **Create Customer:** Remove records from the database.
- **Create Payment Consent:** Remove records from the database.
- **Get Payment Consent:** Remove records from the database.
- **Get Payment Consent:** Remove records from the database.


## Example

    $crud = new Crud("TableName");

    $x = $crud->groupStart()
        ->where(['a' =>'a'],"!=")
        ->NotGroupStart()
        ->orWhere('b', "=",'b')
        ->where('c', "=",'c')
        ->groupEnd()
        ->groupEnd()
        ->where('d', "=",'d')->get();




# config() 
 
"config()" method is used to add Airwallex configuration data.This method takes some arguments type of array.
 It's return Airwallex class;

## Example

    config(
             "aw_url"=> String,
             "aw_login"=> String,
             "aw_client_id" => String,
             "aw_api" => String
        )   


# createPaymentIntent()

Create payment intent is first step for a payment . 

## Example

    createPaymentIntent(string $token, float $amount)


# createCustomer()

"createCustomer()" method is used to create customer for Airwallex.This is return created customer id (string);

## Example

    createCustomer()

# cancelPaymentIntent()

"cancelPaymentIntent()" method is used to  payment intent cancel. This method take argument type of intent id (string).
 Its method is return object.

## Example 

    cancelPaymentIntent(string $id)

# createPaymentConsent()

"createPaymentConsent()" method is used to add cart on pacific customer. It's return stdClass;

## Example

    createPaymentConsent()

# client_secret()

"client_secret()" method is used to get client secret. It's return client secret (string).

## Example 

    client_secret()

# get_payment_consents()

"get_payment_consents()" method is used to get payment consents. This method take argument type of consent id (string).
Its method is return object.

## Example

    get_payment_consents(string consent_id)

# createPaymentMethod()

"createPaymentMethod()" method is used to create payment method. This method take argument type of customer id (string).
Its return object.

## Example 

    createPaymentMethod()

# createConsents()

    createAccount()

"createConsents()" method is used to create consent. This method return object.

## Example

    createConsents()

# createSubscription()

"createSubscription()" method is used to create subscription. This method takes some arguments type of array.
It's return array;

## Example

    createSubscription([
             'airwallexCustomerID' => String,
             'price_id' => String,
             'payment_consent' => String,
             'period' => Integer,
             'period_unit' => "DAY" | "WEEK" | "MONTH" | "YEAR"
    ])

# create_Product()

"create_Product()" method is used to create product. This method take argument type of product name and unit (string).
Its return object. 

## Example

    create_Product(string product_id, string unit)

# create_Price()

"create_Price()" method is used to create price. This method take argument type of unit amount(integer), product id,
period unit (string) and period (integer). Its return object.

## Example

    create_Price()

# get_subscriptions()

"get_subscriptions()" method is used to get subscription. This method take argument type of subscription id (string).
Its return object.

## Example

    get_subscriptions(string id)

# get_price()

"get_price()" method is used to get price. This method take argument type of price id (string).
Its return object.

## Example

    get_price(string id)

# cancel_subscription()

"cancel_subscription()" method is used to cancel subscription. This method take argument type of subscription id (string).
Its return object.

## Example

    cancel_subscription(string id)

# get_invoices()

"get_invoices()" method is used to get invoice. This method take argument type of invoice id (string). Its return object.

## Example
 
    get_invoices(string id)

# get_intent()

"get_intent()" method is used to get intent. This method take argument type of intent id (string). Its return object.

## Example

    get_intent()

# subscriptions_update()

"subscriptions_update()" method is used to update subscriptions. This method take argument type of subscription id and consent id (string).
Its return object.

## Example

    subscriptions_update()
