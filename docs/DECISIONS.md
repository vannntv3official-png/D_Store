# D STORE - Architectural Decisions Record (ADR)

## ADR-001: Technology Stack Selection
- **Status**: Accepted
- **Decision**: Laravel 11.x on PHP 8.3 with Vue 3 (Inertia.js v2), Tailwind CSS, Vite, and strict TypeScript.
- **Rationale**: Provides server-driven state security (totals, stock, authorization, validation) paired with a reactive Single Page Application feel. Avoids API duplication while maintaining monolithic simplicity and strict type safety across props and components.

## ADR-002: Currency & Money Handling
- **Status**: Accepted
- **Decision**: 
  - Base currency for all catalog pricing, cart subtotals, tax, shipping, and accounting is **USD**.
  - Persisted as `DECIMAL(12, 4)` for unit prices, tax rates, and calculations, and `DECIMAL(12, 2)` for finalized line subtotals and order totals.
  - Calculations use a dedicated `Money` value object backed by `bcmath` or integer cents. Floating-point arithmetic is strictly prohibited for authoritative monetary state.
  - **KHR (Cambodian Riel)** is a display and settlement option. The USD/KHR exchange rate is persisted in settings (e.g. 4,100 KHR per USD) and snapshotted on orders. Display rounding rounds to the nearest 100 KHR.

## ADR-003: Store Timezone & Timestamps
- **Status**: Accepted
- **Decision**: All database timestamps (`created_at`, `updated_at`, `expires_at`, `scheduled_at`) are persisted in UTC. The display timezone is `Asia/Phnom_Penh` (UTC+7).

## ADR-004: Dual Locales (Khmer & English)
- **Status**: Accepted
- **Decision**: Default locale is `km` (Khmer), secondary is `en` (English). The UI uses translation keys (`__('shop.checkout')`). Dynamic catalog attributes and entity names/descriptions store localized values in JSON columns (`{"en": "...", "km": "..."}`) with English fallback when Khmer is missing.

## ADR-005: Concurrency, Inventory Reservations & Checkout Idempotency
- **Status**: Accepted
- **Decision**:
  - Available stock = `on_hand - reserved`.
  - Adding items to cart does not reserve stock.
  - Order submission atomically locks variant rows (`SELECT ... FOR UPDATE`), verifies available quantity, reserves required units, reserves coupon quota, and commits in a single transaction.
  - Reservations have a configurable TTL (default: 30 minutes for online payment methods; COD orders use an admin confirmation deadline).
  - Checkouts require an `idempotency_key` (UUIDv4) and SHA-256 payload hash stored in `checkout_idempotencies` table. Repeated requests with the identical key and hash return the existing order; conflicting hashes return HTTP 409.

## ADR-006: Loyalty Point Ledger & Clawback Debt
- **Status**: Accepted
- **Decision**:
  - Loyalty points are tracked in an append-only ledger (`loyalty_transactions`).
  - Account balances are computed/projected; spendable balance can never be negative.
  - If an order is refunded after points were already redeemed, points are clawed back. If balance is insufficient, clawback records a distinct `points_debt`. Future earned points automatically clear debt before becoming spendable.

## ADR-007: Payment Adapters & Webhooks
- **Status**: Accepted
- **Decision**:
  - Payment operations implement `PaymentGatewayInterface` (initiate, verify, handleWebhook, refund).
  - Out of the box: Cash on Delivery (`cod`), Manual Bank Transfer (`bank_transfer`), and official KHQR/ABA PayWay Sandbox Adapters (`khqr_sandbox`, `aba_payway_sandbox`).
  - Webhooks require raw payload signature validation, idempotent processing (persisted event logs), and state-guarded order transitions.
