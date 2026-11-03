# D STORE - Security Architecture & Threat Model

## 1. Authentication & Session Security
- **Passwords**: Bcrypt with work factor >= 12 via Laravel `Hash`.
- **Sessions**: Database-backed sessions with HTTP-only, SameSite=Lax, and Secure cookie attributes. Regenerated upon login (`$request->session()->regenerate()`) to prevent session fixation.
- **Staff Access**: Separate `/admin` authentication gate with active staff checks (`is_active = true`), dual-identifier authentication (email or phone), password policy enforcement, and role permissions.
- **Guest Access**: Guest order tracking uses 64-character cryptographically secure tokens (`bin2hex(random_bytes(32))`), never predictable sequential order IDs.

## 2. Authorization & Granular RBAC
- Granular permission middleware (`permission:<name>`) enforced on all admin sub-routes:
  - `catalog.view`, `catalog.create`, `catalog.edit`, `catalog.delete`
  - `orders.view`, `orders.process`
  - `refunds.view`, `refunds.create`, `refunds.approve`
  - `inventory.view`, `inventory.adjust`
  - `marketing.manage`, `loyalty.manage`
  - `staff.manage`, `settings.manage`
- Super Administrators bypass permission checks automatically.
- Customer access strictly verifies resource ownership (`$order->user_id === auth()->id()`).
- IDOR defense on `/orders/{orderNumber}/confirmed`: only order owners, authorized staff, or parties holding the unguessable guest token (via session or verified query param) can view order confirmation details.

## 3. Financial & Concurrency Invariants
- Pessimistic locking (`lockForUpdate()`) inside database transactions during checkout and stock reservations.
- Monetary calculations handled strictly server-side via `Money.php` (BCMath string math); client-submitted totals are ignored.
- Checkout idempotency enforced by database unique constraint on `(customer_scope, idempotency_key)` with SHA-256 payload verification.
- Over-refund prevention: `RefundService` locks the order and existing refunds, calculating cumulative sums to guarantee refunds never exceed the original captured grand total.

## 4. Defensive HTTP Security Headers
Applied globally via `SecurityHeaders` middleware on all web responses:
- `X-Frame-Options: SAMEORIGIN` (prevents clickjacking)
- `X-Content-Type-Options: nosniff` (prevents MIME sniffing attacks)
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`

## 5. Input Validation & Data Sanitization
- Form Requests with typed validation rules for every customer and admin endpoint.
- CSV imports/exports sanitized against spreadsheet formula injection: leading `=`, `+`, `-`, `@`, `\t`, `\r`, `|` characters prepended with `'` to neutralize executable macros in Excel and Google Sheets.
- Rich text content pages escape script tags; SVG uploads forbidden without XML sanitization.

## 6. Rate Limiting (Throttle Middleware)
Configured in `AppServiceProvider` and enforced on routes:
- **Customer & Admin Login**: 5 attempts per minute per IP + login identifier (`throttle:login`).
- **Customer Registration**: 5 attempts per minute per IP (`throttle:register`).
- **Checkout Processing**: 10 attempts per minute per user/IP (`throttle:checkout`).
- **Contact Concierge & Newsletter**: 3 submissions per minute per IP (`throttle:contact`).
- **Guest Order Tracking**: 10 attempts per minute per IP (`throttle:guest_orders`).

## 7. Automated Security Verification
Verified via `tests/Feature/SecurityTest.php` (7 tests, 42 assertions):
- Global security headers presence
- Brute-force rate limiting returns HTTP 429
- IDOR customer order isolation (HTTP 404 on mismatched ownership)
- Order confirmation unauthorized access blocking (HTTP 403)
- Staff RBAC least privilege enforcement (HTTP 403 on unpermitted routes)
- Deactivated staff session invalidation and logout
- CSV formula injection sanitization matrix
