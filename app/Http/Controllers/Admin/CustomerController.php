<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * List all customers with search + pagination.
     */
    public function index(Request $request)
    {
        $query = Customer::withCount('orders')
            ->withMax('orders', 'created_at')
            ->withSum('orders', 'total')
            ->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Show a single customer with their orders.
     */
    public function show(Customer $customer)
    {
        $orders = $customer->orders()
            ->latest()
            ->paginate(10);

        $ordersCount = $customer->orders()->count();
        $totalSpent  = (float) $customer->orders()->sum('total');
        $lastOrder   = $customer->orders()->latest()->first();

        return view('admin.customers.show', compact('customer', 'orders', 'ordersCount', 'totalSpent', 'lastOrder'));
    }

    /**
     * Block a customer.
     */
    public function block(Customer $customer)
    {
        $customer->update(['is_blocked' => true]);

        return back()->with('success', __('app.customer_blocked_success'));
    }

    /**
     * Unblock a customer.
     */
    public function unblock(Customer $customer)
    {
        $customer->update(['is_blocked' => false]);

        return back()->with('success', __('app.customer_unblocked_success'));
    }

    /**
     * Toggle the blocked status of a customer (legacy – kept for backward compat).
     */
    public function toggleBlock(Customer $customer)
    {
        $customer->update(['is_blocked' => ! $customer->is_blocked]);

        $msg = $customer->is_blocked
            ? __('app.customer_blocked_success')
            : __('app.customer_unblocked_success');

        return back()->with('success', $msg);
    }
}

