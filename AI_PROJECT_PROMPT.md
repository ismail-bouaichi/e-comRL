# E-Commerce Project AI Assistant Prompt

## Project Overview
You are working on a full-stack e-commerce platform with a Laravel backend and Next.js frontend. The project uses a modern tech stack with GraphQL API, Action Pattern architecture, and shadcn/ui components.

## Backend Stack
- **Framework:** Laravel 10.x
- **API:** RESTful + GraphQL (Laravel Lighthouse)
- **Authentication:** Laravel Passport (OAuth2)
- **Payment:** Stripe Checkout with webhook verification
- **Architecture:** Action Pattern for business logic
- **Database:** MySQL (via Laragon)

## Frontend Stack (To Be Built)
- **Framework:** Next.js 14 (App Router)
- **Language:** JavaScript (NOT TypeScript)
- **State Management:** Zustand (lightweight, 1KB)
- **GraphQL Client:** Apollo Client
- **UI Components:** shadcn/ui (Tailwind-based)
- **Forms:** React Hook Form + Zod validation
- **Styling:** Tailwind CSS
- **Maps:** Leaflet + React Leaflet (optional)
- **Icons:** Lucide React

## Project Structure

### Backend (Laravel)
```
app/
├── Actions/
│   ├── Payment/                    # Payment-related business logic
│   │   ├── CreateStripeCheckoutAction.php
│   │   ├── ProcessWebhookAction.php
│   │   ├── RefundPaymentAction.php
│   │   └── VerifyPaymentAction.php
│   └── Product/                    # Product-related business logic
│       ├── CalculateProductDiscountAction.php
│       ├── GetBestSellingProductsAction.php
│       ├── SearchProductsAction.php
│       └── GetProductDetailsAction.php
├── GraphQL/
│   ├── Queries/                    # 6 Query resolvers
│   │   ├── ProductsQuery.php
│   │   ├── BestSellingProductsQuery.php
│   │   ├── SearchProductsQuery.php
│   │   ├── CategoriesWithProductsQuery.php
│   │   ├── BrandsWithProductsQuery.php
│   │   └── OrderHistoryQuery.php
│   └── Mutations/                  # 5 Mutation resolvers
│       ├── LoginMutation.php
│       ├── RegisterMutation.php
│       ├── LogoutMutation.php
│       ├── CreateOrderMutation.php
│       └── CancelOrderMutation.php
├── Http/Controllers/Api/
│   ├── OrderController.php        # Refactored to use Actions
│   └── ProductController.php      # Refactored to use Actions
└── Models/
    ├── User.php
    ├── Product.php
    ├── Order.php
    ├── Category.php
    ├── Brand.php
    └── ... (other models)

graphql/
└── schema.graphql                  # Complete GraphQL schema (15+ types)

routes/
├── api.php                         # REST API routes
└── web.php                         # Web routes (payment success/cancel)
```

### Frontend (Next.js - To Be Built)
```
src/
├── app/                            # Next.js App Router
│   ├── layout.jsx
│   ├── page.jsx                   # Home page
│   ├── products/
│   │   ├── page.jsx               # Product listing
│   │   └── [id]/page.jsx          # Product details
│   ├── cart/page.jsx
│   ├── checkout/page.jsx
│   ├── orders/page.jsx            # Order history
│   └── auth/
│       ├── login/page.jsx
│       └── register/page.jsx
├── components/
│   ├── ui/                         # shadcn/ui components (auto-generated)
│   │   ├── button.jsx
│   │   ├── card.jsx
│   │   ├── input.jsx
│   │   ├── dialog.jsx
│   │   ├── badge.jsx
│   │   └── ... (more shadcn components)
│   ├── product/                    # Product-specific components
│   │   ├── product-card.jsx
│   │   ├── product-grid.jsx
│   │   └── product-filters.jsx
│   ├── cart/
│   │   ├── cart-item.jsx
│   │   └── cart-drawer.jsx
│   └── layout/
│       ├── header.jsx
│       └── navigation.jsx
├── lib/
│   └── apollo-client.js           # Apollo Client config
└── store/
    ├── auth-store.js              # Zustand auth store
    ├── cart-store.js              # Zustand cart store
    └── filter-store.js            # Zustand filter store
```

## Key Features & Business Logic

### 1. **Action Pattern Architecture**
All business logic is extracted into reusable Action classes:
- **Purpose:** Single Responsibility, Reusability, Testability
- **Usage:** Both REST Controllers and GraphQL Resolvers use the same Actions
- **Example:** `CalculateProductDiscountAction` is used by ProductController, ProductsQuery, and BestSellingProductsQuery

