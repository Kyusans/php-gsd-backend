<?php      
  require_once("./vendor/autoload.php");

  use Minishlink\WebPush\Subscription;
  use Minishlink\WebPush\WebPush;
  
  class Notification{
    function sendNotif($token, $subject){
      $auth = [
        'VAPID' => [
            'subject' => 'mailto:me@website.com', // can be a mailto: or your website address
            'publicKey' => 'BKpp3bZGmXDPhvW4Zxf9CBybvQ6oH4gKOEfybeid60ncfQ61E7LQxs70sNOyX9sXcS5C-03nju19QwlYq5vsSQQ', // (recommended) uncompressed public key P-256 encoded in Base64-URL
            'privateKey' => 'v6KkmZpU0EAd8_cEAZCW_klkQ8HA3qr5iDC2rFXIgM0'
        ],
      ];
      $webPush = new WebPush($auth);
      $payload = '{"title":"New complaint ticket", "body": "' . $subject . '"}';
      $subscription = Subscription::create(json_decode($token), true);
      $webPush->sendOneNotification($subscription, $payload, ['TTL' => 5000]);
    }
  }


