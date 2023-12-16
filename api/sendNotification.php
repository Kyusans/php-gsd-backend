<?php
require_once("./vendor/autoload.php");

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class Notification
{
    function sendNotif($token, $subject, $message)
    {
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:me@website.com',
                'publicKey' => 'BKpp3bZGmXDPhvW4Zxf9CBybvQ6oH4gKOEfybeid60ncfQ61E7LQxs70sNOyX9sXcS5C-03nju19QwlYq5vsSQQ',
                'privateKey' => 'v6KkmZpU0EAd8_cEAZCW_klkQ8HA3qr5iDC2rFXIgM0'
            ],
        ];
        $webPush = new WebPush($auth);
        $url = 'https://coc-studentinfo.net/gsd/';
        $payload = ['title' => $message, 'body' => $subject, 'url' => $url];
        $tokenArray = $token;

        try {
            if ($tokenArray !== null) {
                $subscription = Subscription::create($tokenArray);
                $webPush->sendOneNotification($subscription, json_encode($payload), ['TTL' => 5000]);
            }
        } catch (TypeError $e) {
            error_log('Caught TypeError: ' . $e->getMessage());
        }
    }
}
?>
