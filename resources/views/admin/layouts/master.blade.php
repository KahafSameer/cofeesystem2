<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System</title>
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
            <a href="{{ route('adminDashboard') }}" class="btn mb-2" style="background-color: #66401d; color: white;">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>

            @if (auth()->user()->role === 'cashier')
                <a href="{{ route('bookingPage') }}" class="btn mb-2" style="background-color: #66401d; color: white;">
                    <i class="fas fa-calendar-alt me-2"></i> Booking
                </a>
            @endif

            @if (auth()->user()->role === 'admin' || auth()->user()->role === 'cashier')
                <button class="btn d-flex justify-content-between align-items-center mb-2"
                    onclick="toggleDropdown('catmenu')"
                    style="background-color: #66401d; color: white;">
                    <span><i class="fa-solid fa-gears me-2"></i> Settings</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <ul class="catmenu list-unstyled">
                    <li><a href="{{ route('category.list') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fas fa-tags me-2"></i> Categories</a></li>
                    <li><a href="{{ route('product.prodlist') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fas fa-boxes me-2"></i> Products</a></li>
                    <li><a href="{{ route('discountPage') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fas fa-percentage me-2"></i> Discounts</a></li>
                        </a>
                    <li><a href="{{ route('deliveryInfoPage') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                    <i class="fa-solid fa-truck"></i> Delivery Fees</a></li>
                        </a>
                    <li><a href="{{ route('taxPage') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                    <i class="fa-solid fa-comment-dollar"></i> Tax Settings</a></li>
                        </a>
                </ul>
            @endif

            @if (auth()->user()->role === 'admin')
            <button class="btn d-flex justify-content-between align-items-center mb-2"
                    onclick="toggleDropdown('purchasemenu')"
                    style="background-color: #66401d; color: white;">
                    <span><i class="fa-solid fa-bag-shopping me-2"></i>Purchase Management</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <ul class="purchasemenu list-unstyled">
                    <li><a href="{{ route('supplier.index') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fa-solid fa-circle-info me-2"></i>Supplier Info</a></li>
                    <li><a href="{{ route('purchasePage') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fa-solid fa-basket-shopping me-2"></i>Purchase Info</a></li>

                </ul>

                <button class="btn d-flex justify-content-between align-items-center mb-2"
                    onclick="toggleDropdown('assetmenu')"
                    style="background-color: #66401d; color: white;">
                    <span><i class="fa-sharp-duotone fa-solid fa-house me-2"></i>Asset Management</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <ul class="assetmenu list-unstyled">
                    <li><a href="{{ route('assetCategories.index')}}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fa-solid fa-layer-group me-2"></i>Asset Category</a></li>
                    <li><a href="{{ route('assets.index')}}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fa-solid fa-list me-2"></i>Asset List</a></li>

                </ul>

                <button class="btn d-flex justify-content-between align-items-center mb-2"
                onclick="toggleDropdown('profilemenu')"
                style="background-color: #66401d; color: white;">
                <span><i class="fa-solid fa-user-gear me-2"></i> Manage Profile</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <ul class="profilemenu list-unstyled">
                <li><a href="{{ route('profile.createNewUser') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                    <i class="fa-solid fa-user-plus me-2"></i> Create New User</a></li>
                <li><a href="{{ route('profile.overview') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                    <i class="fa-solid fa-user me-2"></i>My Profile</a></li>
                <li><a href="{{ route('changeProfilePage') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                    <i class="fa-solid fa-unlock me-2"></i>Manage Profile</a></li>
                <li><a href="{{ route('resetPasswordPage') }}" class="btn mb-2" style="background-color: #f1e797; color: black;">
                    <i class="fa-solid fa-lock-open me-2"></i> Reset Password</a></li>
            </ul>

            @endif
            <button class="btn d-flex justify-content-between align-items-center mb-2"
                    onclick="toggleDropdown('submenu')"
                    style="background-color: #66401d; color: white;">
                    <span><i class="fas fa-chart-line me-2"></i> Reports</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <ul class="submenu list-unstyled">
                    <li><a href="{{ route('salesReportPage') }}" class="btn  mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fa-brands fa-sellsy me-2"></i>Sales Reports</a></li>
                    <li><a href="{{ route('inventoryPage') }}" class="btn  mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fa-solid fa-warehouse me-2"></i>Inventory Analysis</a></li>
                    <li><a href="{{ route('supplierPurchasePage') }}" class="btn  mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fa-solid fa-money-check-dollar me-2"></i>Summary Purchase</a></li>
                    <li><a href="{{ route('purchasedetailsPage') }}" class="btn  mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fa-solid fa-money-check-dollar me-2"></i>Details Purchase</a></li>
                    <li><a href="{{ route('assetPage') }}" class="btn  mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fa-solid fa-money-check-dollar me-2"></i>Asset Report</a></li>
                    <li><a href="{{ route('feedbackPage') }}" class="btn  mb-2" style="background-color: #f1e797; color: black;">
                        <i class="fa-solid fa-comments me-2"></i>Feedback</a></li>
                </ul>
        @endif
        <form action="{{ route('logout') }}" method="POST">
            @csrf

            <input type="submit" value="Logout" class="btn btn-outline-light text-center mt-2">
        </form>

    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top shadow">
    <div class="w-100 px-3 d-flex justify-content-between align-items-center">
        <!-- Left: Logo -->
        <a class="navbar-brand text-light fw-bold ms-4" href="{{ route('dashboard') }}">
            <i class="fas fa-store"></i> Coffee POS
        </a>

        <!-- Navbar Toggler for Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Right: Profile Dropdown -->
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <a href="{{ route('order.orderlist') }}" class="nav-link text-light position-relative me-4">Orders
                        @if(($orderPending ?? 0) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $orderPending }}
                            </span>
                        @endif
                    </a>
               </li>
                <li class="nav-item">
                    <a class="nav-link text-light me-2" href="{{ route('paymentRecord') }}">Invoice</a>
                </li>


                <!-- Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        @if (auth()->user()->profile == null)
                            <img class="rounded-circle profile-img"
                                src="{{ asset('admin/images/undraw_profile.svg') }}">
                        @else
                            <img class="rounded-circle profile-img"
                                src="{{ asset('adminProfile/' . auth()->user()->profile) }}">
                        @endif
                    </a>

                    <!-- Dropdown Menu -->
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li class="dropdown-item"><strong>{{ auth()->user()->name }}</strong></li>
                        <li class="dropdown-item text-muted small">Role: {{ auth()->user()->role }}</li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="{{ route('profile.overview') }}">Profile</a></li>
                        <li><a class="dropdown-item" href="{{ route('passwordpage') }}">Reset Password</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger ">Logout</button>
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


    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>

    <script>
        //toggle sidebar
        document.addEventListener('DOMContentLoaded', function () {
            const toggleButton = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebarContainer');
            const body = document.querySelector('.body');

            toggleButton.addEventListener('click', function () {
                sidebar.classList.toggle('show');
                body.classList.toggle('sidebar-open');
            });

            // Close sidebar on click outside
            document.addEventListener('click', function (e) {
                if (body.classList.contains('sidebar-open') &&
                    !sidebar.contains(e.target) &&
                    !toggleButton.contains(e.target)) {
                    sidebar.classList.remove('show');
                    body.classList.remove('sidebar-open');
                }
            });
        });
        // Toggle Submenu
        function toggleSubMenu() {
            const subMenu = document.querySelector('.submenu');
            const icon = document.querySelector('.bi-chevron-down');

            // Toggle display of submenu
            if (subMenu.style.display === 'none') {
                subMenu.style.display = 'block';
                icon.classList.add('rotate-180');
            } else {
                subMenu.style.display = 'none';
                icon.classList.remove('rotate-180');
            }
        }

        function toggleDropdown(menuClass) {
        document.querySelector("." + menuClass).classList.toggle("show");
    }

        function loadFile(event) {
            var file = event.target.files[0];  // Get the selected file

            if (file) {
                var reader = new FileReader();

                reader.onload = function() {
                    var output = document.getElementById('output');
                    output.src = reader.result;
                    console.log("Image preview updated");
                }

                reader.readAsDataURL(file);
                console.log("File read successfully");
            } else {
                console.log("No file selected");
            }
        }
    </script>
</body>

<!--Global Sweet Alert  -->
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

@yield('scripts')

</html>
