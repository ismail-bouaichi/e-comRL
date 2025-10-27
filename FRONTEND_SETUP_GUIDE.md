# 🚀 Next.js Frontend Setup Guide

## 📋 Project Overview
**High-Performance E-Commerce Frontend**
- **Framework:** Next.js 14 (App Router)
- **State Management:** Zustand
- **Data Layer:** GraphQL (Apollo Client)
- **Styling:** Tailwind CSS
- **Language:** JavaScript

---

## 🛠️ Installation Steps

### 1. Create Next.js Project
```bash
npx create-next-app@latest ecommerce-frontend --js --tailwind --app --no-src-dir --import-alias "@/*"
cd ecommerce-frontend
```

**Configuration during setup:**
- ❌ TypeScript: No (using JavaScript)
- ✅ ESLint: Yes
- ✅ Tailwind CSS: Yes
- ✅ App Router: Yes
- ✅ Turbopack: Yes (for faster dev)
- ❌ src/ directory: No

---

### 2. Install Required Dependencies

```bash
# GraphQL Client
npm install @apollo/client graphql

# State Management
npm install zustand

# Form Handling
npm install react-hook-form zod @hookform/resolvers

# shadcn/ui (UI Components)
npx shadcn@latest init

# Additional UI utilities
npm install lucide-react # Icons

# Map Integration (if needed)
npm install leaflet react-leaflet

# Date Handling
npm install date-fns

# Image Optimization (optional)
npm install sharp
```

**shadcn/ui Configuration:**
During `shadcn init`, select:
- ✅ Style: Default
- ✅ Base color: Slate (or your preference)
- ✅ CSS variables: Yes
- ✅ React Server Components: Yes
- ✅ Components location: `components/ui`

**Install shadcn components you'll need:**
```bash
# Essential components for e-commerce
npx shadcn@latest add button
npx shadcn@latest add card
npx shadcn@latest add input
npx shadcn@latest add label
npx shadcn@latest add select
npx shadcn@latest add dialog
npx shadcn@latest add dropdown-menu
npx shadcn@latest add badge
npx shadcn@latest add separator
npx shadcn@latest add slider
npx shadcn@latest add toast
npx shadcn@latest add skeleton
npx shadcn@latest add tabs
npx shadcn@latest add checkbox
npx shadcn@latest add radio-group
npx shadcn@latest add form
```

---

### 3. Install Dev Dependencies

```bash
npm install -D prettier prettier-plugin-tailwindcss
npm install -D eslint-config-next
```

---

## 📁 Project Structure

