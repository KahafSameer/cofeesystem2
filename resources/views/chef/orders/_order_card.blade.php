@php
    $first = $orders->first();
    $hasWaiter = ! empty($first->waiter);
    $hasSession = ! empty($first->customerSession);
@endphp
<div class="col-md-6 col-lg-4">
    <div class="card shadow-sm h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>#{{ $orderCode }}</strong>
            <span class="small text-muted">{{ $first->created_at->format('h:i A') }}</span>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-2">
                @if ($hasWaiter)
                    <div><i class="fa fa-user me-1"></i> Waiter: {{ $first->waiter->name }}</div>
                @endif
                @if ($hasSession)
                    <div><i class="fa fa-receipt me-1"></i> Session: {{ $first->customerSession->session_code }}</div>
                @endif
            </div>

            <table class="table table-sm table-bordered mb-2">
                <thead class="table-dark">
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $o)
                        <tr>
                            <td>{{ $o->product->name ?? 'Product' }}</td>
                            <td>{{ $o->quantity }}</td>
                            <td>{{ $o->size }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @php
                $groupNotes = collect($orders)->pluck('notes')->filter()->unique()->values();
            @endphp
            @if ($groupNotes->isNotEmpty())
                <div class="small mb-2">
                    <strong>Notes:</strong>
                    @foreach ($groupNotes as $note)
                        <div>{{ $note }}</div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <a href="{{ route('chef.orderDetails', $orderCode) }}" class="btn btn-outline-primary btn-sm">View</a>
            <a href="{{ route('kitchen.kotPrint', $orderCode) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-print me-1"></i>KOT
            </a>
            {!! $actions ?? '' !!}
        </div>
    </div>
</div>
