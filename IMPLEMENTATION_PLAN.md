# Implementation Plan — e-comRL

> **E-Commerce + Warehouse / ERP Platform — Laravel (PHP) + React**

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Current State Analysis](#2-current-state-analysis)
3. [System Architecture](#3-system-architecture)
4. [E-Commerce Side — Implementation Plan](#4-e-commerce-side--implementation-plan)
5. [Warehouse / Inventory Side — Implementation Plan](#5-warehouse--inventory-side--implementation-plan)
6. [Accounting / Finance Side — Implementation Plan](#6-accounting--finance-side--implementation-plan)
7. [Integration Points](#7-integration-points)
8. [API Design Plan](#8-api-design-plan)
9. [Frontend Plan](#9-frontend-plan)
10. [Database Considerations](#10-database-considerations)
11. [Development Phases / Roadmap](#11-development-phases--roadmap)

---

## 1. Project Overview

**e-comRL** is a comprehensive **E-Commerce and Business Management Platform** built with Laravel (PHP) on the backend and a React single-page application on the frontend (backed by Blade-based admin views). The system is designed to handle the full lifecycle of retail operations:

| Domain | Description |
|---|---|
| 🛒 **E-Commerce Storefront** | Product catalog, shopping cart, checkout, order tracking |
| 🏭 **Warehouse / Inventory** | Multi-warehouse stock management, stock movements, transfers |
| 📦 **Purchasing** | Supplier management, purchase orders, goods receiving |
| 💰 **Accounting / Finance** | Invoicing, payments, double-entry bookkeeping, expense tracking |
| 👥 **User & Role Management** | Admin, Warehouse Manager, Delivery Worker, Client |
| 🚚 **Shipping** | Dynamic shipping zones and cost calculation |

### Goals

- Provide customers with a modern, responsive online store (React SPA)
- Give warehouse managers a full-featured inventory management panel
- Automate financial record-keeping (invoices, journal entries) from business events
- Enable administrators a unified dashboard over all domains

### Scope

The MVP covers the five domains listed above plus reporting/analytics. Internationalization (i18next) and map-based delivery tracking (Bing Maps) are already partially in place.

---

## 2. Current State Analysis

### ✅ What is Already Implemented (E-Commerce Side)

| Area | Files | Status |
|---|---|---|
| Product listing, filtering, search | `app/Http/Controllers/Api/ProductController.php` | ✅ Complete |
| Product ratings & comments | `app/Http/Controllers/Api/CommentController.php` | ✅ Complete |
| Shopping cart (CRUD) | `app/Http/Controllers/Api/CartController.php` | ✅ Complete |
| Order creation (Stripe checkout) | `app/Http/Controllers/Api/OrderController.php` | ✅ Complete |
| Order history & status | `OrderController@orderHistory`, `OrderController@success` | ✅ Complete |
| Stripe webhooks & payment recording | `OrderController@webhook`, `OrderController@success` | ✅ Complete |
| Discount / promo codes | `Discount` model, applied in `OrderController@store` | ✅ Complete |
| Favorites / wishlist | `CommentController@favoriteProduct`, `getUserFavorites` | ✅ Complete |
| User auth (register/login) | `app/Http/Controllers/Api/AuthController.php` | ✅ Complete |
| Delivery worker assignment | `User::findAvailableDeliveryWorker()`, `DeliveryController` | ✅ Complete |
| Admin Blade panel (basic) | `resources/views/tables/`, `resources/views/forms/` | ✅ Partial |
| React storefront | `react/src/component/` — Home, Cart, Checkout, Profile | ✅ Partial |
| QR code for orders | `OrderController@generateQrCode` | ✅ Complete |
| Shipping zone calculation | `ShippingZone::calculateShipping()` | ✅ Complete |

### ❌ What is Missing / Not Wired Up (Warehouse & Other Sides)

| Area | Models Exist | Controllers | Routes | Frontend |
|---|---|---|---|---|
| Warehouse management | ✅ `Warehouse`, `WarehouseProduct` | ❌ None | ❌ None | ❌ None |
| Stock movements | ✅ `StockMovement` | ❌ None | ❌ None | ❌ None |
| Stock transfers | ✅ `StockTransfer`, `StockTransferItem` | ❌ None | ❌ None | ❌ None |
| Purchase orders | ✅ `PurchaseOrder`, `PurchaseOrderItem` | ❌ None | ❌ None | ❌ None |
| Supplier management | ✅ `Supplier` | ❌ None | ❌ None | ❌ None |
| Invoicing | ✅ `Invoice` | ❌ None | ❌ None | ❌ None |
| Payments (manual) | ✅ `Payment` | ❌ None (only Stripe auto) | ❌ None | ❌ None |
| Accounting (chart of accounts) | ✅ `Account` | ❌ None | ❌ None | ❌ None |
| Journal entries | ✅ `JournalEntry`, `JournalEntryLine` | ❌ None | ❌ None | ❌ None |
| Expenses | ✅ `Expense` | ❌ None | ❌ None | ❌ None |
| Inventory dashboard | — | ❌ None | ❌ None | ❌ None |
| Client detail profile | ✅ `ClientDetail` (stub) | ❌ Stub only | — | ❌ None |

> **Key Insight:** The database layer is well-designed and nearly complete. The work is primarily in building business-logic controllers, API endpoints, and frontend views on top of the existing models.

---

## 3. System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENTS                              │
│  React SPA (port 3000)      Blade Admin Panel (Laravel)     │
└────────────────┬────────────────────────┬───────────────────┘
                 │ HTTP / Axios           │ Livewire / Inertia
                 ▼                        ▼
┌─────────────────────────────────────────────────────────────┐
│              Laravel Application (routes/api.php)           │
│  Auth Middleware  │  Role Middleware  │  Throttle           │
├──────────┬────────┴──────────────────┬──────────────────────┤
│ Api      │ Controllers               │ Services / Models    │
│ Routes   │ ProductController         │ Eloquent ORM         │
│          │ OrderController           │ Stripe SDK           │
│          │ CartController            │ Mailer (SMTP)        │
│          │ [Warehouse Controllers]   │ Cache (Redis/file)   │
│          │ [Accounting Controllers]  │ Laravel Passport     │
└──────────┴───────────────────────────┴──────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │   MySQL Database │
                    │ (32 models / 46  │
                    │   migrations)    │
                    └─────────────────┘
```

### Role-Based Access Control

| Role | Access Level |
|---|---|
| **Admin** (role_id = 1) | Full access — all domains, all data |
| **Delivery Worker** (role_id = 2) | Own assigned orders only |
| **Client / Customer** (role_id = 3) | Storefront, own orders, profile |
| **Warehouse Manager** *(to be added)* | Inventory, POs, stock transfers |

### Multi-Warehouse Support

The schema already supports multiple warehouses via the `warehouses` table and the `warehouse_products` pivot table. Each product can have a different quantity and min-stock threshold per warehouse.

---

## 4. E-Commerce Side — Implementation Plan

### 4.1 Product Catalog

**Current:** `ProductController` handles listing with filtering, sorting, caching, and search. Categories and brands are fully supported.

**Remaining work:**

- [ ] Add pagination metadata to `GET /api/product/list` response (already returns `->paginate()` but the React component may need `meta.last_page` handling)
- [ ] Implement advanced filters: price range (`min_price`, `max_price`), multiple categories/brands in one request
- [ ] Add `GET /api/product/list?warehouse_id={id}` filter to show only in-stock products at a given warehouse

**Key files:**
```
app/Http/Controllers/Api/ProductController.php   ← index(), category(), brand(), search()
react/src/component/products/                    ← ProductList, ProductDetail components
react/src/redux/productActions.js               ← API calls
```

### 4.2 Product Detail Page

**Current:** `ProductController@show` returns product with images, related products, ratings, and comments.

**Remaining work:**

- [ ] Return `warehouse_products` aggregate in `show()` response so the storefront can display "In Stock / Low Stock / Out of Stock"
- [ ] Add `stock_quantity` from `WarehouseProduct` to the product detail response

### 4.3 Shopping Cart

**Current:** `CartController` provides full CRUD over `product_carts`. Stock is not pre-validated on add-to-cart.

**Remaining work:**

- [ ] Validate that `quantity` requested ≤ `product.stock_quantity` in `CartController@store` and `CartController@update`
- [ ] Return cart item count in the user's session response so the React navbar badge updates immediately
- [ ] Merge guest cart with user cart on login (optional enhancement)

**Key files:**
```
app/Http/Controllers/Api/CartController.php
react/src/component/cart/Cart.jsx
react/src/redux/cartActions.js
```

### 4.4 Checkout Flow

**Current:** `OrderController@store` and `CartController@create` handle order creation with Stripe Checkout Sessions. Shipping zones and discount codes are applied. Stock is decremented on `OrderController@success` (Stripe success callback).

**Remaining work:**

- [ ] Validate discount code availability before creating Stripe session (currently applied optimistically)
- [ ] Add address fields (street, city, zip) to the checkout form and persist in `order_details`
- [ ] Display estimated delivery date based on selected shipping zone
- [ ] Add order confirmation email triggered after `success()` callback
- [ ] Handle the case where Stripe session expires without payment — currently no cleanup job exists

**Key files:**
```
app/Http/Controllers/Api/OrderController.php   ← store(), success(), webhook()
react/src/component/checkout/Checkout.jsx
```

### 4.5 Order Management (Customer)

**Current:** `OrderController@orderHistory` returns paginated order history. `DeliveryController` manages delivery worker updates.

**Remaining work:**

- [ ] Expose order status progression in `orderHistory` response: `not_complete → paid → onProgress → complete`
- [ ] Add `GET /api/orders/{orderId}/detail` endpoint to fetch a single order with all items and product images
- [ ] Add cancel-order endpoint for customers (partial — `cancel()` handles Stripe refund but needs order status update)
- [ ] Integrate QR code display in React `OrderDetail.jsx`

**Key files:**
```
app/Http/Controllers/Api/OrderController.php
react/src/component/profile/OrderList.jsx
react/src/component/profile/OrderDetail.jsx
```

### 4.6 Favorites / Wishlist

**Current:** `CommentController@favoriteProduct` toggles favorite status. `getUserFavorites` returns the list.

**Remaining work:**

- [ ] Create a dedicated `FavoriteController` — `FavoriteController.php` exists as a stub (`app/Http/Controllers/FavoriteController.php`) but is empty
- [ ] Add favorite count / indicator on product cards in the React listing

### 4.7 User Authentication & Profile

**Current:** `AuthController` handles register/login with Passport tokens. `UserController` provides profile CRUD. Password reset via email token is implemented in `ForgetController` and `ResetController`.

**Remaining work:**

- [ ] Complete `ClientDetail` model (currently a stub) — add fields for billing address, date of birth, preferences
- [ ] Add `GET /api/profile` endpoint that returns user + client_details in a single call
- [ ] Implement email-verified guard on sensitive operations (cart checkout, order placement)
- [ ] Add profile picture upload

---

## 5. Warehouse / Inventory Side — Implementation Plan

> All models and migrations are in place. The entire business-logic layer needs to be built.

### 5.1 Warehouse Management

**Goal:** CRUD for warehouses; assign and track products per warehouse.

**Models:** `Warehouse`, `WarehouseProduct`

**Controllers to create:** `Api/WarehouseController`

```php
// Routes to add in routes/api.php (middleware: auth:api, role:admin|warehouse_manager)
Route::apiResource('warehouses', WarehouseController::class);
Route::get('warehouses/{id}/products', [WarehouseController::class, 'products']);
Route::post('warehouses/{id}/products', [WarehouseController::class, 'assignProduct']);
Route::put('warehouses/{id}/products/{productId}', [WarehouseController::class, 'updateProduct']);
```

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/WarehouseController.php`
  - `index()` — paginated list of warehouses (filterable by `is_active`)
  - `store()` — create warehouse, validate unique `code`
  - `show($id)` — warehouse detail with product count and total stock value
  - `update($id)` — update warehouse metadata
  - `destroy($id)` — soft-delete or deactivate (`is_active = false`)
  - `products($id)` — list `WarehouseProduct` records with product info, flag low-stock items
  - `assignProduct($id)` — create/update `WarehouseProduct` record
- [ ] Add `WarehouseManager` role (role_id = 4) to the `roles` seeder
- [ ] Add `role:warehouse_manager` middleware or extend `hasRole()` in `User` model

### 5.2 Stock Movements

**Goal:** Record every change to warehouse stock (in/out/adjustment) and maintain an audit trail.

**Model:** `StockMovement`

**Fields:** `product_id`, `warehouse_id`, `type` (in/out/adjustment), `quantity`, `reference_type` (e.g. `App\Models\Order`), `reference_id`, `notes`, `performed_by`

**Controllers to create:** `Api/StockMovementController`

```php
// Routes
Route::get('stock-movements', [StockMovementController::class, 'index']);
Route::post('stock-movements', [StockMovementController::class, 'store']);
Route::get('stock-movements/{id}', [StockMovementController::class, 'show']);
```

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/StockMovementController.php`
  - `index()` — filter by warehouse, product, type, date range; paginated
  - `store()` — manual adjustment (admin only): create `StockMovement`, update `WarehouseProduct.quantity`
  - `show($id)` — full movement detail
- [ ] Create a reusable `StockService` class:

```php
// app/Services/StockService.php
class StockService {
    public function recordMovement(
        int $productId, int $warehouseId, string $type,
        int $quantity, string $referenceType, int $referenceId,
        int $performedBy, string $notes = ''
    ): StockMovement { ... }

    public function decrementStock(int $productId, int $warehouseId, int $quantity): void { ... }
    public function incrementStock(int $productId, int $warehouseId, int $quantity): void { ... }
}
```

- [ ] **Auto-decrement hook:** Call `StockService::decrementStock()` inside `OrderController@success` after marking payment as complete (currently only `product->decrement('stock_quantity')` is used — needs warehouse-level tracking)
- [ ] **Auto-increment hook:** Call `StockService::incrementStock()` when a purchase order is received (see §5.4)

### 5.3 Stock Transfers

**Goal:** Move stock between warehouses with an approval workflow.

**Models:** `StockTransfer`, `StockTransferItem`

**Status flow:** `pending → approved → completed | cancelled`

**Controllers to create:** `Api/StockTransferController`

```php
// Routes
Route::apiResource('stock-transfers', StockTransferController::class);
Route::post('stock-transfers/{id}/approve', [StockTransferController::class, 'approve']);
Route::post('stock-transfers/{id}/complete', [StockTransferController::class, 'complete']);
Route::post('stock-transfers/{id}/cancel', [StockTransferController::class, 'cancel']);
```

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/StockTransferController.php`
  - `store()` — create transfer with items; status defaults to `pending`
  - `approve($id)` — admin/warehouse manager approves; validate sufficient source stock
  - `complete($id)` — mark completed, call `StockService::decrementStock()` on source warehouse and `incrementStock()` on destination warehouse, set `completed_at`
  - `cancel($id)` — cancel pending/approved transfer
  - `index()` — filter by status, warehouse, date
- [ ] Validate that `from_warehouse_id ≠ to_warehouse_id`
- [ ] Validate that source `WarehouseProduct.quantity ≥ sum(items.quantity)` before approval

### 5.4 Purchase Orders

**Goal:** Create POs to suppliers, receive goods into a warehouse, and automatically update stock.

**Models:** `PurchaseOrder`, `PurchaseOrderItem`

**Status flow:** `draft → sent → partially_received → received | cancelled`

**Controllers to create:** `Api/PurchaseOrderController`

```php
// Routes
Route::apiResource('purchase-orders', PurchaseOrderController::class);
Route::post('purchase-orders/{id}/send', [PurchaseOrderController::class, 'send']);
Route::post('purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive']);
Route::post('purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
```

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/PurchaseOrderController.php`
  - `store()` — generate `order_number` (e.g. `PO-YYYYMMDD-XXXX`), set status to `draft`
  - `send($id)` — mark as `sent` (optionally email the supplier)
  - `receive($id)` — accept a payload of `{items: [{id, quantity_received}]}`, update `PurchaseOrderItem.quantity_received`, call `StockService::incrementStock()` for each item in target warehouse, update PO status to `received` or `partially_received`
  - `cancel($id)` — only allowed in `draft` or `sent` status
  - `index()` — filter by supplier, warehouse, status, date range
- [ ] Auto-generate journal entries on PO receipt (see §6)

### 5.5 Supplier Management

**Goal:** CRUD for suppliers, linked to purchase orders.

**Model:** `Supplier`

**Controllers to create:** `Api/SupplierController`

```php
// Routes
Route::apiResource('suppliers', SupplierController::class);
Route::get('suppliers/{id}/purchase-orders', [SupplierController::class, 'purchaseOrders']);
```

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/SupplierController.php`
  - Standard CRUD: `index`, `store`, `show`, `update`, `destroy`
  - `index()` — filter by `is_active`, search by name/email
  - `purchaseOrders($id)` — list POs for a given supplier

### 5.6 Inventory Dashboard

**Goal:** Provide a live overview of stock levels per warehouse, with low-stock alerts.

**Controllers to create:** `Api/InventoryDashboardController`

```php
// Routes
Route::get('inventory/dashboard', [InventoryDashboardController::class, 'index']);
Route::get('inventory/low-stock', [InventoryDashboardController::class, 'lowStock']);
Route::get('inventory/valuation', [InventoryDashboardController::class, 'valuation']);
```

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/InventoryDashboardController.php`
  - `index()` — total product count, total stock units, warehouse count, pending PO count, pending transfer count
  - `lowStock()` — `WarehouseProduct` records where `quantity <= min_stock_threshold` (uses `isLowStock()` method already on `WarehouseProduct`)
  - `valuation()` — sum of `warehouse_products.quantity × products.price` per warehouse

---

## 6. Accounting / Finance Side — Implementation Plan

> All models (`Invoice`, `Payment`, `Account`, `JournalEntry`, `JournalEntryLine`, `Expense`) are in place. Business logic and endpoints need to be built.

### 6.1 Invoice Generation

**Model:** `Invoice`

**Status flow:** `draft → issued → paid | overdue | cancelled`

**Controllers to create:** `Api/InvoiceController`

```php
// Routes
Route::apiResource('invoices', InvoiceController::class);
Route::post('invoices/{id}/issue', [InvoiceController::class, 'issue']);
Route::get('invoices/{id}/pdf', [InvoiceController::class, 'pdf']);
```

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/InvoiceController.php`
  - `store()` — auto-generate `invoice_number` (e.g. `INV-YYYYMMDD-XXXX`), link to `order_id`, calculate `total_amount`, `tax_amount`, `discount_amount`, `net_amount`
  - `issue($id)` — set status to `issued`, set `issued_at = now()`, trigger journal entry (see §6.4)
  - `pdf($id)` — generate PDF using Laravel DomPDF or Snappy
- [ ] Auto-create invoice draft when an order status transitions to `paid` in `OrderController@success`

### 6.2 Payment Recording

**Model:** `Payment`

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/PaymentController.php`
  - `index()` — filter by order, status, date
  - `store()` — record manual payment (cash, bank transfer, etc.)
  - `show($id)` — payment detail
- [ ] Stripe payments are already auto-recorded in `OrderController@success` — ensure `Payment` record is created there with `transaction_reference = session_id`
- [ ] Update invoice `status` to `paid` when corresponding payment is confirmed

### 6.3 Expense Management

**Model:** `Expense`

**Controllers to create:** `Api/ExpenseController`

```php
// Routes
Route::apiResource('expenses', ExpenseController::class);
Route::post('expenses/{id}/approve', [ExpenseController::class, 'approve']);
```

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/ExpenseController.php`
  - `store()` — create expense linked to an account (`account_id`), set `created_by`
  - `approve($id)` — admin approval: set `approved_by`, trigger journal entry
  - Filter by category, date range, account

### 6.4 Chart of Accounts & Journal Entries (Double-Entry)

**Models:** `Account`, `JournalEntry`, `JournalEntryLine`

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/AccountController.php` — CRUD for accounts
- [ ] Create `app/Http/Controllers/Api/JournalEntryController.php`
  - `index()` — filter by date, reference_type, status
  - `store()` — manual journal entry (must balance: sum(debit) == sum(credit))
  - `show($id)` — entry with all lines
- [ ] Create `AccountingService` to auto-generate journal entries:

```php
// app/Services/AccountingService.php
class AccountingService {
    // Called when order is invoiced
    public function createSalesJournalEntry(Invoice $invoice): JournalEntry { ... }
    // Called when expense is approved
    public function createExpenseJournalEntry(Expense $expense): JournalEntry { ... }
    // Called when PO goods are received
    public function createPurchaseJournalEntry(PurchaseOrder $po): JournalEntry { ... }
    // Called when payment is received
    public function createPaymentJournalEntry(Payment $payment): JournalEntry { ... }
}
```

- [ ] Seed default chart of accounts (Assets, Liabilities, Revenue, Expenses, Equity)

### 6.5 Financial Reports

**Controllers to create:** `Api/ReportController`

```php
// Routes
Route::get('reports/profit-loss', [ReportController::class, 'profitLoss']);
Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet']);
Route::get('reports/sales-summary', [ReportController::class, 'salesSummary']);
Route::get('reports/expense-summary', [ReportController::class, 'expenseSummary']);
```

**Implementation checklist:**

- [ ] Create `app/Http/Controllers/Api/ReportController.php`
  - `profitLoss($startDate, $endDate)` — revenue minus expenses from journal entries
  - `balanceSheet()` — assets vs liabilities + equity from account balances
  - `salesSummary($period)` — total sales, top products, total orders by status
  - `expenseSummary($period)` — expenses grouped by category/account

---

## 7. Integration Points

### 7.1 Order Placement → Stock Decrement

**Current state:** `OrderController@success` calls `$product->decrement('stock_quantity')` on the `products` table but does **not** update `warehouse_products`.

**Required change:**

```php
// In OrderController@success(), after marking payment
foreach ($order->orderItems as $item) {
    $stockService->decrementStock(
        productId: $item->product_id,
        warehouseId: /* resolve nearest/default warehouse */,
        quantity: $item->quantity
    );
}
```

- [ ] Decide warehouse selection strategy: "nearest warehouse with sufficient stock" or "default warehouse per product"
- [ ] Add `StockMovement` record with `type = 'out'`, `reference_type = App\Models\Order`, `reference_id = $order->id`

### 7.2 Purchase Order Received → Stock Increment

When `PurchaseOrderController@receive` is called:

```php
foreach ($receivedItems as $item) {
    $stockService->incrementStock($item->product_id, $po->warehouse_id, $item->quantity_received);
    // Also update global product.stock_quantity
    $item->product->increment('stock_quantity', $item->quantity_received);
}
$accountingService->createPurchaseJournalEntry($po);
```

### 7.3 Stock Transfer Approved → Stock Movement

When `StockTransferController@complete` is called:

```php
foreach ($transfer->items as $item) {
    $stockService->decrementStock($item->product_id, $transfer->from_warehouse_id, $item->quantity);
    $stockService->incrementStock($item->product_id, $transfer->to_warehouse_id, $item->quantity);
}
$transfer->update(['status' => 'completed', 'completed_at' => now()]);
```

### 7.4 Order Invoiced → Journal Entry

When an order's status changes to `paid` (in `OrderController@success`):

```php
$invoice = Invoice::createFromOrder($order); // auto-create draft invoice
$invoice->issue();                           // triggers AccountingService
$accountingService->createSalesJournalEntry($invoice);
$accountingService->createPaymentJournalEntry($payment);
```

### 7.5 Role-Based Access Summary

| Resource | Admin | Warehouse Manager | Delivery Worker | Client |
|---|---|---|---|---|
| Warehouses | ✅ Full CRUD | ✅ Read + stock ops | ❌ | ❌ |
| Stock Movements | ✅ Full | ✅ Create/Read | ❌ | ❌ |
| Stock Transfers | ✅ Full | ✅ Create + Complete | ❌ | ❌ |
| Purchase Orders | ✅ Full | ✅ Create/Receive | ❌ | ❌ |
| Suppliers | ✅ Full | ✅ Read | ❌ | ❌ |
| Invoices | ✅ Full | ❌ | ❌ | ✅ Own only |
| Accounting | ✅ Full | ❌ | ❌ | ❌ |
| Products/Orders | ✅ Full | ✅ Read | ✅ Assigned orders | ✅ Storefront |

---

## 8. API Design Plan

### 8.1 Existing Endpoints (Already Implemented)

```
POST   /api/login                          Auth
POST   /api/register                       Auth
POST   /api/forgetPassword                 Auth
POST   /api/resetPassword                  Auth

GET    /api/product/list                   Products
GET    /api/product/{id}                   Products
GET    /api/search/{key}                   Products
GET    /api/categories                     Categories
GET    /api/brands                         Brands

GET    /api/cart/{userId}                  Cart
POST   /api/cart/add-to-cart               Cart
POST   /api/cart/update/{id}               Cart
DELETE /api/cart/delete/{id}               Cart

POST   /api/order/create                   Orders (Stripe)
GET    /api/orders/{userId}                Orders
POST   /api/calculate-shipping             Shipping
GET    /api/success                        Stripe callback
POST   /api/webhook                        Stripe webhook

GET    /api/user/favorites                 Favorites
POST   /api/product/{id}/favorite          Favorites

GET    /api/delivery-worker/orders         Delivery
POST   /api/delivery-worker/orders/{id}    Delivery
```

### 8.2 Endpoints to Add — Warehouse / Inventory

```
GET    /api/warehouses                     List warehouses
POST   /api/warehouses                     Create warehouse
GET    /api/warehouses/{id}                Get warehouse
PUT    /api/warehouses/{id}                Update warehouse
DELETE /api/warehouses/{id}                Deactivate warehouse
GET    /api/warehouses/{id}/products       Products in warehouse

GET    /api/stock-movements                List movements (filters: warehouse, product, type, date)
POST   /api/stock-movements                Manual adjustment
GET    /api/stock-movements/{id}           Movement detail

GET    /api/stock-transfers                List transfers
POST   /api/stock-transfers                Create transfer
GET    /api/stock-transfers/{id}           Transfer detail
POST   /api/stock-transfers/{id}/approve   Approve transfer
POST   /api/stock-transfers/{id}/complete  Complete transfer
POST   /api/stock-transfers/{id}/cancel    Cancel transfer

GET    /api/purchase-orders                List POs
POST   /api/purchase-orders                Create PO
GET    /api/purchase-orders/{id}           PO detail
PUT    /api/purchase-orders/{id}           Update PO (draft only)
POST   /api/purchase-orders/{id}/send      Mark as sent
POST   /api/purchase-orders/{id}/receive   Receive goods
POST   /api/purchase-orders/{id}/cancel    Cancel PO

GET    /api/suppliers                      List suppliers
POST   /api/suppliers                      Create supplier
GET    /api/suppliers/{id}                 Supplier detail
PUT    /api/suppliers/{id}                 Update supplier
DELETE /api/suppliers/{id}                 Deactivate supplier
GET    /api/suppliers/{id}/purchase-orders Supplier PO history

GET    /api/inventory/dashboard            Dashboard stats
GET    /api/inventory/low-stock            Low-stock alerts
GET    /api/inventory/valuation            Stock valuation
```

### 8.3 Endpoints to Add — Accounting / Finance

```
GET    /api/invoices                       List invoices
POST   /api/invoices                       Create invoice
GET    /api/invoices/{id}                  Invoice detail
PUT    /api/invoices/{id}                  Update draft invoice
POST   /api/invoices/{id}/issue            Issue invoice
GET    /api/invoices/{id}/pdf              Download PDF

GET    /api/payments                       List payments
POST   /api/payments                       Record manual payment
GET    /api/payments/{id}                  Payment detail

GET    /api/expenses                       List expenses
POST   /api/expenses                       Create expense
GET    /api/expenses/{id}                  Expense detail
PUT    /api/expenses/{id}                  Update expense
POST   /api/expenses/{id}/approve          Approve expense

GET    /api/accounts                       Chart of accounts
POST   /api/accounts                       Create account
PUT    /api/accounts/{id}                  Update account

GET    /api/journal-entries                List journal entries
POST   /api/journal-entries                Manual journal entry
GET    /api/journal-entries/{id}           Entry detail

GET    /api/reports/profit-loss            P&L report
GET    /api/reports/balance-sheet          Balance sheet
GET    /api/reports/sales-summary          Sales summary
GET    /api/reports/expense-summary        Expense summary
GET    /api/reports/inventory-valuation    Inventory report
```

---

## 9. Frontend Plan

### 9.1 Storefront (React SPA — `react/src/`)

**Already implemented components:**

| Component | File | Status |
|---|---|---|
| Home page | `component/home/Home.jsx` | ✅ Done |
| Product listing | `component/products/` | ✅ Done |
| Shopping cart | `component/cart/Cart.jsx` | ✅ Done |
| Checkout | `component/checkout/Checkout.jsx` | ✅ Done |
| User profile | `component/profile/Profile.jsx` | ✅ Done |
| Order list | `component/profile/OrderList.jsx` | ✅ Done |
| Order detail | `component/profile/OrderDetail.jsx` | ✅ Done |
| Ratings & comments | `CommentForm.jsx`, `Rating.jsx` | ✅ Done |

**Components to add:**

- [ ] `component/profile/FavoritesList.jsx` — display wishlist
- [ ] `component/products/StockBadge.jsx` — "In Stock / Low Stock / Out of Stock" badge
- [ ] `component/cart/CartSummary.jsx` — order summary sidebar with discount code input
- [ ] `component/checkout/AddressForm.jsx` — full shipping address form

### 9.2 Admin / Warehouse Panel (Blade + Livewire — `resources/views/`)

**Already implemented Blade views:**

| View | Location | Status |
|---|---|---|
| Dashboard | `views/dashboard.blade.php` | ✅ Done |
| User table | `views/tables/user-table.blade.php` | ✅ Done |
| Product table | `views/tables/product-table.blade.php` | ✅ Done |
| Order table | `views/tables/order-table.blade.php` | ✅ Done |
| Create forms | `views/forms/create/` | ✅ Partial |
| Edit forms | `views/forms/edit/` | ✅ Partial |

**Views / pages to add:**

- [ ] `views/tables/warehouse-table.blade.php` — warehouse list with stock summary
- [ ] `views/tables/supplier-table.blade.php` — supplier list
- [ ] `views/tables/purchase-order-table.blade.php` — PO list with status badges
- [ ] `views/tables/stock-movement-table.blade.php` — audit trail
- [ ] `views/forms/create/create-warehouse.blade.php`
- [ ] `views/forms/create/create-supplier.blade.php`
- [ ] `views/forms/create/create-purchase-order.blade.php`
- [ ] `views/inventory/dashboard.blade.php` — inventory KPI cards + low-stock table
- [ ] `views/accounting/dashboard.blade.php` — financial summary cards

**New web routes to add in `routes/web.php`:**

```php
// Warehouse management
Route::get('/warehouses', ...)->name('warehouses');
Route::get('/warehouses/{id}', ...)->name('warehouses.show');
Route::get('/create/warehouse', ...)->name('create.warehouse');

// Inventory
Route::get('/inventory', ...)->name('inventory.dashboard');
Route::get('/stock-movements', ...)->name('stock-movements');
Route::get('/stock-transfers', ...)->name('stock-transfers');

// Purchasing
Route::get('/purchase-orders', ...)->name('purchase-orders');
Route::get('/suppliers', ...)->name('suppliers');

// Accounting
Route::get('/invoices', ...)->name('invoices');
Route::get('/expenses', ...)->name('expenses');
Route::get('/accounts', ...)->name('accounts');
Route::get('/reports', ...)->name('reports');
```

### 9.3 Role-Based Navigation

```blade
{{-- In layouts/navigation.blade.php --}}
@if(auth()->user()->hasRole('admin'))
    {{-- Show all nav items --}}
@elseif(auth()->user()->hasRole('warehouse_manager'))
    {{-- Show inventory, POs, suppliers --}}
@elseif(auth()->user()->hasRole('delivery_worker'))
    {{-- Show assigned orders --}}
@else
    {{-- Client: redirect to React storefront --}}
@endif
```

---

## 10. Database Considerations

### 10.1 Key Model Relationships

```
User ──────────────────────┐
  hasMany: orders           │
  hasMany: carts            │
  hasMany: stockMovements   │ (performed_by)
  hasMany: purchaseOrders   │ (ordered_by)
  hasMany: journalEntries   │ (created_by)
  hasMany: expenses         │ (created_by, approved_by)
  belongsToMany: products   │ (via favorites)

Product ───────────────────┤
  belongsTo: category       │
  belongsTo: brand          │
  hasMany: images           │
  hasMany: orderItems       │
  belongsToMany: warehouses │ (via warehouse_products)
  hasMany: stockMovements   │
  hasMany: purchaseOrderItems│
  belongsToMany: discounts  │ (via discount_product)
  hasMany: ratings          │
  hasMany: comments         │

Order ──────────────────────┤
  belongsTo: customer (User)│
  belongsTo: shippingZone   │
  belongsTo: discount       │
  hasMany: orderItems       │
  hasOne: invoice           │
  hasMany: payments         │

Warehouse ──────────────────┤
  hasMany: warehouseProducts│
  belongsToMany: products   │
  hasMany: stockMovements   │
  hasMany: stockTransfersFrom│
  hasMany: stockTransfersTo │
  hasMany: purchaseOrders   │

PurchaseOrder ──────────────┤
  belongsTo: supplier       │
  belongsTo: warehouse      │
  belongsTo: orderedBy (User)│
  hasMany: items (PurchaseOrderItem)

JournalEntry ───────────────┘
  hasMany: lines (JournalEntryLine)
    each line → belongsTo: account
```

### 10.2 Missing Fields / Migrations

| Table | Missing Field | Reason |
|---|---|---|
| `orders` | `invoice_id` (or use `invoices.order_id` FK — already present) | Invoice lookup |
| `orders` | `warehouse_id` (default fulfillment warehouse) | Stock decrement routing |
| `products` | `sku` (stock-keeping unit) | Warehouse operations |
| `warehouse_products` | `last_counted_at` (date) | Physical inventory count tracking |
| `purchase_orders` | `received_at` (datetime) | Goods receipt timestamp |
| `users` | `warehouse_id` (for warehouse manager) | Restrict manager to their warehouse |
| `roles` | Add `warehouse_manager` row (role_id = 4) | RBAC |

**Example migration for missing `warehouse_id` on `orders`:**

```php
// database/migrations/2024_09_10_add_warehouse_id_to_orders.php
Schema::table('orders', function (Blueprint $table) {
    $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
});
```

### 10.3 Suggested Indexes for Performance

```sql
-- Products
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_brand ON products(brand_id);
CREATE INDEX idx_products_price ON products(price);

-- Orders
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);

-- Stock movements (high write volume)
CREATE INDEX idx_stock_mv_warehouse ON stock_movements(warehouse_id);
CREATE INDEX idx_stock_mv_product ON stock_movements(product_id);
CREATE INDEX idx_stock_mv_created ON stock_movements(created_at);
CREATE INDEX idx_stock_mv_reference ON stock_movements(reference_type, reference_id);

-- Warehouse products
CREATE UNIQUE INDEX idx_wp_unique ON warehouse_products(warehouse_id, product_id);
CREATE INDEX idx_wp_low_stock ON warehouse_products(quantity, min_stock_threshold);

-- Journal entries
CREATE INDEX idx_je_date ON journal_entries(date);
CREATE INDEX idx_je_reference ON journal_entries(reference_type, reference_id);

-- Purchase orders
CREATE INDEX idx_po_supplier ON purchase_orders(supplier_id);
CREATE INDEX idx_po_status ON purchase_orders(status);
```

---

## 11. Development Phases / Roadmap

### Phase 1 — Complete E-Commerce Storefront
**Estimated effort: 2–3 weeks**

- [ ] Fix `CartController` stock validation on add/update
- [ ] Add address fields to checkout form and persist in `order_details`
- [ ] Add order confirmation email after `OrderController@success`
- [ ] Complete `FavoriteController` stub
- [ ] Complete `ClientDetail` model + profile endpoint
- [ ] Add stock status badge to product listing/detail (React)
- [ ] Complete React cart summary with discount code input
- [ ] Test full checkout flow end-to-end (add to cart → Stripe → success → stock decremented)

### Phase 2 — Warehouse & Inventory Management
**Estimated effort: 3–4 weeks**

- [ ] Add `warehouse_manager` role
- [ ] Create `StockService` class
- [ ] Build `WarehouseController` + routes + Blade views
- [ ] Build `StockMovementController` + routes + Blade views
- [ ] Build `StockTransferController` (with approval workflow) + routes + Blade views
- [ ] Build `SupplierController` + routes + Blade views
- [ ] Build `PurchaseOrderController` (with receive goods flow) + routes + Blade views
- [ ] Build `InventoryDashboardController` (KPIs + low-stock alerts)
- [ ] Wire order fulfillment → stock decrement via `StockService`
- [ ] Wire PO receiving → stock increment via `StockService`
- [ ] Wire stock transfer completion → stock movement records
- [ ] Add missing database migrations (warehouse_id on orders, sku on products, etc.)

### Phase 3 — Accounting & Finance Module
**Estimated effort: 3 weeks**

- [ ] Seed default chart of accounts
- [ ] Build `AccountController` + routes + Blade views
- [ ] Build `InvoiceController` (auto-create on order payment) + PDF export
- [ ] Build `PaymentController` (manual payment recording)
- [ ] Build `ExpenseController` (with approval workflow)
- [ ] Build `JournalEntryController` (manual entries + auto-generated)
- [ ] Create `AccountingService` with all auto-journal-entry hooks
- [ ] Wire invoice issuance → journal entry creation
- [ ] Wire expense approval → journal entry creation
- [ ] Wire PO receipt → journal entry creation
- [ ] Add payment recording to Stripe success callback

### Phase 4 — Reporting & Analytics Dashboard
**Estimated effort: 2 weeks**

- [ ] Build `ReportController` with P&L, balance sheet, sales summary, expense summary
- [ ] Add inventory valuation report
- [ ] Add top-selling products report
- [ ] Build Blade report views with charts (Chart.js or similar)
- [ ] Add date-range filters to all reports
- [ ] Add export to PDF/CSV for all reports

### Phase 5 — Performance, Testing & Deployment
**Estimated effort: 2–3 weeks**

- [ ] Add database indexes (see §10.3)
- [ ] Implement Redis caching for inventory dashboard queries
- [ ] Add queue-based processing for journal entry auto-generation (Laravel Jobs)
- [ ] Write feature tests for critical paths:
  - Checkout → payment → stock decrement
  - PO creation → receive goods → stock increment
  - Stock transfer → approval → completion → stock movement
  - Invoice auto-generation from order
- [ ] Set up CI/CD pipeline (GitHub Actions)
- [ ] Production deployment checklist (env config, queue workers, storage symlink, caching)
- [ ] Load testing for the API endpoints

---

## Appendix — File Reference

### Controllers to Create

| File | Purpose |
|---|---|
| `app/Http/Controllers/Api/WarehouseController.php` | Warehouse CRUD + product management |
| `app/Http/Controllers/Api/StockMovementController.php` | Stock movement audit trail |
| `app/Http/Controllers/Api/StockTransferController.php` | Inter-warehouse transfers |
| `app/Http/Controllers/Api/PurchaseOrderController.php` | PO lifecycle |
| `app/Http/Controllers/Api/SupplierController.php` | Supplier CRUD |
| `app/Http/Controllers/Api/InventoryDashboardController.php` | Inventory KPIs |
| `app/Http/Controllers/Api/InvoiceController.php` | Invoice management |
| `app/Http/Controllers/Api/PaymentController.php` | Payment recording |
| `app/Http/Controllers/Api/ExpenseController.php` | Expense management |
| `app/Http/Controllers/Api/AccountController.php` | Chart of accounts |
| `app/Http/Controllers/Api/JournalEntryController.php` | Double-entry bookkeeping |
| `app/Http/Controllers/Api/ReportController.php` | Financial + inventory reports |

### Services to Create

| File | Purpose |
|---|---|
| `app/Services/StockService.php` | Centralized stock operations |
| `app/Services/AccountingService.php` | Auto journal entry generation |
| `app/Services/InvoiceService.php` | Invoice number generation + PDF |
| `app/Services/PurchaseOrderService.php` | PO number generation + receiving logic |

### Existing Key Files

| File | Purpose |
|---|---|
| `app/Http/Controllers/Api/OrderController.php` | Main order flow (needs StockService integration) |
| `app/Models/WarehouseProduct.php` | Has `isLowStock()` method |
| `app/Models/User.php` | Has `hasRole()`, `findAvailableDeliveryWorker()` |
| `app/Models/Product.php` | Has `currentDiscount()`, `scopeWithAvgRating()` |
| `app/Models/ShippingZone.php` | Has `calculateShipping()` static method |
| `routes/api.php` | All API routes (add warehouse/accounting routes here) |
| `routes/web.php` | Blade admin routes (add warehouse/accounting pages here) |
| `react/src/redux/` | Redux store (add warehouse/accounting slices here) |
