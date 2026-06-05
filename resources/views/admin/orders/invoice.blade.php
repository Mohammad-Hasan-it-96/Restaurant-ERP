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

        /* ── Items list ─────────────────────────────────────────────── */
        .item-block { margin: 1.5mm 0; }
        .item-name  { font-weight: bold; font-size: 11px; }
        .item-detail {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 11px;
            gap: 4px;
        }
        .item-detail-left  { flex: 1; color: #333; }
        .item-detail-right { font-weight: bold; white-space: nowrap; }
        .item-sep {
            border: none;
            border-top: 1px dashed #bbb;
            margin: 1.5mm 0;
        }

        /* ── Totals table ───────────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
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

        /* ── Screen-only controls ───────────────────────────────────── */
        .no-print {
            text-align: center;
            margin-bottom: 5mm;
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .no-print button, .no-print a {
            padding: 6px 20px;
            font-size: 13px;
            cursor: pointer;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .no-print a { background: #555; }

        /* ══════════════════════════════════════════════════════════════
           THERMAL RECEIPT PRINT STYLES  —  80mm paper
           ══════════════════════════════════════════════════════════════ */
        @media print {

            /* ── Page setup ─────────────────────────────────────────── */
            @page {
                size: 80mm auto;   /* width fixed, height grows with content */
                margin: 0;
            }

            /* ── Hide everything that must not print ────────────────── */
            .no-print,
            nav, header, footer,
            .navbar, .sidebar, .sidebar-item,
            .main-content > :not(.invoice-wrap),
            .btn, .alert, .breadcrumb,
            .dropdown, .language-selector { display: none !important; }

            /* ── Root layout ─────────────────────────────────────────── */
            html, body {
                width: 65mm !important;
                max-width: 65mm !important;
                margin: 0 !important;
                padding: 4mm 3mm !important;
                font-family: 'Courier New', Courier, monospace !important;
                font-size: 11px !important;
                color: #000 !important;
                background: #fff !important;
            }

            /* ── Strip all decorative styles ────────────────────────── */
            * {
                background: transparent !important;
                background-color: transparent !important;
                color: #000 !important;
                box-shadow: none !important;
                text-shadow: none !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* ── Restaurant header ───────────────────────────────────── */
            .invoice-header {
                text-align: center !important;
                margin-bottom: 3mm !important;
            }
            .invoice-header img {
                max-width: 80px !important;
                max-height: 60px !important;
                width: auto !important;
                display: block !important;
                margin: 0 auto 2mm auto !important;
                object-fit: contain !important;
            }
            .invoice-header h1 {
                text-align: center !important;
                font-size: 13px !important;
                font-weight: bold !important;
                margin-bottom: 1mm !important;
            }
            .invoice-header p {
                text-align: center !important;
                font-size: 11px !important;
            }

            /* ── Section dividers ────────────────────────────────────── */
            hr, .divider {
                border: none !important;
                border-top: 1px dashed #000 !important;
                margin: 3mm 0 !important;
                height: 0 !important;
            }

            /* ── Info key-value rows (dl) ────────────────────────────── */
            dl { width: 100% !important; }
            dl dt, dl dd {
                display: inline-block !important;
                vertical-align: top !important;
                font-size: 11px !important;
                line-height: 1.6 !important;
                border: none !important;
            }
            dl dt { width: 42% !important; font-weight: bold !important; }
            dl dd { width: 56% !important; word-break: break-word !important; }

            /* ── Items list ──────────────────────────────────────────── */
            .item-block { margin: 1.5mm 0 !important; }
            .item-name  { font-weight: bold !important; font-size: 11px !important; }
            .item-detail {
                display: flex !important;
                justify-content: space-between !important;
                align-items: baseline !important;
                font-size: 11px !important;
            }
            .item-detail-left  { flex: 1 !important; }
            .item-detail-right { font-weight: bold !important; white-space: nowrap !important; }
            .item-sep {
                border: none !important;
                border-top: 1px dashed #000 !important;
                margin: 1.5mm 0 !important;
            }

            /* ── Totals table ─────────────────────────────────────────── */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                border: none !important;
                margin: 2mm 0 !important;
            }
            th, td {
                border: none !important;
                padding: 2px 0 !important;
                font-size: 11px !important;
                line-height: 1.5 !important;
                background: transparent !important;
            }
            /* First tfoot row: dashed separator */
            tfoot tr:first-child td {
                border-top: 1px dashed #000 !important;
                padding-top: 2mm !important;
            }
            /* Grand total row: solid line + bold */
            tfoot .total-row td {
                font-weight: bold !important;
                font-size: 12px !important;
                border-top: 1px solid #000 !important;
                padding-top: 2mm !important;
            }

            /* ── Strong / bold helpers ───────────────────────────────── */
            strong, b { font-weight: bold !important; }

            /* ── Typography helpers ──────────────────────────────────── */
            h1 { font-size: 15px !important; }
            h2 { font-size: 13px !important; }
            p, td, th, dd, dt { font-size: 11px !important; }

            /* ── Thank-you footer ────────────────────────────────────── */
            .invoice-footer {
                text-align: center !important;
                margin-top: 12px !important;
                font-size: 10px !important;
            }

            /* ── Avoid orphan rows across page breaks ────────────────── */
            tr { page-break-inside: avoid; }
            .invoice-footer { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    {{-- Screen-only controls (hidden on print) --}}
    <div class="no-print">
        <button onclick="window.print()">
            🖨 {{ __('app.print') }}
        </button>
        <a href="{{ route('admin.orders.show', $order) }}">
            ← {{ __('app.back') }}
        </a>
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
        <dd>{{ $order->customer_name ?? '—' }}</dd>

        <dt>{{ __('app.phone') }}</dt>
        <dd>{{ $order->phone ?? '—' }}</dd>

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

    @foreach($order->items as $item)
    @php
        $isWeighted = !empty($item->weight_name) && $item->weight_value_kg > 0;
        $wKg        = $isWeighted
            ? rtrim(rtrim(number_format($item->weight_value_kg, 3), '0'), '.')
            : '';
        $ratePerKg  = $isWeighted ? round($item->product_price / $item->weight_value_kg, 0) : 0;
    @endphp
    <div class="item-block">
        <div class="item-name">{{ $item->product_name }}</div>
        <div class="item-detail">
            <span class="item-detail-left">
                @if($isWeighted)
                    {{ $item->weight_name }} ({{ $wKg }} كغ) &times; {{ number_format($ratePerKg, 0) }} / كغ
                @else
                    {{ $item->quantity }} &times; {{ number_format($item->product_price, 0) }}
                @endif
            </span>
            <span class="item-detail-right">{{ number_format($item->total, 0) }}</span>
        </div>
    </div>
    @if(!$loop->last)
    <hr class="item-sep">
    @endif
    @endforeach

    {{-- ── Totals ── --}}
    <table>
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

