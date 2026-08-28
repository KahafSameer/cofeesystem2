@extends('admin.layouts.master')
@section('content')
    <section class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col">
                <div class="row mt-4">
                @if (auth()->user()->role === 'admin' || auth()->user()->role === 'cashier')
                    <div class="col-xl-3 col-md-6 col-sm-12 mb-2">
                        <div class="card border-left-warning shadow h-80 d-flex flex-column"
                            data-bs-toggle="modal" data-bs-target="#outOfStockModal">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-1">
                                        <div class="text-xs fw-bold text-danger mb-1">
                                            Low Stock(items)
                                        </div>
                                        @if($outofstock->isNotEmpty())
                                            <div class="h5 mb-0 fw-bold text-gray-400">{{ count($outofstock) }}</div>
                                        @else
                                            <div class="h5 mb-0 fw-bold text-gray-400">0</div>
                                        @endif
                                    </div>
                                    <div class="col-auto">
                                        <i class="fa-solid fa-2x fa-hourglass-half"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal to show details-->
                    <div class="modal fade" id="outOfStockModal" tabindex="-1" aria-labelledby="outOfStockModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title text-danger" id="outOfStockModalLabel">Almost Out of Stock</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    @if($outofstock->isNotEmpty())
                                        <ul class="list-group">
                                            @foreach($outofstock as $product)
                                                <li class="list-group-item">{{ $product->name }} (Stock: {{ $product->stock }})</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p>No out-of-stock products.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                        <div class="col-xl-3 col-md-6 col-sm-12 mb-2" >
                                <div class="card border-left-warning shadow h-80 d-flex flex-column">
                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                            <div class="text-xs fw-bold text-success mb-1">
                                                    Monthly Purchase</div>
                                                <div class="h5 mb-0 fw-bold text-gray-400">{{ number_format($purchaseCost, 2)  }}
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                            <i class="fa-solid fa-2x fa-cart-shopping"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                                <!-- Daily Sales Card -->
                                <div class="col-xl-3 col-md-6 col-sm-12 mb-2" >
                                    <div class="card border-left-warning shadow h-80 d-flex flex-column">
                                        <div class="card-body d-flex flex-column justify-content-between">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-1">
                                                    <div class="text-xs fw-bold text-primary mb-1">
                                                        Daily Sales</div>
                                                    <div class="h5 mb-0 fw-bold text-gray-400">
                                                        {{ $dailySales }}</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa-solid fa-2x fa-coins"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Monthly Sales Card -->
                                <div class="col-xl-3 col-md-6 col-sm-12 mb-2">
                                    <div class="card border-left-warning shadow h-80 d-flex flex-column">
                                        <div class="card-body d-flex flex-column justify-content-between">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-1">
                                                    <div class="text-xs fw-bold text-secondary mb-1">
                                                        Monthly Sales</div>
                                                    <div class="h5 mb-0 fw-bold text-gray-400">
                                                        {{ $monthlySales }}</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa-solid fa-2x fa-chart-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <!-- Pending Orders Card -->
                    </div>
                    @php
                        // Ensure variables exist for all roles
                        $topProductLabels = [];
                        $topProductCounts = [];

                        // Only populate them if user is an admin
                        if (auth()->user()->role === 'admin') {
                            $topProductLabels = $topProducts->pluck('name')->toArray();
                            $topProductCounts = $topProducts->pluck('total_quantity_sold')->toArray();
                        }
                    @endphp


                        @if (auth()->user()->role === 'admin')
                            <div class="row mt-2">
                            <!-- Popular Products -->
                                <div class="col-xl-6 col-md-12 mb-3">
                                    <div class="card shadow-lg p-3 border-left-warning shadow h-100 d-flex flex-column justify-content-between" style="backdrop-filter: blur(10px); border-radius: 12px;">
                                        <div class="card-body">
                                            <h5 class="fw-bold"><i class="fa-solid fa-chart-pie me-2"></i> Top Products</h5>
                                            <div style="position: relative; height: 200px; max-width: 100%;">
                                                <canvas id="topProductsChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Payment Method -->
                                <div class="col-xl-6 col-md-12 mb-3">
                                    <div class="card shadow-lg p-3 border-left-warning shadow h-100 d-flex flex-column justify-content-between" style="backdrop-filter: blur(10px); border-radius: 12px;">
                                        <div class="card-body">
                                            <h5 class="fw-bold"><i class="fa-solid fa-credit-card me-2"></i> Payment Types</h5>
                                            <div>
                                                <p class="fw-bold mt-3">Cash <span class="float-end">{{ $paymentMethods->where('payment_method', 'cash')->first()->count ?? 0 }}</span></p>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($paymentMethods->where('payment_method', 'cash')->first()->count ?? 0) * 10 }}%"></div>
                                                </div>

                                                <p class="fw-bold mt-3">Mobile <span class="float-end">{{ $paymentMethods->where('payment_method', 'mobile')->first()->count ?? 0 }}</span></p>
                                                <div class="progress">
                                                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ ($paymentMethods->where('payment_method', 'mobile')->first()->count ?? 0) * 10 }}%"></div>
                                                </div>

                                                <p class="fw-bold mt-3">Card <span class="float-end">{{ $paymentMethods->where('payment_method', 'card')->first()->count ?? 0 }}</span></p>
                                                <div class="progress">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ ($paymentMethods->where('payment_method', 'card')->first()->count ?? 0) * 10 }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row mt-2">
                            <!-- Sales Overview Chart -->
                            <div class="col-xl-8 col-md-7">
                                <div class="card shadow">
                                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between" style="background-color: #50301a; border-radius: 6px;">
                                        <h6 class="m-0 fw-bold text-primary">Sales Overview</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-area" style="position: relative; height: 250px;">
                                            <canvas id="myAreaChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @php
                                $labels = $orderType->pluck('order_type'); // ['In-Store', 'Online']
                                $counts = $orderType->pluck('count'); // [25, 45]
                            @endphp
                            <div class="col-xl-4 col-md-5">
                                <div class="card shadow h-100 py-1">
                                    <div class="card-body">
                                        <h5 class="card-title fw-bold">Order Types</h5>
                                        <div style="position: relative; height: 250px; max-width: 100%;">
                                            <canvas id="myPieChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
            </div>
        </div>
    </section>
