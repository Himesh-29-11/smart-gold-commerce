# Production Integration & Launch Checklist

This application is structurally ready for credentialed integrations, but no gold-commerce platform is production-ready merely because the code runs. Complete and sign off every applicable item below.

## 1. Legal, brand, and partner onboarding

- [ ] Incorporate the operating/selling entity and publish its legal name, registered address, tax details, support contacts, terms, privacy policy, refund/cancellation policy, shipping policy, grievance process, and risk disclosure.
- [ ] Obtain written trademark/media approval before displaying Tanishq or any other real partner name, logo, catalog, certificate, price, or video.
- [ ] Execute jewellery partner agreements covering inventory ownership, purity evidence, hallmark/HUID, packing, transit risk, returns, buyback statements, settlement, disputes, and data processing.
- [ ] Verify lender regulatory status and execute referral/data-processing agreements.
- [ ] Review the financing UX for applicable RBI digital-lending, advertising, consent, disclosure, and grievance requirements with qualified counsel.
- [ ] Prevent staff from describing N & H Trust as a lender unless the operating entity is separately authorized to lend.
- [ ] Add seller GSTIN, HSN, place of supply, tax breakup, and legally required invoice fields.

## 2. Gold pricing and product evidence

- [ ] License an authorized market-data API with contractual rights to display and use rates for checkout.
- [ ] Map the vendor response to the configurable paths in `config/gold.php`.
- [ ] Define whether checkout uses spot, partner retail, city-specific, tax-inclusive, or locked rates.
- [ ] Define quote validity and show a countdown if a price is locked.
- [ ] Alert operations when rates are stale, missing, anomalous, or outside configured bounds.
- [ ] Add circuit-breaker behavior so stale/invalid prices prevent checkout instead of silently falling back in production.
- [ ] Retain source, timestamp, unit, currency, raw-response audit hash, and applicable vendor quote ID.
- [ ] Verify every product's gross/net weight, purity, hallmark/HUID, certificate, making charge, stone value, wastage, and return eligibility.
- [ ] Obtain approved high-resolution images/videos and serve them from a controlled CDN with optimized variants.

## 3. Payments and money operations

- [ ] Complete Razorpay/Stripe merchant KYC and use live credentials only in a managed secret store.
- [ ] Force HTTPS and secure cookies; never expose secrets in JS, logs, source control, or client errors.
- [ ] Test success, decline, cancellation, timeout, duplicate callback, replay, webhook delay, malformed signature, and out-of-order event scenarios.
- [ ] Add a formal refund workflow. Paid orders are intentionally blocked from direct cancellation in the current admin UI.
- [ ] Add daily provider settlement/reconciliation and alerts for amount/currency/reference mismatches.
- [ ] Add webhook event IDs and a processed-event table if provider event volume grows; current payment transitions are idempotent by payment row.
- [ ] Define pending-order reservation expiry, release stock safely, and handle payments captured after reservation expiry.
- [ ] Do not mark an order paid from a browser success URL alone; retain signed webhook/API verification.
- [ ] Complete PCI SAQ scope review even though hosted checkout is used.

## 4. Inventory, fulfilment, and insurance

- [ ] Replace demo stock with partner/warehouse inventory synchronization.
- [ ] Define reservation duration, concurrency rules, safety stock, serial/hallmark allocation, and cancellation release.
- [ ] Integrate insured high-value logistics, identity checks, tamper-evident packing, proof of delivery, and delivery exception handling.
- [ ] Record immutable fulfilment events and restrict status transitions by role.
- [ ] Add customer notifications for confirmed, packed, shipped, delivered, cancelled, refunded, and exception states.
- [ ] Define returns, purity verification, payout/refund, and chain-of-custody procedures.

## 5. Financing connections

