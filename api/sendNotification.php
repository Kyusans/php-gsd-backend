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
      $url = 'http://localhost:3000';
      $payload = ['title' => 'New complaint ticket', 'body' => $subject, 'url' => $url];
      $tokenArray = json_decode($token, true);
      $subscription = Subscription::create($tokenArray, true);
      $report = $webPush->sendOneNotification($subscription, json_encode($payload), ['TTL' => 5000]);
      print_r($report);
    }
  }

  // $notification = new Notification();
  // $notification->sendNotif('{"endpoint":"https://wns2-bl2p.notify.windows.com/w/?token=BQYAAAAtnhFkvEvu3Mj8wKSwsyRuy8MVD1LiJUQG%2bkeKWfnVmkPxEeUltQ9H1uAazE6ISJtV1h88JTrfut3JYoxKcHtljit%2fKClz%2bkPsdSp2sMMJdwq3OnJvBWY3EluVVGhnoQcQFpvthamjqKTAIxhWRDHau0gTYHHUpvGZMHQq%2bxcx9GRmnS9m1MHKJJvFlrZ8KAJMk7ZMVq9PYOBDhsuDIf8iTerc6VO8DW3PUpyQ9HDbcjIis2XJ9dlhhGzWDS6X5I%2bktjnlTbR%2bG%2bK7%2bA0RvjvrkNFOAELoZPq9VcfmlizHf1OQSEcm%2fReWbOaXubzRUTU%3d","expirationTime":null,"keys":{"p256dh":"BP5puLNmVVM_6j0mkFXQyO24OEqgD75YAUrrs2A8lMDW8NNsaLy8uNPL-UkJ58ReaSrOORq4V3-GdvAJwbrkv5s","auth":"_72lstpFFGrcO4fAdzS6Xg"}}', "subject");


