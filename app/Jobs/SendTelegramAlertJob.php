<?php

namespace App\Jobs;

use App\Services\TelegramClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * SendTelegramAlertJob
 *
 * Queued delivery for non-infra alert categories (payment / critical). Mirrors
 * SendPushNotificationJob: 3 tries with exponential backoff. A final failure is
 * logged at WARNING (below the alert floor) so a Telegram outage cannot trigger
 * another alert — no loops.
 */
class SendTelegramAlertJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public array $bot,
        public string $text,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(TelegramClient $client): void
    {
        try {
            $client->send($this->bot, $this->text);
        } catch (\Throwable $e) {
            logService()->warning('telegram.alert.failed', [
                'bot' => $this->bot['name'] ?? '?',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
