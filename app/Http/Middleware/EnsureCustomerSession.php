<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureCustomerSession
 *
 * A lightweight, guest-based middleware that reads the customer_id stored in
 * the session (set automatically when an order is placed) and attaches the
 * resolved Customer model to the request as `$request->customer`.
 *
 * If no session customer exists the request is allowed through unchanged —
 * the visitor is treated as a guest.  No authentication or login is required.
 *
 * Usage (routes/api.php):
 *   Route::middleware('customer.session')->group(function () { ... });
 */
class EnsureCustomerSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $customerId = session()->get('customer_id');

        Log::debug('customer.session', ['id' => $customerId ?? null]);

        if ($customerId) {
            $customer = Customer::findOrFail($customerId);

            if ($customer) {
                // Make the model available throughout the request lifecycle
                $request->merge(['_resolved_customer' => $customer]);
                // Also attach via request macro-style attribute for convenience
                $request->attributes->set('customer', $customer);
            } else {
                // Stale / deleted customer — clean up the orphaned session value
                session()->forget('customer_id');
            }
        }

        // Always continue — guests are welcome
        return $next($request);
    }
}
