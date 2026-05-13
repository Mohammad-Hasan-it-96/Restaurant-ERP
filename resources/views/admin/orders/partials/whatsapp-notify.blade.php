{{-- ══════════════════════════════════════════════════════════
     WhatsApp Customer Notification Panel
     Variables needed: $order, $restaurantName, $restaurantWhatsapp
     The admin clicks a button to open WhatsApp with a pre-filled
     message addressed to the customer's phone number.
═══════════════════════════════════════════════════════════ --}}

@php
    // Normalise phone to international format (Syria: 09XXXXXXXX → 9639XXXXXXXX)
    $rawPhone   = $order->customer->phone ?? '';
    $digitsOnly = preg_replace('/[^0-9]/', '', $rawPhone);

    if (strlen($digitsOnly) === 10 && str_starts_with($digitsOnly, '09')) {
        // Local Syrian format: 09XXXXXXXX → 9639XXXXXXXX
        $phoneDigits = '9639' . substr($digitsOnly, 2);
    } elseif (str_starts_with($digitsOnly, '963')) {
        // Already has Syrian country code
        $phoneDigits = $digitsOnly;
    } elseif (str_starts_with($digitsOnly, '0')) {
        // Other local format: strip leading 0, prepend country code
        $phoneDigits = '963' . substr($digitsOnly, 1);
    } else {
        $phoneDigits = $digitsOnly;
    }

    $hasPhone = !empty($phoneDigits);

    // Restaurant name for messages
    $rName    = $restaurantName ?? config('app.name');
    $custName = $order->customer->full_name ?? '';
    $orderNo  = $order->order_number;

    $waBase = 'https://wa.me/' . $phoneDigits . '?text=';

    // ── Per-status messages (use current app locale) ──────────────
    $messages = [
        'accepted' => __('app.wa_msg_accepted', [
            'name'       => $custName,
            'order'      => $orderNo,
            'restaurant' => $rName,
        ]),
        'rejected' => __('app.wa_msg_rejected', [
            'name'       => $custName,
            'order'      => $orderNo,
            'reason'     => $order->rejection_reason ?? '',
            'restaurant' => $rName,
        ]),
        'preparing' => __('app.wa_msg_preparing', [
            'name'       => $custName,
            'order'      => $orderNo,
            'restaurant' => $rName,
        ]),
        'ready' => $order->order_type === 'delivery'
            ? __('app.wa_msg_ready_delivery', [
                'name'       => $custName,
                'order'      => $orderNo,
                'restaurant' => $rName,
              ])
            : __('app.wa_msg_ready_pickup', [
                'name'       => $custName,
                'order'      => $orderNo,
                'restaurant' => $rName,
              ]),
        'delivered' => __('app.wa_msg_delivered', [
            'name'       => $custName,
            'order'      => $orderNo,
            'restaurant' => $rName,
        ]),
        'completed' => __('app.wa_msg_completed', [
            'name'       => $custName,
            'order'      => $orderNo,
            'restaurant' => $rName,
        ]),
        'cancelled_by_admin' => __('app.wa_msg_cancelled', [
            'name'       => $custName,
            'order'      => $orderNo,
            'restaurant' => $rName,
        ]),
    ];

    $currentStatus = $order->status;
    $relevantStatuses = ['accepted', 'rejected', 'preparing', 'ready', 'delivered', 'completed', 'cancelled_by_admin'];
@endphp

<div class="card shadow-sm mb-4 border-success">
    <div class="card-header fw-bold bg-success bg-opacity-10 text-success">
        <i class="bi bi-whatsapp me-2"></i>{{ __('app.wa_notify_customer') }}
        @if(!$hasPhone)
            <span class="badge bg-warning text-dark ms-2 fw-normal small">{{ __('app.wa_no_phone') }}</span>
        @endif
    </div>
    <div class="card-body">
        @if($hasPhone)
            <p class="text-muted small mb-3">{{ __('app.wa_notify_hint') }}</p>
            <div class="d-flex flex-wrap gap-2">
                @foreach($relevantStatuses as $st)
                    @if(isset($messages[$st]))
                        @php
                            $isCurrentStatus = ($st === $currentStatus);
                            $url = $waBase . rawurlencode($messages[$st]);
                        @endphp
                        <a href="{{ $url }}"
                           target="_blank" rel="noopener noreferrer"
                           data-wa-status="{{ $st }}"
                           class="btn btn-sm {{ $isCurrentStatus ? 'btn-success' : 'btn-outline-success' }}"
                           title="{{ $messages[$st] }}">
                            <i class="bi bi-whatsapp me-1"></i>
                            {{ __('app.wa_btn_' . $st) }}
                            @if($isCurrentStatus)
                                <span class="badge bg-white text-success ms-1 small">{{ __('app.wa_current') }}</span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>
            <div class="mt-3 p-2 bg-light rounded border small text-muted" id="waPreviewBox">
                <strong>{{ __('app.wa_preview_label') }}:</strong>
                <span id="waPreviewText">{{ $messages[$currentStatus] ?? ($messages['accepted'] ?? '') }}</span>
            </div>
            <div class="mt-2 small text-muted">
                <i class="bi bi-person-circle me-1"></i>
                {{ $order->customer->full_name ?? '—' }}
                &nbsp;|&nbsp;
                <i class="bi bi-telephone me-1"></i>
                <span dir="ltr">{{ $rawPhone }}</span>
            </div>
        @else
            <div class="text-muted small">
                <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
                {{ __('app.wa_no_phone_detail') }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    // Highlight preview text when hovering a button
    const previewEl = document.getElementById('waPreviewText');
    if (!previewEl) return;
    const messages = {!! json_encode($messages, JSON_UNESCAPED_UNICODE) !!};

    document.querySelectorAll('[data-wa-status]').forEach(btn => {
        btn.addEventListener('mouseenter', () => {
            const st = btn.dataset.waStatus;
            if (messages[st]) previewEl.textContent = messages[st];
        });
    });
})();
</script>
@endpush


