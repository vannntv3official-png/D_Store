# D STORE - Requirements Traceability Matrix

| Req ID | Domain / Feature | Specification | Route / Component | Implementation Service / Model | Test Suite | Status |
|---|---|---|---|---|---|---|
| REQ-001 | Identity | Customer Registration with Phone & Email | `/register` | `RegisteredUserController`, `User` | `CustomerFlowTest`, `LoyaltyTest` | **Verified** |
| REQ-002 | Identity | Customer Login & Session Invalidation | `/login`, `/logout` | `AuthenticatedSessionController` | `CustomerFlowTest` | **Verified** |
| REQ-003 | Identity | Guest Scoped Order Token Access | `/guest/orders/{token}` | `OrderTrackingController`, `Order` | `OrderTransitionTest`, `CustomerFlowTest` | **Verified** |
| REQ-004 | RBAC | 6 Distinct Staff Roles & Permission Matrix | `/admin/*` | `Role`, `Permission`, `RequirePermission`, `EnsureStaffActive` | `AdminFlowTest` | **Verified** |
| REQ-005 | Catalog | 11 Core Seeded Categories with Cycle Prevention | `/shop`, `/admin/categories` | `Category`, `CategoryAdminController` | `AdminFlowTest`, `CustomerFlowTest` | **Verified** |
| REQ-006 | Catalog | Category Dynamic Typed Attributes (EAV/JSON) | Product Detail, Filters | `AttributeDefinition`, `AttributeOption`, `ProductVariantAttribute` | `CustomerFlowTest` | **Verified** |
| REQ-007 | Catalog | Multi-variant Management & Unique SKUs | `/products/{slug}`, `/admin/products` | `Product`, `ProductVariant`, `ProductAdminController` | `AdminFlowTest`, `CustomerFlowTest` | **Verified** |
| REQ-008 | Catalog | Safe CSV Import/Export & Formula Sanitization | `/admin/products/export/csv` | `ProductAdminController` | `AdminFlowTest` | **Verified** |
| REQ-009 | Pricing | Minor-unit / BCMath Pricing Pipeline | Server-side pipeline | `PricingPipelineService`, `Money` | `MoneyTest` | **Verified** |
| REQ-010 | Pricing | Multi-currency (USD base, KHR display/settle at 4,100 rate) | Storewide headers/summary | `SetLocaleAndCurrency`, `Money::toKhr` | `MoneyTest`, `CustomerFlowTest` | **Verified** |
| REQ-011 | Pricing | Coupon Reservation, Validation & Usage Limits | `/cart/coupon`, `/admin/marketing/coupons` | `Coupon`, `PricingPipelineService` | `AdminFlowTest`, `CheckoutConcurrencyTest` | **Verified** |
| REQ-012 | Cart | Guest & Authenticated Cart Merge | `/cart` | `CartController`, `Cart`, `CartItem` | `CustomerFlowTest` | **Verified** |
| REQ-013 | Checkout | Idempotent Order Creation with Request Hashing | `/checkout` | `CheckoutController`, `CheckoutIdempotency` | `CheckoutConcurrencyTest` | **Verified** |
| REQ-014 | Inventory | Append-only Movements & Available Stock Formula | `/admin/inventory` | `InventoryService`, `InventoryMovement` | `AdminFlowTest`, `ReservationExpiryTest` | **Verified** |
| REQ-015 | Inventory | Atomic Checkout Reservation & Expiry Release | `/checkout`, `orders:expire-reservations` | `InventoryService`, `ExpireReservationsCommand` | `ReservationExpiryTest`, `ConsoleCommandsTest` | **Verified** |
| REQ-016 | Orders | Strict Fulfillment State Machine Transitions | `/admin/orders/{id}` | `OrderStateMachineService`, `OrderAdminController` | `OrderTransitionTest`, `AdminFlowTest` | **Verified** |
| REQ-017 | Payments | Payment Gateway Interface & Sandbox Adapters | `/checkout`, `/orders/{id}/confirmed` | `PaymentGatewayInterface`, `BakongKhqrGateway`, `AbaPaywayGateway` | `WebhookTest`, `CheckoutConcurrencyTest` | **Verified** |
| REQ-018 | Payments | Webhook Deduplication & Cryptographic Verification | `/webhook/payment/{gateway}` | `KhqrWebhookController`, `AbaPaywayWebhookController` | `WebhookTest` | **Verified** |
| REQ-019 | Refunds | Partial/Full Allocation & Return Inspection Restock | `/admin/refunds` | `RefundService`, `RefundAdminController` | `RefundTest`, `AdminFlowTest` | **Verified** |
| REQ-020 | Shipping | Cambodia Province/District Zones & Calculation | `/checkout`, `/account/addresses` | `ShippingZone`, `Address`, `AccountController` | `CustomerAccountFlowTest` | **Verified** |
| REQ-021 | Loyalty | Signed Points Ledger & Expiry Lots | `/account/loyalty`, `loyalty:expire-lots` | `LoyaltyService`, `PointLot`, `ExpireLoyaltyLotsCommand` | `LoyaltyTest`, `ConsoleCommandsTest` | **Verified** |
| REQ-022 | Loyalty | Spent Points Debt on Return/Clawback | Return processing | `LoyaltyService`, `LoyaltyAccount` | `LoyaltyTest`, `CustomerAccountFlowTest` | **Verified** |
| REQ-023 | Reviews | Verified Purchase Review Moderation & Rating | `/product/{slug}`, `ProductController` | `Review`, `ProductController` | `CustomerFlowTest` | **Verified** |
| REQ-024 | Admin | Reconciled Dashboard Metrics & 30-Day Sales Chart | `/admin` | `AdminDashboardController` | `AdminFlowTest` | **Verified** |
| REQ-025 | Content | CMS Pages (About, FAQ, Terms) & Throttled Contact | `/page/{slug}`, `/contact` | `ContentController`, `ContentPage`, `ContactMessage` | `CustomerAccountFlowTest` | **Verified** |
| REQ-026 | Design | Apple-inspired Minimalist UI (Khmer & English) | Global Layouts & Pages | `CustomerLayout.vue`, `AdminLayout.vue`, `Button`, `Dialog` | `npm run typecheck`, `npm run build` | **Verified** |
| REQ-027 | Security | Mass Assignment, RBAC, XSS, Strict Types | Global Middleware & Form Requests | `SetLocaleAndCurrency`, `EnsureStaffActive`, `RequirePermission` | `AdminFlowTest`, `CustomerFlowTest` | **Verified** |
