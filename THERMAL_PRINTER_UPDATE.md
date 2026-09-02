# V8 Cafe Receipt — 80mm Thermal Printer Update

## Changes Made

The receipt print formatting has been completely optimized for **80mm thermal printers** (standard POS printer size).

### Files Updated

1. **`public/admin/CSS/slip.css`** — Complete CSS rewrite
2. **`resources/views/cashier/sessions/bill.blade.php`** — Image loading sync script
3. **`resources/views/admin/order/payment-slip.blade.php`** — Image loading sync script

---

## Key Optimizations for 80mm Thermal Printer

### Page Layout
- **Container Width**: 80mm (304px at 96dpi) - matches thermal printer paper width
- **Margins**: Minimal (6px top/bottom, 10px left/right)
- **Page Size**: Auto-height to accommodate variable receipt lengths

### Typography & Spacing
All dimensions scaled for thermal printer readability:
- **Base Font Size**: 11px (was 13px)
- **Cafe Name**: 22px (was 36px)
- **Bill Grid**: 10px labels with 2-column layout
- **Items Table**: 9px font with compact column widths
- **Total Row**: 12px bold

### Logo & Images
- **Logo**: 140×70px (was 353×180px) - optimized for thermal printer
- **Social Icons**: 20×20px (was 32×32px)
- **Coffee Bean Divider**: Reduced line width and spacing
- **All images**: Using asset() URLs with proper loading synchronization

### Spacing & Padding
Reduced throughout to minimize paper waste:
- Margins: 3px-6px (was 6px-24px)
- Gaps: 2px-12px (was 6px-24px)
- Line heights: 1.3-1.5 (was 1.4-1.7)

### Items Table
- **Column widths**: QTY 30px | UNIT PRICE 55px | TOTAL 60px (was 45px/90px/90px)
- **Header padding**: 4px (was 7px)
- **Item padding**: 4px (was 7px)

### Social Footer
- **Font size**: 8px handles (was 11px)
- **Icon size**: 20px (was 32px)
- **Border thickness**: 0.5px-1px (was 1px)

### Print Media Query
```css
@page {
    size: 80mm auto;
    margin: 0;
    padding: 0;
}
```
- Automatically sets to 80mm width when printing
- Height is auto to fit content
- No margins for full paper utilization

---

## Image Loading Synchronization

### Problem Solved
Previously, `window.print()` was called immediately via `body onload`, causing images to appear blank in print preview because they hadn't finished loading.

### Solution Implemented
An async JavaScript function now:
1. **Detects all images** in `.slip-container`
2. **Checks if already loaded** (img.complete && img.naturalWidth > 0)
3. **Waits for pending images** (attaches load/error event listeners)
4. **Decodes images** (uses img.decode() where supported)
5. **Adds 300ms buffer** for render pipeline completion
6. **Then triggers print** (window.print())

### Features
✅ Works with both PNG and SVG images  
✅ Graceful fallback for older browsers  
✅ Continues even if images fail to load  
✅ No longer blocks page on immediate load  

---

## Print Output Specifications

### Receipt Dimensions
- **Width**: 80mm (exactly fits thermal printer paper)
- **Height**: Variable (auto, based on content)
- **Font**: Optimized for 203 dpi thermal printer resolution
- **Colors**: Black on white (thermal printer compatible)

### What Prints
- Logo (140×70px)
- Cafe name & dividers
- Contact info (phone, email)
- Branch location
- Bill ID, Cashier, Date, Time, Table, Order Type
- Item table with quantities and prices
- Subtotal, Tax, Discounts
- **Total** (bold, prominent)
- Payment method & change
- Thank you message
- Social media footer with icons
- Tagline pill

### Best Practices
- Print directly to thermal printer (no scaling)
- Use portrait orientation
- Ensure printer driver preserves print-color-adjust: exact
- Test with your actual 80mm thermal printer before production

---

## Testing Checklist

- [ ] Open receipt bill page in browser
- [ ] Verify all images display correctly
- [ ] Open print preview (Ctrl+P or Cmd+P)
- [ ] Wait for images to load (automatic via new script)
- [ ] Verify images appear in preview before printing
- [ ] Print to 80mm thermal printer
- [ ] Check output dimensions and clarity
- [ ] Verify logo, icons, and all text print correctly
- [ ] Check alignment of columns and spacing

---

## Rollback (if needed)

If reverting to previous version:
- Restore `public/admin/CSS/slip.css`
- Restore `resources/views/cashier/sessions/bill.blade.php`
- Restore `resources/views/admin/order/payment-slip.blade.php`

---

## Notes

- **No design changes**: Logo, layout, and content remain the same
- **All images preserved**: Logo, SVG icons, all graphics included
- **Responsive browser view**: Scales nicely on different screen sizes
- **Print optimized**: 80mm thermal printer is primary output target
- **Asset URLs**: All images use Laravel `asset()` for reliable loading

---

**Updated**: September 2, 2026  
**Optimized for**: 80mm Thermal Receipt Printer (POS Standard)
