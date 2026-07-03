<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('repair_orders.pdf.title', ['version' => $estimate->version]) }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }

        h1 {
            font-size: 24px;
            margin: 0 0 4px;
        }

        h2 {
            font-size: 14px;
            margin: 24px 0 8px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 6px;
            vertical-align: top;
        }

        th {
            color: #4b5563;
            font-size: 10px;
            text-align: left;
            text-transform: uppercase;
        }

        .muted {
            color: #6b7280;
        }

        .grid {
            display: table;
            margin-top: 20px;
            width: 100%;
        }

        .grid > div {
            display: table-cell;
            width: 50%;
        }

        .right {
            text-align: right;
        }

        .totals {
            margin-left: auto;
            margin-top: 20px;
            width: 260px;
        }
    </style>
</head>
<body>
    <h1>{{ __('repair_orders.pdf.heading', ['version' => $estimate->version]) }}</h1>
    <div class="muted">{{ __('repair_orders.pdf.generated', ['date' => $estimate->generated_at?->format('M j, Y') ?? now()->format('M j, Y')]) }}</div>

    <div class="grid">
        <div>
            <strong>{{ $estimate->repairOrder->workshop->name }}</strong><br>
            {{ __('repair_orders.pdf.repair_order', ['id' => $estimate->repair_order_id]) }}
        </div>
        <div>
            <strong>{{ $estimate->repairOrder->customer?->name ?? __('repair_orders.pdf.customer') }}</strong><br>
            {{ $estimate->repairOrder->customer?->phone }}
            @if ($estimate->repairOrder->vehicle)
                <br>{{ trim(($estimate->repairOrder->vehicle->brand ?? '').' '.($estimate->repairOrder->vehicle->model ?? '')) }}
            @endif
        </div>
    </div>

    <h2>{{ __('repair_orders.pdf.lines_heading') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('repair_orders.pdf.columns.type') }}</th>
                <th>{{ __('repair_orders.pdf.columns.description') }}</th>
                <th class="right">{{ __('repair_orders.pdf.columns.quantity') }}</th>
                <th class="right">{{ __('repair_orders.pdf.columns.unit') }}</th>
                <th class="right">{{ __('repair_orders.pdf.columns.tax') }}</th>
                <th class="right">{{ __('repair_orders.pdf.columns.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($estimate->lines as $line)
                <tr>
                    <td>{{ $line->type->label() }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="right">{{ $line->quantity }}</td>
                    <td class="right">{{ number_format($line->unit_price_cents / 100, 2) }} {{ $estimate->currency }}</td>
                    <td class="right">{{ $line->tax_rate }}%</td>
                    <td class="right">{{ number_format($line->total_cents / 100, 2) }} {{ $estimate->currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>{{ __('repair_orders.pdf.totals.subtotal') }}</td>
            <td class="right">{{ number_format($estimate->subtotal_cents / 100, 2) }} {{ $estimate->currency }}</td>
        </tr>
        <tr>
            <td>{{ __('repair_orders.pdf.totals.tax') }}</td>
            <td class="right">{{ number_format($estimate->tax_cents / 100, 2) }} {{ $estimate->currency }}</td>
        </tr>
        <tr>
            <td><strong>{{ __('repair_orders.pdf.totals.total') }}</strong></td>
            <td class="right"><strong>{{ number_format($estimate->total_cents / 100, 2) }} {{ $estimate->currency }}</strong></td>
        </tr>
    </table>
</body>
</html>
