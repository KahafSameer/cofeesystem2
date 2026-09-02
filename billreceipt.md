# V8 Cafe - Bill/Receipt Documentation

## 📋 Overview
Complete documentation for the V8 Cafe bill/receipt printing system with modern design, SVG icons, and thermal printer support.

---

## 🎯 Features

✅ **Professional Design** - Clean, modern receipt layout  
✅ **Logo Support** - 180px logo with PNG/SVG fallback  
✅ **Custom Slogan** - "Quality and passion in every cup."  
✅ **SVG Icons** - Social media icons, location, email, coffee beans  
✅ **Responsive** - Works on screen + thermal printers (80mm)  
✅ **Auto Print** - Opens print dialog automatically  
✅ **2-Column Bill Info** - Compact bill details layout  
✅ **Dark Header Table** - Professional items table with black header  

---

## 📁 File Structure

```
├── resources/views/
│   ├── cashier/sessions/
│   │   └── bill.blade.php          # Waiter session bill
│   └── admin/order/
│       └── payment-slip.blade.php  # Direct cashier POS receipt
├── public/
│   ├── admin/CSS/
│   │   └── slip.css                # Receipt stylesheet
│   └── images/
│       ├── logo.png                # Main logo (677x369)
│       ├── logo.svg                # Logo SVG fallback
│       ├── icons8-coffee-beans-50.svg
│       ├── icons8-coffee-cup-64.svg
│       ├── icons8-facebook-logo.svg
│       ├── icons8-instagram-logo.svg
│       ├── icons8-location-50.svg
│       ├── icons8-mail-24.svg
│       └── icons8-tiktok-logo.svg
```

---

## 🎨 Design Specifications

### Logo
- **Size**: 180px × 180px (screen), 180px × 180px (print)
- **Format**: PNG (primary), SVG (fallback)
- **Location**: `public/images/logo.png`

### Colors
- **Header Background**: `#000` (black)
- **Header Text**: `#fff` (white)
- **Body Text**: `#000` (black)
- **Secondary Text**: `#555` (gray)
- **Separator**: `#aaa` (dashed)

### Fonts
- **Primary**: Segoe UI, Arial, Helvetica, sans-serif
- **Size**: 13px (body), 36px (cafe name), 15px (total)
- **Thank You**: Georgia, serif (italic)

### Spacing
- **Container Padding**: 8px 20px 16px
- **Logo Margin**: 0 auto 2px
- **Header Margin**: 4px bottom
- **Separator Margin**: 6px top/bottom

---

## 🖨️ Routes

### Cashier Session Bill
```php
Route: cashier.sessionBill
URL: GET /cashier/sessions/{sessionId}/bill
Controller: CashierController@sessionBill
View: resources/views/cashier/sessions/bill.blade.php
```

### Admin Payment Slip
```php
Route: admin.printPaymentSlip
URL: GET /admin/orders/printPaymentSlip/{orderCode}
Controller: OrderController@printPaymentSlip
View: resources/views/admin/order/payment-slip.blade.php
```

---

## 💾 Required Data

### Session Bill (`bill.blade.php`)
```php
$session        // CustomerSession model
$groupedOrders  // Orders grouped by order_code
$subTotal       // float
$taxRate        // float (percentage)
$taxAmount      // float
$total          // float
$settlement     // PaymentRecord model (nullable)
$branchName     // string (optional)
```

### Payment Slip (`payment-slip.blade.php`)
```php
$records        // Collection of order records
$subTotalAmt    // float
$taxAmount      // float
$deliveryFee    // float (optional)
$cashierName    // string (optional)
$branchName     // string (optional)
$orderType      // int (1=Dine In, 2=Take Away, 3=Delivery)
```

---

## 🏗️ HTML Structure