### 2. **Payment Flow (Stripe)**
```
User → Add to Cart → Checkout Form → CreateStripeCheckoutAction
→ Redirect to Stripe → Payment → Stripe Webhook → ProcessWebhookAction
→ Order Status: unpaid → paid → Stock Decrement → Email Notification
```

**Actions:**
- `CreateStripeCheckoutAction`: Creates Stripe session with line items
- `ProcessWebhookAction`: Handles webhook with idempotency, updates order
- `VerifyPaymentAction`: Verifies payment on success redirect
- `RefundPaymentAction`: Processes refunds + stock restoration

### 3. **Product Discount Logic**
- **Types:** Percentage or Fixed amount
- **Calculation:** Single source of truth in `CalculateProductDiscountAction`
- **Fields:** `is_discounted`, `discount_type`, `discount_value`, `discounted_price`

### 4. **Order Status Flow**
```
unpaid → paid → onProgress → complete
                         ↘ cancelled (with refund)
```

### 5. **GraphQL API**
- **Endpoint:** `http://localhost:8000/graphql`
- **Playground:** `http://localhost:8000/graphql-playground`
- **Authentication:** Bearer token in `Authorization` header
- **Caching:** 10-minute cache on read queries

**Key Queries:**
- `products(page, limit, category_id, brand_id, min_price, max_price, sort, search)`
- `product(id)`
- `bestSellingProducts(limit)`
- `searchProducts(query)`
- `categories` / `brands`
- `orderHistory(userId)`

**Key Mutations:**
- `login(email, password)` → Returns token + user
- `register(input)` → Returns token + user
- `createOrder(input)` → Returns `stripe_url` for redirect
- `cancelOrder(orderId)` → Refunds payment + restores stock

## Important Configurations

### Environment Variables (.env)
```env
# Laravel
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=root
DB_PASSWORD=

# Stripe (IMPORTANT: No line breaks in keys!)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Frontend URL
FRONTEND_URL=http://localhost:3000
```

### Stripe Configuration
- **Critical:** Stripe keys must be on single lines (no line breaks)
- **Webhook URL:** `http://localhost:8000/api/orders/webhook`
- **Events:** `checkout.session.completed`

### CORS Configuration
Ensure `config/cors.php` allows frontend origin:
```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:3000'),
],
```

## Development Guidelines

### Backend Best Practices
1. **Always use Actions for business logic** - Don't put complex logic in controllers
2. **Reuse Actions across REST and GraphQL** - No duplicate code
3. **Use DB transactions for critical operations** - Payment processing, stock updates
4. **Add idempotency checks for webhooks** - Prevent double-processing
5. **Cache expensive queries** - Use `Cache::remember()` with 10-minute TTL
6. **Validate inputs** - Use Form Requests for REST, Input types for GraphQL

### Frontend Best Practices (When Building)
1. **Use shadcn/ui components** - Don't rebuild basic UI from scratch
2. **JavaScript, not TypeScript** - Keep it simple for faster development
3. **Zustand for state management** - Lightweight, no boilerplate
4. **Apollo Client for GraphQL** - Built-in caching and error handling
5. **Server Components for initial loads** - Client Components for interactivity
6. **Use `next/image` for all images** - Performance optimization
7. **Form validation with Zod** - Type-safe validation without TypeScript
8. **Toast notifications for feedback** - Use shadcn Toast component

### Code Style
- **JavaScript:** Use arrow functions, destructuring, modern ES6+ syntax
- **Components:** Functional components with hooks
- **File naming:** kebab-case for files, PascalCase for components
- **Props documentation:** Use JSDoc comments

## Common Tasks & Commands

### Backend Commands
```bash
# Start Laravel server
php artisan serve

# Validate GraphQL schema
php artisan lighthouse:validate-schema

# Clear cache
php artisan cache:clear

# Run migrations
php artisan migrate

# Tinker (debugging)
php artisan tinker
```

### Frontend Commands (To Be Used)
```bash
# Create Next.js project
npx create-next-app@latest ecommerce-frontend --js --tailwind --app

# Install shadcn/ui
npx shadcn@latest init

# Add shadcn components
npx shadcn@latest add button card input dialog badge

# Install dependencies
npm install @apollo/client graphql zustand react-hook-form zod

# Run dev server
npm run dev
```

