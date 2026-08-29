<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Bill</title>
    <link rel="stylesheet" href="{{ asset('admin/CSS/slip.css') }}">
</head>
<body onload="window.print();">
    <div class="slip-container" style="font-family: Arial, sans-serif; font-size: 14px; width: 400px; margin: auto; border: 1px solid #000; padding: 10px;">
        <div class="title">Session Bill</div>

        <div style="display: flex; justify-content: space-between;">
            <div>Session: #{{ $session->session_code }}</div>
            <div>Waiter: {{ $session->waiter?->name ?? '—' }}</div>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
            <div>Date: {{ $session->opened_at?->format('M j, Y') }}</div>
            <div>
                @if ($settlement)
                    Bill #: {{ $settlement->order_code }}
                @else
                    Bill requested
                @endif
            </div>
        </div>

        <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align: left; border-bottom: 1px solid #000;">Name</th>
                    <th style="text-align: center; border-bottom: 1px solid #000;">Size</th>
                    <th style="text-align: center; border-bottom: 1px solid #000;">Qty</th>
                    <th style="text-align: right; border-bottom: 1px solid #000;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupedOrders as $orderCode => $group)
                    <tr>
                        <td colspan="4" style="font-weight: bold; padding-top: 8px;">Order #{{ $orderCode }}</td>
                    </tr>
                    @foreach ($group as $line)
                        <tr>
                            <td style="padding: 4px 0;">{{ $line->product?->name ?? '—' }}</td>
                            <td style="text-align: center;">{{ $line->size }}</td>
                            <td style="text-align: center;">{{ $line->quantity }}</td>
                            <td style="text-align: right;">{{ number_format((float) $line->totalprice * (int) $line->quantity, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 10px; border-top: 1px solid #000; padding-top: 5px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: left;">Sub Total</td>
                    <td style="text-align: right;">{{ number_format($subTotal, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td style="text-align: left;">Tax ({{ $taxRate }}%)</td>
                    <td style="text-align: right;">{{ number_format($taxAmount, 2, '.', ',') }}</td>
                </tr>
                <tr style="font-weight: bold;">
                    <td style="text-align: left;">Net Amount</td>
                    <td style="text-align: right;">{{ number_format($total, 2, '.', ',') }}</td>
                </tr>
            </table>
        </div>

        @if ($settlement)
            <div style="margin-top: 10px; border-top: 1px solid #000; padding-top: 5px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: left;">Paid Amount</td>
                        <td style="text-align: right;">{{ number_format((float) $settlement->paid_amount, 2, '.', ',') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Change</td>
                        <td style="text-align: right;">{{ number_format((float) $settlement->change_amount, 2, '.', ',') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Method</td>
                        <td style="text-align: right;">{{ $settlement->payment_method }}</td>
                    </tr>
                </table>
            </div>
        @endif

        <div style="text-align: center; margin-top: 10px; font-weight: bold;">Thank You</div>
    </div>
</body>
</html>