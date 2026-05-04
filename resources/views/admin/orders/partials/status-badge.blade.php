@php
    $map = [
        'pending'   => ['warning',  'app.pending'],
        'accepted'  => ['primary',  'app.accepted'],
        'preparing' => ['info',     'app.preparing'],
        'ready'     => ['info',     'app.ready'],
        'delivered' => ['success',  'app.delivered'],
        'completed' => ['success',  'app.completed'],
        'cancelled' => ['dark',     'app.cancelled'],
        'rejected'  => ['danger',   'app.rejected'],
    ];
    [$color, $key] = $map[$status] ?? ['secondary', 'app.unknown'];
@endphp
<span class="badge bg-{{ $color }}">{{ __($key) }}</span>

