<?php

namespace Tests\Feature;

use App\Services\LogService;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class LoggingTest extends TestCase
{
    /** The LogService routes each level to the matching Log facade method. */
    public function test_log_service_logs_at_each_level(): void
    {
        Log::spy();

        $service = app(LogService::class);

        $service->info('test.info', ['a' => 1]);
        $service->warning('test.warning');
        $service->error('test.error');
        $service->critical('test.critical');

        Log::shouldHaveReceived('info')->once()->with('test.info', ['a' => 1]);
        Log::shouldHaveReceived('warning')->once()->with('test.warning', []);
        Log::shouldHaveReceived('error')->once()->with('test.error', []);
        Log::shouldHaveReceived('critical')->once()->with('test.critical', []);
    }

    /** error()/critical() fold a compact, non-sensitive exception summary into the context. */
    public function test_error_includes_exception_summary(): void
    {
        Log::spy();

        app(LogService::class)->error('test.boom', ['order_id' => 7], new RuntimeException('kaboom'));

        Log::shouldHaveReceived('error')->once()->withArgs(function ($event, $context) {
            return $event === 'test.boom'
                && $context['order_id'] === 7
                && $context['exception']['class'] === RuntimeException::class
                && $context['exception']['message'] === 'kaboom'
                && str_contains($context['exception']['file'], ':');
        });
    }

    /** The global middleware emits a correlation id as the X-Request-Id header. */
    public function test_request_id_header_is_present(): void
    {
        $response = $this->getJson('/api/v1/settings/public');

        $response->assertOk();
        $requestId = $response->headers->get('X-Request-Id');

        $this->assertNotNull($requestId);
        $this->assertTrue(Str::isUuid($requestId), 'X-Request-Id should be a UUID');
    }

    /** InjectLogContext populates the shared context fields on an API request. */
    public function test_context_is_populated_on_request(): void
    {
        Route::middleware('api')->get('/__test/log-context', function () {
            return response()->json(Context::all());
        });

        $response = $this->getJson('/__test/log-context');

        $response->assertOk()
            ->assertJsonStructure(['request_id', 'route', 'ip', 'user_agent']);

        $this->assertTrue(Str::isUuid($response->json('request_id')));
        $this->assertSame('__test/log-context', $response->json('route'));
    }
}
