# Real-Time Delivery Tracking System — Architecture & Setup

This document explains the **enhanced architecture** for real-time delivery worker tracking with live map updates using **Laravel + Node.js + React Native + Geoapify**.

This is a **production-grade microservices approach** ideal for your certification project.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│  Delivery Worker (React Native App)                      │
│  - Sends GPS location every 5 seconds                    │
│  └─> Socket.io connection to Node.js                    │
└──────────────────────┬──────────────────────────────────┘
                       │
                       │ emit('location-update')
                       ▼
┌─────────────────────────────────────────────────────────┐
│  Node.js + Socket.io Server (Real-time Hub)             │
│  - Receives worker location updates                      │
│  - Broadcasts to watching customers in real-time        │
│  - Syncs location data to Laravel API                   │
└──────────────────────┬──────────────────────────────────┘
                       │
      ┌────────────────┼────────────────┐
      │                │                │
      │ POST location  │ GET delivery   │ GET auth
      │ data           │ data           │
      ▼                ▼                ▼
┌─────────────────────────────────────────────────────────┐
│  Laravel API (Source of Truth)                           │
│  - REST API endpoints                                    │
│  - Database models (Order, Delivery, Location)          │
│  - Authentication & Authorization                        │
│  - Business logic                                        │
└─────────────────────────────────────────────────────────┘
                       │
      ┌────────────────┴────────────────┐
      │                                 │
      ▼                                 ▼
┌──────────────────────┐      ┌──────────────────────┐
│  MySQL Database      │      │  Stripe (Payment)    │
│  - Orders            │      │  - Webhooks          │
│  - Deliveries        │      │  - Sessions          │
│  - Locations         │      └──────────────────────┘
│  - Users             │
└──────────────────────┘
                       ▲
                       │
                       │ Socket.io connection
                       │ listen('location-update')
                       │
┌─────────────────────────────────────────────────────────┐
│  Customer (React Native App)                             │
│  - Views live map with worker location                  │
│  - Receives real-time updates via Socket.io             │
│  - Fetches order details from Laravel API               │
└─────────────────────────────────────────────────────────┘
```

---

## Why This Architecture?

| Component | Purpose | Why? |
|-----------|---------|------|
| **Laravel** | Main backend, database, business logic | Source of truth, handles auth, payments, orders |
| **Node.js + Socket.io** | Real-time location streaming | Efficient WebSocket handling, scales for concurrent tracking |
| **React Native** | Mobile apps (worker + customer) | Cross-platform, native feel |
| **Geoapify** | Map rendering | Shows worker location with markers |

This demonstrates **microservices separation of concerns** — perfect for your certification project.

---

## What's New: Real-Time Tracking System

### New Models & Database Tables (Laravel)

You need to add these to your Laravel backend:

#### 1. **DeliveryWorker Model**
```sql
CREATE TABLE delivery_workers (
    id BIGINT PRIMARY KEY,
    user_id BIGINT UNIQUE,
    phone VARCHAR(20),
    vehicle_type ENUM('bike', 'car', 'scooter'),
    status ENUM('available', 'on_delivery', 'offline'),
    current_order_id BIGINT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (current_order_id) REFERENCES orders(id)
);
```

#### 2. **DeliveryLocation Model** (Location History)
```sql
CREATE TABLE delivery_locations (
    id BIGINT PRIMARY KEY,
    order_id BIGINT,
    delivery_worker_id BIGINT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    accuracy FLOAT NULLABLE,
    speed FLOAT NULLABLE,
    heading FLOAT NULLABLE,
    timestamp TIMESTAMP,
    created_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (delivery_worker_id) REFERENCES delivery_workers(id),
    INDEX (order_id, created_at DESC),
    INDEX (delivery_worker_id, created_at DESC)
);
```

#### 3. Update **Order Model**
Add fields to existing `orders` table:
```sql
ALTER TABLE orders ADD COLUMN delivery_worker_id BIGINT NULLABLE;
ALTER TABLE orders ADD COLUMN delivery_started_at TIMESTAMP NULLABLE;
ALTER TABLE orders ADD COLUMN delivery_completed_at TIMESTAMP NULLABLE;
ALTER TABLE orders ADD FOREIGN KEY (delivery_worker_id) REFERENCES delivery_workers(id);
```

---

## Laravel Backend Changes Required

### 1. Create Eloquent Models

**app/Models/DeliveryWorker.php**
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryWorker extends Model
{
    protected $fillable = ['user_id', 'phone', 'vehicle_type', 'status', 'current_order_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentOrder()
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    public function locations()
    {
        return $this->hasMany(DeliveryLocation::class);
    }

    public function latestLocation()
    {
        return $this->locations()->latest('created_at')->first();
    }
}
```

