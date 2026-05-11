# Customer Home Page - Comprehensive Debugging Guide

## Overview
The customer home page (`resources/views/public/home.blade.php`) is a fully functional SPA (Single Page Application) that loads restaurant data, displays products by category, manages a shopping cart, and submits orders. This guide covers debugging, testing, and common issues.

---

## Architecture Overview

### State Management
The application uses a centralized state object containing:
- `settings` - Restaurant configuration (name, logo, hours, phone, etc.)
- `categories` - All product categories with parent-child hierarchy
- `products` - Filtered products based on category selection
- `allProducts` - Complete unfiltered product list
- `featuredProducts` - Products marked as featured
- `deliveryZones` - Available delivery zones
- `selectedRoot` - Currently selected root category
- `selectedSub` - Currently selected subcategory
- `cart` - Shopping cart items with product details
- `errors` - API error tracking

### Data Flow
```
1. Page Load
   ↓
2. init() → wireEvents() + renderLoadingSkeletons()
   ↓
3. loadAll() → fetchSettings/Categories/Products/Zones (parallel)
   ↓
4. renderAll() → Display all UI components
   ↓
5. Event Delegation → User interactions trigger state updates → Re-render affected components
```

---

## API Endpoints Reference

### 1. Settings
**Endpoint:** `GET /api/v1/settings/public`
**Response:**
```json
{
  "success": true,
  "data": {
    "restaurant_name": "My Restaurant",
    "restaurant_logo": "storage/...",
    "restaurant_phone": "+1234567890",
    "restaurant_whatsapp": "+1234567890",
    "opening_hours": {
      "monday": {"is_open": true, "from": "09:00", "to": "22:00"},
      ...
    },
    "is_open_now": true,
    "is_accepting_orders": true,
    "delivery_note": "Delivery available within 5km"
  }
}
```

### 2. Categories
**Endpoint:** `GET /api/v1/categories`
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Appetizers",
      "name_ar": "المقبلات",
      "name_en": "Appetizers",
      "image": "storage/...",
      "parent_id": null,
      "sort_order": 1
    },
    {
      "id": 2,
      "name": "Small Appetizers",
      "parent_id": 1,
      ...
    }
  ]
}
```

### 3. Products
**Endpoint:** `GET /api/v1/products`
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Hummus",
      "name_ar": "حمص",
      "name_en": "Hummus",
      "description_ar": "...",
      "description_en": "...",
      "price": 5.99,
      "discount_price": null,
      "effective_price": 5.99,
      "image": "storage/...",
      "category_id": 1,
      "is_available": true,
      "is_featured": true,
      "sort_order": 1,
      "slug": "hummus"
    }
  ]
}
```

### 4. Delivery Zones
**Endpoint:** `GET /api/v1/delivery-zones`
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "area_name": "Downtown",
      "estimated_fee": 5.00,
      "sort_order": 1
    }
  ]
}
```

### 5. Create Order
**Endpoint:** `POST /api/v1/orders`
**Request:**
```json
{
  "customer_name": "John Doe",
  "customer_phone": "+1234567890",
  "order_type": "delivery|table|takeaway",
  "order_type": "delivery",
  "address": "123 Main St",
  "delivery_type": "immediate|scheduled",
  "scheduled_at": "2026-05-15T18:00:00",
  "estimated_delivery_fee": 5.00,
  "customer_note": "No onions please",
  "items": [
    {"product_id": 1, "quantity": 2},
    {"product_id": 3, "quantity": 1}
  ]
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Order placed successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20260509-001",
    "status": "pending",
    "items": [...]
  }
}
```

---

## Debugging Steps

### Step 1: Open Browser DevTools Console
Open your browser's Developer Tools and navigate to the **Console** tab. You'll see real-time debug messages as the application runs.

### Step 2: Initial Load Debugging
When the page loads, you should see console messages like:
```
[API] settings: {restaurant_name: "...", is_open_now: true, ...}
[API] categories: 5 (5) [Category1, Category2, ...]
[API] products: 25 (25) [Product1, Product2, ...]
[API] delivery zones: 2
[API] auto-selected root: 1
```

**If you see errors instead:**
- Check network tab for failed API calls
- Verify API endpoints are correct in the code (line 305: `const apiBase = '/api/v1'`)
- Check Laravel logs in `storage/logs/`

### Step 3: Category Filtering Debugging
Click on a category chip. You should see:
```
[filter] root selected: 1
[filter] shown: 12 | root: 1 | sub: null | search: —
```

Click a subcategory:
```
[filter] sub selected: 2
[filter] shown: 5 | root: 1 | sub: 2 | search: —
```

**Issues:**
- If products don't update, check if `renderProducts()` is being called
- Verify `state.selectedRoot` changes in console: `state.selectedRoot` before and after clicking

### Step 4: Product Search Debugging
Type in the search box. You should see:
```
[filter] shown: 3 | root: 1 | sub: null | search: hummus
```

**If search doesn't work:**
- Check if product names have correct locales: `p.name_ar`, `p.name_en`
- Verify search is case-insensitive (the code uses `.toLowerCase()`)

### Step 5: Product Modal Debugging
Click "Details" button on a product. You should see:
```
[modal] opening product: 1
[findProduct] searching for id: 1 in products: 25 allProducts: 25
[findProduct] found: Hummus price: 5.99
```

**If modal doesn't open:**
- Check if `openProductModal()` is called
- Verify `activeProduct` is set before showing
- Check Bootstrap modal instance: `bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal'))`

