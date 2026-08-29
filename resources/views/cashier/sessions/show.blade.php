@extends('admin.layouts.master')

@section('content')
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>
                <i class="fa-solid fa-receipt me-2"></i>Session #{{ $session->session_code }}
                @if ($session->isOpen())
                    <span class="badge bg-secondary">Open</span>
                @elseif ($session->isBillRequested())
                    <span class="badge bg-warning text-dark">Bill Requested</span>
                @else
                    <span class="badge bg-success">Settled</span>
                @endif
            </h4>
            <div>
                <a href="{{ route('cashier.sessions') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Running Bills
                </a>
                <a href="{{ route('cashier.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-left me-2"></i>POS
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <strong>Ticket Lines</strong>
                    </div>
                    <div class="card-body">
                        @if ($groupedOrders->isEmpty())
                            <p class="text-muted mb-0">This session has no items.</p>
                        @else
                            @foreach ($groupedOrders as $orderCode => $group)
                                @php
                                    $record = $group->first();
                                    $nonRejected = $group->where('status', '!=', 3);
                                    $ticketTotal = $nonRejected->sum(fn ($o) => (float) $o->totalprice * (int) $o->quantity);
                                @endphp
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>#{{ $orderCode }}</strong>
                                        <span class="small text-muted">{{ $record->created_at?->format('g:i A') }}</span>
                                    </div>
                                    <table class="table table-sm mt-2 mb-1 align-middle">
                                        <tbody>
                                            @foreach ($group as $line)
                                                @php
                                                    $statusLabel = match ((int) $line->status) {
                                                        1 => 'New', 2 => 'Served', 3 => 'Rejected',
                                                        4 => 'Preparing', 5 => 'Ready', default => '—',
                                                    };
                                                    $badge = match ((int) $line->status) {
                                                        2 => 'success', 3 => 'danger',
                                                        4 => 'info', 5 => 'primary', default => 'secondary',
                                                    };
                                                @endphp
                                                <tr>
                                                    <td>{{ $line->product?->name ?? '—' }}</td>
                                                    <td class="text-center">{{ $line->size }}</td>
                                                    <td class="text-center">x{{ $line->quantity }}</td>
                                                    <td class="text-end">
                                                        PKR {{ number_format((float) $line->totalprice * (int) $line->quantity, 2) }}
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge bg-{{ $badge }}">{{ $statusLabel }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <div class="text-end fw-bold" style="color: #66401d;">
                                        Ticket Total: PKR {{ number_format($ticketTotal, 2) }}
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <strong>Session Total</strong>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td>Sub Total</td>
                                <td class="text-end">PKR {{ number_format($subTotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Tax ({{ $taxRate }}%)</td>
                                <td class="text-end">PKR {{ number_format($taxAmount, 2) }}</td>
                            </tr>
                            <tr class="fw-bold" style="color: #66401d;">
                                <td>Total</td>
                                <td class="text-end">PKR {{ number_format($total, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if ($session->isOpen())
                    <div class="alert alert-info mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        This session is still open. The waiter must request the bill from the kitchen
                        before it can be settled here.
                    </div>
                @elseif ($session->isBillRequested())
                    <div class="card shadow-sm border-warning">
                        <div class="card-header bg-warning text-dark">
                            <strong><i class="fa-solid fa-money-bill-wave me-2"></i>Settle Bill</strong>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('cashier.settleSession', $session->id) }}" method="POST" id="settleForm">
                                @csrf
                                <label class="form-label">Payment Method</label>
                                <div class="btn-group w-100 mb-3" role="group">
                                    <input type="radio" class="btn-check" name="paymentMethod" id="pm-cash" value="cash" checked>
                                    <label class="btn btn-outline-primary" for="pm-cash">Cash</label>
                                    <input type="radio" class="btn-check" name="paymentMethod" id="pm-card" value="card">
                                    <label class="btn btn-outline-primary" for="pm-card">Card</label>
                                    <input type="radio" class="btn-check" name="paymentMethod" id="pm-mobile" value="mobile">
                                    <label class="btn btn-outline-primary" for="pm-mobile">Mobile</label>
                                </div>

                                <div id="cashFields">
                                    <label class="form-label" for="cashReceived">Cash Received</label>
                                    <input type="number" step="0.01" min="0" name="cashReceived" id="cashReceived"
                                        class="form-control mb-2" placeholder="0.00" required>
                                </div>

                                <div id="changeRow" class="alert alert-info py-2 d-none">
                                    Change: <strong id="changeAmount">PKR 0.00</strong>
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fa-solid fa-check-circle me-2"></i>Settle Session
                                </button>
                            </form>
                        </div>
                    </div>
                @elseif ($settlement)
                    <div class="card shadow-sm border-success">
                        <div class="card-header bg-success text-white">
                            <strong><i class="fa-solid fa-circle-check me-2"></i>Settled</strong>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm mb-3">
                                <tr>
                                    <td>Bill #</td>
                                    <td class="text-end">{{ $settlement->order_code }}</td>
                                </tr>
                                <tr>
                                    <td>Method</td>
                                    <td class="text-end">{{ $settlement->payment_method }}</td>
                                </tr>
                                <tr>
                                    <td>Net Amount</td>
                                    <td class="text-end">PKR {{ number_format((float) $settlement->net_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Paid</td>
                                    <td class="text-end">PKR {{ number_format((float) $settlement->paid_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Change</td>
                                    <td class="text-end">PKR {{ number_format((float) $settlement->change_amount, 2) }}</td>
                                </tr>
                            </table>
                            <a href="{{ route('cashier.sessionBill', $session->id) }}" target="_blank"
                                class="btn btn-outline-dark w-100">
                                <i class="fa-solid fa-print me-2"></i>Print Bill
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const total = {{ $total }};
            const methodInputs = document.querySelectorAll('input[name="paymentMethod"]');
            const cashFields = document.getElementById('cashFields');
            const cashReceived = document.getElementById('cashReceived');
            const changeRow = document.getElementById('changeRow');
            const changeAmount = document.getElementById('changeAmount');

            function update() {
                const method = document.querySelector('input[name="paymentMethod"]:checked').value;
                const isCash = method === 'cash';
                cashFields.style.display = isCash ? 'block' : 'none';
                cashReceived.required = isCash;

                if (isCash) {
                    const paid = parseFloat(cashReceived.value) || 0;
                    const change = paid - total;
                    if (change >= 0) {
                        changeRow.classList.remove('d-none');
                        changeAmount.textContent = 'PKR ' + change.toFixed(2);
                    } else {
                        changeRow.classList.add('d-none');
                    }
                } else {
                    changeRow.classList.remove('d-none');
                    changeAmount.textContent = 'PKR 0.00';
                }
            }

            methodInputs.forEach(function (input) { input.addEventListener('change', update); });
            cashReceived.addEventListener('input', update);
            update();
        })();
    </script>
@endsection