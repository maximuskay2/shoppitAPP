# Cart Coupon Integration

## Overview
Successfully integrated per-vendor coupon discounting into the cart system. Each vendor in the cart can have their own coupon applied independently.

## Database Changes

### Migration: `2025_12_22_122630_add_coupon_fields_to_cart_vendors_table`
Added coupon fields to `cart_vendors` table:
- `coupon_id` (UUID, nullable) - Foreign key to coupons table
- `coupon_code` (string, nullable) - Coupon code for reference
- `coupon_discount` (bigint, default 0) - Discount amount in cents

## Model Updates

### CartVendor Model
**Added:**
- Cast for `coupon_discount` using `TXAmountCast`
- `coupon()` relationship - belongsTo Coupon
- `subtotal()` method - Returns vendor subtotal before discount
- `total()` method - Returns vendor total after discount (subtotal - discount)
- `discountAmount()` method - Returns the discount amount

**Methods:**
```php
$cartVendor->subtotal();        // Subtotal before discount
$cartVendor->discountAmount();  // Discount amount
$cartVendor->total();           // Total after discount
```

### Cart Model
No changes needed - `total()` method already aggregates vendor totals (which now include discounts).

## Service Layer

### CartService - New Methods

#### `applyCoupon(User $user, string $vendorId, string $couponCode)`
Applies a coupon to a specific vendor's cart.

**Validation:**
- Checks if vendor exists in cart
- Validates coupon code belongs to the vendor
- Checks if coupon is active and visible
- Verifies user hasn't exceeded usage limit
- Validates minimum order value requirement

**Returns:** Fresh cart with eager-loaded relationships

**Throws:** `InvalidArgumentException` with specific error messages

#### `removeCoupon(User $user, string $vendorId)`
Removes the applied coupon from a specific vendor's cart.

**Returns:** Fresh cart with eager-loaded relationships

#### `validateCoupon(User $user, string $vendorId, string $couponCode)`
Validates if a coupon can be applied without actually applying it.

**Returns:**
```php
[
    'valid' => true|false,
    'message' => 'Validation message',
    'discount' => 100.00,  // If valid
    'coupon' => [          // If valid
        'id' => 'uuid',
        'code' => 'SAVE10',
        'discount_type' => 'percent',
        'discount_amount' => 0,
        'percent' => 10,
    ]
]
```

## Controller Updates

### CartController - New Endpoints

#### Apply Coupon
```
POST /api/v1/user/cart/vendor/{vendorId}/coupon/apply
Body: {
  "coupon_code": "SAVE10"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Coupon applied successfully",
  "data": {
    "id": "cart-uuid",
    "vendors": [
      {
        "id": "cart-vendor-uuid",
        "vendor": {...},
        "items": [...],
        "subtotal": 1000.00,
        "coupon": {
          "id": "coupon-uuid",
          "code": "SAVE10",
          "discount": 100.00
        },
        "vendor_total": 900.00
      }
    ],
    "subtotal": 1000.00,
    "total_discount": 100.00,
    "cart_total": 900.00
  }
}
```

#### Remove Coupon
```
DELETE /api/v1/user/cart/vendor/{vendorId}/coupon/remove
```

**Response:**
```json
{
  "status": true,
  "message": "Coupon removed successfully",
  "data": {
    "id": "cart-uuid",
    "vendors": [...],
    "subtotal": 1000.00,
    "total_discount": 0.00,
    "cart_total": 1000.00
  }
}
```

#### Validate Coupon
```
POST /api/v1/user/cart/vendor/{vendorId}/coupon/validate
Body: {
  "coupon_code": "SAVE10"
}
```

**Response (Valid):**
```json
{
  "status": true,
  "message": "Coupon is valid",
  "data": {
    "valid": true,
    "message": "Coupon is valid",
    "discount": 100.00,
    "coupon": {
      "id": "coupon-uuid",
      "code": "SAVE10",
      "discount_type": "percent",
      "discount_amount": 0,
      "percent": 10
    }
  }
}
```

**Response (Invalid):**
```json
{
  "status": false,
  "message": "Coupon is not valid or has expired",
  "data": null
}
```

## Resource Updates

### CartVendorResource
**Added fields:**
- `subtotal` - Vendor subtotal before discount
- `coupon` - Coupon information (only if coupon is applied)
  - `id` - Coupon ID
  - `code` - Coupon code
  - `discount` - Discount amount
