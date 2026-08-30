@extends('admin.layouts.master')

@section('content')
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><i class="fa-solid fa-receipt me-2"></i>Running Bills</h4>
            <a href="{{ route('cashier.index') }}" class="btn btn-primary d-inline-flex align-items-center">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to POS
            </a>
        </div>

        @php
            $payable = $sessions->filter(fn ($s) => $s->isBillRequested());
            $open = $sessions->filter(fn ($s) => $s->isOpen());
        @endphp

        @if ($isAdmin)
            <div class="alert alert-light border d-flex align-items-center">
                <i class="fa-solid fa-eye me-2"></i>
                <span>Showing bills from <strong>all branches</strong> (admin full access).</span>
            </div>
        @endif

        {{-- Payable: waiter requested the bill --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <strong><i class="fa-solid fa-hourglass-half me-2"></i>Awaiting Bill Settlement</strong>
            </div>
            <div class="card-body">
                @if ($payable->isEmpty())
                    <p class="text-muted mb-0">No bills waiting to be settled right now.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Session</th>
                                    <th>Branch</th>
                                    <th>Waiter</th>
                                    <th>Tickets</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payable as $session)
                                    @php
                                        $sum = $summary[$session->id] ?? ['orders' => 0, 'total' => 0];
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('cashier.sessionDetails', $session->id) }}" class="fw-bold text-decoration-none">
                                                #{{ $session->session_code }}
                                            </a>
                                            <div class="small text-muted">{{ $session->opened_at?->format('M j, g:i A') }}</div>
                                        </td>
                                        <td><span class="badge bg-dark">{{ $session->branch?->name ?? '—' }}</span></td>
                                        <td>{{ $session->waiter?->name ?? '—' }}</td>
                                        <td>{{ $sum['orders'] }}</td>
                                        <td class="text-end fw-bold">PKR {{ number_format($sum['total'], 2) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('cashier.sessionDetails', $session->id) }}" class="btn btn-sm btn-success">
                                                <i class="fa-solid fa-money-bill-wave me-1"></i>Settle
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Still open: shown for tracking, not payable yet --}}
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <strong><i class="fa-solid fa-bell-concierge me-2"></i>Open Sessions</strong>
            </div>
            <div class="card-body">
                @if ($open->isEmpty())
                    <p class="text-muted mb-0">No open sessions are being served right now.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Session</th>
                                    <th>Branch</th>
                                    <th>Waiter</th>
                                    <th>Tickets</th>
                                    <th class="text-end">Running Total</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($open as $session)
                                    @php
                                        $sum = $summary[$session->id] ?? ['orders' => 0, 'total' => 0];
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('cashier.sessionDetails', $session->id) }}" class="text-decoration-none">
                                                #{{ $session->session_code }}
                                            </a>
                                            <div class="small text-muted">{{ $session->opened_at?->format('M j, g:i A') }}</div>
                                        </td>
                                        <td><span class="badge bg-dark">{{ $session->branch?->name ?? '—' }}</span></td>
                                        <td>{{ $session->waiter?->name ?? '—' }}</td>
                                        <td>{{ $sum['orders'] }}</td>
                                        <td class="text-end">PKR {{ number_format($sum['total'], 2) }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-secondary">Open — bill not requested</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection