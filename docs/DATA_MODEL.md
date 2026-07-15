# Data Model

## Relationship overview

```text
users ──1:1── carts ──1:N── cart_items ──N:1── products ──N:1── categories
  │                                         │    └────N:1── partners (jewellery)
  ├──1:N── wishlists ───────────────────────┤
  ├──1:N── reviews ─────────────────────────┤
  ├──1:N── orders ──1:N── order_items ──────┘
  │                 └──1:N── payments
  ├──1:N── loan_requests ──N:1── partners (loan)
  └──1:N── otp_codes

gold_price_histories (time-series observations by carat)
coupons (referenced by code from carts and order snapshots)
```

## Tables

### `users`

Customer/admin identity, account state, role, email OTP verification timestamps, and last login. Passwords use Laravel's hashed cast. The role string currently supports `customer` and `admin`; production should introduce permission-based roles for larger teams.

### `categories`

Storefront grouping for coins, bars, and jewellery. Slugs are stable URL/filter identifiers.

### `partners`

Polymorphic-by-type business directory. `type=jewelry` supplies products; `type=loan` accepts financing introductions. Interest/tenure fields apply to loan profiles. `meta` stores non-sensitive display configuration, not credentials.

### `products`

Gold identity and commercial facts: purity, weight, certification, hallmark reference, pricing mode, base price, making charge, GST, stock, primary image, media gallery, featured state, and visibility.

- `pricing_mode=live`: latest same-carat price per gram × weight + making charge.
- `pricing_mode=fixed`: base price + making charge.
- GST is applied in cart/order calculation, not included in product value.

### `gold_price_histories`

Immutable-ish market observations by carat, price/gram, currency, market change, source, and provider timestamp. The latest row drives product calculations. A production design may add raw response hash, quote ID, region, retail premium, and anomaly state.

### `carts` / `cart_items`

One persistent cart per user. A product appears once per cart with a quantity. Coupon code is revalidated while quoting; no client-provided price is trusted.

### `coupons`

Fixed or percentage discounts with minimum order, optional maximum discount, validity range, and use limit. Usage increments only after verified payment.

### `wishlists`

Unique user/product saved pair.

### `orders`

Purchase financial snapshot, fulfilment state, payment state, coupon, delivery address JSON, and customer notes. Payment and fulfilment status are deliberately separate.

Notable states:

- Fulfilment: `pending`, `confirmed`, `processing`, `shipped`, `delivered`, `cancelled`
- Exception: `payment_review` when a signed capture arrives after an unpaid order was cancelled/released
- Payment: `unpaid`, `paid`

A production state machine should enforce permitted transitions and add refund/return states.

### `order_items`

Product ID plus a purchase-time JSON snapshot. The snapshot retains product name, SKU, purity, weight, certification, and image even if the catalog later changes. Financial fields record unit product value, line tax, quantity, and line total.

### `payments`

One or more attempts per order. Stores provider identifiers, state, amount/currency, limited provider payload, and paid timestamp. Never stores card/UPI credentials. Production should minimize/redact provider payloads and consider a separate processed-webhook table.

### `loan_requests`

Consented financing introduction with selected partner, affordability inputs, estimated EMI, non-underwriting score, state, stated document availability, optional external provider reference/transmission timestamp, and non-sensitive admin notes. It intentionally has no KYC file upload.

### `reviews`

One review per user/product. Only users with a paid order can submit. New reviews require admin/database moderation (`is_approved`) before public display.

### `otp_codes`

Hashed single-use account verification codes, purpose, attempt count, expiry, and verification timestamp. Plain OTP values are never stored.

## Monetary precision

Monetary columns use `DECIMAL(14,2)` and product weight uses `DECIMAL(10,3)`. PHP services currently calculate with floats and round at currency boundaries. For very high-volume or multi-currency production use, migrate calculation internals to integer minor units or an audited money library while retaining decimal database storage.

## Deletion strategy

Foreign keys default to one of:

- cascade for private child data (cart items, OTPs, wishlists),
- restrict for financial ownership records (orders/loan requests), or
- null on delete when a historical record can survive a catalog/partner removal.

Production should generally deactivate products/partners/users instead of deleting records linked to legal, tax, payment, or consent evidence.