**app/Models/DeliveryLocation.php**
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryLocation extends Model
{
    protected $fillable = ['order_id', 'delivery_worker_id', 'latitude', 'longitude', 'accuracy', 'speed', 'heading', 'timestamp'];
    protected $casts = ['latitude' => 'float', 'longitude' => 'float'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryWorker()
    {
        return $this->belongsTo(DeliveryWorker::class);
    }
}
```

### 2. Create REST API Endpoints

**routes/api.php** — Add these routes:

```php
Route::middleware('auth:api')->group(function () {
    // Delivery worker endpoints
    Route::post('/delivery-worker/location', 'Api\DeliveryLocationController@store');
    Route::get('/delivery/{id}/worker-location', 'Api\DeliveryLocationController@getCurrentLocation');
    Route::get('/delivery-worker/me', 'Api\DeliveryWorkerController@me');
    Route::put('/delivery-worker/{id}/status', 'Api\DeliveryWorkerController@updateStatus');
    
    // Customer tracking
    Route::get('/orders/{id}/tracking', 'Api\OrderTrackingController@show');
});
```

**app/Http/Controllers/Api/DeliveryLocationController.php**
```php
<?php
namespace App\Http\Controllers\Api;

use App\Models\DeliveryLocation;
use Illuminate\Http\Request;

class DeliveryLocationController
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'delivery_worker_id' => 'required|exists:delivery_workers,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
        ]);

        $location = DeliveryLocation::create($validated);

        return response()->json([
            'success' => true,
            'location' => $location,
        ]);
    }

    public function getCurrentLocation($orderId)
    {
        $location = DeliveryLocation::where('order_id', $orderId)
            ->latest('created_at')
            ->first();

        return response()->json($location ?? []);
    }
}
```

### 3. Create Locations Migration

**database/migrations/YYYY_MM_DD_create_delivery_tables.php**
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('delivery_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained();
            $table->string('phone', 20)->nullable();
            $table->enum('vehicle_type', ['bike', 'car', 'scooter'])->default('bike');
            $table->enum('status', ['available', 'on_delivery', 'offline'])->default('offline');
            $table->foreignId('current_order_id')->nullable()->constrained('orders');
            $table->timestamps();
        });

        Schema::create('delivery_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('delivery_worker_id')->constrained();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('accuracy')->nullable();
            $table->float('speed')->nullable();
            $table->float('heading')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
            $table->index(['delivery_worker_id', 'created_at']);
        });

        // Add column to existing orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_worker_id')->nullable()->constrained('delivery_workers');
            $table->timestamp('delivery_started_at')->nullable();
            $table->timestamp('delivery_completed_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_locations');
        Schema::dropIfExists('delivery_workers');
    }
};
```

### 4. Update GraphQL Schema

**graphql/schema.graphql** — Add:

```graphql
type DeliveryWorker {
    id: ID!
    user: User!
    phone: String
    vehicleType: String!
    status: String!
    currentOrder: Order
    latestLocation: DeliveryLocation
    createdAt: DateTime!
    updatedAt: DateTime!
}

type DeliveryLocation {
    id: ID!
    order: Order!
    deliveryWorker: DeliveryWorker!
    latitude: Float!
    longitude: Float!
    accuracy: Float
    speed: Float
    heading: Float
    timestamp: DateTime!
    createdAt: DateTime!
}

type OrderTracking {
    order: Order!
    deliveryWorker: DeliveryWorker
    currentLocation: DeliveryLocation
    locationHistory: [DeliveryLocation!]!
}

extend type Query {
    deliveryWorker(id: ID!): DeliveryWorker
    orderTracking(id: ID!): OrderTracking @auth
}

extend type Mutation {
    updateDeliveryWorkerStatus(id: ID!, status: String!): DeliveryWorker @auth
}
```

---

## Node.js Real-Time Server Setup

### Installation

```bash
mkdir delivery-tracking-server
cd delivery-tracking-server
npm init -y
npm install express socket.io cors dotenv axios
```

### .env file
```env
PORT=3001
LARAVEL_API_URL=http://localhost:8000
NODE_ENV=development
```

### server.js
```javascript
const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');
require('dotenv').config();
const axios = require('axios');

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

app.use(cors());
app.use(express.json());

// Store active delivery tracking rooms
const activeDeliveries = new Map();

io.on('connection', (socket) => {
  console.log('New client connected:', socket.id);

  // Customer joins a delivery tracking room
  socket.on('join-tracking', (orderId) => {
    socket.join(`order-${orderId}`);
    console.log(`Client ${socket.id} joined tracking for order ${orderId}`);
  });

  // Customer leaves tracking room
  socket.on('leave-tracking', (orderId) => {
    socket.leave(`order-${orderId}`);
    console.log(`Client ${socket.id} left tracking for order ${orderId}`);
  });

  // Delivery worker sends location update
  socket.on('location-update', async (data) => {
    const { orderId, workerId, latitude, longitude, accuracy, speed, heading } = data;

    try {
      // Save location to Laravel backend
      await axios.post(`${process.env.LARAVEL_API_URL}/api/delivery-worker/location`, {
        order_id: orderId,
        delivery_worker_id: workerId,
        latitude,
        longitude,
        accuracy,
        speed,
        heading,
        timestamp: new Date().toISOString()
      });

      // Broadcast location to all customers watching this delivery
      io.to(`order-${orderId}`).emit('location-updated', {
        orderId,
        workerId,
        latitude,
        longitude,
        accuracy,
        speed,
        heading,
        timestamp: new Date().toISOString()
      });

      console.log(`📍 Location update for order ${orderId}: ${latitude}, ${longitude}`);
    } catch (error) {
      console.error('Error saving location:', error.message);
      socket.emit('error', { message: 'Failed to update location' });
    }
  });

  socket.on('disconnect', () => {
    console.log('Client disconnected:', socket.id);
  });
});

server.listen(process.env.PORT, () => {
  console.log(`🚀 Real-time tracking server running on port ${process.env.PORT}`);
});
```

