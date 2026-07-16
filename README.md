# N & H Trust — Smart Gold Commerce

A complete Laravel 12 implementation of a gold-commerce storefront, financing-assistance workflow, live market dashboard, secure cart/checkout, customer account area, and operations console.

> **Important:** The included brand, jewellery partner, lender profiles, prices, certification references, reviews, and credentials are demonstration data. No relationship with Tanishq or any other real-world company is claimed. A real company name/logo may be published only after a signed partnership, trademark approval, compliance review, and authorized technical integration.

## Included modules

### Customer experience

- Responsive home and certified gold catalog
- Coins, bars, and jewellery categories
- Product purity, weight, certification, hallmark, partner, stock, and price breakup
- Search, category/purity/weight filters, reviews, wishlist, and product media gallery/video support
- Live-rate dashboard for 22K and 24K, 30-day Chart.js history, daily change, source, timestamp, freshness, and rule-based market signal
- Persistent MySQL cart, quantity controls, current-price recalculation, GST, delivery threshold, coupons, and order summary
- Razorpay and Stripe hosted payment flows with return verification and signed webhooks
- Printable itemized invoice
- Registration, login, email OTP verification, secure sessions, order history, and status tracking
- Financing-assistance page with EMI calculator, provider comparison, eligibility signal, consent, document information, request submission, and status tracking

### Operations console

- Revenue/order/customer/open-loan/low-stock metrics
- 30-day paid-sales chart
- Product CRUD, stock, visibility, pricing mode, certification, and gallery JSON
- Order fulfilment management with payment/fulfilment separation
- Customer account activation/deactivation
- Financing request workflow and customer notification
- CSV order report
- Scheduled authorized gold-price synchronization

### Security and integrity controls

- CSRF on browser writes; only verified payment webhook paths are excluded
- Auth, active-account, OTP, and administrator middleware
- Route-level throttling for login, registration, OTP, loans, checkout, and webhooks
- Hashed OTPs, expiry, attempt limits, and resend limits
- Server-side prices and totals; the browser never supplies a trusted amount
- Product inventory locking within order transactions
- Payment signature verification and idempotent paid-state transition
- Stripe timestamp tolerance and Razorpay HMAC verification
- No card, UPI PIN, CVV, bank credential, or full KYC document is stored
- Order-item snapshots preserve purchase-time evidence
- Seeded rates explicitly identify themselves as `demo-seed-not-live`

## Requirements

- PHP 8.2 or newer (tested with PHP 8.4)
- Composer 2
- MySQL 8+
- Node.js 20+ and npm
- A configured SMTP provider for production OTP/email delivery
- Authorized gold-data, Razorpay, and/or Stripe credentials for real integrations

Required PHP extensions include PDO MySQL, Mbstring, OpenSSL, Ctype, JSON, XML, and cURL.

## Local installation with MySQL

```bash
cp .env.example .env
composer install
php artisan key:generate
```

Create the database and credentials:

```sql
CREATE DATABASE smart_gold CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smart_gold'@'localhost' IDENTIFIED BY 'replace-with-a-long-password';
GRANT ALL PRIVILEGES ON smart_gold.* TO 'smart_gold'@'localhost';
FLUSH PRIVILEGES;
```

Update `DB_*` in `.env`, then run:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`.

### Demonstration accounts

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@aurumtrust.test` | `Admin@12345` |
| Customer | `customer@aurumtrust.test` | `Customer@123` |

Both seeded accounts are pre-verified. **Delete or rotate them before any shared deployment.** New users receive an email OTP. With the default `MAIL_MAILER=log`, the OTP is written to `storage/logs/laravel.log` for local development.

## Authorized gold-price integration

The app never scrapes a retailer or asks staff to type a “live” rate. `App\Services\GoldPriceService` reads a licensed endpoint configured by environment values and writes immutable observations to `gold_price_histories`.

### Explicit demo mode when no provider exists

With `GOLD_PRICE_PROVIDER=database`, the application serves records labelled `demo-seed-not-live`. Refresh a deterministic 365-day demonstration series through the current local date with:

```bash
php artisan gold:refresh-demo-history --days=365
```

The dashboard and JSON endpoint return `mode: demo`, `is_demo: true`, a disclaimer, coverage dates and a `through_today` flag. Demo records are never described as live or authorized. Checkout is blocked by default while demo prices are active. For local payment-flow testing only, set `GOLD_PRICE_ALLOW_DEMO_CHECKOUT=true` in `.env`.

### Licensed provider mode

Expected normalized response (paths are configurable):

```json
{
  "rates": { "22K": 9350.25, "24K": 10200.50 },
  "changes": { "22K": -12.10, "24K": -13.20 },
  "timestamp": "2026-07-11T16:30:00+05:30"
}
```

Provider-neutral configuration:

```dotenv
GOLD_PRICE_PROVIDER=licensed_vendor_name
GOLD_PRICE_API_URL=https://vendor.example/v1/gold/latest
GOLD_PRICE_HISTORY_API_URL=https://vendor.example/v1/gold/history/{date}?currency={currency}
GOLD_PRICE_API_KEY=secret
GOLD_PRICE_API_AUTH_MODE=header
GOLD_PRICE_API_KEY_HEADER=X-API-Key
GOLD_PRICE_API_UNIT=gram
GOLD_PRICE_22K_PATH=rates.22K
GOLD_PRICE_24K_PATH=rates.24K
GOLD_PRICE_22K_CHANGE_PATH=changes.22K
GOLD_PRICE_24K_CHANGE_PATH=changes.24K
GOLD_PRICE_TIMESTAMP_PATH=timestamp
GOLD_PRICE_HISTORY_22K_PATH=rates.22K
GOLD_PRICE_HISTORY_24K_PATH=rates.24K
GOLD_PRICE_HISTORY_TIMESTAMP_PATH=timestamp
GOLD_PRICE_DASHBOARD_POLL_SECONDS=60
```

