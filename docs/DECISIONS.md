# ETMS Architecture and Business Decisions

This file records approved decisions. New decisions or changes must be added with a date and rationale.

## Original architecture and business decisions

| ID | Decision | Approved position |
|---|---|---|
| D-001 | Customer market | Both individuals and companies |
| D-002 | Customer workflow | One standard customer role, registration and checkout workflow |
| D-003 | Corporate functionality | No separate corporate portal, organisation account administration, employee management or employee assignment in the initial release |
| D-004 | System roles | Administrator, Instructor, Trainee/Customer |
| D-005 | Delivery models | Physical training sessions and independent module purchases |
| D-006 | Module access | Lifetime access after successful purchase |
| D-007 | Certificate eligibility | Verified physical attendance and administrator approval |
| D-008 | Payment provider | Billplz proposed, subject to merchant onboarding and commercial verification |
| D-009 | Architecture style | Modular monolith |
| D-010 | Backend | Laravel / PHP |
| D-011 | Frontend | Blade + Livewire |
| D-012 | Admin interface | Filament |
| D-013 | Database | MySQL |
| D-014 | Video storage | Bunny Stream proposed |
| D-015 | Document storage | Cloudflare R2 proposed |
| D-016 | Production hosting | Ubuntu VPS + Nginx proposed |

## Confirmed Stage 1 business decisions — 17 November 2026

The following decisions were explicitly confirmed by the project owner and supersede the corresponding unresolved questions in the Stage 1 database design.

| ID | Decision | Confirmed position |
|---|---|---|
| D-017 | Session recording eligibility | `attendees_only`: access is limited to attendees whose attendance is recorded as present and verified. |
| D-018 | Physical session and module access | Strictly separate. A physical session purchase or attendance does not automatically grant a module entitlement. |
| D-019 | Reservation hold duration | 15 minutes. Store as configurable system setting with a default of 15 minutes. |
| D-020 | Payment methods at launch | Billplz only. Manual bank transfer is excluded from the initial launch scope. |
| D-021 | Cancellation and refunds | Admin-controlled initially. Customer self-cancellation is not part of the initial release. Refund decisions and processing are administered and audited. |
| D-022 | Multi-day session structure | A single continuous `starts_at` / `ends_at` date-time range is sufficient for the initial release. No `session_days` table. |
| D-023 | Company purchase for another attendee | Not supported initially. The authenticated customer account is the named trainee and each booking is for that account holder. |
| D-024 | Module category cardinality | One primary category per module via `modules.category_id`. |
| D-025 | Booking and attendance lifecycle | Separate. Booking status represents commercial/seat allocation; attendance status records physical participation. |
| D-026 | RBAC and staff purchasing | Use the proposed native role/permission tables and Laravel policies. An Administrator or Instructor may also hold the Customer role when purchasing training. |
| D-027 | Instructor profiles and certificate signatory | Public instructor profile information is required. Certificate signatory details are managed globally through system settings and snapshotted onto each issued certificate. |
| D-028 | Tax and identity data | Data minimisation. Do not collect NRIC/passport numbers by default. Do not add tax/e-Invoice fields until the business confirms a concrete requirement. |
| D-029 | Registration deadline | Registration closes before the session starts. Walk-in registration is not part of the standard workflow. |
| D-030 | Checkout composition | Strictly separate order types. A single order cannot mix physical session bookings and independent module purchases. |

### Implementation implications

- D-017 requires the session recording access policy to default to `attendees_only`; access must check both `attendance_status = 'present'` and `review_status = 'verified'`.
- D-019 requires the default setting `booking.reservation_hold_minutes = 15`; the value remains configurable by an authorised administrator.
- D-020 removes manual bank transfer from the initial payment workflow. The schema should not carry launch-critical manual-transfer states or UI requirements unless retained explicitly as a future extension.
- D-021 leaves refund amounts, eligibility cut-offs, and operational handling under administrator control. Those detailed policy parameters remain to be specified before refund implementation.
- D-026 permits multiple roles per user, including a staff role together with `customer`. Authorisation must be permission-based and avoid accidental privilege escalation.
- D-027 requires an instructor profile data structure and global signatory settings. Certificate signatory values must be copied into immutable certificate snapshots at issuance.
- D-028 leaves tax/e-Invoice requirements subject to later confirmation; personal identity numbers are excluded from the MVP data model.
- D-030 requires the order header and all its line items to be consistent. This invariant must be enforced reliably, not merely described.

