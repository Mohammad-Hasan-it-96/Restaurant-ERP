<?php

namespace Tests\Feature;

use App\Jobs\SendPushNotificationJob;
use App\Models\Customer;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class FcmNotificationTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeCustomer(array $overrides = []): Customer
    {
        $customer = new Customer(array_merge([
            'full_name' => 'Test Customer',
            'phone' => '01000000001',
        ], $overrides));
        // Tokens are stored hashed; issueToken() stashes the plaintext on
        // $customer->plainTextToken for use as the Bearer credential in tests.
        $customer->issueToken();
        $customer->save();

        return $customer;
    }

    private function makeOrder(Customer $customer, string $status = Order::STATUS_ACCEPTED): Order
    {
        return Order::create([
            'order_number' => 'ORD-TEST-'.Str::random(4),
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
            'phone' => $customer->phone,
            'source' => 'spa',
            'order_type' => 'delivery',
            'status' => $status,
            'subtotal' => 50.00,
            'total' => 50.00,
        ]);
    }

    // ── 1. Job metadata ───────────────────────────────────────────────────────

    public function test_job_has_correct_tries(): void
    {
        $job = new SendPushNotificationJob('fake-token', 'Title', 'Body');
        $this->assertSame(3, $job->tries);
    }

    public function test_job_has_correct_backoff(): void
    {
        $job = new SendPushNotificationJob('fake-token', 'Title', 'Body');
        $this->assertSame([10, 30, 60], $job->backoff());
    }

    public function test_job_implements_should_queue(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new SendPushNotificationJob('token', 'T', 'B'),
        );
    }

    // ── 2. Job handle() swallows exceptions ───────────────────────────────────

    public function test_job_handle_swallows_throwable_on_bad_token(): void
    {
        $service = $this->createMock(NotificationService::class);
        $service->method('sendFcm')->willThrowException(
            new \RuntimeException('FCM API error 400: bad token'),
        );

        $job = new SendPushNotificationJob('invalid-token', 'Title', 'Body', ['order_number' => 'ORD-X']);

        // Should not throw — job swallows the error and logs a warning
        $job->handle($service);

        $this->assertTrue(true); // Reached here = swallowed correctly
    }

    // ── 3. NotificationService dispatches job (not sync) ─────────────────────

    public function test_notify_order_status_dispatches_job_for_accepted(): void
    {
        Queue::fake();

        $customer = $this->makeCustomer(['fcm_token' => 'ExampleFcmToken123']);
        $order = $this->makeOrder($customer, Order::STATUS_ACCEPTED);

        app(NotificationService::class)->notifyOrderStatus($order);

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) use ($order, $customer) {
            return $job->token === $customer->fcm_token
                && str_contains($job->title, 'قبول')
                && $job->data['order_number'] === $order->order_number
                && $job->data['status'] === Order::STATUS_ACCEPTED;
        });
    }

    public function test_notify_order_status_dispatches_job_for_rejected(): void
    {
        Queue::fake();

        $customer = $this->makeCustomer(['fcm_token' => 'ExampleFcmToken456']);
        $order = $this->makeOrder($customer, Order::STATUS_REJECTED);

        app(NotificationService::class)->notifyOrderStatus($order);

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) {
            return str_contains($job->title, 'رفض')
                && $job->data['status'] === Order::STATUS_REJECTED;
        });
    }

    public function test_notify_order_status_dispatches_job_for_ready(): void
    {
        Queue::fake();

        $customer = $this->makeCustomer(['fcm_token' => 'ExampleFcmToken789']);
        $order = $this->makeOrder($customer, Order::STATUS_READY);

        app(NotificationService::class)->notifyOrderStatus($order);

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) {
            return str_contains($job->title, 'جاهز')
                && $job->data['status'] === Order::STATUS_READY;
        });
    }

    public function test_notify_order_status_skips_customer_without_fcm_token(): void
    {
        Queue::fake();

        $customer = $this->makeCustomer(); // no fcm_token
        $order = $this->makeOrder($customer, Order::STATUS_ACCEPTED);

        app(NotificationService::class)->notifyOrderStatus($order);

        Queue::assertNothingPushed();
    }

    public function test_notify_order_status_skips_when_no_customer(): void
    {
        Queue::fake();

        // Order with no customer_id (detached order, edge case)
        $order = new Order([
            'order_number' => 'ORD-GHOST-001',
            'status' => Order::STATUS_ACCEPTED,
        ]);

        app(NotificationService::class)->notifyOrderStatus($order);

        Queue::assertNothingPushed();
    }

    // ── 4. POST /api/v1/customer/guest ────────────────────────────────────────

    public function test_create_guest_without_fcm_token(): void
    {
        $response = $this->postJson('/api/v1/customer/guest');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['customer_token']]);

        $token = $response->json('data.customer_token');
        $this->assertNotEmpty($token);

        $customer = Customer::where('token', Customer::hashToken($token))->first();
        $this->assertNotNull($customer);
        $this->assertNull($customer->phone);
        $this->assertSame('guest', $customer->full_name);
        $this->assertNull($customer->fcm_token);
    }

    public function test_create_guest_stores_fcm_token(): void
    {
        $fakeFcmToken = 'fake-fcm-token-'.Str::random(10);

        $response = $this->postJson('/api/v1/customer/guest', [
            'fcm_token' => $fakeFcmToken,
        ]);

        $response->assertStatus(200);

        $token = $response->json('data.customer_token');
        $customer = Customer::where('token', Customer::hashToken($token))->first();

        $this->assertNotNull($customer);
        $this->assertNull($customer->phone);
        $this->assertSame($fakeFcmToken, $customer->fcm_token);
    }

    public function test_create_guest_rejects_overlong_fcm_token(): void
    {
        $response = $this->postJson('/api/v1/customer/guest', [
            'fcm_token' => Str::random(256), // max 255
        ]);

        $response->assertStatus(422);
    }

    // ── 5. POST /api/v1/customer/fcm-token ───────────────────────────────────

    public function test_save_fcm_token_updates_customer(): void
    {
        $customer = $this->makeCustomer();
        $newToken = 'new-fcm-token-'.Str::random(10);

        $response = $this->withHeader('Authorization', 'Bearer '.$customer->plainTextToken)
            ->postJson('/api/v1/customer/fcm-token', ['fcm_token' => $newToken]);

        $response->assertStatus(200);

        $this->assertSame($newToken, $customer->fresh()->fcm_token);
    }

    public function test_save_fcm_token_is_idempotent(): void
    {
        $existingToken = 'existing-fcm-token-abc';
        $customer = $this->makeCustomer(['fcm_token' => $existingToken]);

        // Call twice with the same token
        $this->withHeader('Authorization', 'Bearer '.$customer->plainTextToken)
            ->postJson('/api/v1/customer/fcm-token', ['fcm_token' => $existingToken])
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$customer->plainTextToken)
            ->postJson('/api/v1/customer/fcm-token', ['fcm_token' => $existingToken])
            ->assertStatus(200);

        $this->assertSame($existingToken, $customer->fresh()->fcm_token);
    }

    public function test_save_fcm_token_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/customer/fcm-token', [
            'fcm_token' => 'some-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_save_fcm_token_requires_token_field(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->withHeader('Authorization', 'Bearer '.$customer->plainTextToken)
            ->postJson('/api/v1/customer/fcm-token', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fcm_token']);
    }
}
