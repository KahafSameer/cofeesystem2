# V8 Cafe Receipt — Item Row Border Update

## Changes Made

### Removed
- ❌ Background fill colors on item rows
- ❌ Alternating white/light gray backgrounds
- ❌ Bottom dashed borders only

### Added
- ✅ Dotted borders around entire item row
- ✅ Clean white background (#fff) for all items
- ✅ Consistent styling across all items

## Visual Changes

### Before
```
┌─ Item Row (alternating colors)
├─ White background
├─ Light gray background
└─ Bottom dashed line only
```

### After
```
┌──────────────────────────────┐
│  Item Row (dotted border)    │
│  • Clean white background    │
│  • Dotted border all around  │
├──────────────────────────────┤
```

## CSS Applied

```css
.receipt-item {
    padding: 6px;
    border: 1px dotted #ccc;
    background: #fff;
}
```

## Benefits

✅ **Cleaner appearance** — no distracting alternating colors  
✅ **Better focus** — dotted border frames each item  
✅ **Professional look** — consistent white background  
✅ **Thermal printer friendly** — dotted lines print clearly  
✅ **Improved readability** — subtle visual separation  

## Thermal Printer Output

On 80mm thermal printer:
- Dotted border prints clearly and visibly
- White background provides clean contrast
- Text stands out against white without background color distraction
- Professional, minimalist appearance

## Files Updated

- `public/admin/CSS/slip.css` — Item row styling

## Testing

1. Open receipt in browser
2. Verify items have dotted borders
3. Confirm no background fill (pure white)
4. Print preview — check dotted border appearance
5. Print to thermal printer
6. Verify output matches design

---

**Updated**: September 3, 2026  
**Style**: Dotted borders, clean white background  
**Target**: 80mm Thermal Printer