Authentication modes are `header`, `bearer`, `query`, and `none`. Never commit `GOLD_PRICE_API_KEY`; place it only in `.env` or a production secret manager. For APIs quoted per troy ounce, set `GOLD_PRICE_API_UNIT=troy_ounce`; the adapter converts to grams.

Import genuine history and append the current observation:

```bash
php artisan gold:backfill-prices --days=30
php artisan gold:sync-prices
```

`GOLD_PRICE_HISTORY_API_URL` must contain `{date}`. Response paths can be different for current and historical endpoints. The backfill command requests completed dates oldest-first and updates matching observations rather than creating duplicates.

The scheduler obtains current rates every 15 minutes. The browser polls the local JSON endpoint every 60 seconds; it does not expose the provider key or call the vendor directly. On Windows development, keep this running in a separate terminal:

```powershell
php artisan schedule:work
```

On production Linux, run one scheduler process:

```cron
* * * * * cd /var/www/smart-gold-commerce && php artisan schedule:run >> /dev/null 2>&1
```

The graph filters to one active provider and one closing observation per day, so demo data is never joined to real data. It offers `5D`, `1M`, `1Y`, and `Max` periods plus a 24K/22K switch. The chart uses the Indian market convention of INR per 10 grams; product cards continue to show INR per gram. Hovering a point shows its exact price and weekday/date. If fewer than two genuine points exist, the dashboard shows a collection message instead of drawing a misleading line. The default `database` provider refuses remote synchronization and serves cached/demo records only.

## Payment configuration

### Razorpay

```dotenv
PAYMENT_PROVIDER=razorpay
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
```

Configure the provider webhook to:

```text
POST https://your-domain.example/payments/webhook/razorpay
```

Subscribe to `payment.captured`. The app validates `X-Razorpay-Signature` before changing payment state.

### Stripe

```dotenv
PAYMENT_PROVIDER=stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

Configure the provider webhook to:

```text
POST https://your-domain.example/payments/webhook/stripe
```

Subscribe to `checkout.session.completed`. The app checks Stripe's signed timestamp and rejects signatures older than five minutes.

Both integrations use hosted/overlay provider checkout. Production onboarding still requires merchant KYC, tested refund/dispute flows, webhook replay tests, reconciliation, and approved legal copy.

## Financing-provider integration boundary

A request is stored locally only after explicit consent and is assigned to the selected verified partner. The app deliberately does not collect KYC file uploads. `TransmitLoanRequest` provides a queued, idempotent HTTPS handoff through `ConfiguredHttpLoanConnector`. It activates only when the selected partner slug matches a credentialed connection:

```dotenv
LOAN_PROVIDER_PRIMARY_SLUG=contracted-provider-slug
LOAN_PROVIDER_PRIMARY_ENDPOINT=https://provider.example/v1/applications
LOAN_PROVIDER_PRIMARY_TOKEN=secret
LOAN_PROVIDER_TIMEOUT=10
```

The normalized adapter sends applicant contact details, affordability inputs, stated document *types*, and a consent record—never KYC files. A real partner with a different schema should receive a dedicated implementation of `LoanProviderConnector` rather than forcing its protocol into the generic adapter.

Before production:

1. Contract and verify each regulated provider.
2. Replace demo profiles and remove the word “verified” until compliance approval.
3. Build a provider-specific adapter for consented API transmission.
4. Encrypt any additional sensitive fields and define retention/deletion schedules.
5. Record request/response audit IDs without logging sensitive payloads.
6. Let the provider collect PAN, identity, bank, and income documents through its approved KYC channel.
7. Reconcile status callbacks and customer notices.

The locally calculated score is only an affordability signal. It is not underwriting, approval, or financial advice.

## Background workers

Production should run both:

```bash
php artisan schedule:work
php artisan queue:work --tries=3 --timeout=90
```

The current mail notifications work with a synchronous queue too. Switch to Redis/database queues for production latency and resilience.

## Tests and quality checks

```bash
php artisan test
./vendor/bin/pint --test
npm run build
```

Current automated coverage exercises public pages, cart updates, consented loan requests, cross-customer order authorization, valid Razorpay webhooks, and invalid-signature rejection.

## Key locations

- Routes: `routes/web.php`
- Schema: `database/migrations/2026_01_01_000100_create_commerce_tables.php`
- Seed data: `database/seeders/DatabaseSeeder.php`
- Pricing: `app/Services/GoldPriceService.php`
- Cart/order totals: `app/Services/CartService.php`, `app/Services/OrderService.php`
- Payments: `app/Services/Payments/`, `app/Services/PaymentService.php`
- Storefront views: `resources/views/`
- Responsive UI: `resources/css/app.css`, `resources/js/app.js`
- Production plan: `docs/PRODUCTION_CHECKLIST.md`
- Data model: `docs/DATA_MODEL.md`

## License and media

The Laravel skeleton is MIT-licensed. The generated demonstration product/hero images in `public/images` are project assets; legal and brand teams should approve or replace all media before launch. External promotional video URLs must use an approved CDN and documented usage rights.
