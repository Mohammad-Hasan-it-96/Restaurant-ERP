{{-- ══════════════════════════════════════════════════════════
     MODALS for order actions (Accept / Reject / Cancel / Complete)
     Include this partial inside the show page.
     Variables needed: $order, $rejectionReasons
═══════════════════════════════════════════════════════════ --}}

{{-- ── ACCEPT MODAL ──────────────────────────────────────────── --}}
@if($order->status === 'pending')
<div class="modal fade" id="acceptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.orders.accept', $order) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle text-primary me-2"></i>{{ __('app.accept_order') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                @if($order->order_type === 'delivery')
                {{-- ══ DELIVERY ORDER: show address + zones reference + fee input ══ --}}
                <div class="modal-body">

                    {{-- Customer address --}}
                    @if($order->address)
                    <div class="alert alert-light border d-flex align-items-start gap-2 py-2 mb-3">
                        <i class="bi bi-geo-alt-fill text-primary mt-1 flex-shrink-0"></i>
                        <div>
                            <div class="fw-semibold small text-muted mb-0">{{ __('app.address') }}</div>
                            <div>{{ $order->address }}</div>
                        </div>
                    </div>
                    @endif

                    {{-- Delivery zones reference list --}}
                    @if($deliveryZones->isNotEmpty())
                    <div class="mb-3">
                        <p class="fw-semibold small mb-1">
                            <i class="bi bi-map me-1 text-primary"></i>{{ __('app.delivery_zones') }}
                        </p>
                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-striped mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('app.area_name') }}</th>
                                        <th class="text-end">{{ __('app.price') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($deliveryZones as $zone)
                                    <tr>
                                        <td>{{ $zone->area_name }}</td>
                                        <td class="text-end fw-semibold">
                                            {{ number_format($zone->estimated_fee, 2) }}
                                            <span class="text-muted fw-normal">{{ __('app.currency') }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- Manual fee input --}}
                    <div class="mb-1">
                        <label class="form-label fw-semibold" for="deliveryFeeInput">{{ __('app.delivery_fee') }}</label>
                        <div class="input-group">
                            <input type="number" id="deliveryFeeInput" name="delivery_fee" class="form-control"
                                   min="0" step="0.01"
                                   value="{{ $order->estimated_delivery_fee ?? 0 }}"
                                   placeholder="0.00">
                            <span class="input-group-text">{{ __('app.currency') }}</span>
                        </div>
                        <div class="form-text">{{ __('app.delivery_fee_hint') }}</div>
                    </div>
                </div>

                @else
                {{-- ══ DINE-IN / TAKEAWAY: simple confirmation ══ --}}
                <div class="modal-body">
                    <p class="mb-0">{{ __('app.accept_order_confirm') }}</p>
                </div>
                @endif

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('app.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>{{ __('app.accept') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── REJECT MODAL ──────────────────────────────────────────── --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.orders.reject', $order) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-x-circle text-danger me-2"></i>{{ __('app.reject_order') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('app.rejection_reason') }} <span class="text-danger">*</span></label>
                        @if(!empty($rejectionReasons))
                        <select name="rejection_reason" class="form-select mb-2" id="rejectSelect">
                            <option value="">— {{ __('app.select_reason') }} —</option>
                            @foreach($rejectionReasons as $reason)
                            <option value="{{ $reason }}">{{ $reason }}</option>
                            @endforeach
                            <option value="__custom__">{{ __('app.custom_reason') }}</option>
                        </select>
                        <input type="text" id="rejectCustomInput" class="form-control d-none"
                               placeholder="{{ __('app.type_reason') }}">
                        <input type="hidden" name="rejection_reason" id="rejectFinalReason">
                        @else
                        <input type="text" name="rejection_reason" class="form-control" required
                               placeholder="{{ __('app.type_reason') }}">
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('app.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-lg me-1"></i>{{ __('app.reject') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ── CANCEL MODAL ──────────────────────────────────────────── --}}
@php
    $terminal = ['completed', 'rejected', 'cancelled', 'cancelled_by_admin', 'cancelled_by_customer'];
@endphp
@if(!in_array($order->status, $terminal))
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('admin.orders.cancel', $order) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-slash-circle text-dark me-2"></i>{{ __('app.cancel_order') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('app.cancel_order_confirm') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('app.no') }}</button>
                    <button type="submit" class="btn btn-dark">{{ __('app.yes_cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ── COMPLETE MODAL (legacy: admin can still force-complete from accepted) ──
     Note: the normal workflow now uses inline PATCH forms via the new routes.
     This modal is kept as a fallback and triggered from the old POST /complete route. --}}
@if($order->status === 'accepted')
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('admin.orders.complete', $order) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bag-check text-success me-2"></i>{{ __('app.complete_order') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('app.complete_order_confirm') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('app.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('app.complete') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ── MARK PAID MODAL ─────────────────────────────────────────────────── --}}
@php $ps = $order->payment_status ?? 'unpaid'; @endphp
@if($ps !== 'paid' && !in_array($order->status, ['rejected', 'cancelled', 'cancelled_by_admin', 'cancelled_by_customer']))
<div class="modal fade" id="markPaidModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('admin.orders.mark-paid', $order) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-credit-card text-success me-2"></i>{{ __('app.mark_paid') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">{{ __('app.payment_method') }} <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">{{ __('app.payment_method_cash') }}</option>
                        <option value="card">{{ __('app.payment_method_card') }}</option>
                        <option value="online">{{ __('app.payment_method_online') }}</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('app.cancel') }}</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>{{ __('app.confirm') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>

    // Reject: handle custom reason select
    const rejectSelect = document.getElementById('rejectSelect');
    const rejectCustom = document.getElementById('rejectCustomInput');
    const rejectFinal  = document.getElementById('rejectFinalReason');

    if (rejectSelect) {
        rejectSelect.addEventListener('change', function () {
            if (this.value === '__custom__') {
                rejectCustom.classList.remove('d-none');
                rejectFinal.value = '';
            } else {
                rejectCustom.classList.add('d-none');
                rejectFinal.value = this.value;
            }
        });
        rejectCustom.addEventListener('input', function () {
            rejectFinal.value = this.value;
        });
        // Pre-set on load
        if (rejectSelect.value && rejectSelect.value !== '__custom__') {
            rejectFinal.value = rejectSelect.value;
        }

        // On form submit, ensure final reason is set
        rejectSelect.closest('form').addEventListener('submit', function (e) {
            if (!rejectFinal.value) {
                e.preventDefault();
                rejectSelect.classList.add('is-invalid');
            }
        });
    }
</script>
@endpush