@endsection
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@section('scripts')
<script>
    const salesOverview = @json($salesOverview);

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize chart for sales overview
        const ctxArea = document.getElementById('myAreaChart');
        if (ctxArea) {
            const areaCtx = ctxArea.getContext('2d');
            new Chart(areaCtx, {
                type: 'line',
                data: {
                    labels: salesOverview.map(data => data.date),
                    datasets: [{
                        label: 'Total Sales',
                        data: salesOverview.map(data => data.daily_sales),
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        lineTension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Sales'
                            }
                        }
                    }
                }
            });
        } else {
            console.error('Canvas element for myAreaChart not found');
        }

        // Initialize chart for Order Types
        const ctxPie = document.getElementById('myPieChart');
        if (ctxPie) {
            const pieCtx = ctxPie.getContext('2d');

            // Dynamic data from Blade
            const labels = @json($labels); //Eat-in, deli, take_away
            const data = @json($counts); // [25, 45]

            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#4e73df', '#1cc88a', '#d6cf0d'],
                        hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        } else {
            console.error('Canvas element for myPieChart not found');
        }

        const ctxTopProducts = document.getElementById('topProductsChart');
        if (ctxTopProducts) {
            const topProductLabels = @json($topProductLabels);
            const topProductCounts = @json($topProductCounts);

            if (topProductLabels.length > 0) {
                console.log("Top Products Labels:", topProductLabels);
                console.log("Top Products Counts:", topProductCounts);

                new Chart(ctxTopProducts, {
                    type: 'doughnut',
                    data: {
                        labels: topProductLabels,
                        datasets: [{
                            data: topProductCounts,
                            backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4caf50', '#9c27b0'],
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            } else {
                console.warn("No data available for Top Products.");
            }
        }
    });

</script>

@endsection