```
ecommerce-frontend/
├── app/                          # Next.js App Router
│   ├── (auth)/                   # Auth routes group
│   │   ├── login/
│   │   │   └── page.jsx
│   │   └── register/
│   │       └── page.jsx
│   ├── (shop)/                   # Shop routes group
│   │   ├── page.jsx             # Home (product listing)
│   │   ├── products/
│   │   │   └── [id]/            # Product details
│   │   │       └── page.jsx
│   │   ├── cart/
│   │   │   └── page.jsx
│   │   ├── checkout/
│   │   │   └── page.jsx
│   │   └── orders/
│   │       └── page.jsx
│   ├── layout.jsx               # Root layout
│   ├── providers.jsx            # Apollo + Auth providers
│   └── globals.css              # Global styles
│
├── lib/                         # Core utilities
│   ├── apollo-client.js         # Apollo Client config
│   ├── graphql/                 # GraphQL operations
│   │   ├── queries/
│   │   │   ├── products.js
│   │   │   ├── categories.js
│   │   │   └── orders.js
│   │   └── mutations/
│   │       ├── auth.js
│   │       ├── orders.js
│   │       └── cart.js
│   └── utils.js                 # Helper functions
│
├── store/                       # Zustand stores
│   ├── auth-store.js            # User auth state
│   ├── cart-store.js            # Shopping cart
│   ├── filter-store.js          # Product filters
│   └── ui-store.js              # UI state (modals, etc)
│
├── components/                  # Reusable components
│   ├── ui/                      # shadcn/ui components (auto-generated)
│   │   ├── button.jsx          # shadcn button
│   │   ├── card.jsx            # shadcn card
│   │   ├── input.jsx           # shadcn input
│   │   ├── label.jsx           # shadcn label
│   │   ├── select.jsx          # shadcn select
│   │   ├── dialog.jsx          # shadcn dialog
│   │   ├── dropdown-menu.jsx   # shadcn dropdown
│   │   ├── badge.jsx           # shadcn badge
│   │   ├── slider.jsx          # shadcn slider (for price filter)
│   │   ├── skeleton.jsx        # shadcn skeleton (loading)
│   │   ├── toast.jsx           # shadcn toast (notifications)
│   │   ├── tabs.jsx            # shadcn tabs
│   │   └── form.jsx            # shadcn form
│   ├── product/                 # Product-specific components
│   │   ├── product-card.jsx
│   │   ├── product-grid.jsx
│   │   ├── product-filters.jsx
│   │   └── product-details.jsx
│   ├── cart/                    # Cart components
│   │   ├── cart-item.jsx
│   │   ├── cart-summary.jsx
│   │   └── cart-drawer.jsx
│   ├── order/                   # Order components
│   │   ├── order-form.jsx
│   │   ├── order-history.jsx
│   │   └── order-status-badge.jsx
│   └── layout/                  # Layout components
│       ├── header.jsx
│       ├── footer.jsx
│       ├── navigation.jsx
│       └── search-bar.jsx
│
├── hooks/                       # Custom React hooks
│   ├── use-products.js
│   ├── use-auth.js
│   ├── use-cart.js
│   └── use-orders.js
│
└── public/                      # Static assets
    ├── images/
    └── icons/
```

---

## 🔌 API Connection Setup

### GraphQL Endpoint Configuration

**File:** `lib/apollo-client.js`

```javascript
const API_URL = 'http://localhost:8000/graphql'
```

**Key Configuration:**
- Base URL: `http://localhost:8000/graphql`
- Auth Header: `Authorization: Bearer {token}`
- Cache: InMemoryCache with type policies
- Error Handling: Global error link

---

## 📊 API Response Structures

### 1. Products Query Response
```json
{
  "data": {
    "products": {
      "data": [
        {
          "id": "1",
          "name": "iPhone 15 Pro",
          "price": 999.99,
          "is_discounted": true,
          "discounted_price": 899.99,
          "discount_name": "Black Friday Sale",
          "discount_code": "BF2024",
          "stock_quantity": 50,
          "category": {
            "id": "2",
            "name": "Smartphones"
          },
          "brand": {
            "id": "1",
            "name": "Apple"
          },
          "images": [
            {
              "id": "5",
              "file_path": "/storage/products/iphone15.jpg"
            }
          ]
        }
      ],
      "paginatorInfo": {
        "total": 100,
        "currentPage": 1,
        "lastPage": 12,
        "perPage": 9,
        "hasMorePages": true
      }
    }
  }
}
```

---

### 2. Product Details Response
```json
{
  "data": {
    "product": {
      "id": "1",
      "name": "iPhone 15 Pro",
      "description": "Latest iPhone with A17 chip...",
      "price": 999.99,
      "is_discounted": true,
      "discounted_price": 899.99,
      "discount_name": "Black Friday Sale",
      "stock_quantity": 50,
      "avg_rating": "4.5",
      "ratings_count": 128,
      "category": {
        "id": "2",
        "name": "Smartphones"
      },
      "brand": {
        "id": "1",
        "name": "Apple",
        "logo_path": "/storage/brands/apple.png"
      },
      "images": [
        {
          "id": "5",
          "file_path": "/storage/products/iphone15-1.jpg"
        },
        {
          "id": "6",
          "file_path": "/storage/products/iphone15-2.jpg"
        }
      ],
      "ratings": [
        {
          "id": "10",
          "rating": 5,
          "comment": "Great phone!",
          "user": {
            "id": "3",
            "name": "John Doe"
          }
        }
      ]
    }
  }
}
```

