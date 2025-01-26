<?php
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
        $this->firebase = (new Factory)->withServiceAccount([
            "type" => "service_account",
            "project_id" => "yemen-stores",
            "private_key_id" => "d9befb63dcc2829df46737c748c5ad7d02f52d19",
            "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDTC4lCACsehefi\n6sb6vIaNd2OWbuxP8i10AmsD0Flva9SVDuSMsIu+kmCEqk6RHsGqtj4ccfJp1cW7\nekpidchGCtKGReo3FMsnWjnKBoraD9AsysLZtH5DEa78KNPsWHlo6hVz5F0X5jGS\nJHkAI9sQp47qT7kI9HEeYiXR6FIeeqLVHzr2qx0cfFY9r2g0YMyLSoIfKWkzhN1A\n6B197B/MR1teWs3KvJVWgfnH1rKRKdKzyo2W0OjELxDU0Q+R1rQ8CUk+NcKbKtO3\nPVei2Rjv8D43jF/7fqSpS4twcknrvqScX/e9puQHvjCW+/aolbxEHThXtZMTb78x\n2hnHGcrXAgMBAAECggEAFKVxq+niTgRUyo4KiBsLbfZUR5im0hQwi10Oy3rr7orC\nnqmsHqT8dtjqslY+HqC/DfLx1P/DJffXQt9uCX9zGuiru2qsxO8dOyzur5HzFLqS\n56uwyXJVhXAxPNMtsFbgQcasvjK4D/XhuzKJ190QhreUGd4eWjIeIuMAYJTblV/4\ntE6PO9lrh4REhdKCGZKyktp5lpC9K2dCZNa/ja6z51R5q9Y5oMTGvLrpGDVHazwk\nvZMMd1mQv6crhjpl2jo/3P25HfXPpaS5V/mjvEyW9mNvoQMVHcxOVFiTF5OB+m5h\nDLYp7Pe8EloJ//YHv5j1A24EgInRDQOaPX/b2ksWmQKBgQD/jjC+76W9A9rM5V0x\n5kPVatgRmkBpY5lLrThkVnZmtF/xKjIHXXkyI7snD3mXEe8IptqNM0bhWhGUdpMm\nAjvmlT4TanfPNBvqchDJeIMgkdq4Nken1fvuPU+1z/GcGF7sx/GnlDUkpykWheRE\n1PVbIIw7DVTr1srdXkM3r/Hq3QKBgQDTaYX+NATJCCYCzhgYQmf2PgY/ICP8G+16\nNOO8I7x6QIMxfgYbNDyUUpUDzdKRsJ18hyTt+069FxUT4+s5cxt9j4IAFmbyCvWe\n6y7ZzyASXLHSAvseBpzcZGCKpHrFd0yzYtc4hMyXmL/8xFcOsTAG8rdU88BIilPb\nNdGGTcnvQwKBgFbg+CFxR18i2FegAjbcmWMMl7gkQJGTkqHvmaRC4K251IQgXDG0\nzWcGTrHQyP1a03CViOdH72jdPezDAvOA/uw9AIWJRIHkrTje3mYf2jRQYZMOoP2l\n+afcoCSnNPRkNKE6uCTIdeioC4fkrN3ZqC/6uLG6rowe0YjAawmbfxrhAoGABmCj\niySUlF/rjaAb9/dg3XvHgnX8v+kzw8D+sbk+QU3a505O7tknjq3jEudNl9mFFrGY\n+pjfKjMdDqmMegIv7Ry8JjaGynxsJmwf0LA/3m3va09ttd0rNDbO9r+5eGV96dds\neKcA6P3RpNVjbu0Hbt45i5WC0m1h1DYOaQfFtLMCgYBCGOyvXFRFU7zdx8Y+G1VX\ngJh3MwSp/w99Vy4WgtPTLl066Um3bFbzzdwZrdbmssbxW4OhAoEKIT+RYBIMBtRB\neGgtH/HpowFvJzY80/OJAjMY8/cMco2Zga8g+5IE1Idm5PYnjIuZ8vqySsggVIDt\n2fjim53c4QJ3SjrZqHwo3g==\n-----END PRIVATE KEY-----\n",
            "client_email" => "firebase-adminsdk-7qbeo@yemen-stores.iam.gserviceaccount.com",
            "client_id" => "101185850926854167583",
            "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
            "token_uri" => "https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
            "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-7qbeo%40yemen-stores.iam.gserviceaccount.com",
            "universe_domain" => "googleapis.com"

        ]);
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
            'body' => $message,
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