### Running Node.js Server
```bash
node server.js
```

---

## React Native Implementation

### Install dependencies
```bash
npm install socket.io-client geoapify-map-request react-native-geolocation-service
```

### Delivery Worker Component (sends location)
```javascript
import { useEffect, useRef } from 'react';
import { io } from 'socket.io-client';
import Geolocation from 'react-native-geolocation-service';

export function WorkerTracking({ orderId, workerId }) {
  const socketRef = useRef(null);

  useEffect(() => {
    socketRef.current = io('http://YOUR_NODE_SERVER:3001');

    // Send location every 5 seconds
    const locationInterval = setInterval(() => {
      Geolocation.getCurrentPosition(
        (position) => {
          socketRef.current.emit('location-update', {
            orderId,
            workerId,
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            speed: position.coords.speed,
          });
        },
        (error) => console.error(error),
        { enableHighAccuracy: true, timeout: 15000 }
      );
    }, 5000);

    return () => {
      clearInterval(locationInterval);
      socketRef.current?.disconnect();
    };
  }, [orderId, workerId]);

  return <Text>📍 Tracking active...</Text>;
}
```

### Customer Component (receives location)
```javascript
import { useEffect, useRef, useState } from 'react';
import { io } from 'socket.io-client';

export function CustomerTracking({ orderId }) {
  const socketRef = useRef(null);
  const [workerLocation, setWorkerLocation] = useState(null);

  useEffect(() => {
    socketRef.current = io('http://YOUR_NODE_SERVER:3001');

    // Join tracking room
    socketRef.current.emit('join-tracking', orderId);

    // Listen for location updates
    socketRef.current.on('location-updated', (data) => {
      setWorkerLocation(data);
    });

    return () => {
      socketRef.current?.emit('leave-tracking', orderId);
      socketRef.current?.disconnect();
    };
  }, [orderId]);

  return (
    <MapView>
      {workerLocation && (
        <Marker
          coordinate={{
            latitude: workerLocation.latitude,
            longitude: workerLocation.longitude,
          }}
          title="Delivery Worker"
        />
      )}
    </MapView>
  );
}
```

---

## Data Flow Summary

### When a delivery is assigned:
1. Customer views order → joins Socket.io room `order-{id}`
2. Delivery worker gets order → starts sending GPS every 5 seconds
3. Node.js receives location → saves to Laravel + broadcasts to customers
4. Customer map updates in real-time ✨

### When delivery is complete:
1. Worker marks delivery complete in app
2. Laravel API updates Order status to `delivered`
3. Row deleted from `orders` table (as per your system)
4. Socket.io room closes automatically
5. Tracking data persists in `delivery_locations` table (history)

---

## Deployment Considerations

| Component | Deployment | Notes |
|-----------|-----------|-------|
| **Laravel** | Traditional server/cloud | Use your existing setup |
| **Node.js** | Lightweight VPS ($5-10/month) | Can handle 1000+ concurrent connections |
| **Database** | MySQL (existing) | Add indexes to `delivery_locations` for queries |
| **React Native** | App stores / TestFlight | Update API URLs for production |

---

## Testing Checklist

- [ ] Laravel migrations run successfully
- [ ] DeliveryWorker and DeliveryLocation models work in tinker
- [ ] REST endpoints return correct data
- [ ] GraphQL schema validates
- [ ] Node.js server starts without errors
- [ ] Socket.io client connects from React Native
- [ ] Location updates broadcast to all watching customers
- [ ] Map updates in real-time on customer app
- [ ] Location history saved to database

---

## Common Commands

```bash
# Laravel
php artisan make:model DeliveryWorker -m
php artisan make:controller Api/DeliveryLocationController
php artisan migrate
php artisan tinker

# Node.js
npm install
node server.js
npm install -g nodemon  # for auto-reload: nodemon server.js

# GraphQL validation
php artisan lighthouse:validate-schema
```

---

## Architecture Advantages for Your Certification

✅ **Microservices approach** — Shows separation of concerns  
✅ **Real-time systems** — WebSocket knowledge (Node.js + Socket.io)  
✅ **Scalable** — Each component can scale independently  
✅ **Production-grade** — Used by real delivery apps (Uber, DoorDash style)  
✅ **Full-stack** — Laravel + Node.js + React Native (impressive!)  
✅ **Database modeling** — Complex relationships & indexing  
✅ **API integration** — REST + GraphQL + WebSockets  

This is **way beyond** a typical student project. Your professor will be impressed! 🚀

---

## File created: `README_TRACKING_SYSTEM.md`