## Technical integrity decisions — 17 November 2026

The following technical design decisions were added during the Stage 1 database correction and integrity review to enforce the confirmed business rules reliably in MySQL 8.4 and Laravel:

| ID | Decision | Approved technical position & rationale |
|---|---|---|
| D-031 | Order & order-item type consistency | Add `order_items.order_type` referencing `orders (id, order_type)` via a composite foreign key (`fk_order_items_order_type`) paired with `chk_order_items_type_alignment`, so MySQL 8.4 physically rejects mismatched or mixed item types under any order. |
| D-032 | Cross-record customer ownership integrity | Enforce composite foreign keys across `bookings`, `payments`, `refunds`, and purchased `module_entitlements` referencing `orders (id, user_id)`, `payments (id, order_id, user_id)`, and `bookings (id, order_id, user_id)` so child records can never reference an order or payment owned by a different customer. |
| D-033 | Authoritative seat occupancy & counter reconciliation | Active `bookings` rows (`pending_payment` and `confirmed`) are the authoritative source of seat occupancy. `training_sessions.reserved_seats_count` and `confirmed_seats_count` are recomputed directly from `bookings` inside `SELECT ... FOR UPDATE` transactions (preventing double-decrement drift on `confirmed -> cancelled -> refunded`) and verified by a scheduled reconciliation command (`etms:reconcile-session-seats`). |
| D-034 | Certificate approval & async PDF generation tracking | `certificates` requires non-null `approved_by` and `approved_at` at creation and separates official certificate status (`status`: `pending`, `issued`, `revoked`) from asynchronous PDF rendering status (`pdf_generation_status`: `queued`, `generating`, `completed`, `failed`), supporting idempotent retries without duplicating certificate references. |
| D-035 | Verified webhook idempotency namespace | `payment_webhook_events.idempotency_key` is nullable and unique only for HMAC-verified primary callback events. Invalid-signature and duplicate audit rows use a separate non-unique request fingerprint, so audit retention cannot block a later legitimate callback. |

## Stage 2 architecture decisions — 17 November 2026

| ID | Decision | Approved position & rationale |
|---|---|---|
| D-036 | Containerisation architecture | Use Docker and Docker Compose v2 for a consistent development and production deployment environment encapsulating the Laravel 13 / PHP 8.3+-FPM runtime (`app`), Nginx (`nginx`), MySQL 8.4 (`mysql`), Redis (`redis`), queue worker (`queue`), and scheduler (`scheduler`) containers with persistent volumes for database data, Redis persistence, and required application storage. External services (Billplz, Bunny Stream, Cloudflare R2, and transactional email) remain external managed integrations. |

## Frontend Modernisation Architecture Decisions — 19 November 2026 (v1.2)

The following decisions record the approved controlled frontend modernisation strategy documented in [ETMS SRS & System Architecture v1.2](ETMS-SRS-and-System-Architecture-v1.2.md) without altering the underlying v1.1 business rules (`D-001`–`D-035`) or container topology (`D-036`).

