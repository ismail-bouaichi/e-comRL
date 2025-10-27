# GraphQL API Documentation

## 🚀 Your GraphQL Endpoint
**URL:** `http://localhost:8000/graphql`

## 📊 Example Queries

### 1. Get Products with Filters
```graphql
query GetProducts {
  products(
    page: 1
    first: 9
    sort: "price_asc"
    minPrice: 0
    maxPrice: 1000
    searchKey: "phone"
  ) {
    data {
      id
      name
      price
      is_discounted
      discounted_price
      stock_quantity
      category {
        id
        name
      }
      brand {
        id
        name
      }
      images {
        file_path
      }
    }
    paginatorInfo {
      total
      currentPage
      lastPage
    }
  }
}
```

### 2. Get Best Selling Products
```graphql
query BestSellers {
  bestSellingProducts(limit: 10) {
    id
    name
    price
    is_discounted
    discounted_price
    category {
      id
      name
    }
    brand {
      id
      name
    }
    image {
      file_path
    }
  }
}
```

### 3. Search Products
```graphql
query SearchProducts {
  searchProducts(searchKey: "laptop") {
    id
    name
    price
    is_discounted
    discounted_price
    images {
      file_path
    }
  }
}
```

### 4. Get Product Details
```graphql
query GetProduct {
  product(id: 1) {
    id
    name
    description
    price
    is_discounted
    discounted_price
    discount_name
    stock_quantity
    avg_rating
    ratings_count
    category {
      name
    }
    brand {
      name
    }
    images {
      file_path
    }
    ratings {
      rating
      comment
      user {
        name
      }
    }
  }
}
```

### 5. Get Categories with Product Count
```graphql
query GetCategories {
  categoriesWithProducts {
    id
    name
    icon
    products_count
  }
}
```

### 6. Get Brands with Product Count
```graphql
query GetBrands {
  brandsWithProducts {
    id
    name
    logo_path
    products_count
  }
}
```

## 🔐 Authentication Mutations

### 1. Register
```graphql
mutation Register {
  register(input: {
    name: "John Doe"
    email: "john@example.com"
    password: "password123"
    password_confirmation: "password123"
  }) {
    access_token
    token_type
    user {
      id
      name
      email
      role {
        name
      }
    }
  }
}
```

### 2. Login
```graphql
mutation Login {
  login(email: "john@example.com", password: "password123") {
    access_token
    token_type
    user {
      id
      name
      email
      role {
        name
      }
    }
  }
}
```

### 3. Get Current User (Protected)
```graphql
query Me {
  me {
    id
    name
    email
    role {
      name
    }
  }
}
```

**Headers:**
```json
{
  "Authorization": "Bearer YOUR_TOKEN_HERE"
}
```

## 🛒 Order Mutations

### 1. Create Order
```graphql
mutation CreateOrder {
  createOrder(input: {
    first_name: "John"
    last_name: "Doe"
    email: "john@example.com"
    phone: "+1234567890"
    customer_id: 1
    products: [
      { product_id: 1, quantity: 2 }
      { product_id: 3, quantity: 1 }
    ]
    address: "123 Main St"
    zip_code: "12345"
    city: "New York"
    country: "USA"
    latitude: 40.7128
    longitude: -74.0060
  }) {
    message
    stripe_url
    session_id
  }
}
```

### 2. Get Order History
```graphql
query OrderHistory {
  orderHistory(userId: 1) {
    order_id
    status
    order_date
    products {
      name
      quantity
      total
    }
    subtotal
    shipping
    total
    shipping_address {
      city
      address
      zip_code
    }
  }
}
```

### 3. Cancel Order
```graphql
mutation CancelOrder {
  cancelOrder(order_id: 1) {
    message
  }
}
```

## 🎯 Performance Features

✅ **Built-in Caching** - 10 minute cache on all product queries
✅ **Pagination** - All lists support pagination
✅ **Selective Loading** - Only request fields you need
✅ **N+1 Prevention** - Eager loading relationships
✅ **Discount Calculation** - Automatic with every product query

## 🧪 Testing

Visit: `http://localhost:8000/graphql-playground`

