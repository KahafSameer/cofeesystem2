<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiter Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    {{-- sweet alert  --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="{{ asset('admin/CSS/style.css') }}">
</head>

<body class="body">
    <button id="sidebarToggle" class="btn d-lg-none">
        <i class="fas fa-bars"></i>
    </button>
    <!-- Sidebar -->
    <div id="sidebarContainer" class="sidebar d-flex flex-column p-3 position-fixed shadow">
        @if (auth()->check())
            <a href="{{ route('waiter.dashboard') }}" class="btn mb-2" style="background-color: #66401d; color: white;">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>

            <a href="{{ route('waiter.newOrder') }}" class="btn mb-2" style="background-color: #66401d; color: white;">
                <i class="fas fa-plus-circle me-2"></i> New Order
            </a>

            <a href="{{ route('waiter.currentOrders') }}" class="btn mb-2" style="background-color: #66401d; color: white;">
                <i class="fas fa-clipboard-list me-2"></i> Current Orders
            </a>

            <a href="{{ route('waiter.sessions') }}" class="btn mb-2" style="background-color: #66401d; color: white;">
                <i class="fas fa-receipt me-2"></i> Running Bills
            </a>

            <a href="{{ route('waiter.orderHistory') }}" class="btn mb-2" style="background-color: #66401d; color: white;">
                <i class="fas fa-history me-2"></i> Order History
            </a>

            <a href="{{ route('waiter.profile') }}" class="btn mb-2" style="background-color: #66401d; color: white;">
                <i class="fas fa-user me-2"></i> Profile
            </a>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <input type="submit" value="Logout" class="btn btn-outline-light text-center mt-2">
            </form>
        @endif
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top shadow">
        <div class="w-100 px-3 d-flex justify-content-between align-items-center">
            <a class="navbar-brand text-light fw-bold ms-4" href="{{ route('waiter.dashboard') }}">
                <i class="fas fa-store"></i> Waiter Panel
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item">
                        <a class="nav-link text-light me-2" href="{{ route('waiter.cart') }}">
                            <i class="fa fa-shopping-bag me-1"></i> Cart
                            @if (($cartCount ?? 0) > 0)
                                <span class="badge rounded-pill bg-danger">{{ $cartCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            @if (auth()->user()->profile == null)
                                <img class="rounded-circle profile-img" src="{{ asset('admin/images/undraw_profile.svg') }}">
                            @else
                                <img class="rounded-circle profile-img" src="{{ asset('adminProfile/' . auth()->user()->profile) }}">
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li class="dropdown-item"><strong>{{ auth()->user()->name }}</strong></li>
                            <li class="dropdown-item text-muted small">Role: {{ auth()->user()->role }}</li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="{{ route('waiter.profile') }}">Profile</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content content-wrapper">
        @yield('content')
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleButton = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebarContainer');
            const body = document.querySelector('.body');

            toggleButton.addEventListener('click', function () {
                sidebar.classList.toggle('show');
                body.classList.toggle('sidebar-open');
            });

            document.addEventListener('click', function (e) {
                if (body.classList.contains('sidebar-open') &&
                    !sidebar.contains(e.target) &&
                    !toggleButton.contains(e.target)) {
                    sidebar.classList.remove('show');
                    body.classList.remove('sidebar-open');
                }
            });
        });
    </script>
</body>

@if (session('alert'))
    <script>
        Swal.fire({
            title: "{{ session('alert')['type'] == 'success' ? 'Success!' : 'Error!' }}",
            text: "{{ session('alert')['message'] }}",
            icon: "{{ session('alert')['type'] }}",
            confirmButtonText: 'OK'
        });
    </script>
@endif

@if (session('kotOrderCode'))
    <script>
        // Automatic KOT print - fires once after a successful order submission.
        // The flash is consumed immediately, so refreshing/viewing this page will
        // not print again. Manual reprint is available from the order pages.
        (function () {
            var kotCode = @json(session('kotOrderCode'));
            if (kotCode) {
                var url = @json(route('kitchen.kotPrint', session('kotOrderCode')));
                window.open(url, '_blank');
            }
        })();
    </script>
@endif

@yield('scripts')

</html>
