<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Order Ticket</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('admin/CSS/kot.css') }}">
</head>
<body onload="window.print();">
    <div class="kot-container">
        <div class="kot-title">KITCHEN ORDER</div>

        <div class="kot-row">
            <span>Order:</span>
            <span>{{ $kot['orderCode'] }}</span>
        </div>
        @if (! empty($kot['sessionCode']))
            <div class="kot-row">
                <span>Session:</span>
                <span>{{ $kot['sessionCode'] }}</span>
            </div>
        @endif
        @if (! empty($kot['waiterName']))
            <div class="kot-row">
                <span>Waiter:</span>
                <span>{{ $kot['waiterName'] }}</span>
            </div>
        @endif
        @if (! empty($kot['branchName']))
            <div class="kot-row">
                <span>Branch:</span>
                <span>{{ $kot['branchName'] }}</span>
            </div>
        @endif
        <div class="kot-row">
            <span>Time:</span>
            <span>{{ $kot['createdAt'] ? $kot['createdAt']->format('h:i A - m/d/Y') : '' }}</span>
        </div>

        <hr>

        <table class="kot-table">
            <thead>
                <tr>
                    <th>ITEM</th>
                    <th>QTY</th>
                    <th>SIZE</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($kot['items'] as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>{{ $item['size'] }}</td>
                    </tr>
                    @if (! empty($item['notes']))
                        <tr>
                            <td colspan="3" class="kot-note">{{ $item['notes'] }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <hr>

        @if (! empty($kot['notes']))
            <div class="kot-notes">
                <strong>Notes:</strong>
                @foreach ($kot['notes'] as $note)
                    <div>{{ $note }}</div>
                @endforeach
            </div>
            <hr>
        @endif

        <div class="kot-footer">Kitchen Order Ticket</div>
    </div>
</body>
</html>