---

### 3. Categories Response
```json
{
  "data": {
    "categoriesWithProducts": [
      {
        "id": "1",
        "name": "Electronics",
        "icon": "laptop",
        "products_count": 45
      },
      {
        "id": "2",
        "name": "Smartphones",
        "icon": "smartphone",
        "products_count": 32
      }
    ]
  }
}
```

---

### 4. Best Selling Products Response
```json
{
  "data": {
    "bestSellingProducts": [
      {
        "id": "5",
        "name": "MacBook Pro M3",
        "price": 1999.99,
        "is_discounted": false,
        "discounted_price": null,
        "category": {
          "id": "1",
          "name": "Laptops"
        },
        "brand": {
          "id": "1",
          "name": "Apple"
        },
        "image": {
          "file_path": "/storage/products/macbook.jpg"
        }
      }
    ]
  }
}
```

---

### 5. Authentication Responses

**Login/Register:**
```json
{
  "data": {
    "login": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
      "token_type": "Bearer",
      "user": {
        "id": "1",
        "name": "John Doe",
        "email": "john@example.com",
        "role": {
          "id": "3",
          "name": "customer"
        }
      }
    }
  }
}
```

**Current User (me):**
```json
{
  "data": {
    "me": {
      "id": "1",
      "name": "John Doe",
      "email": "john@example.com",
      "role": {
        "name": "customer"
      },
      "created_at": "2024-01-15T10:30:00Z"
    }
  }
}
```

---

### 6. Order Creation Response
```json
{
  "data": {
    "createOrder": {
      "message": "Your Order has been initiated. Complete payment to confirm.",
      "stripe_url": "https://checkout.stripe.com/c/pay/cs_test_...",
      "session_id": "cs_test_a1b2c3d4e5f6..."
    }
  }
}
```

---

### 7. Order History Response
```json
{
  "data": {
    "orderHistory": [
      {
        "order_id": "42",
        "status": "paid",
        "order_date": "2024-01-20T14:30:00Z",
        "products": [
          {
            "name": "iPhone 15",
            "quantity": 1,
            "total": 899.99
          },
          {
            "name": "AirPods Pro",
            "quantity": 2,
            "total": 498.00
          }
        ],
        "subtotal": 1397.99,
        "shipping": 17.00,
        "total": 1414.99,
        "shipping_address": {
          "city": "New York",
          "address": "123 Main St, Apt 4B",
          "zip_code": "10001"
        },
        "note": "Order placed"
      }
    ]
  }
}
```

---

### 8. Error Response Format
```json
{
  "errors": [
    {
      "message": "Unauthenticated.",
      "extensions": {
        "category": "authentication"
      },
      "path": ["me"]
    }
  ],
  "data": null
}
```

---

## 🗄️ Zustand Store Structures

### 1. Auth Store
```javascript
// store/auth-store.js
const useAuthStore = create((set) => ({
  user: null,
  token: null,
  isAuthenticated: false,
  login: async (email, password) => { /* ... */ },
  register: async (data) => { /* ... */ },
  logout: () => { /* ... */ },
  checkAuth: () => { /* ... */ }
}))
```

### 2. Cart Store
```javascript
// store/cart-store.js
const useCartStore = create((set, get) => ({
  items: [],
  total: 0,
  addItem: (product, quantity) => { /* ... */ },
  removeItem: (productId) => { /* ... */ },
  updateQuantity: (productId, quantity) => { /* ... */ },
  clearCart: () => { /* ... */ },
  getItemCount: () => { /* ... */ }
}))
```

### 3. Filter Store
```javascript
// store/filter-store.js
const useFilterStore = create((set) => ({
  categories: [],
  brands: [],
  priceRange: [0, 10000],
  sortBy: 'all',
  searchKey: '',
  setCategories: (categories) => set({ categories }),
  setBrands: (brands) => set({ brands }),
  setPriceRange: (range) => set({ priceRange: range }),
  setSortBy: (sort) => set({ sortBy: sort }),
  setSearchKey: (search) => set({ searchKey: search }),
  reset: () => set({ /* defaults */ })
}))
```

