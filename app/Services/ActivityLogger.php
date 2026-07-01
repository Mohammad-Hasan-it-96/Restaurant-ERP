<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

/**
 * ActivityLogger — the single write path for the business audit trail.
 *
 * Resolve via the container or the global activity() helper:
 *   activity()->log('order.accepted', $order, 'Order #'.$order->order_number,
 *       ['from' => 'pending', 'to' => 'accepted']);
 *
 * Causer defaults to the authenticated admin/moderator; pass a Customer for
 * customer-initiated events, or null for system actions. Writing an audit row
 * must NEVER break the business action — failures are swallowed and logged at
 * warning through LogService.
 */
class ActivityLogger
{
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?Model $causer = null,
    ): void {
        if (! config('activitylog.enabled', true)) {
            return;
        }

        try {
            $causer = $causer ?: Auth::user();

            ActivityLog::create([
                'causer_type' => $causer?->getMorphClass(),
                'causer_id' => $causer?->getKey(),
                'causer_label' => $this->causerLabel($causer),
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject ? $this->subjectLabel($subject) : null,
                'action' => $action,
                'description' => $description,
                'properties' => $properties ?: null,
                'ip_address' => Context::get('ip'),
                'request_id' => Context::get('request_id'),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            logService()->warning('activity.log.failed', ['action' => $action], $e);
        }
    }

    private function causerLabel(?Model $causer): ?string
    {
        return match (true) {
            $causer instanceof User => $causer->name ?: $causer->email,
            $causer instanceof Customer => $causer->full_name ?: $causer->phone,
            $causer === null => null,
            default => class_basename($causer).' #'.$causer->getKey(),
        };
    }

    private function subjectLabel(Model $subject): ?string
    {
        return match (true) {
            $subject instanceof Order => $subject->order_number,
            $subject instanceof Product => $subject->name_ar ?: $subject->name,
            $subject instanceof Customer => $subject->full_name ?: $subject->phone,
            $subject instanceof SystemConfig => $subject->key,
            default => class_basename($subject).' #'.$subject->getKey(),
        };
    }
}
