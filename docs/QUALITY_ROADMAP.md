# Quality Roadmap

The project is being stabilized in stages. A stage is complete only when formatting, automated tests, the Vite build, dependency validation, and both mirrored Git branches pass.

## Stage 1 — Gold data integrity (completed)

- Provider-neutral latest and historical adapters
- TLS verification, configurable authentication, timeouts and retries
- Daily history de-duplication and source isolation
- 5D, 1M, 1Y and Max API ranges
- Server-side market-signal algorithm
- API mode metadata: `live`, `demo`, or `unavailable`
- Coverage metadata including `through_today`
- Clearly labelled deterministic demo history through the current date
- Demo checkout blocked unless explicitly enabled for local testing
- Real provider synchronization every 15 minutes
- Local demo freshness checked hourly, adding the current date after midnight
- No provider credentials exposed to the browser

A licensed provider is still required for real market values. Demo values must never be marketed as live or tradable.

## Stage 2 — Design system and accessibility (next)

- Consolidate typography, spacing, color and elevation tokens
- Standardize buttons, inputs, selects, textareas, validation and disabled/loading states
- Audit focus indicators, keyboard order, labels, contrast and reduced motion
- Normalize responsive headers, cards, tables, dialogs, empty states and alerts
- Remove remaining inline presentation styles from Blade templates
- Test desktop, tablet and phone layouts

## Stage 3 — Commerce correctness

- Formal order/payment/refund state machine
- Reservation expiry and inventory release worker
- Coupon concurrency and tax-policy review
- Address validation and fulfilment events
- Payment amount/currency reconciliation and webhook event ledger
- Production invoice legal/tax fields

## Stage 4 — Admin and operations

- Granular staff permissions and administrator MFA
- Immutable audit log
- Product, partner, review and price-feed operations
- Export permissions and sensitive-data controls
- Queue, scheduler, mail and webhook monitoring

## Stage 5 — Production deployment

- Final provider contracts and credentials in a secret manager
- HTTPS, secure cookies, CSP, trusted proxies/hosts and WAF
- Managed MySQL/Redis, backups and restore test
- Nginx/Apache and queue/scheduler service configuration
- Load, accessibility and penetration testing
- Legal/compliance and launch sign-off