### Step 6: Add to Cart Debugging
Click "Add to Cart" button. You should see:
```
[addToCart] card → product: 1 Hummus
[cart] [{product_id: 1, product_name: "Hummus", product_price: 5.99, quantity: 1}]
[persistCart] saving 1 items to localStorage
[loadCart] loaded 1 items from localStorage: [...]
```

**If items don't appear:**
- Check localStorage in DevTools → Application → Local Storage
- Look for key `restaurant_cart_v1`
- Verify cart renders: check `cartItems` innerHTML

### Step 7: Cart Modifications Debugging
Increase quantity:
```
[addToCart] card → product: 1 Hummus
[cart] [{..., quantity: 2}]
[persistCart] saving 1 items to localStorage
```

Remove item:
```
[findProduct] NOT FOUND for id: 1
```

**This is normal** - the item was removed from cart.

### Step 8: Checkout Flow Debugging
1. Click checkout button

Check if cart has items:
```
[calcSubtotal]: 11.98 from 2 items
```

2. Fill order form and submit:
```
[order] payload → {customer_name: "John", customer_phone: "555...", ...}
```

3. Check response:
```
[API] settings: {order_number: "ORD-20260509-001", ...}
```

**Issues:**
- Check API response in Network tab
- Look for validation errors: check `error.errors` object
- Verify all required fields are filled

---

## Common Issues and Solutions

### Issue 1: Products Not Loading
**Symptoms:** Empty grid, "No products available" message

**Debug Steps:**
1. Check Network tab → `/api/v1/products`
2. Verify response status is 200
3. Check response body has `data` array
4. Run in console: `state.allProducts.length`
5. Check for API errors: `state.errors.products`

**Fix:**
- Verify database has active products: `Product::where('is_active', true)->count()`
- Check if resource is mapping fields correctly
- Look for SQL errors in Laravel logs

### Issue 2: Categories Not Showing
**Symptoms:** "No categories added" message

**Debug Steps:**
1. Check Network tab → `/api/v1/categories`
2. Run: `state.categories.length`
3. Check for category errors: `state.errors.categories`
4. Verify root categories exist: `state.categories.filter(c => c.parent_id === null)`

**Fix:**
- Create test categories in database
- Ensure `is_active = 1` and `sort_order` is set
- Check CategoryResource mapping

### Issue 3: Cart Items Disappear
**Symptoms:** Items added but not visible after refresh

**Debug Steps:**
1. Add item, refresh page
2. Check localStorage in DevTools
3. Run: `state.cart` before and after refresh
4. Check console for load errors

**Fix:**
- Check localStorage isn't full
- Verify `loadCart()` completes correctly
- Check for parse errors in localStorage data

### Issue 4: Modal Doesn't Open
**Symptoms:** Click Details, nothing happens

**Debug Steps:**
1. Check if `openProductModal()` is called
2. Verify `activeProduct` is set
3. Check Bootstrap modal: `bootstrap.Modal.getOrCreateInstance(...).show()`
4. Open Network tab, check for JS errors

**Fix:**
- Verify Bootstrap JS is loaded: `typeof bootstrap !== 'undefined'`
- Check for JS errors in Console
- Verify element IDs match (productModal)

### Issue 5: Order Submission Fails
**Symptoms:** Error message appears instead of success

**Debug Steps:**
1. Check console logs: `[order] error: ...`
2. Look at Network → `/api/v1/orders` request/response
3. Check error object: `error.errors` contains validation errors
4. Verify cart has items: `state.cart.length > 0`