Or use any GraphQL client:
- GraphQL Playground (built-in)
- Postman
- Insomnia
- Apollo Studio

---

## 🚚 Delivery Tracking API

### Types

#### DeliveryWorker
```graphql
type DeliveryWorker {
    id: ID!
    user_id: ID!
    user: User
    phone: String
    vehicle_type: String!  # "bike", "car", "scooter"
    status: String!        # "available", "on_delivery", "offline"
    current_order_id: ID
    currentOrder: Order
    orders: [Order!]!
    locations: [DeliveryLocation!]!
    latestLocation: DeliveryLocation
    created_at: DateTime!
    updated_at: DateTime!
}
```

#### DeliveryLocation
```graphql
type DeliveryLocation {
    id: ID!
    order_id: ID!
    delivery_worker_id: ID!
    order: Order
    deliveryWorker: DeliveryWorker
    latitude: Float!
    longitude: Float!
    accuracy: Float
    speed: Float
    heading: Float
    timestamp: DateTime!
    created_at: DateTime!
    updated_at: DateTime!
}
```

### Queries

#### Get My Delivery Worker Profile
```graphql
query {
    myDeliveryWorkerProfile {
        id
        phone
        vehicle_type
        status
        user {
            name
            email
        }
        currentOrder {
            id
            status
        }
        latestLocation {
            latitude
            longitude
            timestamp
        }
    }
}
```

#### Track Order
Get comprehensive tracking information for an order (customer or assigned delivery worker only).

```graphql
query {
    orderTracking(order_id: 123) {
        order {
            id
            status
            delivery_started_at
            delivery_completed_at
        }
        deliveryWorker {
            id
            phone
            vehicle_type
            user {
                name
            }
        }
        latestLocation {
            latitude
            longitude
            speed
            timestamp
        }
        locationHistory {
            latitude
            longitude
            timestamp
        }
    }
}
```

#### Get Delivery Location History
```graphql
query {
    deliveryLocationHistory(order_id: 123, limit: 50) {
        latitude
        longitude
        accuracy
        speed
        heading
        timestamp
    }
}
```

### Mutations

#### Update Delivery Worker Status
```graphql
mutation {
    updateDeliveryWorkerStatus(input: {
        delivery_worker_id: 1
        status: "available"  # "available", "on_delivery", "offline"
    }) {
        id
        status
        user {
            name
        }
    }
}
```

#### Assign Delivery Worker to Order (Admin Only)
```graphql
mutation {
    assignDeliveryWorker(input: {
        order_id: 123
        delivery_worker_id: 5
    }) {
        id
        status
        delivery_started_at
        deliveryWorker {
            id
            user {
                name
            }
            status
        }
    }
}
```

#### Save Delivery Location
```graphql
mutation {
    saveDeliveryLocation(input: {
        order_id: 123
        delivery_worker_id: 5
        latitude: 40.7128
        longitude: -74.0060
        accuracy: 10.5
        speed: 15.5
        heading: 45.0
    }) {
        id
        latitude
        longitude
        timestamp
        order {
            id
            status
        }
    }
}
```

### Authorization

- **Customer**: Can view tracking for their own orders
- **Delivery Worker**: Can view/update their own profile, view assigned orders, save location updates
- **Admin**: Can assign workers to orders, view all workers

### Example Delivery Flow

**1. Admin assigns worker to order:**
```graphql
mutation {
    assignDeliveryWorker(input: {
        order_id: 123
        delivery_worker_id: 5
    }) {
        id
        delivery_started_at
    }
}
```

**2. Worker updates location while delivering:**
```graphql
mutation {
    saveDeliveryLocation(input: {
        order_id: 123
        delivery_worker_id: 5
        latitude: 40.7128
        longitude: -74.0060
        speed: 15.5
    }) {
        id
        timestamp
    }
}
```

**3. Customer tracks order in real-time:**
```graphql
query {
    orderTracking(order_id: 123) {
        latestLocation {
            latitude
            longitude
            speed
            timestamp
        }
    }
}
```

**4. Worker completes delivery:**
```graphql
mutation {
    updateDeliveryWorkerStatus(input: {
        delivery_worker_id: 5
        status: "available"
    }) {
        status
    }
}
```
