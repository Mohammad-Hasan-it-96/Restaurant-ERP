<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CustomerController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/customer/me
     *
     * Returns the customer linked to the current session (if any).
     * No auth required — purely session-based (guest flow).
     */
    public function me(): JsonResponse
    {
        $customerId = session('customer_id');

        if (! $customerId) {
            return $this->success(null);          // 200 with data: null → no session yet
        }

        $customer = Customer::find($customerId);

        if (! $customer) {
            // Stale session — clean it up
            session()->forget('customer_id');
            return $this->success(null);
        }

        return $this->success([
            'name'  => $customer->full_name,
            'phone' => $customer->phone,
        ]);
    }
}

