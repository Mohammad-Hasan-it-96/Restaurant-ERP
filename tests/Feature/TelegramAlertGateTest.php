<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramAlertJob;
use App\Services\TelegramAlertGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelegramAlertGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'alerts.enabled' => true,
            'alerts.environments' => ['testing'],
            'alerts.min_level' => 'error',
            'alerts.bots' => [[
                'name' => 'default',
                'token' => 'TESTTOKEN',
                'chat_id' => '123',
                'categories' => ['*'],
            ]],
        ]);
    }

    private function gate(): TelegramAlertGate
    {
        return app(TelegramAlertGate::class);
    }

    private function record(string $level, string $message, array $context = []): array
    {
        return [
            'level' => strtoupper($level),
            'level_value' => $level === 'critical' ? 500 : 400,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function test_critical_is_queued(): void
    {
        Queue::fake();

        $this->gate()->handle($this->record('critical', 'something.bad'));

        Queue::assertPushed(SendTelegramAlertJob::class, 1);
    }

    public function test_payment_error_is_queued(): void
    {
        Queue::fake();

        $this->gate()->handle($this->record('error', 'payment.failed'));

        Queue::assertPushed(SendTelegramAlertJob::class, 1);
    }

    public function test_routine_error_is_ignored(): void
    {
        Queue::fake();
        Http::fake();

        $this->gate()->handle($this->record('error', 'product.import.read_failed'));

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_warning_below_floor_is_ignored(): void
    {
        Queue::fake();

        $this->gate()->handle([
            'level' => 'WARNING',
            'level_value' => 300,
            'message' => 'payment.failed',
            'context' => [],
        ]);

        Queue::assertNothingPushed();
    }

    public function test_database_category_sends_synchronously(): void
    {
        Queue::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->gate()->handle($this->record('error', 'db.connection_lost'));

        Queue::assertNothingPushed();
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.telegram.org'));
    }

    public function test_disabled_environment_sends_nothing(): void
    {
        config(['alerts.environments' => ['production']]);
        Queue::fake();
        Http::fake();

        $this->gate()->handle($this->record('critical', 'something.bad'));

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_duplicate_is_suppressed(): void
    {
        Queue::fake();
        $record = $this->record('critical', 'dup.event');

        $this->gate()->handle($record);
        $this->gate()->handle($record);

        Queue::assertPushed(SendTelegramAlertJob::class, 1);
    }

    public function test_rate_limit_caps_per_category(): void
    {
        config(['alerts.rate_limit' => ['max' => 1, 'per_seconds' => 300]]);
        Queue::fake();

        $this->gate()->handle($this->record('critical', 'first.event'));
        $this->gate()->handle($this->record('critical', 'second.event'));

        Queue::assertPushed(SendTelegramAlertJob::class, 1);
    }
}