### 1. Header Section
```html
<div class="receipt-header">
    <!-- Logo (180x180px) -->
    <div class="receipt-logo-wrap">
        <img src="logo.png" class="receipt-logo">
    </div>
    
    <!-- Cafe Name -->
    <div class="receipt-cafe-name">V8 CAFE</div>
    
    <!-- Coffee Bean Divider -->
    <div class="receipt-bean-divider">
        <img src="icons8-coffee-beans-50.svg">
    </div>
    
    <!-- Contact Info (Phone + Email) -->
    <div class="receipt-contact-row">
        <div class="receipt-contact-item">
            <img src="icons8-mail-24.svg">
            <span>+92 312 3355774</span>
        </div>
        <div class="receipt-contact-item">
            <img src="icons8-mail-24.svg">
            <span>v8cafe0@gmail.com</span>
        </div>
    </div>
    
    <!-- Branch Address -->
    <div class="receipt-branch-line">
        <img src="icons8-location-50.svg">
        <strong>Branch:</strong> Khyber Mall, Chichawatni
    </div>
</div>
```

### 2. Bill Info Grid (2-Column)
```html
<div class="receipt-bill-grid">
    <!-- Row 1 -->
    <div class="receipt-bill-grid-item">
        <span class="bill-grid-label">Bill ID</span>
        <span class="bill-grid-colon">:</span>
        <span class="bill-grid-value">#V8C-000125</span>
    </div>
    <div class="receipt-bill-grid-item">
        <span class="bill-grid-label">Cashier</span>
        <span class="bill-grid-colon">:</span>
        <span class="bill-grid-value">Ahmed</span>
    </div>
    
    <!-- Row 2 -->
    <div class="receipt-bill-grid-item">
        <span class="bill-grid-label">Date</span>
        <span class="bill-grid-colon">:</span>
        <span class="bill-grid-value">31 Aug 2026</span>
    </div>
    <div class="receipt-bill-grid-item">
        <span class="bill-grid-label">Table</span>
        <span class="bill-grid-colon">:</span>
        <span class="bill-grid-value">05</span>
    </div>
    
    <!-- Row 3 -->
    <div class="receipt-bill-grid-item">
        <span class="bill-grid-label">Time</span>
        <span class="bill-grid-colon">:</span>
        <span class="bill-grid-value">03:42 PM</span>
    </div>
    <div class="receipt-bill-grid-item">
        <span class="bill-grid-label">Order Type</span>
        <span class="bill-grid-colon">:</span>
        <span class="bill-grid-value">Dine In</span>
    </div>
</div>
```

### 3. Items Table
```html
<!-- Header (Dark Background) -->
<div class="receipt-items-header">
    <span class="col-item">ITEM</span>
    <span class="col-qty">QTY</span>
    <span class="col-price">UNIT PRICE</span>
    <span class="col-total">TOTAL</span>
</div>

<!-- Item Rows -->
<div class="receipt-item">
    <div class="receipt-item-row">
        <span class="col-item">Cappuccino</span>
        <span class="col-qty">2</span>
        <span class="col-price">450.00</span>
        <span class="col-total">900.00</span>
    </div>
    <div class="receipt-item-size">Double Shot, Large</div>
</div>
```

### 4. Financial Summary
```html
<div class="receipt-summary">
    <!-- Subtotal -->
    <div class="receipt-summary-row">
        <span class="summary-label">Subtotal</span>
        <span class="summary-value">1,800.00</span>
    </div>
    
    <!-- Discount (if applicable) -->
    <div class="receipt-summary-row discount">
        <span class="summary-label">Discount (10%)</span>
        <span class="summary-value">180.00</span>
    </div>
    
    <!-- Tax -->
    <div class="receipt-summary-row">
        <span class="summary-label">Tax (5%)</span>
        <span class="summary-value">81.00</span>
    </div>
</div>

<!-- Total -->
<div class="receipt-total-row">
    <span class="total-label">TOTAL</span>
    <span class="total-value">Rs. 1,701.00</span>
</div>
```

