<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ResolveCustomerByToken
 *
 * Reads  Authorization: Bearer <token>  header.
 * Finds the customer by token in DB.
 * Attaches the model to $request->attributes so controllers can do:
 *   $customer = $request->attributes->get('customer');
 *
 * Never blocks — guests are welcome (customer will be null).
 */
class ResolveCustomerByToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token    = null;
        $customer = null;

        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            $token = trim(substr($header, 7));
        }

        if ($token) {
            $customer = Customer::query()->where('token', $token)->first();

            if ($customer) {
                Log::debug('customer.resolved', ['id' => $customer->id, 'token' => substr($token, 0, 8) . '...']);
                $request->attributes->set('customer', $customer);
            } else {
                Log::debug('customer.token.invalid', ['token' => substr($token, 0, 8) . '...']);
            }
        } else {
            Log::debug('customer.session', ['id' => null]);
        }

        return $next($request);
    }
}