| ID | Decision | Approved position & rationale |
|---|---|---|
| D-037 | Incremental frontend modernisation to Vue 3 + TypeScript + Inertia.js + shadcn-vue | Incrementally supersede `D-011` (Blade + Livewire) across Public, Authentication, Trainee Portal, and Instructor Portal views using Vue 3, TypeScript, Inertia.js (`@inertiajs/vue3` + `inertiajs/inertia-laravel`), and customised `shadcn-vue` (`reka-ui`) inside the existing Laravel 13 modular monolith (`D-009`). Do not introduce a separate frontend repository, separate Node.js runtime server, or client-side rewrites of backend validation, pricing, booking, payment, or entitlement rules. |
| D-038 | Filament 5 Admin Panel remains permanently separate | Preserve Filament 5 at `/admin` permanently (`D-012`). All 17 administrative resources, relation managers, bulk actions, charts, `ReportsPage`, and TOTP MFA gates remain server-rendered via Filament/Livewire without Vue duplication. |
| D-039 | Dual-runtime Blade/Livewire + Vue/Inertia coexistence | Operate migrated Inertia routes (`resources/views/app.blade.php` + `resources/js/inertia.ts`) alongside unmigrated Blade/Livewire routes (`resources/js/app.js` untouched with `vendor-hls` isolated by Vite 8) under shared Laravel sessions, CSRF protection, and `resources/css/app.css` Tailwind CSS 4 tokens. Cross-boundary links between Inertia and Blade/Livewire/Filament use native HTML `<a href>` transitions; route URLs are shared explicitly via `HandleInertiaRequests` without adding extra routing packages. |
| D-040 | Minimal backend dependency scope (`inertiajs/inertia-laravel` only) | Install only `inertiajs/inertia-laravel` (`^3.0`) on Composer as the strict requirement for Laravel 13 Inertia responses, middleware, and `AssertableInertia` tests. Avoid unsolicited packages (`laravel/boost`, `tightenco/ziggy`, etc.). |

## Remaining clarifications

The following matters are not fully resolved by the confirmed decisions and must be settled before the relevant implementation work.

| ID | Clarification required | Why it remains open |
|---|---|---|
| R-01 | Refund policy details: eligibility, request window, cancellation cut-off, partial vs full refunds, and refund treatment after module access has begun. | D-021 establishes administrator control but does not define the actual business rules. |
| R-02 | Multi-day contiguous session semantics: confirm that attendance is recorded once for the whole session and that the event has no separate daily time windows. | D-022 selects a single date range but does not define attendance granularity for a multi-day event. |
| R-03 | Instructor profile fields and publication controls: confirm required fields (e.g. biography, qualifications, photograph) and whether profiles can be hidden. | D-027 confirms public profiles but not their exact content. |
| R-04 | Certificate signatory settings: confirm the initial signatory name/title and whether the organisation name/logo and certificate template are also global settings. | D-027 establishes global signatory configuration, but initial values and template governance are not specified. |
| R-05 | Tax/e-Invoice readiness: obtain a decision from the business/accountant on SST registration and LHDN e-Invoice applicability before financial implementation. | D-028 intentionally excludes speculative tax and identity fields. |
| R-06 | Email provider, VPS provider/region, backup retention and recovery objectives. | These infrastructure choices remain unresolved in the original architecture register. |
| R-07 | Exact Billplz payment methods enabled for the merchant account. | Billplz is selected for launch, but available merchant methods still depend on onboarding and verification. |
| R-08 | Refund disbursement method for completed Billplz/FPX payments: Billplz Payment Order API, administrator-arranged manual bank transfer, or an approved combination. | Billplz documents completed FPX payments as non-reversible. A Payment Order is a separate outbound transaction with its own status and callback/checksum lifecycle (HMAC-SHA512, distinct from the Bill callback HMAC-SHA256); manual handling also requires protected customer bank details. The business must choose the launch method before refund implementation (target Stage 6). |

## Previously unresolved policy register

The following earlier questions are now resolved by D-017 through D-030: session recording eligibility, physical-session/module entitlement separation, reservation duration, launch payment methods, initial cancellation control, multi-day session structure, attendee identity for company purchases, category cardinality, booking/attendance separation, RBAC approach and staff purchasing, instructor profiles/signatory configuration, tax/identity minimisation, registration deadline, and checkout separation.

Do not silently introduce new business policies. Raise remaining clarifications when they become relevant to the design or implementation stage.