### 5. Payment + Thank You
```html
<div class="receipt-payment-section">
    <!-- Left: Payment Info -->
    <div class="receipt-payment">
        <div class="receipt-payment-row">
            <span class="pay-label">Payment Method</span>
            <span class="pay-colon">:</span>
            <span class="pay-value">CASH</span>
        </div>
        <div class="receipt-payment-row">
            <span class="pay-label">Paid</span>
            <span class="pay-colon">:</span>
            <span class="pay-value">Rs. 2,000.00</span>
        </div>
        <div class="receipt-payment-row">
            <span class="pay-label">Change</span>
            <span class="pay-colon">:</span>
            <span class="pay-value">Rs. 299.00</span>
        </div>
    </div>
    
    <!-- Right: Thank You -->
    <div class="receipt-thankyou">
        <img src="icons8-coffee-cup-64.svg" style="width:22px;">
        <span class="receipt-thankyou-text">
            Thank you<br>for visiting! ♡
        </span>
    </div>
</div>
```

### 6. Social Footer
```html
<div class="receipt-social-section">
    <!-- FOLLOW US Label -->
    <div class="receipt-follow-label">FOLLOW US</div>
    
    <!-- Social Icons Row -->
    <div class="receipt-social-row">
        <!-- TikTok -->
        <div class="receipt-social-item">
            <span class="receipt-social-icon">
                <img src="icons8-tiktok-logo.svg">
            </span>
            <span class="receipt-social-platform">TikTok</span>
            <span class="receipt-social-handle">V8.cafe</span>
        </div>
        
        <!-- Instagram -->
        <div class="receipt-social-item">
            <span class="receipt-social-icon">
                <img src="icons8-instagram-logo.svg">
            </span>
            <span class="receipt-social-platform">Instagram</span>
            <span class="receipt-social-handle">v8cafee</span>
        </div>
        
        <!-- Facebook -->
        <div class="receipt-social-item">
            <span class="receipt-social-icon">
                <img src="icons8-facebook-logo.svg">
            </span>
            <span class="receipt-social-platform">Facebook</span>
            <span class="receipt-social-handle">V8 Cafe</span>
        </div>
    </div>
    
    <!-- Tagline Pill -->
    <div class="receipt-tagline">
        <span class="receipt-tagline-pill">
            <img src="icons8-coffee-cup-64.svg" style="width:14px;">
            Quality and passion in every cup.
        </span>
    </div>
</div>
```

---

## 🎯 CSS Classes Reference

### Container
- `.slip-container` - Main receipt wrapper (480px max)

### Header
- `.receipt-header` - Header section wrapper
- `.receipt-logo-wrap` - Logo container
- `.receipt-logo` - Logo image (180x180px)
- `.receipt-cafe-name` - Cafe name (36px, bold)
- `.receipt-bean-divider` - Coffee bean separator
- `.receipt-contact-row` - Contact info row (phone + email)
- `.receipt-contact-item` - Individual contact item
- `.receipt-branch-line` - Branch address line

### Bill Info
- `.receipt-bill-grid` - 2-column grid container
- `.receipt-bill-grid-item` - Single grid item
- `.bill-grid-label` - Label (Bill ID, Date, etc.)
- `.bill-grid-colon` - Colon separator
- `.bill-grid-value` - Value text

### Items Table
- `.receipt-items-header` - Table header (black bg)
- `.col-item` - Item name column
- `.col-qty` - Quantity column (45px)
- `.col-price` - Unit price column (90px)
- `.col-total` - Total column (90px)
- `.receipt-item` - Item row wrapper
- `.receipt-item-row` - Item row flex container
- `.receipt-item-size` - Size/modifier text (italic)
- `.receipt-item-notes` - Order notes (italic)

### Summary
- `.receipt-summary` - Summary section wrapper
- `.receipt-summary-row` - Single summary row
- `.summary-label` - Label (Subtotal, Tax, etc.)
- `.summary-value` - Value (right-aligned)
- `.receipt-total-row` - Total row (bold, 15px)
- `.total-label` - TOTAL label
- `.total-value` - Total amount

