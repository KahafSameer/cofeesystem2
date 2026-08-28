<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Slip</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('admin/CSS/slip.css') }}">
</head>
<body onload="window.print();">
    <div class="slip-container" style="font-family: Arial, sans-serif; font-size: 14px; width: 400px; margin: auto; border: 1px solid #000; padding: 10px;">
        <div class="title">Payment Slip</div>

        <div style="display: flex; justify-content: space-between;">
            <div>Cashier ID: {{ $records->first()->CashierID ?? 'N/A' }}</div>
            <div>Date: {{ $records->first()->Date ?? 'N/A' }}</div>

        </div>
        <div style="display: flex; justify-content: space-between;">
            <div>{{ $records->first()->order_code ?? 'N/A' }}</div>
            <div>Paid by: {{  $records->first()->payment_method ?? 'N/A' }}</div>

        </div>

        <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align: left; border-bottom: 1px solid #000;">Name</th>
                    <th style="text-align: center; border-bottom: 1px solid #000;">Qty</th>
                    <th style="text-align: right; border-bottom: 1px solid #000;">Price</th>
                    <th style="text-align: right; border-bottom: 1px solid #000;">Size</th>
                    <th style="text-align: right; border-bottom: 1px solid #000;">Discount</th>
                    <th style="text-align: right; border-bottom: 1px solid #000;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $item)
                    <tr>
                        <td style="padding: 5px 0;">{{ $item->ProductName }}</td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">{{ $item->price }}</td>
                        <td style="text-align: right;">{{ strtoupper(substr($item->size, 0, 1)) }}</td>
                        <td style="text-align: right;">{{ $item->discount_percentage ?? 0 }}%</td>
                        <td style="text-align: right;">{{ number_format($item->total_price, 2, '.', ',') }}</td>
                    </tr>
        @endforeach
            </tbody>
        </table>
        <div style="margin-top: 10px; border-top: 1px solid #000; padding-top: 5px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: left;">Sub Total</td>
                    <td style="text-align: right;">{{ number_format($subTotalAmt, 2, '.', ',') }}</td>
                </tr>
                 <tr>
                    <td style="text-align: left;">Tax</td>
                    <td style="text-align: right;">{{ $taxAmount }}</td>
                </tr>
                <tr>
                    <td style="text-align: left;">Deli Fees</td>
                    <td style="text-align: right;">{{ number_format($deliveryFee, 2, '.', ',') }}</td>
                </tr>
                <tr style="font-weight: bold;">
                    <td style="text-align: left;">Net Amount</td>
                    <td style="text-align: right;">{{ $records->first()->paid_amount - $records->first()->change_amount }}</td>
                </tr>
            </table>
        </div>
        <div style="margin-top: 10px; border-top: 1px solid #000; padding-top: 5px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: left;">Paid Amount</td>
                    <td style="text-align: right;">{{ $records->first()->paid_amount }}</td>
                </tr>
                <tr>
                    <td style="text-align: left;">Change</td>
                    <td style="text-align: right;">{{ $records->first()->change_amount }}</td>
                </tr>
            </table>
        </div>
        <div style="text-align: center; margin-top: 10px; font-weight: bold;">Thank You</div>

    </div>

</body>
</html>
