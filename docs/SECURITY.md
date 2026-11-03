# D STORE - Security Architecture & Threat Model

## 1. Authentication & Session Security
- **Passwords**: Argon2id or Bcrypt with work factor >= 12 via Laravel `Hash`.
- **Sessions**: Database-backed sessions with HTTP-only, SameSite=Lax, and Secure cookie attributes. Regenerated upon login to prevent session fixation.
- **Staff Access**: Separate `/admin` authentication gate with active staff checks (`is_active = true`), password policy enforcement, and role permissions.
- **Guest Access**: Guest order tracking uses 64-character cryptographically secure tokens (`bin2hex(random_bytes(32))`), never predictable sequential order IDs.

## 2. Authorization & RBAC
- Granular Laravel Policies on all resources (`ProductPolicy`, `OrderPolicy`, `RefundPolicy`, etc.).
- Policies check specific permissions (`orders.view`, `orders.process`, `refunds.create`, `catalog.edit`).
- Customer access strictly verifies resource ownership (`order->user_id === auth()->id()`).

## 3. Financial & Concurrency Invariants
- Pessimistic locking (`lockForUpdate()`) inside database transactions during checkout and stock reservations.
- Monetary calculations handled server-side in integer cents or `bcmath` strings; client totals are discarded.
- Checkout idempotency enforced by database unique constraint on `(customer_scope, idempotency_key)` with SHA-256 payload verification.

## 4. Input Validation & Data Sanitization
- Form Requests with typed validation rules for every endpoint.
- CSV imports sanitized against spreadsheet formula injection (leading `=`, `+`, `-`, `@` characters quoted).
- Content pages sanitize rich text using an allowlist (no raw script tags).
- SVG uploads forbidden or sanitized via XML parser before storage.

## 5. Rate Limiting
- Login: 5 attempts per minute per IP / email.
- Checkout: 10 attempts per minute per IP / user.
- Guest Order Lookup: 10 attempts per minute per IP.
- Contact Form: 3 submissions per minute per IP.
- Product Search: 60 requests per minute per IP.
