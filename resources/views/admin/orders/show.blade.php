@extends('layouts.app')

@section('title', __('app.order_details') . ' — ' . $order->order_number)

@section('content')
<div class="container-fluid py-4">

    {{-- Header row --}}
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <a href="{{ $back ?? route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}-short fs-5"></i>
        </a>
        <h2 class="fw-bold mb-0 me-auto">
            <i class="bi bi-receipt me-2 text-primary"></i>
            {{ $order->order_number }}
        </h2>
        @include('admin.orders.partials.status-badge', ['status' => $order->status])

        {{-- Action Buttons --}}
        @if($order->status === 'pending')
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#acceptModal">
                <i class="bi bi-check-lg me-1"></i>{{ __('app.accept') }}
            </button>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-lg me-1"></i>{{ __('app.reject') }}
            </button>
        @endif

        @if($order->status === 'accepted')
            <form method="POST" action="{{ route('admin.orders.preparing', $order) }}" class="d-inline">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="bi bi-fire me-1"></i>{{ __('app.mark_preparing') }}
                </button>
            </form>
        @endif

        @if($order->status === 'preparing')
            <form method="POST" action="{{ route('admin.orders.ready', $order) }}" class="d-inline">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-info btn-sm text-white">
                    <i class="bi bi-check2-all me-1"></i>{{ __('app.mark_ready') }}
                </button>
            </form>
        @endif

        @if($order->status === 'ready')
            @if($order->order_type === 'delivery')
                <form method="POST" action="{{ route('admin.orders.delivered', $order) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-truck me-1"></i>{{ __('app.mark_delivered') }}
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.orders.completed', $order) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-bag-check me-1"></i>{{ __('app.mark_completed') }}
                    </button>
                </form>
            @endif
        @endif

        @if($order->status === 'delivered')
            <form method="POST" action="{{ route('admin.orders.completed', $order) }}" class="d-inline">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-bag-check me-1"></i>{{ __('app.mark_completed') }}
                </button>
            </form>
        @endif

        @if(!in_array($order->status, ['completed', 'rejected', 'cancelled', 'cancelled_by_admin', 'cancelled_by_customer']))
            <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                <i class="bi bi-slash-circle me-1"></i>{{ __('app.cancel') }}
            </button>
        @endif

        {{-- Print Invoice --}}
        @if(in_array($order->status, ['accepted', 'completed']))
        <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>{{ __('app.print_invoice') }}
        </a>
        @endif
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-4">

        {{-- ── Left column: customer + order info ── --}}
        <div class="col-lg-5">

            {{-- WhatsApp Notification Panel --}}
            @include('admin.orders.partials.whatsapp-notify')

            {{-- Customer Card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-bold">
                    <i class="bi bi-person-circle me-2"></i>{{ __('app.customer_info') }}
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">{{ __('app.customer_name') }}</dt>
                        <dd class="col-7">{{ $order->customer_name ?? '—' }}</dd>

                        <dt class="col-5 text-muted">{{ __('app.phone') }}</dt>
                        <dd class="col-7" dir="ltr">{{ $order->phone ?? '—' }}</dd>

                        @if($order->customer && $order->customer->is_blocked)
                        <dd class="col-12">
                            <span class="badge bg-danger">{{ __('app.customer_blocked') }}</span>
                            @if($order->customer->blocked_reason)
                            <small class="text-muted ms-1">{{ $order->customer->blocked_reason }}</small>
                            @endif
                        </dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Order Info Card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-bold">
                    <i class="bi bi-info-circle me-2"></i>{{ __('app.order_info') }}
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6 text-muted">{{ __('app.order_number') }}</dt>
                        <dd class="col-6 fw-semibold">{{ $order->order_number }}</dd>

                        <dt class="col-6 text-muted">{{ __('app.order_type') }}</dt>
                        <dd class="col-6">{{ __('app.' . $order->order_type) }}</dd>

                        @if($order->order_type === 'table' && $order->table_number)
                        <dt class="col-6 text-muted">{{ __('app.table_number') }}</dt>
                        <dd class="col-6">{{ $order->table_number }}</dd>
                        @endif

                        @if($order->order_type === 'delivery')
                        <dt class="col-6 text-muted">{{ __('app.address') }}</dt>
                        <dd class="col-6">{{ $order->address ?? '—' }}</dd>

                        <dt class="col-6 text-muted">{{ __('app.delivery_type') }}</dt>
                        <dd class="col-6">{{ $order->delivery_type ? __('app.' . $order->delivery_type) : '—' }}</dd>

                        @if($order->scheduled_at)
                        <dt class="col-6 text-muted">{{ __('app.scheduled_at') }}</dt>
                        <dd class="col-6">{{ $order->scheduled_at->format('Y-m-d H:i') }}</dd>
                        @endif
                        @endif

                        @if($order->customer_note)
                        <dt class="col-6 text-muted">{{ __('app.customer_note') }}</dt>
                        <dd class="col-6">{{ $order->customer_note }}</dd>
                        @endif

                        @if($order->rejection_reason)
                        <dt class="col-6 text-muted">{{ __('app.rejection_reason') }}</dt>
                        <dd class="col-6 text-danger">{{ $order->rejection_reason }}</dd>
                        @endif

                        <dt class="col-6 text-muted">{{ __('app.source') }}</dt>
                        <dd class="col-6">{{ $order->source ?? '—' }}</dd>

                        <dt class="col-6 text-muted">{{ __('app.created_at') }}</dt>
                        <dd class="col-6">{{ $order->created_at->format('Y-m-d H:i') }}</dd>

                        @if($order->accepted_at)
                        <dt class="col-6 text-muted">{{ __('app.accepted_at') }}</dt>
                        <dd class="col-6">{{ $order->accepted_at->format('Y-m-d H:i') }}</dd>
                        @endif

                        @if($order->completed_at)
                        <dt class="col-6 text-muted">{{ __('app.completed_at') }}</dt>
                        <dd class="col-6">{{ $order->completed_at->format('Y-m-d H:i') }}</dd>
                        @endif

                        @if($order->cancelled_at)
                        <dt class="col-6 text-muted">{{ __('app.cancelled_at') }}</dt>
                        <dd class="col-6">{{ $order->cancelled_at->format('Y-m-d H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

        </div>

        {{-- ── Right column: items + totals ── --}}
        <div class="col-lg-7">

            {{-- Items --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-bold">
                    <i class="bi bi-bag me-2"></i>{{ __('app.order_items') }}
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('app.product_name') }}</th>
                                    <th class="text-center">{{ __('app.quantity') }}</th>
                                    <th class="text-end">{{ __('app.unit_price') }}</th>
                                    <th class="text-end">{{ __('app.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-center">
                                        @if($item->weight_name)
                                            <span class="fw-semibold">{{ $item->quantity }} × {{ $item->weight_name }}</span>
                                            @if($item->weight_value_kg)
                                                <small class="text-muted d-block">
                                                    ({{ rtrim(rtrim(number_format($item->weight_value_kg, 3), '0'), '.') }} كغ)
                                                </small>
                                            @endif
                                        @else
                                            {{ $item->quantity }}
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($item->product_price, 2) }}
                                        @if($item->weight_name && $item->weight_value_kg > 0)
                                            <small class="text-muted d-block">
                                                {{ number_format($item->product_price / $item->weight_value_kg, 2) }} / كغ
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Totals --}}
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    <i class="bi bi-calculator me-2"></i>{{ __('app.order_summary') }}
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6 text-muted">{{ __('app.subtotal') }}</dt>
                        <dd class="col-6 text-end">{{ number_format($order->subtotal, 2) }}</dd>

                        @if($order->estimated_delivery_fee !== null)
                        <dt class="col-6 text-muted">{{ __('app.estimated_delivery_fee') }}</dt>
                        <dd class="col-6 text-end text-muted">{{ number_format($order->estimated_delivery_fee, 2) }}</dd>
                        @endif

                        <dt class="col-6 text-muted">{{ __('app.delivery_fee') }}</dt>
                        <dd class="col-6 text-end">{{ $order->delivery_fee !== null ? number_format($order->delivery_fee, 2) : '—' }}</dd>

                        @if($order->discount)
                        <dt class="col-6 text-muted">{{ __('app.discount') }}</dt>
                        <dd class="col-6 text-end text-danger">- {{ number_format($order->discount, 2) }}</dd>
                        @endif

                        <dt class="col-6 fw-bold border-top pt-2">{{ __('app.total') }}</dt>
                        <dd class="col-6 text-end fw-bold fs-5 border-top pt-2 text-primary">{{ number_format($order->total, 2) }}</dd>

                        <dt class="col-6 text-muted">{{ __('app.payment_status') }}</dt>
                        <dd class="col-6 text-end">
                            @php $ps = $order->payment_status ?? 'unpaid'; @endphp
                            @if($ps === 'paid')
                                <span class="badge bg-success fs-6">
                                    <i class="bi bi-check-circle me-1"></i>{{ __('app.paid') }}
                                </span>
                            @elseif($ps === 'refunded')
                                <span class="badge bg-warning text-dark">{{ __('app.refunded') }}</span>
                            @else
                                @if(!in_array($order->status, ['rejected', 'cancelled', 'cancelled_by_admin', 'cancelled_by_customer']))
                                <form method="POST" action="{{ route('admin.orders.mark-paid', $order) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-sm"
                                        onclick="return confirm('{{ __('app.confirm_mark_paid') }}')">
                                        <i class="bi bi-cash-coin me-1"></i>{{ __('app.mark_paid') }}
                                    </button>
                                </form>
                                @else
                                    <span class="badge bg-secondary">{{ __('app.' . $ps) }}</span>
                                @endif
                            @endif
                        </dd>
                        @if($order->payment_method)
                        <dt class="col-6 text-muted">{{ __('app.payment_method') }}</dt>
                        <dd class="col-6 text-end">{{ __('app.payment_method_' . $order->payment_method) }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- Modals --}}
@include('admin.orders.partials.modals')
@endsection

