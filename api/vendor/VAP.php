<?php

require_once './vendor/autoload.php';

use Minishlink\WebPush\VAPID;

// Create a new EC key pair using the 'prime256v1' curve
$keyPair = openssl_pkey_new(array(
  "curve_name" => 'secp256k1',
  "private_key_type" => OPENSSL_KEYTYPE_EC,
));

$vapidKeys = VAPID::createVapidKeys($keyPair);

print_r(VAPID::createVapidKeys($keyPair));

/*
    [publicKey] => BEqUmBJbzkKg3aeldL4K98XmxX6SF2qMhsJ93W2ZRFMYU6l6oRMykHAXlVondJ9N40bV5be34lwcPDj0do91d20
    [privateKey] => _0SO5m5icVICLQj7om6LkbvlnFDu0Bwfw4FALyeDf0w
*/