### Payment
- `.receipt-payment-section` - Payment + thank you wrapper
- `.receipt-payment` - Payment info left side
- `.receipt-payment-row` - Single payment row
- `.pay-label` - Payment label
- `.pay-colon` - Colon separator
- `.pay-value` - Payment value
- `.receipt-thankyou` - Thank you right side (italic)
- `.receipt-thankyou-icon` - Coffee cup icon
- `.receipt-thankyou-text` - Thank you text

### Social Footer
- `.receipt-social-section` - Social footer wrapper
- `.receipt-follow-label` - "FOLLOW US" label
- `.receipt-social-row` - Social icons row
- `.receipt-social-item` - Single social item (TikTok/Insta/FB)
- `.receipt-social-icon` - Icon wrapper (32x32px)
- `.receipt-social-platform` - Platform name (bold)
- `.receipt-social-handle` - Handle/username
- `.receipt-tagline` - Tagline wrapper
- `.receipt-tagline-pill` - Pill button with slogan

### Utilities
- `.receipt-separator` - Dashed line separator
- `.receipt-text-center` - Center align text
- `.receipt-text-right` - Right align text
- `.receipt-font-bold` - Bold text

---

## 🖨️ Print Optimization

### Print Media Query
```css
@media print {
    @page { size: A4; margin: 10mm; }
    body { font-size: 12px; }
    .slip-container { 
        max-width: 100%;
        margin: 0;
        padding: 0;
        border: none;
    }
    .receipt-logo { width: 180px; height: 180px; }
    .receipt-items-header {
        background: #000 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
```

---

## 🔧 Customization Guide

### Change Logo
Replace `public/images/logo.png` with your logo (recommended size: 180x180px)

### Change Contact Info
Update in blade templates:
```php
<span>+92 312 3355774</span>      // Phone
<span>v8cafe0@gmail.com</span>    // Email
```

### Change Branch
Update in blade templates:
```php
<strong>Branch:</strong> {{ $branchName }}
```

### Change Slogan
Update in blade templates:
```html
Quality and passion in every cup.
```

### Change Social Handles
Update in blade templates:
```html
<span class="receipt-social-handle">V8.cafe</span>      // TikTok
<span class="receipt-social-handle">v8cafee</span>      // Instagram
<span class="receipt-social-handle">V8 Cafe</span>      // Facebook
```

### Change Colors
Edit `slip.css`:
```css
.receipt-items-header { background: #000; color: #fff; }  /* Table header */
.receipt-cafe-name { color: #1a1a1a; }                    /* Cafe name */
```

---

## 🐛 Troubleshooting

### Images Not Loading
1. Check file exists: `ls -la public/images/logo.png`
2. Fix permissions: `chmod 644 public/images/*.png`
3. Clear cache: `php artisan cache:clear`
4. Check nginx config allows static files

### Print Dialog Not Opening
Check `<body onload="window.print();">` is present in HTML

### Logo Too Small/Large
Adjust in `slip.css`:
```css
.receipt-logo { width: 180px; height: 180px; }
```

### Extra White Space
Reduce margins in `slip.css`:
```css
.slip-container { padding: 8px 20px 16px; }
.receipt-header { margin-bottom: 4px; }
```

---

## 📝 Notes

- **Auto Print**: Opens print dialog on page load
- **Thermal Printer**: Optimized for 80mm thermal printers
- **Responsive**: Works on all screen sizes
- **SVG Icons**: Scalable vector graphics for sharp prints
- **Fallback Logo**: Shows "YOUR LOGO HERE" if image fails

---

## ✅ Checklist

- [x] Logo uploaded to `public/images/logo.png`
- [x] All SVG icons in `public/images/`
- [x] CSS file at `public/admin/CSS/slip.css`
- [x] Both blade files updated
- [x] File permissions set (644 for files, 755 for folders)
- [x] Nginx configured to serve static files
- [x] Laravel cache cleared
- [x] Tested on actual printer

---

**Created**: September 2, 2026  
**Version**: 1.0  
**Author**: V8 Cafe Development Team
