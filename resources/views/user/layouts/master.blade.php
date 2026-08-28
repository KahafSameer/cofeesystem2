<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- MDB UI Kit CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.min.css" rel="stylesheet" />

    <!-- Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <link rel="stylesheet" href="{{ asset('user/css/style.css') }}">
    <title>Coffee Shop</title>
</head>

<body>

    <header class="text-center py-3" style="background-color: #42280e;">
        <div>
            <h1 class="fw-bold text-white mb-0">MAY BROWN</h1>
            <h1 class="text-white">Coffee</h1>
        </div>
        <div class="d-flex align-items-center justify-content-center">
            <img src="{{ asset('user/images/logo.jpg') }}" height="70" class="rounded-circle" alt="Coffee Shop Logo"
                style="margin-right: 15px;">
        </div>
    </header>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #42280e;">
        <div class="container">
            <!-- Toggler -->
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars text-white"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ url('user/home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('climenu') }}">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('about') }}">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('contactus') }}">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('reivewPage') }}">Review</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('customerProfile') }}">Profile</a>
                    </li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="nav-link text-white"
                                style="background:none; border:none;">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


    @yield('content')

    <footer class="text-center py-3" style="background-color: #42280e;">
        <p class="text-white mb-0">Copyright © 2024-2025 Coffee. All Rights are reserved</p>
    </footer>

    @yield('scripts')
    @stack('scripts')

    <script>
    document.querySelector('.navbar-toggler').addEventListener('click', () => {
        document.getElementById('navbarNav').classList.toggle('show');
    });
    </script>

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

    <script>
        function toggleDropdown(menuClass) {
        document.querySelector("." + menuClass).classList.toggle("show");
    }
    </script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- MDB JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>

<!-- Leaflet and Geocoder -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script src="https://unpkg.com/leaflet.photon/leaflet.photon.js"></script>


</body>

</html>
