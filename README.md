HoyaMasterpassBundle
================
This Symfony 2 bundle implements the backend calls for Masterpass V6 checkout - Standard flow. For complete Masterpass docs, refer https://developer.mastercard.com/product/masterpass

Install
-------
Add HoyaMasterpassBundle in your composer.json:

```js
{
    "require": {
        "hoya/masterpass-bundle": "dev-master"
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
    callback: http://localhost/app_dev.php
    origin_url: http://test.localhost.com
    checkoutidentifier: a4a6x1ywxlkxzhensyvad1hepuouaesuv
    keys:
        sandbox:
            consumerkey: cLb0tKkEJhGTITp_6ltDIibO5Wgbx4rIldeXM_jRd4b0476c!414f4859446c4a366c726a327474695545332b353049303d
            keystorepath: "%kernel.root_dir%/Certs/SandboxMCOpenAPI.p12"
            keystorepassword: changeit
        production:
            consumerkey: cLb0tKkEJhGTITp_6ltDIibO5Wgbx4rIldeXM_jRd4b0476c!414f4859446c4a366c726a327474695545332b353049303d
            keystorepath: "%kernel.root_dir%/Certs/MCOpenAPI.p12"
            keystorepassword: changeit

```
Usage
-----
You may follow some sample [code here](https://github.com/marcoshoya/MasterpassBundle/blob/master/Controller/MasterpassController.php)

Running the Tests
-----------------

Install the [Composer](http://getcomposer.org/) `dev` dependencies:

    php composer.phar install --dev

Then, run the test suite using
[PHPUnit](https://github.com/sebastianbergmann/phpunit/):

    phpunit
