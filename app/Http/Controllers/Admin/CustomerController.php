<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * List all customers with search (name / phone) + sortable pagination.
     */
    public function index(Request $request)
    {
        $allowed = ['created_at', 'full_name', 'orders_count', 'orders_sum_total'];
        $sortBy = in_array($request->input('sort'), $allowed) ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $query = Customer::withCount('orders')
            ->withMax('orders', 'created_at')   // → orders_max_created_at  (last_order_at)
            ->withSum('orders', 'total')
            ->orderBy($sortBy, $direction);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Show a single customer with their paginated orders.
     */
    public function show(Request $request, Customer $customer)
    {
        $orders = $customer->orders()->latest()->paginate(10);
        // Derive count + latest order from the paginator (no extra queries on
        // page 1); only the all-time sum needs its own aggregate query.
        $ordersCount = $orders->total();
        $totalSpent = (float) $customer->orders()->sum('total');
        $lastOrder = $orders->currentPage() === 1
            ? $orders->first()
            : $customer->orders()->latest()->first();

        $back = $request->headers->get('referer', route('admin.customers.index'));

        return view('admin.customers.show',
            compact('customer', 'orders', 'ordersCount', 'totalSpent', 'lastOrder', 'back'));
    }

    /**
     * Block a customer (optionally store a reason).
     */
    public function block(Request $request, Customer $customer)
    {
        $request->validate([
            'blocked_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $customer->update([
            'is_blocked' => true,
            'blocked_reason' => $request->input('blocked_reason') ?: null,
        ]);

        activity()->log('customer.blocked', $customer, 'Customer blocked: '.$customer->full_name, [
            'reason' => $customer->blocked_reason,
        ]);

        return back()->with('success', __('app.customer_blocked_success'));
    }

    /**
     * Unblock a customer and clear the stored reason.
     */
    public function unblock(Customer $customer)
    {
        $customer->update([
            'is_blocked' => false,
            'blocked_reason' => null,
        ]);

        activity()->log('customer.unblocked', $customer, 'Customer unblocked: '.$customer->full_name);

        return back()->with('success', __('app.customer_unblocked_success'));
    }

    /**
     * Toggle the blocked status of a customer.
     * When blocking via toggle an optional `blocked_reason` param is accepted.
     */
    public function toggleBlock(Request $request, Customer $customer)
    {
        $request->validate([
            'blocked_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $nowBlocked = ! $customer->is_blocked;

        $customer->update([
            'is_blocked' => $nowBlocked,
            'blocked_reason' => $nowBlocked ? ($request->input('blocked_reason') ?: null) : null,
        ]);

        activity()->log(
            $nowBlocked ? 'customer.blocked' : 'customer.unblocked',
            $customer,
            ($nowBlocked ? 'Customer blocked: ' : 'Customer unblocked: ').$customer->full_name,
            $nowBlocked ? ['reason' => $customer->blocked_reason] : [],
        );

        $msg = $nowBlocked
            ? __('app.customer_blocked_success')
            : __('app.customer_unblocked_success');

        return back()->with('success', $msg);
    }
}
