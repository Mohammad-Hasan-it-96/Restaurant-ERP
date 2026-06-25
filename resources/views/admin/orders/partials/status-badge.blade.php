@php
    $map = [
        'pending'               => ['warning',  'app.pending'],
        'accepted'              => ['primary',  'app.accepted'],
        'ready'                 => ['info',     'app.ready'],
        'delivered'             => ['primary',  'app.delivered'],
        'completed'             => ['success',  'app.completed'],
        'rejected'              => ['danger',   'app.rejected'],
        'cancelled_by_customer' => ['secondary','app.cancelled_by_customer'],
        'modified'              => ['secondary','app.modified'],
    ];
    [$color, $key] = $map[$status] ?? ['secondary', 'app.unknown'];
@endphp
<span class="badge bg-{{ $color }}">{{ __($key) }}</span>

