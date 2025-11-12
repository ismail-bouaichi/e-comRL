# Delivery Worker System Updates

## Overview
This document outlines the major changes made to implement a dedicated delivery worker tracking system, replacing the previous role-based approach where delivery workers were regular users with a specific role.

## Architecture Changes

### Database Structure

#### New Tables
1. **delivery_workers**
   - `id` - Primary key
   - `user_id` - Foreign key to users table
   - `phone` - Contact number
   - `vehicle_type` - Type of delivery vehicle (bike, car, motorcycle, van, truck)
   - `vehicle_number` - Vehicle registration/plate number
   - `license_number` - Driver's license number
   - `status` - Current status (available, on_delivery, offline)
   - `current_order_id` - Foreign key to current order being delivered (nullable)
   - Timestamps

2. **delivery_locations**
   - `id` - Primary key
   - `delivery_worker_id` - Foreign key to delivery_workers
   - `order_id` - Foreign key to orders
   - `latitude` - GPS latitude
   - `longitude` - GPS longitude
   - `address` - Text address
   - `status` - Location type (picked_up, in_transit, delivered)
   - Timestamps

### Model Changes

#### Orders Table
- `delivery_worker_id` now references `delivery_workers.id` instead of `users.id`
- Delivery worker assignment is **optional** (nullable field)

## Backend Updates

### Controllers Updated

#### 1. OrderController (API)
**Location:** `app/Http/Controllers/Api/OrderController.php`

**Changes:**
- Added `DeliveryWorker` model import
- Removed broken `User::findAvailableDeliveryWorker()` call
- Implemented auto-assignment logic:
  ```php
  $deliveryWorker = DeliveryWorker::where('status', 'available')
      ->whereNull('current_order_id')
      ->first();
  ```
- Made delivery worker assignment optional (nullable)
- Added worker status updates when assigned
- Added worker notifications when order assigned
- Creates orders without worker if none available

### Livewire Components Updated

#### 1. CreateOrder Component
**Location:** `app/Livewire/Create/CreateOrder.php`

**Changes:**
- ✅ Added `DeliveryWorker` model import
- ✅ Updated validation: `delivery_worker_id` from `required|exists:users,id` to `nullable|exists:delivery_workers,id`
- ✅ Updated `mount()` method:
  - Changed from `User::whereHas('role')` to `DeliveryWorker::with('user')`
  - Loads phone, vehicle_type, status fields
- ✅ Updated `updatedDeliverySearch()` method:
  - Queries `DeliveryWorker::with('user')`
  - Searches through worker's associated user name
  - Returns phone, vehicle_type, status in results
- ✅ Updated `selectDelivery()` method:
  - Uses `DeliveryWorker::find()` instead of `User::find()`
  - Displays format: `"Name (Vehicle Type)"`
- ✅ Updated `validateStep()` method:
  - Step 1 validation changed to `nullable|exists:delivery_workers,id`

**Blade View:** `resources/views/livewire/create/create-order.blade.php`
- ✅ Label updated to show "(Optional)"
- ✅ Enhanced dropdown to show:
  - Worker name with vehicle type
  - Email address
  - Status badge (color-coded: green=available, blue=on_delivery, gray=offline)

#### 2. EditOrder Component
**Location:** `app/Livewire/Edit/EditOrder.php`

**Changes:**
- ✅ Added `DeliveryWorker` model import
- ✅ Updated validation: `nullable|exists:delivery_workers,id`
- ✅ Updated `loadSearchOptions()`: Queries `DeliveryWorker::with('user')`
- ✅ Updated `updatedDeliverySearch()`: Searches DeliveryWorker model
- ✅ Updated `selectDelivery()`: Shows `"Name (Vehicle Type)"`

#### 3. OrderShow Component
**Location:** `app/Livewire/Show/OrderShow.php`

**Changes:**
- ✅ Added `DeliveryWorker` model import
- ✅ Added `$deliveryWorker` property
- ✅ Updated `mount()`: Eager loads `deliveryWorker.user` relationship

#### 4. ManageDeliveryWorkers Component (NEW)
**Location:** `app/Livewire/DeliveryWorker/ManageDeliveryWorkers.php`

**Features:**
- Full CRUD operations for delivery workers
- Search by name, email, phone
- Filter by status (available, on_delivery, offline)
- Inline status updates
- Assign workers to orders
- View current order assignments
- Pagination support

