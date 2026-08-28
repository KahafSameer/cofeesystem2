@extends('admin.layouts.master')

@section('content')
<section class="container-fluid">
    <div class="row justify-content-center align-items-center">
        <div class="col-md-12 mt-4">
            <div class="row align-items-center mb-4">
                <div class="col-6">
                    <h3 class="fw-bold text-dark">📊 Daily Sales Report</h3>
                </div>
                <div class="col-6 text-end">
                    <button type="button" class="btn btn-success" onclick="exportTableToExcel('salesTable')">
                        <i class="fas fa-file-excel"></i> Export To Excel
                    </button>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card p-3 shadow-sm mb-4">
                <form action="{{ route('salesReport') }}" method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-dark w-100">🔍 Filter</button>
                    </div>
                </form>
            </div>

            <!-- Table Section -->
            @if(!empty($results) && count($results) > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped text-center" id="salesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Total Sales (MMK)</th>
                            <th>Orders 📦</th>
                            <th>Avg. Order Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $day)
                        <tr>
                            <td>{{ $day->Date }}</td>
                            <td>{{ number_format($day->totalSales, 2) }}</td>
                            <td>{{ $day->totalOrders }}</td>
                            <td>{{ number_format($day->AvgOrdValue, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @else
                <div class="alert alert-secondary text-center" role="alert">
                    🚨 No data found for this date range.
                </div>
            @endif
        </div>
    </div>
</section>


<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
    function exportTableToExcel(tableId, filename = 'Total_Sales_Report.xlsx') {
        const table = document.getElementById(tableId);
        const workbook = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
        XLSX.writeFile(workbook, filename);
    }

</script>

@endsection
