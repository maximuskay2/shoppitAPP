# Cart Restructure Summary

## Overview
Successfully restructured the cart system from a two-level hierarchy (Cart → CartItem) to a three-level hierarchy (Cart → CartVendor → CartItem) to properly group cart items by vendor.

## Changes Made

### 1. Database Migrations

#### Created: `2025_12_18_132949_create_cart_vendors_table.php`
- Creates intermediary `cart_vendors` table
- Fields: `id` (UUID), `cart_id`, `vendor_id`, timestamps
- Unique constraint on `cart_id + vendor_id` combination
- Foreign keys with cascade delete for both cart and vendor

#### Created: `2025_12_18_133051_update_cart_items_table_add_cart_vendor_id.php`
- Migrates existing cart items to cart_vendors structure
- Updates `cart_items` table to reference `cart_vendors` instead of `carts`
- Removes `cart_id` foreign key and column
- Adds `cart_vendor_id` foreign key and column
- Handles data migration for existing cart items

### 2. Models

#### New Model: `CartVendor.php`
**Location:** `app/Modules/Commerce/Models/CartVendor.php`

**Relationships:**
- `cart()` - belongsTo Cart
- `vendor()` - belongsTo Vendor (User model)
- `items()` - hasMany CartItem

**Methods:**
- `total()` - Calculates vendor subtotal from all items

#### Updated: `Cart.php`
**Changes:**
- Added `vendors()` - hasMany CartVendor relationship
- Updated `items()` - hasManyThrough CartVendor to CartItem
- Updated `total()` - Sums vendor totals instead of item totals

#### Updated: `CartItem.php`
**Changes:**
- Replaced `cart()` with `cartVendor()` - belongsTo CartVendor relationship

### 3. Resources

#### New Resource: `CartVendorResource.php`
**Location:** `app/Http/Resources/Commerce/CartVendorResource.php`

**Output Structure:**
```json
{
  "id": "uuid",
  "vendor": { ... },
  "items": [ ... ],
  "vendor_total": "0.00",
  "item_count": 0
}
```

#### Updated: `CartResource.php`
**Changes:**
- Shows `vendors` collection instead of direct items
- Added `vendor_count` field
- Updated `cart_total` to sum vendor totals
- Updated `total_items` to count through vendors

**Output Structure:**
```json
{
  "id": "uuid",
  "vendors": [
    {
      "id": "uuid",
      "vendor": { ... },
      "items": [ ... ],
      "vendor_total": "0.00",
      "item_count": 0
    }
  ],
  "cart_total": "0.00",
  "total_items": 0,
  "vendor_count": 1
}
```

### 4. Service Layer

#### New Service: `CartService.php`
**Location:** `app/Modules/Commerce/Services/CartService.php`

**Methods:**
- `getOrCreateCart($userId)` - Gets or creates user's cart
- `getCart($userId)` - Retrieves cart with eager-loaded vendors and items
- `addItem($userId, $productId, $quantity)` - Adds product to cart, creates CartVendor if needed
- `updateItemQuantity($userId, $itemId, $quantity)` - Updates item quantity and recalculates subtotal
- `removeItem($userId, $itemId)` - Removes item, cascades CartVendor deletion if empty
- `clearCart($userId)` - Removes all vendors and items from cart
- `clearVendorCart($userId, $vendorId)` - Removes specific vendor's items from cart

**Features:**
- Uses DB transactions for data integrity
- Handles Money objects with Brick\Money
- Proper error handling with InvalidArgumentException
- Auto-creates CartVendor records when adding items
- Cascades deletion of empty CartVendor records

### 5. Controllers

#### Updated: `CartController.php`
**Changes:**
- Added CartService dependency injection
- Updated `index()` - Uses CartService::getCart()
- Updated `addItem()` - Uses CartService::addItem()
- Updated `updateItem()` - Uses CartService::updateItemQuantity()
- Updated `removeItem()` - Uses CartService::removeItem()
- Updated `clearCart()` - Uses CartService::clearCart()
- Added `clearVendorCart()` - New endpoint for clearing vendor-specific items
- Updated `processCart()` - Iterates through cart vendors for order creation

**Notes:**
- Removed direct DB transactions (handled in service)
- Removed manual cart/CartVendor creation logic
- Added comment about multi-vendor checkout in processCart()

### 6. Request Validators

#### Updated: `UpdateCartItemRequest.php`
**Changes:**
- Changed quantity validation from `min:0` to `min:1`
- Removed quantity=0 logic from controller (use removeItem endpoint instead)

### 7. Routes

#### Updated: `routes/v1/api.php`
**Changes:**
- Added `DELETE /cart/vendor/{vendorId}/clear` route for clearing vendor carts

## API Endpoints

### Get Cart
```
GET /api/v1/cart
```
Returns cart with vendors grouped and their items.

### Add Item to Cart
```
POST /api/v1/cart/add
Body: {
  "product_id": "uuid",
  "quantity": 1
}
```
Automatically creates CartVendor for product's vendor if needed.

### Update Cart Item
```
PUT /api/v1/cart/item/{itemId}
Body: {
  "quantity": 2
}
```
Updates item quantity. Must be >= 1.

### Remove Cart Item
```
DELETE /api/v1/cart/item/{itemId}
```
Removes item. Deletes CartVendor if it becomes empty.

### Clear Entire Cart
```
DELETE /api/v1/cart/clear
```
Removes all vendors and items.

### Clear Vendor Cart (NEW)
```
DELETE /api/v1/cart/vendor/{vendorId}/clear
```
Removes specific vendor's items from cart.

### Process Cart (Checkout)
```
POST /api/v1/cart/process
Body: {
  "coupon_code": "optional",
  "order_notes": "optional",
  "is_gift": false,
  "receiver_delivery_address": "required",
  "receiver_name": "optional",
  "receiver_email": "optional",
  "receiver_phone": "optional"
}
```
Creates order from cart items across all vendors.

## Migration Status

✅ Migrations executed successfully:
- `2025_12_18_132949_create_cart_vendors_table`
- `2025_12_18_133051_update_cart_items_table_add_cart_vendor_id`

## Testing Recommendations

1. **Add Items from Different Vendors**
   - Add products from Vendor A
   - Add products from Vendor B
   - Verify cart shows separate vendor groupings

2. **Update Quantities**
   - Update item quantity
   - Verify vendor_total recalculates correctly

3. **Remove Items**
   - Remove item from vendor with multiple items
   - Verify CartVendor persists
   - Remove last item from vendor
   - Verify CartVendor is deleted

4. **Clear Vendor Cart**
   - Add items from multiple vendors
   - Clear one vendor's cart
   - Verify other vendor items remain

5. **Clear Entire Cart**
   - Verify all CartVendor records are deleted
   - Verify all CartItem records are deleted

6. **Process Cart**
   - Add items from multiple vendors
   - Process cart
   - Verify order includes all items
   - Verify OrderLineItems created correctly

7. **Coupon Application**
   - Test coupon with vendor-grouped cart
   - Verify discount applies correctly

## Notes

- The `processCart()` method currently creates a single order for all vendors. If you need separate orders per vendor, you'll need to loop through `$cart->vendors` and create an order for each.
- Cart items are automatically grouped by vendor through the CartVendor intermediary model.
- Empty CartVendor records are automatically cleaned up when the last item is removed.
- All cart operations use DB transactions for data integrity.
- Money calculations use Brick\Money for precision.

## Rollback

If you need to rollback these changes:

```bash
php artisan migrate:rollback --step=2
```

This will revert both migrations and restore the original cart structure.
