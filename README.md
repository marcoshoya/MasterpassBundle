HoyaMasterpassBundle
================
This bundle allows you to use Masterpass V6 checkout in your Symfony 2 application

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

And register the bundle in your appkernel.php file

```js
return array(
   // ...
   new Hoya\MasterpassBundle\HoyaMasterpassBundle(),
   // ...
);
```

Running the Tests
-----------------

Install the [Composer](http://getcomposer.org/) `dev` dependencies:

    php composer.phar install --dev

Then, run the test suite using
[PHPUnit](https://github.com/sebastianbergmann/phpunit/):

    phpunit
