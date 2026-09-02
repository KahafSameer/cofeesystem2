<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V8 Cafe — Bill</title>
    <link rel="stylesheet" href="{{ asset('admin/CSS/slip.css') }}">
    <script>
        /**
         * Waits for all receipt images to load before printing.
         * Handles both already-loaded images and pending ones.
         * Supports img.decode() with fallback for older browsers.
         */
        async function initiatePrintWhenReady() {
            const receiptImages = document.querySelectorAll('.slip-container img');
            const imagePromises = [];

            for (const img of receiptImages) {
                const promise = new Promise((resolve) => {
                    // If already loaded and has content
                    if (img.complete && img.naturalWidth > 0) {
                        resolve();
                        return;
                    }

                    // If already failed to load
                    if (img.complete) {
                        resolve();
                        return;
                    }

                    // Wait for load or error event
                    const handleLoad = () => {
                        img.removeEventListener('load', handleLoad);
                        img.removeEventListener('error', handleError);
                        resolve();
                    };

                    const handleError = () => {
                        img.removeEventListener('load', handleLoad);
                        img.removeEventListener('error', handleError);
                        resolve(); // Resolve even on error to continue
                    };

                    img.addEventListener('load', handleLoad);
                    img.addEventListener('error', handleError);

                    // Try decode() if supported
                    if (img.decode && typeof img.decode === 'function') {
                        img.decode().catch(() => {
                            // Silently handle decode errors
                        });
                    }
                });

                imagePromises.push(promise);
            }

            // Wait for all images
            await Promise.all(imagePromises);

            // Additional delay to ensure render pipeline is complete
            await new Promise(resolve => setTimeout(resolve, 300));

            // Trigger print
            window.print();
        }

        // Start when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initiatePrintWhenReady);
        } else {
            initiatePrintWhenReady();
        }
    </script>
