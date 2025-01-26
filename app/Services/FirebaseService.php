<?
// app/Services/FirebaseService.php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $firebase;
    protected $messaging;

    public function __construct()
    {
        $this->firebase = (new Factory)->withServiceAccount(config('firebase.credentials'));
        $this->messaging = $this->firebase->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $message)
    {
        // Create a notification instance
        $notification = Notification::fromArray([
            'title' => $title,
            'body' => $message,
        ]);

        // Create a CloudMessage with target as device token
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification);

        // Send the message
        try {
            $this->messaging->send($message);
            return ['status' => 'Notification sent successfully'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function sendNotificationToTopic($topic, $title, $message)
    {
        // Create a notification instance
        $notification = Notification::fromArray([
            'title' => $title,
            'body'  => $message,
        ]);

        // Create a CloudMessage targeting the topic
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification($notification);

        // Send the message
        try {
            $this->messaging->send($message);
            return ['status' => 'Notification sent successfully to topic: ' . $topic];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