**Fix:**
- Fill all required fields (name, phone, address)
- Check for validation errors in response
- Verify order items have valid product_ids
- Check Laravel logs for processing errors

### Issue 6: Featured Products Not Showing
**Symptoms:** No "Featured" section or empty products

**Debug Steps:**
1. Run: `state.featuredProducts.length`
2. Check if products have `is_featured: true`
3. Look at featured section visibility in HTML

**Fix:**
- Ensure some products have `is_featured = 1` in database
- Check ProductResource includes this field

---

## Testing Checklist

### Initial Page Load
- [ ] Page loads without errors
- [ ] Console shows all API calls succeeded
- [ ] Restaurant name/logo display correctly
- [ ] Categories load and display
- [ ] First category is automatically selected
- [ ] Products display in the selected category
- [ ] Featured section shows (if available)

### Navigation & Filtering
- [ ] Click root category → products update
- [ ] Click subcategory → products filter
- [ ] Click "All" → shows all products in root category
- [ ] Search works with product names
- [ ] Search works with descriptions
- [ ] Search is case-insensitive
- [ ] RTL layout works correctly (if Arabic)

### Product Details
- [ ] Click "Details" → modal opens
- [ ] Product name displays in modal
- [ ] Price displays correctly
- [ ] Old price shows if there's a discount
- [ ] Image loads (or fallback icon shows)
- [ ] Description displays (or "no description" message)
- [ ] Unavailable products show disabled state

### Shopping Cart
- [ ] Click "Add to Cart" → item appears in cart
- [ ] Cart count updates in header/mobile
- [ ] Cart icon badge shows correct number
- [ ] Multiple items combine if adding same product
- [ ] Cart persists after page refresh
- [ ] Quantity can increase/decrease
- [ ] Items can be removed
- [ ] Subtotal calculates correctly
- [ ] Empty cart shows message

### Checkout Process
- [ ] Click "Checkout" with empty cart → error message
- [ ] Checkout button opens modal
- [ ] Form fields are visible and editable
- [ ] Order type selector works (Table/Delivery/Takeaway)
- [ ] Table number field shows only for Table order
- [ ] Delivery fields show only for Delivery order
- [ ] Scheduled field shows only when selected
- [ ] Delivery zones dropdown populates
- [ ] Can submit valid form
- [ ] Success modal shows with order number
- [ ] Cart clears after successful order
- [ ] Cart persists localStorage cleared

### Error Handling
- [ ] API errors display gracefully
- [ ] Retry button reloads data
- [ ] Form validation shows errors
- [ ] Unavailable products alert on add

### Responsive Design
- [ ] Mobile view: cart button floats at bottom
- [ ] Desktop view: cart button in header
- [ ] Modal and offcanvas work on all sizes
- [ ] Text responsive to screen size

---

## Console Debug Commands

Run these in browser console to inspect application state:

```javascript
// View entire state
console.log(state)

// Check specific parts
state.cart                          // View cart items
state.products.length               // Total filtered products
state.allProducts.length            // Total all products
state.categories.length             // Total categories
state.selectedRoot                  // Current root category
state.selectedSub                   // Current subcategory

// Manually manipulate cart (for testing)
state.cart = []                     // Clear cart
state.cart.push({product_id: 1, product_name: "Test", product_price: 9.99, quantity: 1})
persistCart()
renderCart()

// Find products
findProduct(1)                       // Find product by ID
state.products.filter(p => p.is_available === false)  // Find unavailable

// Test rendering
renderProducts()                     // Re-render products
renderCart()                         // Re-render cart
renderAll()                          // Re-render everything

// Check localStorage
localStorage.getItem('restaurant_cart_v1')  // View cart data
localStorage.clear()                // Clear all storage
```

---

## Performance Considerations

1. **Product Search**: May be slow with thousands of products. Consider debouncing if needed.
2. **Image Loading**: Lazy load images for better performance
3. **State Size**: Keep `allProducts` reasonable or implement pagination
4. **localStorage**: Cart limited to browser storage capacity

---

## Localization Notes

- Current locale: `document.documentElement.lang`
- RTL support: `dir="{{ $isRtl ? 'rtl' : 'ltr' }}"`
- Number localization: `isAr ? 'ar-SY' : 'en-US'`
- Text escape required for XSS prevention

---

## Files Modified/Created

- `resources/views/public/home.blade.php` - Enhanced with debugging
- This guide - `DEBUGGING_GUIDE.md`

All changes maintain backward compatibility while adding comprehensive debugging capabilities.

