@extends('admin.layouts.master')

@section('content')

<section class="container-fluid">
    <div class="row justify-content-center align-items-center">
        <div class="col-md-12 mt-4">
            <div class="row align-items-center mb-4">
                <div class="col-6">
                     <h3 class="fw-bold text-dark">Product Stock Analysis</h3>
                </div>
                <div class="col-6 text-end">
                    <button type="button" class="btn btn-success" onclick="exportTableToExcel('salesTable')">
                        <i class="fas fa-file-excel"></i> Export To Excel
                    </button>
                </div>
            </div>

        <!-- Filter Section  -->
        <div class="card p-3 shadow-sm mb-4">
                <form action="{{ route('productAnalysis') }}" method="GET" class="row g-3">
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
        @if(!empty($stock) && count($stock) > 0)
        <table class="table table-bordered text-center" id="salesTable">
            <thead class="table-dark">
                <tr>
                    <th class="text-center">Product ID</th>
                    <th class="text-center">Category</th>
                    <th class="text-center">Product Name</th>
                    <th class="text-center">In-Stock</th>
                    <th class="text-center">Units Sold</th>
                    <th class="text-center">Remaining Stock</th>
               </tr>
            </thead>
            <tbody>
                @foreach ($stock as $item)
                <tr>
                    <td class="text-center">{{ $item->product_id }}</td>
                    <td class="text-center">{{ $item->category_name }}</td>
                    <td class="text-center">{{ $item->product_name }}</td>
                    <td class="text-center">{{ $item->in_stock }}</td>
                    <td class="text-center">{{ $item->units_sold }}</td>
                    <td class="text-center">{{ $item->remaining_stock }}</td>


                </tr>
                @endforeach
            </tbody>

        </table>
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
    function exportTableToExcel(tableId, filename = 'Product_Stock_Analysis_Report.xlsx') {
        const table = document.getElementById(tableId);
        const workbook = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
        XLSX.writeFile(workbook, filename);
    }

</script>
@endsection