---

## 🎯 Key Features to Implement

### 1. Product Listing Page
- **GraphQL Query:** `products` with filters
- **State:** Filter store + local pagination
- **shadcn Components Used:**
  - `Card` - Product card wrapper
  - `Badge` - Discount labels, stock status
  - `Button` - Add to cart, pagination
  - `Select` - Sort dropdown
  - `Slider` - Price range filter
  - `Checkbox` - Category/brand filters
  - `Skeleton` - Loading state
- **Features:**
  - Grid/List view toggle
  - Category filters (sidebar with Checkbox)
  - Brand filters (sidebar with Checkbox)
  - Price range slider (Slider component)
  - Sort dropdown (Select component)
  - Search bar (Input component)
  - Pagination controls
  - Show discount badges (Badge component)
  - "Add to Cart" quick action (Button)

### 2. Product Details Page
- **GraphQL Query:** `product(id: $id)`
- **State:** Cart store
- **shadcn Components Used:**
  - `Card` - Product info container
  - `Badge` - Stock status, discount badge
  - `Button` - Add to cart, quantity controls
  - `Tabs` - Description/Reviews/Specs tabs
  - `Separator` - Section dividers
  - `Toast` - "Added to cart" notification
- **Features:**
  - Image gallery/carousel
  - Price with discount display (Badge for discount %)
  - Stock availability indicator (Badge)
  - Quantity selector (Button +/-)
  - Add to cart button (Button)
  - Related products (by category)
  - Related products (by brand)
  - Star ratings display
  - Customer reviews list (Tabs component)
  - Breadcrumb navigation

### 3. Shopping Cart
- **State:** Cart store (Zustand)
- **shadcn Components Used:**
  - `Sheet` or `Dialog` - Cart drawer/modal
  - `Card` - Cart item wrapper
  - `Button` - Quantity +/-, Remove, Checkout
  - `Separator` - Price breakdown dividers
  - `Badge` - Item count indicator
- **Features:**
  - List all cart items (Card components)
  - Quantity increment/decrement (Button)
  - Remove item (Button with icon)
  - Calculate subtotal
  - Show shipping estimate
  - Calculate total
  - "Proceed to Checkout" button (Button)
  - Empty cart state (with icon)

### 4. Checkout Flow
- **GraphQL Mutation:** `createOrder`
- **State:** Cart store + Form state
- **shadcn Components Used:**
  - `Form` - Form wrapper with validation
  - `Input` - All text inputs
  - `Label` - Form field labels
  - `Button` - Navigation, Submit
  - `Card` - Step containers, order summary
  - `Separator` - Step dividers
  - `RadioGroup` - Shipping options
  - `Toast` - Success/error messages
- **Features:**
  - **Step 1:** Customer information form (Form + Input)
    - First name, last name
    - Email, phone
  - **Step 2:** Shipping address (Form + Input)
    - Address, city, zip, country
    - Optional: Map integration (lat/long)
  - **Step 3:** Review & Payment (Card)
    - Order summary
    - Product list preview
    - Total calculation
    - Submit → Redirect to Stripe (Button)
  - Success page (after Stripe redirect)

### 5. Order History
- **GraphQL Query:** `orderHistory(userId: $id)`
- **State:** Local component state
- **shadcn Components Used:**
  - `Card` - Order card container
  - `Badge` - Order status (paid, shipped, cancelled)
  - `Button` - View details, Cancel order
  - `Dialog` - Order details modal
  - `Separator` - Section dividers
  - `Skeleton` - Loading state
- **Features:**
  - List all past orders (Card components)
  - Order status badges (Badge - variant by status)
  - Order date formatting
  - Product list per order
  - Shipping address display
  - Total amount
  - "View Details" action (Button + Dialog)
  - "Cancel Order" button (Button with confirmation Dialog)