</head>
<body>
<div class="slip-container">

    {{-- ===== HEADER ===== --}}
    <div class="receipt-header">
        <div class="receipt-logo-wrap">
            <img src="{{ asset('images/logo.png') }}" alt="V8 Cafe" class="receipt-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <span class="receipt-logo-placeholder" style="display:none;">YOUR LOGO<br>HERE</span>
        </div>

        <div class="receipt-cafe-name">V8 Cafe</div>

        <div class="receipt-bean-divider">
            <img src="{{ asset('images/icons8-coffee-beans-50.svg') }}" alt="Coffee" class="receipt-bean-icon" style="width:16px;height:16px;">
        </div>

        <div class="receipt-contact-row">
            <div class="receipt-contact-item">
                <img src="{{ asset('images/icons8-mail-24.svg') }}" alt="Phone" style="width:14px;height:14px;">
                <span>+92 312 3355774</span>
            </div>
            <div class="receipt-contact-item">
                <img src="{{ asset('images/icons8-mail-24.svg') }}" alt="Email" style="width:14px;height:14px;">
                <span>v8cafe0@gmail.com</span>
            </div>
        </div>

        @php
            $branch        = $session->branch ?? null;
            $branchAddress = $branch?->address ?? $branchName ?? null;
            $bName         = $branch?->name ?? $branchName ?? null;
            $displayBranch = implode(', ', array_filter([$bName, $branchAddress]));
        @endphp
        @if($displayBranch)
        <div class="receipt-branch-line">
            <img src="{{ asset('images/icons8-location-50.svg') }}" alt="Location" style="width:13px;height:13px;vertical-align:middle;">
            <strong>Branch:</strong> {{ $displayBranch }}
        </div>
        @endif
    </div>

    <hr class="receipt-separator">

    {{-- ===== BILL INFO — 2-column grid ===== --}}
    @php
        $billId        = $settlement?->order_code ?? ('#' . $session->session_code);
        $cashierName   = auth()->user()->name ?? '—';
        $dateStr       = $session->opened_at?->format('d M Y') ?? now()->format('d M Y');
        $timeStr       = $session->opened_at?->format('h:i A') ?? now()->format('h:i A');
        $tableDisplay  = $session->session_code ?? '—';
        $orderTypeLabel = 'Dine In';
    @endphp

    <div class="receipt-bill-grid">
        <div class="receipt-bill-grid-item">
            <span class="bill-grid-label">Bill ID</span>
            <span class="bill-grid-colon">:</span>
            <span class="bill-grid-value">{{ $billId }}</span>
        </div>
        <div class="receipt-bill-grid-item">
            <span class="bill-grid-label">Cashier</span>
            <span class="bill-grid-colon">:</span>
            <span class="bill-grid-value">{{ $cashierName }}</span>
        </div>
        <div class="receipt-bill-grid-item">
            <span class="bill-grid-label">Date</span>
            <span class="bill-grid-colon">:</span>
            <span class="bill-grid-value">{{ $dateStr }}</span>
        </div>
        <div class="receipt-bill-grid-item">
            <span class="bill-grid-label">Table</span>
            <span class="bill-grid-colon">:</span>
            <span class="bill-grid-value">{{ $tableDisplay }}</span>
        </div>
        <div class="receipt-bill-grid-item">
            <span class="bill-grid-label">Time</span>
            <span class="bill-grid-colon">:</span>
            <span class="bill-grid-value">{{ $timeStr }}</span>
        </div>
        <div class="receipt-bill-grid-item">
            <span class="bill-grid-label">Order Type</span>
            <span class="bill-grid-colon">:</span>
            <span class="bill-grid-value">{{ $orderTypeLabel }}</span>
        </div>
    </div>

    <hr class="receipt-separator">

    {{-- ===== ITEMS TABLE ===== --}}
    <div class="receipt-items-header">
        <span class="col-item">ITEM</span>
        <span class="col-qty">QTY</span>
        <span class="col-price">UNIT PRICE</span>
        <span class="col-total">TOTAL</span>
    </div>

    @foreach ($groupedOrders as $orderCode => $group)
        @foreach ($group as $line)
        <div class="receipt-item">
            <div class="receipt-item-row">
                <span class="col-item">{{ $line->product?->name ?? '—' }}</span>
                <span class="col-qty">{{ $line->quantity }}</span>
                <span class="col-price">{{ number_format((float)$line->totalprice, 2) }}</span>
                <span class="col-total">{{ number_format((float)$line->totalprice * (int)$line->quantity, 2) }}</span>
            </div>
            @if($line->size)
            <div class="receipt-item-size">{{ $line->size }}</div>
            @endif
            @if($line->notes)
            <div class="receipt-item-notes">{{ $line->notes }}</div>
            @endif
        </div>
        @endforeach
    @endforeach

    {{-- ===== FINANCIAL SUMMARY ===== --}}
    <div class="receipt-summary">
        <div class="receipt-summary-row">
            <span class="summary-label">Subtotal</span>
            <span class="summary-value">{{ number_format((float)$subTotal, 2) }}</span>
        </div>

        @if((float)$taxAmount > 0)
        <div class="receipt-summary-row">
            <span class="summary-label">Tax ({{ $taxRate }}%)</span>
            <span class="summary-value">{{ number_format((float)$taxAmount, 2) }}</span>
        </div>
        @endif
    </div>

    <div class="receipt-total-row">
        <span class="total-label">TOTAL</span>
        <span class="total-value">Rs. {{ number_format((float)$total, 2) }}</span>
    </div>

    <hr class="receipt-separator">

    {{-- ===== PAYMENT + THANK YOU ===== --}}
    <div class="receipt-payment-section">
        <div class="receipt-payment">
            @if($settlement)
                @if($settlement->payment_method)
                <div class="receipt-payment-row">
                    <span class="pay-label">Payment Method</span>
                    <span class="pay-colon">:</span>
                    <span class="pay-value">{{ strtoupper($settlement->payment_method) }}</span>
                </div>
                @endif
                <div class="receipt-payment-row">
                    <span class="pay-label">Paid</span>
                    <span class="pay-colon">:</span>
                    <span class="pay-value">Rs. {{ number_format((float)$settlement->paid_amount, 2) }}</span>
                </div>
                <div class="receipt-payment-row">
                    <span class="pay-label">Change</span>
                    <span class="pay-colon">:</span>
                    <span class="pay-value">Rs. {{ number_format((float)$settlement->change_amount, 2) }}</span>
                </div>
            @endif
        </div>

        <div class="receipt-thankyou">
            <img src="{{ asset('images/icons8-coffee-cup-64.svg') }}" alt="Coffee" class="receipt-thankyou-icon" style="width:22px;height:22px;">
            <span class="receipt-thankyou-text">
                Thank you<br>for visiting! ♡
            </span>
        </div>
    </div>

    <hr class="receipt-separator">

    {{-- ===== SOCIAL FOOTER ===== --}}
    <div class="receipt-social-section">
        <div class="receipt-follow-label">FOLLOW US</div>

        <div class="receipt-social-row">
            <div class="receipt-social-item">
                <span class="receipt-social-icon">
                    <img src="{{ asset('images/icons8-tiktok-logo.svg') }}" alt="TikTok">
                </span>
                <span class="receipt-social-platform">TikTok</span>
                <span class="receipt-social-handle">V8.cafe</span>
            </div>
            <div class="receipt-social-item">
                <span class="receipt-social-icon">
                    <img src="{{ asset('images/icons8-instagram-logo.svg') }}" alt="Instagram">
                </span>
                <span class="receipt-social-platform">Instagram</span>
                <span class="receipt-social-handle">v8cafee</span>
            </div>
            <div class="receipt-social-item">
                <span class="receipt-social-icon">
                    <img src="{{ asset('images/icons8-facebook-logo.svg') }}" alt="Facebook">
                </span>
                <span class="receipt-social-platform">Facebook</span>
                <span class="receipt-social-handle">V8 Cafe</span>
            </div>
        </div>

        <div class="receipt-tagline">
            <span class="receipt-tagline-pill">
                <img src="{{ asset('images/icons8-coffee-cup-64.svg') }}" alt="Coffee" style="width:14px;height:14px;">
                Quality and passion in every cup.
            </span>
        </div>
    </div>

</div>
</body>
</html>
