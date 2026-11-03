# D STORE - System Architecture & Module Boundaries

## 1. Domain & Module Boundaries

D STORE is architected as a modular monolith in Laravel 11 with distinct service domains:

```
app/
├── Models/                     # Eloquent entities with explicit casts & relationships
├── Http/
│   ├── Controllers/
│   │   ├── Customer/           # Customer-facing Inertia controllers
│   │   └── Admin/              # Protected administrative controllers
│   ├── Requests/               # Form validation requests with strict rules
│   └── Middleware/             # Localization, active staff, role-based checks
├── Services/
│   ├── Identity/               # Authentication, staff RBAC, session management
│   ├── Catalog/                # Category hierarchy, dynamic attributes, variants, media
│   ├── Inventory/              # Append-only movements, atomic reservations, safety thresholds
│   ├── Cart/                   # Guest & user cart lifecycle, item normalization, merge
│   ├── Pricing/                # Minor-unit calculation pipeline, campaign rules, tax, currency
│   ├── Checkout/               # Idempotent order placement, atomic stock & coupon locking
│   ├── Order/                  # State machine transitions, history, invoices
│   ├── Payment/                # Gateway contracts, sandbox adapters, webhook deduplication
│   ├── Refund/                 # Partial/full allocations, return inspection, restock
│   ├── Shipping/               # Geographic zones (Cambodia provinces/districts), rates
│   ├── Loyalty/                # Signed ledger, point lots/expiry, clawback debt accounting
│   ├── Marketing/              # Coupon validation, hero banners, flash sales, campaigns
│   ├── Content/                # CMS pages (About, FAQ, Terms), contact messages, newsletter
│   └── Reporting/              # Financial aggregation, paid sales vs net revenue, charts
```

---

## 2. Key Transactional Flows

### Flow A: Checkout & Inventory Reservation
```mermaid
sequenceDiagram
    autonumber
    actor Customer as Customer / Guest
    participant CartSvc as Cart & Pricing Service
    participant Checkout as Checkout Action
    participant DB as MySQL Database
    participant Gateway as Payment Gateway / Adapter

    Customer->>CartSvc: Review Cart & Summary (Subtotal, Tax, Shipping, Coupon)
    Customer->>Checkout: Submit Order (idempotency_key, addresses, payment_method)
    Note over Checkout,DB: BEGIN TRANSACTION (SERIALIZABLE / REPEATABLE READ)
    Checkout->>DB: Check idempotency_key in checkout_idempotencies
    Checkout->>DB: Lock Variants (SELECT ... FOR UPDATE)
    Checkout->>DB: Verify available stock (on_hand - reserved >= requested)
    Checkout->>DB: Reserve Stock (increment reserved_quantity)
    Checkout->>DB: Reserve Coupon Usage
    Checkout->>DB: Create Order, OrderItems, OrderAddresses, Snapshots
    Note over Checkout,DB: COMMIT TRANSACTION
    alt Online Gateway (KHQR / ABA PayWay)
        Checkout->>Gateway: Initiate Payment (Order Reference, Amount, Currency)
        Gateway-->>Customer: Return Payment QR / Deep Link / Form
    else Cash on Delivery (COD) / Bank Transfer
        Checkout-->>Customer: Order Created (Pending Confirmation)
    end
```

### Flow B: Order Fulfillment & Payment Reconciliation
```mermaid
stateDiagram-v2
    [*] --> Pending : Order placed & reserved
    Pending --> Confirmed : Online payment captured OR COD acknowledged
    Pending --> Cancelled : Customer cancel OR payment timeout expired (stock released)
    Confirmed --> Processing : Staff begins picking
    Processing --> Packed : Packed in warehouse
    Packed --> Shipped : Dispatched with carrier/driver
    Shipped --> Delivered : Customer received
    Delivered --> ReturnRequested : Return initiated within policy
    ReturnRequested --> ReturnInspected : Warehouse staff inspects items
    ReturnInspected --> PartiallyRefunded : Succeeded refund for valid items
    ReturnInspected --> FullyRefunded : All items refunded
    Cancelled --> [*]
    Delivered --> [*]
    FullyRefunded --> [*]
```

### Flow C: Loyalty Ledger & Clawback Debt
```mermaid
flowchart TD
    A[Order Delivered & Paid] --> B[Award Points to Ledger]
    B --> C[Create Point Lot with Expiration Date]
    C --> D[Customer Redeems Points for Reward/Coupon]
    D --> E[Deduct from Earliest Unexpired Lots]
    E --> F{Refund Initiated?}
    F -- Yes --> G[Calculate Clawback Points]
    G --> H{Available Points >= Clawback?}
    H -- Yes --> I[Deduct Points from Balance]
    H -- No --> J[Deduct Available Points to Zero]
    J --> K[Record Remaining as Points Debt]
    K --> L[Future Earned Points First Offset Debt]
```

---

## 3. Entity-Relationship Model (Core Entities)

```mermaid
erDiagram
    USERS ||--o{ ORDERS : places
    USERS ||--o{ ADDRESSES : owns
    USERS ||--o| LOYALTY_ACCOUNTS : maintains
    USERS }o--o{ ROLES : assigned

    CATEGORIES ||--o{ CATEGORIES : parent_child
    CATEGORIES }o--o{ ATTRIBUTE_DEFINITIONS : assigns
    CATEGORIES ||--o{ PRODUCTS : categorizes
    BRANDS ||--o{ PRODUCTS : brands

    PRODUCTS ||--o{ PRODUCT_VARIANTS : has
    PRODUCTS ||--o{ PRODUCT_IMAGES : displays
    PRODUCT_VARIANTS ||--o{ INVENTORY_MOVEMENTS : logs
    PRODUCT_VARIANTS ||--o{ CART_ITEMS : contains
    PRODUCT_VARIANTS ||--o{ ORDER_ITEMS : purchased

    ORDERS ||--o{ ORDER_ITEMS : contains
    ORDERS ||--o{ ORDER_ADDRESSES : delivers_to
    ORDERS ||--o{ ORDER_STATUS_HISTORIES : tracks
    ORDERS ||--o{ PAYMENTS : pays_with
    ORDERS ||--o{ SHIPMENTS : fulfills_with
    ORDERS ||--o{ REFUNDS : reconciles

    COUPONS ||--o{ COUPON_USAGES : tracks
    LOYALTY_ACCOUNTS ||--o{ LOYALTY_TRANSACTIONS : records
```

---

## 4. Deployment Topology

- **Web / App Container**: PHP 8.3-FPM + Nginx, running Laravel 11.
- **Frontend Assets**: Precompiled static assets via Vite, served directly by Nginx with gzip/brotli compression.
- **Database**: MySQL 8.0+ with InnoDB storage engine, `utf8mb4_unicode_ci`, strict SQL mode.
- **Queue / Scheduler**: Laravel worker queue process handling background notifications, payment timeout checks, and point lot expiry.
- **Mail Capture (Dev)**: Mailpit running on port 1025 (SMTP) and 8025 (Web UI).
