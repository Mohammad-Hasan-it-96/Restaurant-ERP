<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPushNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of attempts before the job is marked failed.
     */
    public int $tries = 3;

    public function __construct(
        public string $token,
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    /**
     * Retry transient FCM/network errors with increasing delays (seconds).
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(NotificationService $service): void
    {
        try {
            $service->sendFcm($this->token, $this->title, $this->body, $this->data);
        } catch (\Throwable $e) {
            // Mirror the original swallow-and-log behaviour: a bad token or a
            // transient FCM failure must never crash the worker.
            logService()->warning('fcm.notification.failed', [
                'data' => $this->data,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
