# D STORE - Payment Architecture & Integration Guide

## 1. Gateway Abstraction (`PaymentGatewayInterface`)
Every payment provider implements the unified gateway contract:
```php
interface PaymentGatewayInterface {
    public function initiatePayment(Order $order, array $options = []): PaymentInitiationResult;
    public function verifyPayment(PaymentTransaction $transaction): PaymentVerificationResult;
    public function handleWebhook(Request $request): PaymentWebhookResult;
    public function refund(PaymentTransaction $transaction, Money $amount, string $reason): RefundResult;
    public function supportsRefunds(): bool;
}
```

## 2. Supported Adapters

### A. Cash On Delivery (`cod`)
- Order state: Pending -> Confirmed -> Shipped.
- Payment marked as `collected` only upon courier confirmation in the admin portal.

### B. Manual Bank Transfer (`bank_transfer`)
- Provides merchant bank instructions (e.g. ABA Bank / Wing account numbers).
- Customer uploads transfer slip screenshot (supporting evidence).
- Staff independently reconciles receipt in bank statement before marking `paid`.

### C. KHQR Provider Sandbox Adapter (`khqr_sandbox`)
- Compliant with National Bank of Cambodia (NBC) Bakong KHQR specifications (EMVCo QR standard for KHR and USD).
- Generates dynamic QR payload containing merchant ID, currency (USD: 840, KHR: 116), amount, and 32-character transaction reference.
- Sandbox simulates instant push webhooks and query status verification.
- Production requires merchant onboarding via an acquiring bank (e.g. ABA, Canadia, Sathapana).

### D. ABA PayWay Sandbox Adapter (`aba_payway_sandbox`)
- Implements PayWay API v2 signature hashing using HMAC-SHA512.
- Handles checkout modal / deep-link generation and server-to-server push notifications.
- Excluded from production execution unless legitimate API keys and merchant IDs are configured.

## 3. Webhook Handling & Deduplication
- Webhooks received at `/webhook/payment/{gateway}` are exempted from CSRF.
- Raw payload signatures are validated against gateway public keys or shared HMAC secrets.
- Webhook events are logged in `payment_webhook_events` with unique constraint on `(gateway, event_id)` to reject duplicate delivery.
- Browser redirects do **not** mark payments as captured; payment state transitions exclusively on validated webhooks or backend query verification.
