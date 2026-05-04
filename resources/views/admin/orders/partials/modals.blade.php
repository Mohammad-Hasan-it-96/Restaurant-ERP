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
                    <h5 class="modal-title"><i class="bi bi-check-circle text-primary me-2"></i>{{ __('app.accept_order') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">{{ __('app.accept_order_hint') }}</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('app.delivery_fee') }}</label>
                        <div class="input-group">
                            <input type="number" name="delivery_fee" class="form-control"
                                   min="0" step="0.01"
                                   value="{{ $order->estimated_delivery_fee ?? 0 }}"
                                   placeholder="0.00">
                            <span class="input-group-text">{{ __('app.currency') }}</span>
                        </div>
                        <div class="form-text">{{ __('app.delivery_fee_hint') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('app.cancel') }}</button>
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

{{-- ── COMPLETE MODAL ─────────────────────────────────────────── --}}
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

