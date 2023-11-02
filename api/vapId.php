<?php
require_once("vendor/autoload.php");
use Minishlink\WebPush\VAPID;
var_dump(VAPID::createVapidKeys());

/* 
  ["publicKey"]=>
  string(87) BKpp3bZGmXDPhvW4Zxf9CBybvQ6oH4gKOEfybeid60ncfQ61E7LQxs70sNOyX9sXcS5C-03nju19QwlYq5vsSQQ
  ["privateKey"]=>
  string(43) v6KkmZpU0EAd8_cEAZCW_klkQ8HA3qr5iDC2rFXIgM0
*/
