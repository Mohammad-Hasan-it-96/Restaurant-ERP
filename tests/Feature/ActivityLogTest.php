<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_a_business_event_with_properties(): void
    {
        activity()->log('order.accepted', null, 'Order #X', ['from' => 'pending', 'to' => 'accepted']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'order.accepted',
            'description' => 'Order #X',
        ]);

        $this->assertSame(['from' => 'pending', 'to' => 'accepted'], ActivityLog::first()->properties);
    }

    public function test_causer_defaults_to_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'name' => 'Admin Bob']);
        $this->actingAs($user);

        activity()->log('settings.updated', null, 'updated');

        $log = ActivityLog::first();
        $this->assertSame($user->getMorphClass(), $log->causer_type);
        $this->assertSame($user->id, $log->causer_id);
        $this->assertSame('Admin Bob', $log->causer_label);
    }

    public function test_row_survives_subject_deletion(): void
    {
        $customer = Customer::create(['full_name' => 'Jane Doe', 'phone' => '0900000001']);

        activity()->log('customer.blocked', $customer, 'Customer blocked: '.$customer->full_name);
        $customer->delete();

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'customer.blocked',
            'subject_label' => 'Jane Doe',
        ]);
    }

    public function test_customer_can_be_the_causer(): void
    {
        $customer = Customer::create(['full_name' => 'Buyer', 'phone' => '0900000002']);

        activity()->log('order.placed', null, 'Order #Y', [], $customer);

        $log = ActivityLog::first();
        $this->assertSame($customer->getMorphClass(), $log->causer_type);
        $this->assertSame('Buyer', $log->causer_label);
    }

    public function test_disabled_flag_makes_logging_a_noop(): void
    {
        config(['activitylog.enabled' => false]);

        activity()->log('order.accepted');

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_prune_deletes_only_entries_past_retention(): void
    {
        ActivityLog::create(['action' => 'order.placed', 'created_at' => now()->subDays(400)]);
        ActivityLog::create(['action' => 'order.placed', 'created_at' => now()->subDays(10)]);

        config(['activitylog.retention_days' => 365]);
        Artisan::call('activitylog:prune');

        $this->assertDatabaseCount('activity_logs', 1);
    }
}
