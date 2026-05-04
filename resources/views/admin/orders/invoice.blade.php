<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ App::isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.invoice') }} — {{ $order->order_number }}</title>

    <style>
        /* ── Reset ─────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Base ──────────────────────────────────────────────────── */
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            background: #fff;
            width: 80mm;
            margin: 0 auto;
            padding: 6mm 4mm;
        }

        /* ── Typography ────────────────────────────────────────────── */
        h1 { font-size: 15px; font-weight: bold; }
        h2 { font-size: 13px; font-weight: bold; }
        p, td, th, dd, dt { font-size: 11px; line-height: 1.5; }

        /* ── Divider ────────────────────────────────────────────────── */
        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 4mm 0;
        }

        /* ── Header ─────────────────────────────────────────────────── */
        .invoice-header {
            text-align: center;
            margin-bottom: 3mm;
        }
        .invoice-header img {
            max-width: 30mm;
            max-height: 20mm;
            object-fit: contain;
            margin-bottom: 2mm;
        }
        .invoice-header h1 { font-size: 14px; margin-bottom: 1mm; }
        .invoice-header p  { font-size: 11px; color: #333; }

        /* ── Info rows (dl) ─────────────────────────────────────────── */
        dl { width: 100%; }
        dl dt, dl dd {
            display: inline-block;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.6;
        }
        dl dt { width: 42%; font-weight: bold; }
        dl dd { width: 56%; word-break: break-word; }

        /* ── Items table ────────────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
        }
        thead tr {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        th { font-size: 11px; padding: 1mm 0; text-align: start; }
        td { font-size: 11px; padding: 1mm 0; vertical-align: top; }
        .text-end { text-align: end; }
        .text-center { text-align: center; }

        tfoot tr:first-child td { border-top: 1px dashed #000; padding-top: 2mm; }
        tfoot .total-row td { font-weight: bold; font-size: 12px; border-top: 1px solid #000; padding-top: 2mm; }

        /* ── Footer ─────────────────────────────────────────────────── */
        .invoice-footer {
            text-align: center;
            margin-top: 4mm;
            font-size: 10px;
            color: #444;
        }

        /* ── Print button (screen only) ──────────────────────────────── */
        .no-print {
            text-align: center;
            margin-bottom: 5mm;
        }
        .no-print button {
            padding: 6px 20px;
            font-size: 13px;
            cursor: pointer;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 4px;
        }

        /* ── Print media ─────────────────────────────────────────────── */
        @media print {
            body { width: 80mm; margin: 0; padding: 4mm 3mm; }
            .no-print { display: none !important; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>

    {{-- Print button (hidden on print) --}}
    <div class="no-print">
        <button onclick="window.print()">
            🖨 {{ __('app.print') }}
        </button>
    </div>

    {{-- ── Restaurant header ── --}}
    <div class="invoice-header">
        @if($restaurantLogo && file_exists(storage_path('app/public/' . $restaurantLogo)))
        <img src="{{ asset('storage/' . $restaurantLogo) }}" alt="{{ $restaurantName }}">
        <br>
        @endif
        <h1>{{ $restaurantName }}</h1>
        @if($restaurantPhone)
        <p>{{ $restaurantPhone }}</p>
        @endif
    </div>

    <hr class="divider">

    {{-- ── Order meta ── --}}
    <dl>
        <dt>{{ __('app.invoice') }}</dt>
        <dd>#{{ $order->order_number }}</dd>

        <dt>{{ __('app.date') }}</dt>
        <dd>{{ $order->created_at->format('Y-m-d H:i') }}</dd>

        <dt>{{ __('app.order_type') }}</dt>
        <dd>{{ __('app.' . $order->order_type) }}</dd>

        @if($order->order_type === 'table' && $order->table_number)
        <dt>{{ __('app.table_number') }}</dt>
        <dd>{{ $order->table_number }}</dd>
        @endif
    </dl>

    <hr class="divider">

    {{-- ── Customer ── --}}
    <dl>
        <dt>{{ __('app.customer_name') }}</dt>
        <dd>{{ $order->customer->full_name ?? '—' }}</dd>

        <dt>{{ __('app.phone') }}</dt>
        <dd>{{ $order->customer->phone ?? '—' }}</dd>

        @if($order->order_type === 'delivery' && $order->address)
        <dt>{{ __('app.address') }}</dt>
        <dd>{{ $order->address }}</dd>
        @endif

        @if($order->order_type === 'delivery' && $order->delivery_type)
        <dt>{{ __('app.delivery_type') }}</dt>
        <dd>{{ __('app.' . $order->delivery_type) }}</dd>
        @endif

        @if($order->scheduled_at)
        <dt>{{ __('app.scheduled_at') }}</dt>
        <dd>{{ $order->scheduled_at->format('Y-m-d H:i') }}</dd>
        @endif
    </dl>

    <hr class="divider">

    {{-- ── Items ── --}}
    <table>
        <thead>
            <tr>
                <th style="width:50%">{{ __('app.product_name') }}</th>
                <th class="text-center" style="width:12%">{{ __('app.qty') }}</th>
                <th class="text-end" style="width:18%">{{ __('app.price') }}</th>
                <th class="text-end" style="width:20%">{{ __('app.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-end">{{ number_format($item->product_price, 2) }}</td>
                <td class="text-end">{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">{{ __('app.subtotal') }}</td>
                <td class="text-end">{{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if($order->delivery_fee !== null)
            <tr>
                <td colspan="3">{{ __('app.delivery_fee') }}</td>
                <td class="text-end">{{ number_format($order->delivery_fee, 2) }}</td>
            </tr>
            @endif
            @if($order->discount)
            <tr>
                <td colspan="3">{{ __('app.discount') }}</td>
                <td class="text-end">- {{ number_format($order->discount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3">{{ __('app.total') }}</td>
                <td class="text-end">{{ number_format($order->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($order->customer_note && $order->order_type !== 'delivery')
    <hr class="divider">
    <p><strong>{{ __('app.customer_note') }}:</strong> {{ $order->customer_note }}</p>
    @endif

    <hr class="divider">

    <div class="invoice-footer">
        <p>{{ __('app.invoice_thank_you') }}</p>
    </div>

</body>
</html>