### 6. Authentication
- **GraphQL Mutations:** `login`, `register`, `logout`
- **State:** Auth store
- **shadcn Components Used:**
  - `Form` - Login/Register forms
  - `Input` - Email, Password fields
  - `Label` - Form labels
  - `Button` - Submit, Logout buttons
  - `Card` - Auth form container
  - `Toast` - Success/error messages
  - `DropdownMenu` - User menu in header
- **Features:**
  - Login form with validation (Form + Input)
  - Register form with password confirmation (Form)
  - Token storage (localStorage/cookies)
  - Auto-login on app load
  - Protected routes middleware
  - Logout action (DropdownMenu)
  - Display user name in header (DropdownMenu)

---

## ⚡ Performance Optimizations

### 1. Next.js Features to Use
- **Server Components:** For initial data fetching
- **Client Components:** For interactive UI (cart, filters)
- **Static Generation (ISR):** For product pages
- **Image Optimization:** `next/image` for all product images
- **Route Groups:** Organize by features
- **Parallel Routes:** Load multiple sections simultaneously
- **Suspense Boundaries:** For loading states

### 2. GraphQL Optimizations
- **Query Caching:** Apollo InMemoryCache
- **Batch Requests:** Reduce network calls
- **Selective Fields:** Only request needed data
- **Pagination:** Use cursor or offset pagination
- **Optimistic Updates:** Immediate UI feedback

### 3. Zustand Best Practices
- **Slice Pattern:** Separate concerns
- **Persist Middleware:** Save cart/auth to localStorage
- **DevTools:** Enable for debugging
- **Immer Middleware:** Immutable state updates
- **Shallow Comparison:** Optimize re-renders

---

## 🔐 Authentication Flow

### 1. Token Storage
- Store JWT in httpOnly cookie (secure) or localStorage (simple)
- Include in Apollo Client headers
- Check token on app initialization

### 2. Protected Routes
- Check auth state before rendering
- Redirect to login if unauthenticated
- Show loading state during check

### 3. Token Refresh
- Monitor token expiration
- Auto-refresh or prompt re-login
- Handle 401 errors globally

---

## 🎨 Styling & Theming

### shadcn/ui Theme Configuration
Your theme is defined in `app/globals.css`:
```css
@layer base {
  :root {
    --background: 0 0% 100%;
    --foreground: 222.2 84% 4.9%;
    --primary: 221.2 83.2% 53.3%;
    /* ... more variables */
  }
}
```

**Customize colors in `tailwind.config.js`:**
```javascript
module.exports = {
  theme: {
    extend: {
      colors: {
        border: "hsl(var(--border))",
        primary: "hsl(var(--primary))",
        // Add custom e-commerce colors
        sale: "hsl(0, 84%, 60%)", // Red for sale badges
        success: "hsl(142, 71%, 45%)", // Green for success
      },
    },
  },
}
```

### Image Handling

**Laravel Storage URLs:**
- Base URL: `http://localhost:8000`
- Product images: `http://localhost:8000/storage/products/filename.jpg`
- Brand logos: `http://localhost:8000/storage/brands/filename.png`

**Next.js Configuration:**
Add to `next.config.js`:
```javascript
module.exports = {
  images: {
    domains: ['localhost'],
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8000',
        pathname: '/storage/**',
      },
    ],
  },
}
```

---

## 🧪 Testing

### GraphQL Playground
Test queries at: `http://localhost:8000/graphql-playground`

### Example Test Query
```graphql
query TestProducts {
  products(first: 3) {
    data {
      id
      name
      price
    }
  }
}
```

---

## 📦 Environment Variables

Create `.env.local`:
```bash
NEXT_PUBLIC_GRAPHQL_URL=http://localhost:8000/graphql
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_STORAGE_URL=http://localhost:8000/storage
```

---

## 🚀 Development Workflow

1. **Start Laravel Backend:**
   ```bash
   php artisan serve
   ```

2. **Start Next.js Frontend:**
   ```bash
   npm run dev
   ```

3. **Access:**
   - Frontend: `http://localhost:3000`
   - GraphQL: `http://localhost:8000/graphql-playground`

