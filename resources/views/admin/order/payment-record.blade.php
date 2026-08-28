@extends('admin.layouts.master')
@section('content')
    <section class="container mt-2">
        <div class="row justify-content-center align-items-center">
            <div class="col-md-6">
                <div class="card-body mt-2">
                    <!-- Search by ORDER Code -->
                    <div class="payment-card p-4 shadow-lg rounded-3" style="background-color: #50301a;">
                        <!-- Search Form -->
                        <div class="row align-items-center mb-4">
                            <div class="col-lg-6 mb-4 mb-lg-0">
                                <form action="{{ route('searchRecord') }}" method="GET">
                                    <label for="orderCode" class="form-label text-white">Search...</label>
                                    <div class="input-group">
                                        <input type="text" name="searchKey" value="{{ request('searchKey') }}"
                                               class="form-control" placeholder="Type Order Code...">
                                        <button type="submit" class="btn btn-outline-secondary">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            @if ($records->isNotEmpty())
                                <div class="col-lg-6 text-lg-end text-center mt-4">
                                    <button class="btn btn-outline-light w-100" id="printButton"
                                            data-order-code="{{ $records->first()->order_code }}">
                                        <i class="fa fa-print me-2"></i>Print
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Show if no records are found -->
                        @if ($records->isEmpty())
                            <small class="text-light">No records found. Please search by Order Code.</small>
                        @else
                            <!-- Payment Slip Table -->
                            <div class="slip-container bg-white text-black rounded-3" >
                                <div class="text-center mb-3">
                                    <h5 style="margin: 0; font-weight: bold;">Payment Slip</h5>
                                </div>
                                <div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <div>Cashier ID: {{ $records->first()->CashierID ?? 'N/A' }}</div>
                                        <div>Date: {{ $records->first()->Date ?? 'N/A' }}</div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <div>{{ $records->first()->order_code ?? 'N/A' }}</div>
                                        <div>Paid by: {{ $records->first()->payment_method ?? 'N/A' }}</div>
                                    </div>
                                </div>

                                <!-- Table -->
                                <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <th style="text-align: left; border-bottom: 1px solid #000;">Name</th>
                                            <th style="text-align: center; border-bottom: 1px solid #000;">Qty</th>
                                            <th style="text-align: right; border-bottom: 1px solid #000;">Price</th>
                                            <th style="text-align: right; border-bottom: 1px solid #000;">Size</th>
                                            <th style="text-align: right; border-bottom: 1px solid #000;">Discount</th>
                                            <th style="text-align: right; border-bottom: 1px solid #000;">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($records as $item)
                                            <tr>
                                                <td style="padding: 5px 0;">{{ $item->ProductName }}</td>
                                                <td style="text-align: center;">{{ $item->quantity }}</td>
                                                <td style="text-align: right;">{{ $item->price }}</td>
                                                <td style="text-align: right;">{{ strtoupper(substr($item->size, 0, 1)) }}</td>
                                                <td style="text-align: right;">{{ $item->discount_percentage ?? 0 }}%</td>
                                                <td style="text-align: right;">{{ number_format($item->total_price, 2, '.', ',') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div style="border-top: 1px solid #000; margin-top: 10px; padding-top: 5px;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="text-align: left;">Sub Total</td>
                                            <td style="text-align: right;">{{ number_format($subTotalAmt, 2, '.', ',') }}</td>
                                        </tr>
                                        {{-- <tr>
                                            <td style="text-align: left;">Discount</td>
                                            <td style="text-align: right;">{{ $records->first()->discount }}</td>
                                        </tr> --}}
                                        <tr>
                                            <td style="text-align: left;">Tax</td>
                                            <td style="text-align: right;">{{ $taxAmount }}</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left;">Deli Fees:</td>
                                            <td style="text-align: right;">{{ number_format($delivery_fee, 2, '.', ',') }}</td>
                                        </tr>
                                        <tr style="font-weight: bold;">
                                            <td style="text-align: left;">Net Amount</td>
                                            <td style="text-align: right;">{{ $records->first()->paid_amount - $records->first()->change_amount }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div style="border-top: 1px solid #000; margin-top: 10px; padding-top: 5px;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="text-align: left;">Paid Amount</td>
                                            <td style="text-align: right;">{{ $records->first()->paid_amount }}</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left;">Change</td>
                                            <td style="text-align: right;">{{ $records->first()->change_amount }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="text-center" style="font-weight: bold;">Thank You</div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
<script>

    document.getElementById('printButton').addEventListener('click', function () {
        const orderCode = this.getAttribute('data-order-code');

        const printWindow = window.open(`/admin/order/printPaymentSlip/${orderCode}`, "_blank", "width=600,height=400");

    printWindow.onload = function () {
            setTimeout(() => {
                printWindow.print();
            }, 10000);

        };
    });


</script>

@endsection

