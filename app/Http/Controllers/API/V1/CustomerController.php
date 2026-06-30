<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Resources\V1\OrderResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
     * Returns the session customer's recent orders (most recent first, capped so
     * the payload can't grow unbounded with history). Empty array when no session.
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
            ->limit(50)
            ->get();

        return $this->success(OrderResource::collection($orders));
    }

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

        $customer = new Customer([
            'full_name' => 'guest',
            'phone' => null,
            'fcm_token' => $request->input('fcm_token'),
        ]);
        $plainToken = $customer->issueToken(); // sets the hashed token + plaintext
        $customer->save();

        return $this->success([
            'customer_token' => $plainToken,
        ], 'Guest created.');
    }

    /**
     * POST /api/v1/customer/update
     *
     * Updates the current customer's profile, or promotes a guest / creates a
     * new customer when the submitted phone is not yet registered.
     *
     * SECURITY (account-takeover prevention): a phone number that already belongs
     * to a *different* customer may NOT be claimed here. Previously a guest (or an
     * anonymous caller) could submit a victim's phone and the endpoint would merge
     * into — and return the Bearer token of — that victim's account, with no proof
     * of phone ownership. Reclaiming an existing phone requires an out-of-band
     * verification (OTP) we don't have, so we reject instead of merging.
     */
    public function update(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('customer');

        // A guest is a customer row with no phone yet; it may set a phone for the
        // first time. The unique rule ignores the caller's own row so they can
        // re-save their own phone unchanged.
        $isGuest = $customer && is_null($customer->phone);

        $validated = $request->validate([
            // Same character allowlist as StoreOrderRequest — keeps formula/control
            // characters out of the name that later feeds CSV/XLSX exports.
            'name' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s\-.\']+$/u'],
            'phone' => array_filter([
                'required', 'string', 'max:30',
                // Guests skip the built-in unique rule because we enforce ownership
                // explicitly below (and want a controlled 409, not a validation 422).
                $isGuest
                    ? null
                    : ($customer
                        ? Rule::unique('customers', 'phone')->ignore($customer->id)
                        : Rule::unique('customers', 'phone')),
            ]),
            'address' => 'nullable|string|max:500',
        ]);

        // Ownership guard: if this phone is already registered to a customer that
        // is NOT the caller, refuse — never hand back another customer's token or
        // mutate their record. Covers both the guest path and the no-session path.
        $existing = Customer::where('phone', $validated['phone'])->first();
        if ($existing && (! $customer || $existing->id !== $customer->id)) {
            return $this->error('This phone number is already registered.', 409);
        }

        // Determine the plaintext token to return: a freshly-issued one for a new
        // customer, otherwise echo back the caller's own Bearer token (the stored
        // token is hashed, so the server never has the existing plaintext).
        if (! $customer) {
            // Brand-new visitor with no session: create their own fresh customer row.
            $customer = new Customer(['full_name' => $validated['name'], 'phone' => $validated['phone']]);
            $plainToken = $customer->issueToken();
        } else {
            $plainToken = $request->bearerToken();
        }

        if ($customer->is_blocked) {
            return $this->error('This account is blocked.', 403);
        }

        // Update the caller's own record (guests are promoted in place, keeping
        // their existing row and token — no cross-account transfer).
        $customer->fill([
            'full_name' => $validated['name'],
            'phone' => $validated['phone'],
            'default_address' => $validated['address'] ?? $customer->default_address,
        ])->save();

        return $this->success([
            'id' => $customer->id,
            'name' => $customer->full_name,
            'phone' => $customer->phone,
            'default_address' => $customer->default_address,
            'token' => $plainToken,
        ], 'Profile updated successfully.');
    }
}
