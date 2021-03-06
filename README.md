MasterpassBundle
================
This Symfony 2 bundle implements the backend calls for Masterpass V7 checkout - Standard and Express flows. For complete Masterpass docs, refer https://developer.mastercard.com/product/masterpass

[![Build Status](https://travis-ci.org/marcoshoya/MasterpassBundle.svg?branch=master)](https://travis-ci.org/marcoshoya/MasterpassBundle)
[![Total Downloads](https://poser.pugx.org/hoya/masterpass-bundle/downloads)](https://packagist.org/packages/hoya/masterpass-bundle)
[![Latest Stable Version](https://poser.pugx.org/hoya/masterpass-bundle/v/stable)](https://packagist.org/packages/hoya/masterpass-bundle)


Install
-------
Add HoyaMasterpassBundle in your composer.json:

```js
{
    "require": {
        "hoya/masterpass-bundle": "~3.0.0"
    }
}
```

Register the bundle in your appkernel.php file

```js
return array(
   // ...
   new Hoya\MasterpassBundle\HoyaMasterpassBundle(),
   // ...
);
```

Setup your config.yml file

```yml
# app/config/config.yml

hoya_masterpass:
    production_mode: false
    checkoutidentifier: checkout_id
    keys:
        consumerkey: consumer_key_value
        keystorepath: "%kernel.root_dir%/cert/csr_file.p12"
        keystorepassword: changeit

```
Usage
-----
You may follow some sample [code here](https://github.com/marcoshoya/MasterpassBundle/blob/master/Controller/CheckoutController.php)

Handling Errors
---------------
Any error which may happen while calling Masterpass APIs throws an Exception. Hence, it is highly recommended to use try / catch block
In addition, you can check further details on symfony logs.

```js
try {
                
    $payment = $this->get('hoya_masterpass_service')->getPaymentData($callback, '1234');

} catch (\Exception $e) {
    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
}
```

Running the Tests
-----------------

Install the [Composer](http://getcomposer.org/) `dev` dependencies:

    php composer.phar install --dev

Then, run the test suite using
[PHPUnit](https://github.com/sebastianbergmann/phpunit/):

    ./phpunit
