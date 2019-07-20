# F3 Zarinpal
This is a Zarinpal plugin for the [PHP Fat-Free Framework](https://github.com/bcosca/fatfree).

## Features
* Easy to use API
* Full API Methods
* Full Documented

## Installation
### Using [Composer](https://getcomposer.org)
    composer require nimah79/f3-zarinpal 

## Config sample
Add these lines to your F3 config:
```
[ZARINPAL]
merchant_id=XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
```

## Quick Start
#### Request Authority (and redirecting user)
```php
$zarinpal = new Zarinpal();

$zarinpal->setAmount(5500);
$zarinpal->setDescription('Purchasing test product');
$zarinpal->setCallbackURL('http://example.com/pay/verify');

$result = $zarinpal->request();
if ($result->ok) {
    $zarinpal->redirect();
} else {
    echo 'Error: ' . $result->message;
}
```
#### Verifying transaction
```php
$zarinpal = new Zarinpal('XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX');

$zarinpal->setAmount(5500);

$result = $zarinpal->verify();
if ($result->ok) {
    echo 'Successful transaction!';
}
```
## More!
### Set uesr information
You can pass user information to Zarinpal. This is recommended to have more control on your transactions:
```php
$zarinpal->setEmail('foo@bar.com');
$zarinpal->setMobile('09123456789');
```
### Using Sandbox
You can use sandbox mode when you want to do some test transactions with custom result (succeeded or failed):
```php
$zarinpal->enableSandbox();
```
### Using Zaringate
By default, users will redirect to Zarinpal Webgate page, but you can transfer your users to payment gateway directly. Notice that you need to activate necessary service for using Zaringate (call Zarinpal supports):
```php
$zarinpal->enableZaringate();
```
### Shared payoff

> This method is suitable for those sellers whose benefit from entered price must be distributed in a special way. For example, you own a website that presents ceremony services and you have some contributions with several contractors. In this way you would keep some money and settle the rest of it to the contractors' account.
```php
// 4500 Tomans from the main transaction is sent
// to ZP.125732 with this Description: Testing profit
$zarinpal->addSharedPay('ZP.125732', 4500,  'Testing profit');
```
You can also add multiple items for shared payoff:
```php
$zarinpal->addSharedPay('ZP.125732', 4500, 'Testing profit');
$zarinpal->addSharedPay('ZP.133476.2', 1200, 'More testing profit');
$zarinpal->addSharedPay('ZP.197825.1', 6700, 'More than more testing profit');
```

### Long time authority
By default, an authority will expire at 15 minutes after generating, but you can set your custom lifetime for your generated authority by using `expireIn()` method (between 1800 to 3888000 seconds). You should use it before `request()`:
```php
// Will expire after 7200 seconds (2 hours)
$zarinpal->expireIn(7200);
``` 
Notice that you need to activate necessary service for using thins method (call Zarinpal supports).

### Get redirect URL
Sometimes, you need to have gateway URL instead of redirecting:
```php
$zarinpal->getRedirectURL();
```
Pay attention to use this method after `$zarinpal->request()`. Look at to this example:
```php
$result = $zarinpal->request();
if ($result->ok) {
    // It will return something like:
    // https://www.zarinpal.com/pg/StartPay/xxxxx/
    $zarinpal->getRedirectURL();
}
```

### Get generated authority
Normally you don't need this method, but it's possible to get pure generated authority. It also need to run after `request()`:
```php
$result = $zarinpal->request();
if ($result->ok) {
    $zarinpal->getAuthority();
}
```

### Refresh Authority
If you want to refresh your authority lifetime, use `refreshAuthority()`. It does the same thing that `expireIn()` does. but `refreshAuthority()` can be used when you generated an authority in past and `expireIn()` will help you to set your authority life span before generating:
```php
// This will refresh your authority (xxxxxxxxxx) for 3600 seconds (1 hour)
$zarinpal->refreshAuthority("xxxxxxxxxx", 3600);
```

### Get list of unverified transactions
This method return a list of transactions that you didn't `verify()` them, so it sound that all transactions on this list should be uncompleted:
```php
$result  =  $zarinpal->getUnverified();
if ($result->ok) {
    // Contains an array of arrays
    print_r($result->body->Authorities);
} else {
    echo $result->message;
}
```

## Response
All responses from the API will return a `Response` object. This object contains these datas:
```
{
  "ok": true|false,
  "message": string,
  "status": int,
  "body": {
    ...
  }
}
``` 
You can check if the request was successful or not using `ok`, get returned status in `status` and get translated status message in `message`. Also, you can access to api result in `body`.
