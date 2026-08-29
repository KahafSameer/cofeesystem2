@extends('waiter.layouts.master')
@section('content')
    <section class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-center text-white mb-0">Running Bills / Sessions</h2>
            <form action="{{ route('waiter.createSession') }}" method="POST" class="m-0">
                @csrf
                <button class="btn btn-success"><i class="fa fa-plus me-1"></i> Start New Session</button>
            </form>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if ($activeSessions->isEmpty())
                    <div class="card">
                        <div class="card-body text-center">
                            <p class="text-muted mb-0">No active sessions. Start a new session to begin a running bill.</p>
                        </div>
                    </div>
                @else
                    <div class="card shadow">
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Session #</th>
                                        <th>Opened</th>
                                        <th>Orders</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($activeSessions as $session)
                                        <tr>
                                            <td>{{ $session->session_code }}</td>
                                            <td>{{ $session->opened_at?->format('Y-m-d H:i') }}</td>
                                            <td>{{ $session->ordersCount() }}</td>
                                            <td>
                                                @if ($session->isOpen())
                                                    <span class="badge bg-success">Open</span>
                                                @elseif ($session->isBillRequested())
                                                    <span class="badge bg-warning text-dark">Bill Requested</span>
                                                @else
                                                    <span class="badge bg-secondary">Closed</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('waiter.sessionDetails', $session) }}"
                                                    class="btn btn-outline-primary btn-sm">View Bill</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
