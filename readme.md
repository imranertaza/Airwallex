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