---

## 📝 Implementation Checklist

### Phase 1: Setup
- [ ] Create Next.js project
- [ ] Install dependencies
- [ ] Configure Apollo Client
- [ ] Create Zustand stores
- [ ] Setup TypeScript types

### Phase 2: Core Features
- [ ] Authentication (login/register)
- [ ] Product listing with filters
- [ ] Product details page
- [ ] Shopping cart
- [ ] Checkout flow

### Phase 3: Enhanced Features
- [ ] Order history
- [ ] User profile
- [ ] Search functionality
- [ ] Related products
- [ ] Reviews/ratings display

### Phase 4: Polish
- [ ] Loading states
- [ ] Error boundaries
- [ ] Responsive design
- [ ] SEO optimization
- [ ] Performance tuning

---

## 🔗 Useful Resources

- **Next.js Docs:** https://nextjs.org/docs
- **Apollo Client:** https://www.apollographql.com/docs/react/
- **Zustand:** https://docs.pmnd.rs/zustand
- **Tailwind CSS:** https://tailwindcss.com/docs
- **GraphQL Playground:** http://localhost:8000/graphql-playground

---

## 💡 Tips for Success

1. **Start Small:** Build authentication first, then products, then cart
2. **Use shadcn Components:** Don't rebuild UI from scratch - use shadcn's pre-built components
3. **Customize Variants:** Extend shadcn components with custom variants using `cva`
4. **Document Props:** Add JSDoc comments for component props
5. **Cache Wisely:** Configure Apollo cache policies per query
6. **Optimize Images:** Always use `next/image` component
7. **Error Handling:** Use shadcn Toast for user-friendly error messages
8. **Loading States:** Use shadcn Skeleton components for loading states
9. **Mobile First:** shadcn components are responsive by default
10. **Test Queries:** Use GraphQL Playground before implementing

## 🎨 shadcn Component Patterns

### Example: Product Card with shadcn
```javascript
import { Card, CardContent, CardFooter } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"

export default function ProductCard({ product }) {
  return (
    <Card>
      <CardContent className="p-4">
        <img src={product.images[0].file_path} alt={product.name} />
        <h3>{product.name}</h3>
        {product.is_discounted && (
          <Badge variant="destructive">-{discount}%</Badge>
        )}
        <p className="text-2xl font-bold">
          ${product.discounted_price}
        </p>
      </CardContent>
      <CardFooter>
        <Button className="w-full">Add to Cart</Button>
      </CardFooter>
    </Card>
  )
}
```

### Example: Filter Sidebar
```javascript
import { Label } from "@/components/ui/label"
import { Checkbox } from "@/components/ui/checkbox"
import { Slider } from "@/components/ui/slider"

export default function ProductFilters() {
  return (
    <div className="space-y-4">
      {/* Category Filter */}
      <div>
        <Label>Categories</Label>
        {categories.map((cat) => (
          <div className="flex items-center space-x-2">
            <Checkbox id={cat.id} />
            <label htmlFor={cat.id}>{cat.name}</label>
          </div>
        ))}
      </div>
      
      {/* Price Filter */}
      <div>
        <Label>Price Range</Label>
        <Slider
          min={0}
          max={1000}
          step={10}
          defaultValue={[0, 1000]}
        />
      </div>
    </div>
  )
}
```

### Example: Cart Drawer
```javascript
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"

export default function CartDrawer() {
  const { items, total, getItemCount } = useCartStore()
  
  return (
    <Sheet>
      <SheetTrigger asChild>
        <Button variant="outline" className="relative">
          Cart
          <Badge className="ml-2">{getItemCount()}</Badge>
        </Button>
      </SheetTrigger>
      <SheetContent>
        <SheetHeader>
          <SheetTitle>Shopping Cart</SheetTitle>
        </SheetHeader>
        {/* Cart items */}
        <Button className="w-full">Checkout - ${total}</Button>
      </SheetContent>
    </Sheet>
  )
}
```

---

**Ready to build? Start with authentication and product listing!** 🚀