### Testing Stripe Webhooks (Local)
```bash
# Install Stripe CLI
stripe listen --forward-to localhost:8000/api/orders/webhook

# Trigger test event
stripe trigger checkout.session.completed
```

## Critical Issues Resolved

### Issue 1: Stripe Keys with Line Breaks
**Problem:** `.env` file had Stripe keys split across multiple lines
**Solution:** Ensured keys are on single lines without line breaks
**Validation:** Tested with `php artisan tinker` → `config('services.stripe.secret')`

### Issue 2: Duplicate Discount Logic
**Problem:** Discount calculation logic repeated 4+ times in ProductController
**Solution:** Created `CalculateProductDiscountAction` as single source of truth
**Impact:** Reduced ProductController from 370 to 240 lines (35% reduction)

### Issue 3: Missing GraphQL Schema
**Problem:** Lighthouse installed but no schema/resolvers created
**Solution:** Created complete `schema.graphql` + 11 resolver classes
**Validation:** `php artisan lighthouse:validate-schema` passed

## API Response Examples

### Products Query Response
```json
{
  "data": {
    "products": {
      "data": [
        {
          "id": "1",
          "name": "Product Name",
          "description": "Product description",
          "price": 99.99,
          "is_discounted": true,
          "discount_type": "percentage",
          "discount_value": 20,
          "discounted_price": 79.99,
          "stock": 50,
          "images": [
            {
              "id": "1",
              "file_path": "http://localhost:8000/storage/products/image.jpg"
            }
          ],
          "category": {
            "id": "1",
            "name": "Category Name"
          },
          "brand": {
            "id": "1",
            "name": "Brand Name"
          }
        }
      ],
      "paginatorInfo": {
        "currentPage": 1,
        "lastPage": 5,
        "total": 50
      }
    }
  }
}
```

### Login Mutation Response
```json
{
  "data": {
    "login": {
      "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
      "user": {
        "id": "1",
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com"
      }
    }
  }
}
```

### Create Order Response
```json
{
  "data": {
    "createOrder": {
      "stripe_url": "https://checkout.stripe.com/c/pay/cs_test_..."
    }
  }
}
```

## Documentation Files
- `FRONTEND_SETUP_GUIDE.md` - Complete Next.js frontend setup guide
- `GRAPHQL_API.md` - GraphQL API documentation with example queries
- `AI_PROJECT_PROMPT.md` - This file (AI context prompt)

## When Asking for Help

### Provide Context
1. **What are you trying to do?** (Feature, bug fix, optimization)
2. **What have you tried?** (Commands run, files checked)
3. **What's not working?** (Error messages, unexpected behavior)
4. **Which part of the stack?** (Backend API, GraphQL, Frontend)

### Good Question Examples
- "I need to add a 'favorites' feature where users can save products. Where should I start?"
- "The Stripe webhook is returning 400 error. How do I debug this?"
- "How do I implement the product filter sidebar using shadcn components?"
- "I want to add product reviews. Should I create a new Action class?"

### Code Style Preferences
- **No TypeScript** - Use JavaScript with JSDoc comments
- **Use Actions** - Extract business logic to Action classes
- **Use shadcn/ui** - Don't build basic UI components from scratch
- **Modern JavaScript** - Arrow functions, async/await, destructuring
- **Functional components** - Hooks over class components

## Quick Reference

### File Locations
- GraphQL Schema: `graphql/schema.graphql`
- GraphQL Queries: `app/GraphQL/Queries/`
- GraphQL Mutations: `app/GraphQL/Mutations/`
- Actions: `app/Actions/Payment/` and `app/Actions/Product/`
- REST Controllers: `app/Http/Controllers/Api/`
- Models: `app/Models/`
- Routes: `routes/api.php`, `routes/web.php`

### URLs
- Laravel: `http://localhost:8000`
- GraphQL Endpoint: `http://localhost:8000/graphql`
- GraphQL Playground: `http://localhost:8000/graphql-playground`
- Next.js (when built): `http://localhost:3000`
- Storage: `http://localhost:8000/storage/`

### Authentication
- **Type:** Bearer token (JWT from Laravel Passport)
- **Header:** `Authorization: Bearer {token}`
- **Storage:** localStorage or httpOnly cookie (frontend)
- **Lifetime:** Configurable in `config/passport.php`

---

**Last Updated:** October 21, 2025  
**Project Status:** Backend complete, Frontend to be built  
**Current Phase:** Ready for Next.js frontend development
