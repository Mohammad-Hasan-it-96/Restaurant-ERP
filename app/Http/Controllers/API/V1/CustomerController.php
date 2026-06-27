<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Resources\V1\OrderResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/customer/me
     *
     * Returns the customer linked to the current session.
     * Always returns consistent shape — never null.
     */
    public function me(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('customer');

        if (! $customer) {
            return $this->success([]);   // empty array — no session yet, no null
        }

        // Don't leak order history through `me` when the feature is off — the
        // dedicated customer/orders endpoint is already route-gated on the same flag.
        $orders = \App\Support\Feature::enabled('customer.order_history')
            ? \App\Models\Order::where('customer_id', $customer->id)
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id', 'order_number', 'status', 'total', 'created_at', 'order_type'])
            : collect();

        // A guest is a customer with no phone yet (see update()). Return an empty
        // name so the SPA shows a blank, fillable field instead of the "guest"
        // sentinel stored on the row.
        $isGuest = is_null($customer->phone);

        return $this->success([
            'id' => $customer->id,
            'name' => $isGuest ? '' : $customer->full_name,
            'phone' => $customer->phone,
            'default_address' => $customer->default_address,
            'is_guest' => $isGuest,
            'orders' => $orders,
        ]);
    }

    /**
     * GET /api/v1/customer/orders
     *
     * Returns all orders belonging to the session customer.
     * Returns empty array when no session.
     */
    public function orders(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('customer');

        if (! $customer) {
            return $this->success([]);
        }

        $orders = \App\Models\Order::query()->where('customer_id', $customer->id)
            ->with('items')
            ->latest()
            ->get();

        return $this->success(OrderResource::collection($orders));
    }

    /**
     * POST /api/v1/customer/update
     *
     * Works for both:
     * - Guest (phone=null) → merge into existing customer or promote to real
     * - Existing session → update the linked customer
     * - New visitor (no session yet) → find-or-create by phone, bind to session
     */

    /**
     * POST /api/v1/customer/fcm-token
     *
     * Saves the customer's Firebase Cloud Messaging token so push notifications
     * can be sent when order status changes.
     */
    public function saveFcmToken(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('customer');

        if (! $customer) {
            return $this->error('Unauthorized — no customer session.', 401);
        }

        $validated = $request->validate([
            'fcm_token' => 'required|string|max:255',
        ]);

        // Idempotent: only write when the token actually changed, so repeated
        // calls with the same token don't issue duplicate UPDATEs.
        if ($customer->fcm_token !== $validated['fcm_token']) {
            $customer->update(['fcm_token' => $validated['fcm_token']]);

            logService()->info('fcm.token.updated', [
                'customer_id' => $customer->id,
                'token_prefix' => substr($validated['fcm_token'], 0, 12), // never log the full token
            ]);
        }

        return $this->success(null, 'Token saved.');
    }

    /**
     * POST /api/v1/customer/guest
     *
     * Creates an anonymous "guest" customer record so we can store their FCM
     * token before they place their first order.  Called by the frontend when
     * the visitor grants notification permission but has no customer_token yet.
     */
    public function createGuest(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'nullable|string|max:255',
        ]);

        $customer = Customer::create([
            'full_name' => 'guest',
            'phone' => null,
            'token' => (string) Str::uuid(),
            'fcm_token' => $request->input('fcm_token'),
        ]);

        return $this->success([
            'customer_token' => $customer->token,
        ], 'Guest created.');
    }

    /**
     * POST /api/v1/customer/update  (extended)
     *
     * Extra behaviour when $customer is a guest (phone = null):
     * if the provided phone already belongs to another customer, merge this
     * guest into that customer — transfer the FCM token if needed, then
     * delete the orphaned guest row and return the real customer's token.
     */
    public function update(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('customer');

        // For guests (phone=null) we need a different unique rule: allow the
        // phone even if it exists (we'll merge instead of erroring).
        $isGuest = $customer && is_null($customer->phone);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => array_filter([
                'required', 'string', 'max:30',
                $isGuest
                    ? null  // skip unique check — we handle merge manually below
                    : ($customer
                        ? Rule::unique('customers', 'phone')->ignore($customer->id)
                        : Rule::unique('customers', 'phone')),
            ]),
            'address' => 'nullable|string|max:500',
        ]);

        // ── Guest merge: phone belongs to an existing real customer ──────────
        if ($isGuest) {
            $existing = Customer::where('phone', $validated['phone'])->first();

            if ($existing) {
                // Transfer FCM token if the real customer doesn't have one yet
                if (! $existing->fcm_token && $customer->fcm_token) {
                    $existing->update(['fcm_token' => $customer->fcm_token]);
                }
                // Update name/address on the real customer
                $existing->update([
                    'full_name' => $validated['name'],
                    'default_address' => $validated['address'] ?? $existing->default_address,
                ]);
                // Ensure token exists
                if (! $existing->token) {
                    $existing->update(['token' => (string) Str::uuid()]);
                }
                // Clean up the now-orphaned guest row
                $customer->delete();

                return $this->success([
                    'id' => $existing->id,
                    'name' => $existing->full_name,
                    'phone' => $existing->phone,
                    'default_address' => $existing->default_address,
                    'token' => $existing->token,
                ], 'Profile updated successfully.');
            }
        }

        // ── Normal flow (non-guest, or guest with new phone) ─────────────────
        if (! $customer) {
            $customer = Customer::firstOrCreate(
                ['phone' => $validated['phone']],
                ['full_name' => $validated['name']]
            );
            if (! $customer->token) {
                $customer->update(['token' => (string) Str::uuid()]);
            }
        }

        if ($customer->is_blocked) {
            return $this->error('This account is blocked.', 403);
        }

        $customer->update([
            'full_name' => $validated['name'],
            'phone' => $validated['phone'],
            'default_address' => $validated['address'] ?? $customer->default_address,
        ]);

        return $this->success([
            'id' => $customer->id,
            'name' => $customer->full_name,
            'phone' => $customer->phone,
            'default_address' => $customer->default_address,
            'token' => $customer->token,
        ], 'Profile updated successfully.');
    }
}