**Blade View:** `resources/views/livewire/delivery-worker/manage-delivery-workers.blade.php`
- Modern responsive UI with dark mode support
- Status badges with color coding
- Vehicle type badges
- Modal for order assignment
- Real-time search and filtering

### Blade Views Updated

#### orders-table.blade.php
**Location:** `resources/views/livewire/tables/orders-table.blade.php`

**Changes:**
- Shows `worker.user.name` (not just `worker.name`)
- Displays vehicle type and status
- Color-coded status badges
- Shows "Not assigned" if no worker

## Frontend Requirements

### What You Need to Handle

#### 1. Creating Orders
**IMPORTANT:** `delivery_worker_id` is now **OPTIONAL (nullable)**

You have 3 options:

**Option A: Send null** (Recommended)
- Send `delivery_worker_id: null` in the request
- Order will be created without a worker
- Admin can assign worker later

**Option B: Don't send the field**
- Backend will auto-assign an available worker
- If no workers available, order created with null

**Option C: Send a worker ID**
- Send `delivery_worker_id: 1` (or any valid worker ID)
- Order will be created with that worker assigned

#### 2. Fetching Delivery Workers
**Endpoint:** `GET /api/delivery-workers`

**Query Parameters:**
- `status` - Filter by status (available, on_delivery, offline)
- `search` - Search by name/email/phone

**Response Structure:**
```json
{
  "id": 1,
  "user_id": 5,
  "phone": "123-456-7890",
  "vehicle_type": "car",
  "vehicle_number": "ABC-123",
  "status": "available",
  "user": {
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

#### 3. Displaying Worker Information
**Structure Changed:** Worker data is nested

**OLD (before updates):**
```
order.delivery_worker.name  // Was directly on user
```

**NEW (current):**
```
order.delivery_worker.user.name        // Name is on nested user object
order.delivery_worker.vehicle_type     // Vehicle info on delivery_worker
order.delivery_worker.status           // Status on delivery_worker
```

**What to Display:**
- Worker name: `order.delivery_worker.user.name`
- Vehicle type: `order.delivery_worker.vehicle_type`
- Status: `order.delivery_worker.status`
- Phone: `order.delivery_worker.phone`

**Fallback for null workers:**
- Show "Not assigned" or similar message
- Don't try to access nested properties without checking

#### 4. Status Badge Colors
Use these colors for consistency:

- **available** → Green background
- **on_delivery** → Blue background  
- **offline** → Gray background

#### 5. Worker Dropdown/Select
When fetching workers for selection:

**Endpoint:** `GET /api/delivery-workers?status=available`

**Display in dropdown:**
- Line 1: Worker name + vehicle type (e.g., "John Doe (car)")
- Line 2: Email or phone
- Badge: Status with color

**Filter:** Only show "available" workers in create form

## API Endpoints

### Delivery Workers
- `GET /api/delivery-workers` - List all workers (with filters)
- `GET /api/delivery-workers/{id}` - Get worker details
- `POST /api/delivery-workers` - Create new worker
- `PUT /api/delivery-workers/{id}` - Update worker
- `DELETE /api/delivery-workers/{id}` - Delete worker
- `PATCH /api/delivery-workers/{id}/status` - Update worker status
- `GET /api/delivery-workers/{id}/current-order` - Get current order

### GraphQL
```graphql
# Queries
deliveryWorkers(status: String): [DeliveryWorker]
deliveryWorker(id: ID!): DeliveryWorker
deliveryWorkersByStatus(status: String!): [DeliveryWorker]

# Mutations
createDeliveryWorker(input: DeliveryWorkerInput!): DeliveryWorker
updateDeliveryWorker(id: ID!, input: DeliveryWorkerInput!): DeliveryWorker
deleteDeliveryWorker(id: ID!): Boolean
```

## Validation Rules

### Backend Validation (Already Done)
```php
[
    'customer_id' => 'required|integer|exists:users,id',
    'delivery_worker_id' => 'nullable|integer|exists:delivery_workers,id', // NULLABLE!
    'address' => 'required|string',
    'city' => 'required|string',
    'zip_code' => 'required|string',
]
```

### Frontend Validation
- Customer ID: **REQUIRED**
- Delivery Worker ID: **OPTIONAL** (don't validate, can be null)
- Address, City, Zip: **REQUIRED**

## What Changed - Key Points

### Data Structure Change
```
OLD: order.delivery_worker = User object with role
NEW: order.delivery_worker = DeliveryWorker object with nested user