- `vendor_total` - Total after discount (renamed for clarity)

### CartResource
**Added fields:**
- `subtotal` - Cart subtotal before discounts
- `total_discount` - Total discount amount across all vendors
- `cart_total` - Total after all discounts

## API Flow Examples

### Example 1: Apply Coupon to Single Vendor Cart

1. **Get Cart**
```
GET /api/v1/user/cart
```
Response shows vendor with items, no coupon applied.

2. **Validate Coupon (Optional)**
```
POST /api/v1/user/cart/vendor/{vendorId}/coupon/validate
Body: { "coupon_code": "WELCOME10" }
```
Check if coupon is valid before applying.

3. **Apply Coupon**
```
POST /api/v1/user/cart/vendor/{vendorId}/coupon/apply
Body: { "coupon_code": "WELCOME10" }
```
Cart now shows discount applied.

4. **Get Cart Again**
```
GET /api/v1/user/cart
```
Response shows updated totals with discount.

### Example 2: Multi-Vendor Cart with Different Coupons

1. Add items from Vendor A
2. Add items from Vendor B
3. Apply "VENDOR_A_10" to Vendor A's cart
4. Apply "VENDOR_B_20" to Vendor B's cart
5. Each vendor shows their respective discount
6. Cart total reflects both discounts

### Example 3: Remove Coupon

```
DELETE /api/v1/user/cart/vendor/{vendorId}/coupon/remove
```
Discount removed, totals recalculated.

## Validation Rules

### Coupon Validation (from Coupon Model)

1. **Active & Visible Check**
   - `is_active` must be true
   - `is_visible` must be true

2. **Usage Limit Per Customer**
   - Checks `coupon_usages` table
   - User usage count < `usage_per_customer`

3. **Minimum Order Value**
   - Vendor subtotal >= `minimum_order_value`

4. **Discount Calculation**
   - **Flat discount:** Min of discount_amount or order total
   - **Percent discount:** (order_total × percent) / 100
     - Capped by `maximum_discount` if set

## Error Messages

| Scenario | Error Message |
|----------|--------------|
| Vendor not in cart | "Vendor not found in cart" |
| Invalid coupon code | "Invalid coupon code for this vendor" |
| Coupon expired | "Coupon is not valid or has expired" |
| Usage limit exceeded | "Coupon is not valid or has expired" |
| Below minimum order | "Order total must be at least X to use this coupon" |

## Integration with Existing Checkout

The `processCart()` method in CartController already handles order creation. The cart already has coupons applied per vendor, so the discounts are automatically reflected in the vendor totals.

If you want to store per-vendor coupon usage during checkout, you would need to:
1. Loop through each cart vendor
2. If vendor has coupon applied, create CouponUsage record for that vendor
3. Increment the coupon usage_count

## Testing Recommendations

1. **Basic Coupon Application**
   - Apply valid coupon
   - Verify discount calculation
   - Check cart totals update

2. **Validation Scenarios**
   - Apply invalid coupon code
   - Apply coupon to wrong vendor
   - Apply expired coupon
   - Apply coupon below minimum order value
   - Exceed per-customer usage limit

3. **Multi-Vendor Scenarios**
   - Apply different coupons to different vendors
   - Verify each vendor's discount is independent
   - Check total discount is sum of all vendor discounts

4. **Edge Cases**
   - Remove coupon and re-apply
   - Add items after coupon applied (verify discount recalculates)
   - Remove items (check if falls below minimum order value)
   - Apply multiple coupons to same vendor (should replace)

5. **Percent vs Flat Discount**
   - Test percent discount calculation
   - Test flat discount capping at order total
   - Test maximum discount cap for percent discounts

## Notes

- Coupons are vendor-specific and cannot be applied across multiple vendors
- Each vendor in cart can have only one coupon at a time
- Applying a new coupon replaces any existing coupon for that vendor
- Discounts are calculated on vendor subtotal (sum of items before discount)
- When cart items are modified (add/remove/update quantity), discounts are automatically recalculated based on the new subtotal
- Coupon remains applied even if cart goes below minimum order value, but validation will fail if trying to re-apply
- To clear all coupons, remove them individually from each vendor before checkout
