<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use App\Models\Order;
use App\Support\Feature;
use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class NotificationService
{
    private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function notifyOrderStatus(Order $order): void
    {
        // Single chokepoint for the push feature — covers every status-change
        // call site (accept / reject / ready).
        if (Feature::disabled('notifications.push')) {
            return;
        }

        $customer = $order->customer;
        if (! $customer?->fcm_token) {
            return;
        }

        [$title, $body] = $this->buildMessage($order);

        // Queue the FCM send so the admin's status-update request returns
        // immediately instead of blocking on the FCM HTTP call.
        SendPushNotificationJob::dispatch($customer->fcm_token, $title, $body, [
            'order_number' => $order->order_number,
            'status' => $order->status,
        ]);
    }

    public function sendFcm(string $token, string $title, string $body, array $data = []): void
    {
        $serviceAccount = $this->loadServiceAccount();
        $projectId = $serviceAccount['project_id'];
        $accessToken = $this->getAccessToken($serviceAccount);

        $http = new Client(['timeout' => 10]);
        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        try {
            $http->post($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => array_map('strval', $data),
                    ],
                ],
            ]);
        } catch (ClientException $e) {
            $body = (string) $e->getResponse()->getBody();
            throw new \RuntimeException("FCM API error {$e->getResponse()->getStatusCode()}: {$body}");
        }
    }

    private function getAccessToken(array $serviceAccount): string
    {
        $credentials = new ServiceAccountCredentials(self::FCM_SCOPE, $serviceAccount);
        $token = $credentials->fetchAuthToken();

        if (empty($token['access_token'])) {
            throw new \RuntimeException('Failed to obtain Google OAuth2 access token');
        }

        return $token['access_token'];
    }

    private function loadServiceAccount(): array
    {
        $path = env('FIREBASE_CREDENTIALS', '');

        if ($path && ! str_starts_with($path, '/') && ! preg_match('/^[A-Z]:/i', $path)) {
            $path = base_path($path);
        }

        if (! $path || ! file_exists($path)) {
            throw new \RuntimeException("Firebase credentials file not found: {$path}");
        }

        $json = json_decode(file_get_contents($path), true);

        if (! is_array($json) || empty($json['project_id'])) {
            throw new \RuntimeException("Invalid Firebase credentials JSON at: {$path}");
        }

        return $json;
    }

    private function buildMessage(Order $order): array
    {
        return match ($order->status) {
            Order::STATUS_ACCEPTED => [
                '✅ تم قبول طلبك',
                'يتم الآن تحضير طلبك رقم '.$order->order_number,
            ],
            Order::STATUS_REJECTED => [
                '❌ تم رفض طلبك',
                'طلبك رقم '.$order->order_number.($order->rejection_reason ? ': '.$order->rejection_reason : ''),
            ],
            Order::STATUS_READY => [
                '🎉 طلبك جاهز!',
                'يمكنك الآن استلام طلبك رقم '.$order->order_number,
            ],
            default => ['إشعار طلب', 'تم تحديث حالة طلبك رقم '.$order->order_number],
        };
    }
}