- [ ] Remove all demo lenders and publish only approved, active providers.
- [ ] Implement one adapter per provider with OAuth/API keys in a secret manager.
- [ ] Share only fields covered by explicit, purpose-specific consent.
- [ ] Add consent version, exact disclosure, timestamp, IP, partner, purpose, and revocation/retention records.
- [ ] Never place PAN, Aadhaar, bank statements, or income documents in general application logs.
- [ ] Use provider-hosted KYC or a separately assessed encrypted document vault.
- [ ] Authenticate status callbacks and map provider states to internal states.
- [ ] Make APR, fees, penalties, key-fact statements, and final lender identity available before acceptance where required.
- [ ] Treat the local score as a UX estimate only; do not use it as an automated credit decision without legal, fairness, and model-risk review.

## 6. Security hardening

- [ ] Deploy behind a managed WAF/reverse proxy with TLS 1.2+, HSTS, request limits, bot controls, and DDoS protection.
- [ ] Configure trusted proxies/hosts and canonical HTTPS URLs.
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, and an appropriate cookie domain/SameSite policy.
- [ ] Keep `APP_KEY`, DB, SMTP, provider, webhook, and API secrets in a cloud secret manager; establish rotation.
- [ ] Use least-privilege DB/application accounts and deny public access to MySQL/Redis.
- [ ] Add content security policy, permissions policy, frame restrictions, and strict referrer policy; explicitly allow only required gateway/CDN origins.
- [ ] Add MFA for administrators. Email OTP account verification is not sufficient admin MFA.
- [ ] Add granular admin roles and audit logs for products, rates, customers, orders, loan status, exports, and login activity.
- [ ] Add password reset, compromised-password screening, session/device management, and suspicious-login controls.
- [ ] Encrypt sensitive columns as the data model expands.
- [ ] Run dependency, SAST, secret, container, and infrastructure scans in CI.
- [ ] Commission penetration testing before live payments and after material changes.

## 7. Privacy and retention

- [ ] Publish a purpose/field/recipient/retention matrix.
- [ ] Minimize collection; verify that every form field has a documented business purpose.
- [ ] Add data access, correction, portability, consent withdrawal, and deletion workflows where legally applicable.
- [ ] Restrict CSV exports, watermark or encrypt them, expire generated files, and log access.
- [ ] Redact email, phone, addresses, PAN-like values, credentials, OTPs, and provider payloads from logs/APM.
- [ ] Set retention schedules for OTPs, abandoned carts, failed payments, loan requests, sessions, audit events, and backups.
- [ ] Execute data-processing agreements with cloud, email, analytics, payment, lender, and support vendors.

## 8. Reliability and operations

- [ ] Use managed MySQL with point-in-time recovery, encrypted backups, tested restore procedures, and replication as required.
- [ ] Use Redis for cache/queues/rate limiting where appropriate.
- [ ] Run redundant queue workers and one coordinated scheduler (`onOneServer`).
- [ ] Monitor checkout conversion, HTTP errors, queue failures, payment mismatches, stale rates, low stock, mail failures, webhook failures, and latency.
- [ ] Add health/readiness probes that cover critical dependencies without leaking secrets.
- [ ] Define SLOs, paging thresholds, incident roles, status communication, and provider escalation contacts.
- [ ] Test disaster recovery, key rotation, provider outage, bad-price, data breach, and oversell runbooks.

## 9. Performance and accessibility

- [ ] Move media to an image/video CDN; generate AVIF/WebP, responsive sizes, posters, and lazy loading.
- [ ] Cache public catalog reads while invalidating safely after inventory/rate updates.
- [ ] Load-test catalog, cart locking, checkout creation, webhook bursts, exports, and gold-rate sync.
- [ ] Run WCAG 2.2 AA review for keyboard flow, focus, labels, errors, contrast, charts, motion, and screen readers.
- [ ] Test current Chrome, Safari, Firefox, Edge, Android, and iOS at representative network speeds.
- [ ] Add analytics only after consent/privacy review; never send financial or KYC form values to analytics.

## 10. Release gate

Launch only after engineering, security, legal/compliance, finance, operations, customer support, partner management, and executive owners have signed the production release record. Preserve test evidence, integration approvals, pricing rules, and rollback instructions with that record.
