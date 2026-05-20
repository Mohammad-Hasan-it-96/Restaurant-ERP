<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Customer;
use App\Http\Resources\V1\OrderResource;
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

        $orders = \App\Models\Order::where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'order_number', 'status', 'total', 'created_at', 'order_type']);

        return $this->success([
            'id'              => $customer->id,
            'name'            => $customer->full_name,
            'phone'           => $customer->phone,
            'default_address' => $customer->default_address,
            'orders'          => $orders,
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
     * - Existing session → update the linked customer
     * - New visitor (no session yet) → find-or-create by phone, bind to session
     */
    public function update(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('customer');

        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'phone'   => [
                'required',
                'string',
                'max:30',
                // If customer exists, ignore their own record in unique check
                $customer
                    ? Rule::unique('customers', 'phone')->ignore($customer->id)
                    : Rule::unique('customers', 'phone'),
            ],
            'address' => 'nullable|string|max:500',
        ]);


        if (! $customer) {
            $customer = Customer::firstOrCreate(
                ['phone' => $validated['phone']],
                ['full_name' => $validated['name']]
            );
            // Generate token for new customer so they can authenticate next time
            if (! $customer->token) {
                $customer->update(['token' => (string) Str::uuid()]);
            }
        }

        if ($customer->is_blocked) {
            return $this->error('This account is blocked.', 403);
        }

        $customer->update([
            'full_name'       => $validated['name'],
            'phone'           => $validated['phone'],
            'default_address' => $validated['address'] ?? $customer->default_address,
        ]);

        return $this->success([
            'id'              => $customer->id,
            'name'            => $customer->full_name,
            'phone'           => $customer->phone,
            'default_address' => $customer->default_address,
            'token'           => $customer->token,
        ], 'Profile updated successfully.');
    }
}