OLD: order.delivery_worker.name
NEW: order.delivery_worker.user.name

OLD: No vehicle info
NEW: order.delivery_worker.vehicle_type
```

### Field Validation Change
```
OLD: delivery_worker_id was REQUIRED
NEW: delivery_worker_id is OPTIONAL (nullable)
```

### Status Values
- `available` - Worker is free to take orders
- `on_delivery` - Worker is currently delivering
- `offline` - Worker is not working

### Vehicle Types
- `bike`
- `car`
- `motorcycle`
- `van`
- `truck`

## Best Practices

### 1. Always Check for Null Workers
Worker can be null, always check before accessing properties

### 2. Eager Load Relationships
When fetching orders, include: `?include=deliveryWorker.user`

### 3. Display Format
Show worker as: "Name (Vehicle Type)" with status badge

### 4. Filter by Status
When showing worker dropdown, filter by `status=available`

### 5. Use Consistent Colors
- Green = available
- Blue = on_delivery
- Gray = offline

## Migration from Old System

If you have existing orders with `delivery_worker_id` pointing to `users.id`:

### Data Structure Changed:
**OLD:**
```
order.delivery_worker = User object
order.delivery_worker.name
order.delivery_worker.email
```

**NEW:**
```
order.delivery_worker = DeliveryWorker object
order.delivery_worker.user.name
order.delivery_worker.user.email
order.delivery_worker.vehicle_type
order.delivery_worker.status
```

### What to Update in Frontend:
1. Change `order.delivery_worker.name` → `order.delivery_worker.user.name`
2. Change `order.delivery_worker.email` → `order.delivery_worker.user.email`
3. Add vehicle type display: `order.delivery_worker.vehicle_type`
4. Add status badge: `order.delivery_worker.status`
5. Remove validation that requires delivery_worker_id
6. Add null checks for worker (can be null now)

## Testing

### Test Scenarios

#### 1. Create Order Without Worker
**Request:**
```json
POST /api/orders
{
  "customer_id": 1,
  "address": "123 Main St",
  "delivery_worker_id": null
}
```
**Expected:** Order created successfully with null worker

#### 2. Create Order With Worker
**Request:**
```json
POST /api/orders
{
  "customer_id": 1,
  "address": "123 Main St",
  "delivery_worker_id": 1
}
```
**Expected:** Order created with worker assigned

#### 3. Fetch Available Workers
**Request:**
```
GET /api/delivery-workers?status=available
```
**Expected:** List of available workers with nested user data

#### 4. Fetch Order With Worker
**Request:**
```
GET /api/orders/1?include=deliveryWorker.user
```
**Expected:** Order with nested deliveryWorker.user object

## Common Issues & Solutions

### Issue 1: "delivery_worker_id is required"
**Cause:** Frontend is validating the field as required  
**Fix:** Remove required validation for delivery_worker_id

### Issue 2: "Cannot read property 'name' of null"
**Cause:** Trying to access worker.user.name when worker is null  
**Fix:** Check if worker exists before accessing: `worker?.user?.name || 'Not assigned'`

### Issue 3: Dropdown not showing workers
**Cause:** Calling wrong API endpoint  
**Fix:** Use `/api/delivery-workers` NOT `/api/users`

### Issue 4: Worker info not showing in order list
**Cause:** Not including relationship in API call  
**Fix:** Add `?include=deliveryWorker.user` to orders endpoint

### Issue 5: Getting users table data instead of workers
**Cause:** Still using old structure  
**Fix:** Update from `order.delivery_worker.name` to `order.delivery_worker.user.name`

## Summary

The delivery worker system is now:
- ✅ Separated from user roles
- ✅ Fully optional for order creation
- ✅ Auto-assigns when available
- ✅ Tracks worker status and vehicle info
- ✅ Supports real-time location tracking
- ✅ Has dedicated management interface

**Frontend Requirements:**
- Handle nullable `delivery_worker_id`
- Display worker info with vehicle type
- Use status badges for visual feedback
- Support search/filter functionality
- Show "Not assigned" fallback gracefully
