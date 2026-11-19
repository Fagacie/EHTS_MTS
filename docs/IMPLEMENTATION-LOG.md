# ETMS Implementation Log

Use this file to record completed implementation work with dates and evidence.

## 17 November 2026 — Project documentation baseline

- Created the ETMS project folder on the Windows Desktop.
- Created the SRS and System Architecture Specification, Version 1.1 (750 lines).
- Created the project README and Antigravity execution instructions.
- Created the development roadmap.
- Created the architecture and business decision register.
- Verified the project directory and read back the documentation files.
- Application implementation has not started.

### Verification result
- Project folder exists.
- Main SRS and architecture Markdown file exists and contains 750 lines.
- README, roadmap and decision register were read back successfully.
- No Laravel application code has been generated.

## 17 November 2026 — Stage 1: Database Design (Design Only — Pending Review)

- Read `README.md`, `docs/ETMS-SRS-and-System-Architecture-v1.1.md`, `docs/DECISIONS.md`, and `docs/DEVELOPMENT-ROADMAP.md`.
- Created [docs/ERD.md](ERD.md) containing the Complete System Mermaid ER diagram, four domain-focused Mermaid ER diagrams, a 48-row relationship cardinality matrix, and constraint verification.
- Created [docs/DATABASE-DESIGN.md](DATABASE-DESIGN.md) containing the full MySQL 8.4 data dictionary (28 domain tables + 7 Laravel 13 infrastructure tables in Appendix A), `CHECK` and composite FK constraints, virtual generated active-uniqueness indexes, index strategy, 8 Mermaid state-machine lifecycles, concurrency/idempotency protocols, SRS consistency matrix, and 14 identified ambiguities (`A-01` to `A-14`).
- Updated [docs/DEVELOPMENT-ROADMAP.md](DEVELOPMENT-ROADMAP.md) Stage 1 status to `Pending review`.
- Design-only deliverable: no migrations, application code, or database changes were generated.

### Verification result
- `docs/ERD.md` and `docs/DATABASE-DESIGN.md` created and cross-checked against SRS v1.1 and `DECISIONS.md`.
- Confirmed zero corporate portal, organisation/member, or employee assignment tables.
- Confirmed no Laravel code, migrations, or database changes exist in the workspace.

### Next planned action
Await user review and approval of Stage 1 (`docs/ERD.md` and `docs/DATABASE-DESIGN.md`) and decisions on open ambiguities (`A-01`–`A-14`) before Stage 2 (UI/UX blueprint and navigation).

## 17 November 2026 — Stage 1: Database Design Correction & Integrity Review (Pending Final Approval)

- Reviewed confirmed business decisions `D-017` through `D-030` and remaining clarifications `R-01` through `R-07` in [docs/DECISIONS.md](DECISIONS.md).
- Added technical integrity decisions `D-031` through `D-034` to [docs/DECISIONS.md](DECISIONS.md).
- Revised [docs/ERD.md](ERD.md) and [docs/DATABASE-DESIGN.md](DATABASE-DESIGN.md) across all 29 domain tables (28 original tables + `instructor_profiles` added per `D-027`).
- Resolved all 5 technical review findings:
  1. **Order & Order-Item Type Consistency (`D-030`, `D-031`):** Added `order_items.order_type`, `uq_orders_id_type`, `fk_order_items_order_type`, and `chk_order_items_type_alignment`.
  2. **Attendance Lifecycle Separation (`D-017`, `D-025`):** Separated `attendance_status` and `review_status` into distinct Mermaid state diagrams and transition matrices, enforced `chk_attendance_marked_before_submit` and `chk_attendance_verified_meta`, and made certificate/recording eligibility rules (`present` + `verified`) explicit.
  3. **Cross-Record Ownership Integrity (`D-032`):** Added composite foreign keys across `bookings`, `payments`, `refunds`, `module_entitlements`, and `payment_webhook_events` referencing `orders (id, user_id)`, `payments (id, order_id, user_id)`, and `bookings (id, order_id, user_id)`.
  4. **Session Capacity & Booking Counters (`D-033`):** Defined `bookings` as the authoritative occupancy source, documented exact counter deltas for all 7 booking transitions, specified self-healing recomputation under `SELECT ... FOR UPDATE`, and defined the `etms:reconcile-session-seats` drift detection and repair process.
  5. **Certificate & PDF Generation Lifecycles (`D-027`, `D-034`):** Made `approved_by` and `approved_at` mandatory (`NOT NULL`) at certificate creation, added `pdf_generation_status`, `pdf_generation_attempts`, `pdf_generation_error`, and `signatory_signature_key_snapshot`, and defined global signatory `system_settings` keys.
- Enforced `CHECK (registration_closes_at < starts_at)` (`D-029`), Billplz-only launch constraints (`D-020`), 15-minute default reservation hold (`D-019`), and `ON DELETE RESTRICT` on all FK columns referenced inside `CHECK` constraints (preventing MySQL 8.4 `ERROR 3823`).

### Next planned action
Stop and await independent inspection and approval of the revised Stage 1 database design before proceeding to Stage 2 (UI/UX blueprint and navigation).

## 17 November 2026 — Stage 1.1: Final Database Integrity Corrections (Pending Review)

- Inspected the repository documentation and confirmed the workspace contains documentation only; no Git metadata, Laravel application, migrations or database server was available.
- Consulted the official Billplz API, webhooks/callbacks, API quick-start, issue-a-refund and Payment Order documentation. Corrected the design to use form-encoded `x_signature` callbacks, HMAC-SHA256 canonical verification, MYR-cent reconciliation, a canonical required-field callback identity that excludes optional transaction identifiers, 20-second/HTTP 200 acknowledgement, documented retries, Bill deletion after local expiry, and callback/expiry race handling.
- Added the complete late-payment workflow across booking, order, payment, session capacity and refund states. Seat counters remain authoritative recomputations under a session row lock.
- Corrected certificate replacement cardinality: the historical forward link is non-unique while the active-attendance generated-column index enforces one active certificate.
- Added `R-08` for the unresolved refund disbursement decision and documented Payment Order versus manual-transfer implications without adding customer bank-account fields.
- Reframed verification claims as documentation-level structural checks. The twelve composite foreign keys were re-reviewed for column order, parent unique keys, type compatibility and nullable `MATCH SIMPLE` guards; no MySQL DDL or constraint/concurrency test was executed.

### Verification result
- Official Billplz documentation was fetched and reviewed at the five URLs listed in the Stage 1.1 correction prompt.
- Targeted text searches and bounded reads were run across the five modified documentation files plus `README.md`; no application test suite or Mermaid renderer was available.
- Stage 1 remains **Pending review**. Independent technical review and owner resolution of `R-08` are still required.

## 17 November 2026 — Stage 1.2: Final Payment Integrity Corrections (Pending Review)

- Corrected Billplz X-Signature canonicalization to case-insensitive parameter sorting, direct name/value concatenation without `=`, pipe joining, preserved empty values, HMAC-SHA256 and constant-time comparison.
- Established one global lock hierarchy: `training_sessions -> orders -> payments -> bookings -> refunds` for session workflows, with `orders -> payments -> module_entitlements -> refunds` for independent module purchases; added ordered multi-row locking, bounded deadlock retries and external-API retry/reconciliation rules.
- Changed checkout to persist the local payment attempt and stable `payment_reference` before calling Billplz, pass that reference as `reference_1`, and reconcile callbacks that arrive before Bill-ID mapping.
- Added recoverable `unmatched_pending` webhook processing and asynchronous matching/escalation rules.
- Made refund method and initiating administrator nullable for system-generated `requested` refunds; added request source/event auditing and required method/approval before approval or processing. `R-08` remains unresolved.

### Verification result
- Re-read the affected sections of [docs/DATABASE-DESIGN.md](DATABASE-DESIGN.md), [docs/ERD.md](ERD.md), [docs/DECISIONS.md](DECISIONS.md), [docs/DEVELOPMENT-ROADMAP.md](DEVELOPMENT-ROADMAP.md), and this log.
- Performed targeted cross-reference searches, composite-FK presence checks, and Markdown fence-balance checks.
- No MySQL 8.4 DDL, database constraint, deadlock/concurrency, Mermaid rendering, Laravel, or payment integration test was executed.
- Stage 1 remains **Pending review**.

## 17 November 2026 — Stage 1.3: Final Billplz Correlation and Refund Integrity Corrections (Pending Review)

- Reviewed the official [Billplz API](https://support.billplz.com/api) and [Webhooks and callbacks](https://support.billplz.com/guide/webhooks-and-callbacks) documentation. The Bill object returned by Create Bill/Get a Bill documents `reference_1`; the documented X-Signature callback contract does not establish that `reference_1` is included in callback POST data.
- Changed callback correlation to use the documented `gateway_bill_id` as the primary identifier. A valid callback arriving before local mapping commits remains durably stored as `unmatched_pending` with its Bill ID and sanitized payload.
- Added authenticated, rate-limited Get a Bill recovery by Bill ID. Recovery compares the returned Bill object's `reference_1` with the local payment reference, preserves callback X-Signature verification, uses bounded retry/backoff for transient failures and `429`, and never treats browser redirects as payment authority.
- Kept `gateway_reference_1` only as a snapshot on `payments`; removed it from `payment_webhook_events` and aligned the ERD/indexes to Bill-ID matching.
- Added a refund request-source/actor CHECK constraint requiring system requests to have no `requested_by` and administrator requests to have an initiating administrator.
- Updated Stage Control wording to refer to the selected coding agent. Stage 1 remains **Pending review** and `R-08` remains unresolved.

### Verification result
- Re-read the affected payment, webhook, refund, locking and roadmap sections.
- Confirmed no webhook index references the removed `payment_webhook_events.gateway_reference_1` column.
- Confirmed the ERD and data dictionary agree on the affected payment and webhook fields, the twelve composite FKs remain documented, and Markdown fences remain balanced.
- No MySQL DDL, database CHECK execution, concurrency test, Mermaid rendering, callback replay, or live Billplz recovery request was executed.

## 17 November 2026 — Stage 1.4: Final Webhook Idempotency and Event-Mapping Integrity Review (Pending Review)

- Added D-035 without changing D-001 through D-034: only HMAC-verified primary callbacks populate the unique `payment_webhook_events.idempotency_key`.
- Added a non-unique `request_fingerprint` for all received requests. Invalid-signature rows retain `idempotency_key = NULL`, while exact repeated valid callbacks are recorded as audit-only `ignored_duplicate` rows with a null idempotency key and cannot fulfil an order.
- Added explicit paired-nullability and processing-state/mapping CHECK constraints. Partial `payment_id`/`order_id` mappings are forbidden; `unmatched_pending`, `processed`, `rejected_invalid_signature`, `rejected_mismatch`, `failed` and `ignored_duplicate` now have documented signature, key and mapping requirements.
- Expanded the ERD webhook entity to include all data-dictionary columns and aligned indexes to the verified-key, request-fingerprint and Bill-ID model.
- Preserved Bill ID correlation, Get a Bill recovery, X-Signature verification, the global locking hierarchy, Stage 1 `Pending review` status and unresolved `R-08`.

### Verification result
- Performed structural checks for nullable verified-key idempotency, non-unique invalid-request fingerprints, duplicate handling, paired mapping constraints, processing-state invariants and Bill-ID indexing.
- Confirmed all twelve composite foreign keys remain documented and the ERD/data dictionary webhook fields agree.
- Confirmed Markdown and Mermaid fences are balanced.
- No MySQL CHECK/index execution, concurrency test, callback replay, or live Billplz integration was performed.

## 17 November 2026 — Stage 2: UI/UX Blueprint and Application Design (In Progress — Pending Review)

- Confirmed Stage 1 (Database Design) official approval and read [docs/ETMS-SRS-and-System-Architecture-v1.1.md](ETMS-SRS-and-System-Architecture-v1.1.md), [docs/DATABASE-DESIGN.md](DATABASE-DESIGN.md), [docs/ERD.md](ERD.md), [docs/DECISIONS.md](DECISIONS.md), and [docs/DEVELOPMENT-ROADMAP.md](DEVELOPMENT-ROADMAP.md).
- Created [docs/UI-UX-BLUEPRINT.md](UI-UX-BLUEPRINT.md) defining the Docker Compose v2 container architecture (`nginx`, `app`, `queue`, `scheduler`, `mysql`, `redis` + persistent volumes + external Billplz, Bunny Stream, Cloudflare R2, and transactional email services), the unified Ecohydrotech Tailwind CSS 4 `@theme` design system (colours, typography, spacing, responsive breakpoints, components, status badge taxonomy across all schema states, dashboard widgets, empty/loading/error/validation states, WCAG 2.1 AA accessibility, and Malaysian localisation), and detailed specifications for all four experiences (Public Website, Trainee/Customer Portal, Instructor Portal, and Filament 5 Administrator Portal).
- Created [docs/SCREEN-INVENTORY.md](SCREEN-INVENTORY.md) mapping **51 screens** (`PUB-01`–`PUB-10`, `TRN-01`–`TRN-14`, `INS-01`–`INS-07`, `ADM-01`–`ADM-20`) with exact route patterns, named routes, Livewire 4 / Filament 5 components, policies/middleware, database table/column bindings, layout regions, form validation, and states.
- Created [docs/USER-FLOWS.md](USER-FLOWS.md) documenting the global multi-role navigation architecture (`D-026`), header/sidebar/account/breadcrumb/mobile navigation specs across all four experiences, six end-to-end Mermaid user journey diagrams (registration/login, physical session booking & Billplz checkout, independent module purchase & learning access, instructor attendance & recording submission, admin attendance verification & certificate issuance/QR verification, and session cancellation & admin refund workflow), and the cross-experience RBAC permission matrix.
- Updated [docs/ETMS-SRS-and-System-Architecture-v1.1.md](ETMS-SRS-and-System-Architecture-v1.1.md) Section 6 and Section 13.1.1 to include Docker and Docker Compose v2 in the approved technology stack and deployment architecture.
- Added decision `D-036` (Containerisation architecture) to [docs/DECISIONS.md](DECISIONS.md) without modifying `D-001`–`D-035` or unresolved clarifications `R-01`–`R-08`.
- Updated [docs/DEVELOPMENT-ROADMAP.md](DEVELOPMENT-ROADMAP.md) marking Stage 1 as `Completed` and Stage 2 as `In Progress`.
- Confirmed zero application code, database migrations, or Docker configuration files were generated during Stage 2.

### Verification result
- Confirmed `docs/UI-UX-BLUEPRINT.md`, `docs/SCREEN-INVENTORY.md`, and `docs/USER-FLOWS.md` exist and contain complete specifications.
- Confirmed `docs/DATABASE-DESIGN.md` and `docs/ERD.md` remain unmodified.
- Confirmed Markdown code fences and Mermaid blocks are balanced across all created and updated files.
- Confirmed no Laravel application files, migrations, or Docker configuration files exist in the workspace.

### Next planned action
Await project owner review and approval of Stage 2 deliverables (`docs/UI-UX-BLUEPRINT.md`, `docs/SCREEN-INVENTORY.md`, `docs/USER-FLOWS.md`) and confirmation on open clarifications (`R-03`, `R-04`) before starting Stage 3.

## 18 November 2026 — Stage 3: Laravel Foundation, Docker Environment, Authentication and Design System (In Progress — Pending Review)

- **Docker Environment:**
  - Created `Dockerfile` (`php:8.3-fpm-bookworm`, `mlocati/php-extension-installer:2` for `pdo_mysql`, `bcmath`, `pcntl`, `zip`, `intl`, `redis`, pre-enabled `Zend OPcache` + `mbstring`, lightweight utilities `unzip`, `procps`, `libfcgi-bin`, and `composer:2`), `docker/php/php.ini`, `docker/php/zz-etms-fpm.conf`, `docker/php/entrypoint.sh`, `docker/nginx/default.conf`, `docker/mysql/01-init-test-db.sql`, `.dockerignore`, and `docker-compose.yml` defining `nginx`, `app`, `queue`, `scheduler`, `mysql` (`mysql:8.4`), `redis` (`redis:7-alpine`), plus an optional `node` (`node:22-alpine`) asset build profile.
  - Resolved initial slow build issues by removing unnecessary `nodejs`, `default-mysql-client`, and `gd` compilation dependencies from the PHP-FPM runtime image and isolating PHP extension installation from APT utilities.
  - Resolved `storage/` and `bootstrap/cache/` ownership across `root` CLI and `www-data` PHP-FPM/worker processes via `docker/php/entrypoint.sh` and `user: "www-data"` on `queue` and `scheduler`.
- **Laravel 13 Application & Packages:**
  - Installed `laravel/framework` `v13.43.0`, `livewire/livewire` `v4.4.8`, `filament/filament` `v5.11.4`, and compiled Tailwind CSS `^4.0.0` assets with Vite `v8.3.8` (`public/build/manifest.json`).
  - Configured `.env.example`, `.env` (`APP_PORT=8080`, UTC timezone, `Asia/Kuala_Lumpur` display timezone, `MYR` currency, MySQL `etms`, Redis cache/queues), `config/etms.php`, and `phpunit.xml` (running tests against MySQL 8.4 `etms_test`).
- **Foundation Schema, RBAC & Seeders:**
  - Implemented `app/Enums/RoleSlug.php` (`administrator`, `instructor`, `customer`), `app/Enums/UserStatus.php` (`active`, `suspended`, `deactivated`), foundation migrations (`users` with `chk_users_status`, `password_reset_tokens`, `sessions`, `cache`, `jobs`, `roles` with `chk_roles_slug`, `permissions`, `role_user`, `permission_role`, `notifications`, `audit_logs`), models (`User`, `Role`, `Permission`, `AuditLog`), `UserFactory`, `RoleAndPermissionSeeder` (3 roles, 20 permissions), and `DemoUserSeeder` (4 verified accounts including `D-026` dual-role staff account).
- **Authentication, Policies & Administrator MFA:**
  - Implemented registration (`RegisteredUserController`), throttled login/logout (`AuthenticatedSessionController`), signed email verification (`EmailVerificationController`), password reset (`PasswordResetController`), TOTP MFA setup & challenge (`TwoFactorAuthenticationService` + `TwoFactorChallengeController` using `PragmaRX\Google2FA` and `chillerlan\QRCode`), middlewares (`EnsureAccountIsActive`, `EnsureUserHasRole`, `EnsureAdministratorMfa`), and `UserPolicy` / Gates.
- **Design System & Filament 5 Panel:**
  - Configured Ecohydrotech `@theme` design tokens in `resources/css/app.css`, created `<x-eht.*>` Blade components (`logo`, `button`, `status-badge`, `card`, `stat-card`, `empty-state`, `portal-switcher`), layouts (`public`, `auth`, `portal`, `instructor`, `errors/403`), and branded Filament 5 `AdminPanelProvider` at `/admin` with `UserResource`, `AuditLogResource`, and `FoundationOverviewWidget`.

### Verification result
- **Docker Compose Health (`docker compose ps`):** All 6 containers (`etms_app`, `etms_mysql`, `etms_nginx`, `etms_queue`, `etms_redis`, `etms_scheduler`) confirmed `Up` and `(healthy)`.
- **Database & Redis Connectivity:** `php artisan migrate:fresh --seed` succeeded on MySQL 8.4; Redis ping returned `PONG`; `php artisan about` confirmed `laravel/framework` `13.43.0`, `PHP` `8.3.36`, `mysql` database, `redis` cache/queue.
- **Live HTTP Endpoints:** Confirmed `HTTP 200` on `/up`, `/`, `/login`, and `/register`, and `HTTP 302` redirect to `/login` for unauthenticated `/admin` requests.
- **Automated Tests (`docker compose exec -T -u www-data app php artisan test`):** `12 passed (66 assertions)` in `31.11s` against MySQL 8.4 (`etms_test`), verifying homepage rendering, customer registration + audit log + email verification, password reset, suspended/deactivated login rejection, 3-role boundaries, `D-026` multi-role portal switching, Administrator TOTP MFA + Filament `/admin` + `/admin/users` + `/admin/audit-logs`, MySQL 8.4 `CHECK` constraints (`users.status` and `roles.slug`), and demo seeders.

### Next planned action
Await independent inspection and owner approval of Stage 3 before proceeding to Stage 4 (Customer profiles, categories and module management).

## 18 November 2026 — Stage 4: Customer Profiles, Categories and Training Module Management (In Progress — Pending Review)

- **Schema & Migrations (`DATABASE-DESIGN.md` parity):**
  - Created `database/migrations/0001_01_01_000005_create_stage4_profiles_and_catalogue_tables.php` implementing `customer_profiles` (`chk_customer_profiles_type`), `categories`, `modules` (`chk_modules_status`, `chk_modules_price_non_negative`, `chk_modules_independent_price`), and `module_materials` (`chk_module_materials_type`, `chk_module_materials_size`, `chk_module_materials_status`) with exact foreign key `RESTRICT`/`CASCADE` rules and composite indexes.
  - Configured the private `'r2'` storage disk in `config/filesystems.php` (S3 driver when `R2_ENDPOINT` is configured; private local fallback `storage/app/private/r2` in local/test environments).
- **Domain Enums, Models & Policies:**
  - Created `CustomerType`, `ModuleStatus`, `MaterialType`, and `MaterialStatus` enums in `app/Enums/`.
  - Created `CustomerProfile`, `Category`, `Module`, and `ModuleMaterial` models in `app/Models/` and updated `User` with `customerProfile()` and `hasActiveModuleEntitlement()`.
  - Created `CategoryPolicy`, `ModulePolicy`, and `ModuleMaterialPolicy` (enforcing `D-018` / `FR-07` entitlement-gated downloads) and registered them in `AppServiceProvider`.
- **Customer Profiles (`PUB-07`, `TRN-02`, `ADM-17`):**
  - Updated `RegisteredUserController` and `resources/views/auth/register.blade.php` to support `individual` and `company` profile creation at registration.
  - Created `ProfileController` and `resources/views/portal/profile.blade.php` (`/portal/profile`) for editing trainee contact information, `customer_type`, and optional company billing details with audit logging (`customer_profile.updated`).
  - Extended `UserResource` (`CreateUser` / `EditUser`) in Filament 5 for administrator management of customer profile and company billing fields.
- **Filament 5 Catalogue & Material Administration (`ADM-02`, `ADM-03`, `ADM-04`):**
  - Created `CategoryResource`, `ModuleResource` (with `Publish`, `Unpublish`, `Archive` actions and `MaterialsRelationManager`), and `ModuleMaterialResource` (uploading private files to the `r2` disk with automatic `original_filename`, `mime_type`, `file_size_bytes`, and `checksum_sha256` enrichment).
  - Created `MaterialDownloadController` (`GET /materials/{material}/download`) enforcing `ModuleMaterialPolicy::download` and writing `module_material.downloaded` audit logs.
- **Public Training Catalogue & Module Details (`PUB-01`, `PUB-02`, `PUB-03`):**
  - Created `<x-eht.module-card>`, Livewire 4 `TrainingCatalogue` (`app/Livewire/Public/TrainingCatalogue.php`), `PublicCatalogueController`, `resources/views/public/catalogue.blade.php`, and `resources/views/public/module-show.blade.php` (displaying syllabus, learning objectives, material metadata without exposing private R2 storage keys, and separate physical session vs. independent module purchase panels).
  - Created `CatalogueSeeder` and updated `DemoUserSeeder` and `DatabaseSeeder`.

### Verification result
- **Migrations & Seeders:** `docker compose exec -T -u www-data app php artisan migrate:fresh --seed` succeeded on MySQL 8.4 (`0001_01_01_000005_create_stage4_profiles_and_catalogue_tables` + `CatalogueSeeder`).
- **Live HTTP Endpoints:** Confirmed `HTTP 200` on `/`, `/training`, and `/training/applied-urban-stormwater-management-msma`.
- **Automated Tests (`docker compose exec -T -u www-data app php artisan test`):** `18 passed (117 assertions)` in `63.16s` against MySQL 8.4 (`etms_test`), covering company registration and `/portal/profile` updates, Livewire catalogue filtering/search/visibility, module detail rendering and `404` guards for draft/inactive-category modules, `ModuleMaterialPolicy` download authorization (`403` for unentitled customer, `200` download + audit log for admin), Filament 5 resource access (`/admin/categories`, `/admin/modules`, `/admin/module-materials`), and MySQL 8.4 `CHECK` constraints (`chk_modules_independent_price`).

### Next planned action
Stage 4 approved; proceed to Stage 5 (Session scheduling, instructors and booking).

## 18 November 2026 — Stage 5: Training Session Scheduling, Instructor Management and Booking (In Progress — Pending Review)

- **Schema & Migrations (`DATABASE-DESIGN.md` parity):**
  - Created `database/migrations/0001_01_01_000006_create_stage5_sessions_instructors_and_bookings_tables.php` defining `instructor_profiles`, `training_sessions` (`chk_sessions_duration`, `chk_sessions_capacity_min`, `chk_sessions_price`, `chk_sessions_rec_policy`, `chk_sessions_status`, `chk_sessions_dates`, `chk_sessions_reg_close`, `chk_sessions_reg_window`, `chk_sessions_capacity`, `uq_sessions_id_module`), `session_instructors` (`uq_session_instructors_pair`), `session_recordings` (`chk_session_recordings_provider`, `chk_session_recordings_encoding`, `chk_session_recordings_status`, `uq_session_recordings_provider`), `orders` (`chk_orders_type`, `chk_orders_status`, `chk_orders_amounts`, `chk_orders_customer_type`), `order_items` (`fk_order_items_order_type`, `chk_order_items_qty`, `chk_order_items_type_alignment`), `bookings` (`is_active_booking` virtual generated column, `uq_bookings_active_user_session`, `fk_bookings_order_user`, `fk_bookings_order_item_session`, `chk_bookings_status`), `attendance` (`fk_attendance_booking_composite`, `chk_attendance_status`, `chk_attendance_review_status`, `chk_attendance_marked_before_submit`, `chk_attendance_verified_meta`), and `system_settings` (`chk_system_settings_type`).
- **Domain Enums, Models & Policies:**
  - Created 10 PHP 8.3 backed enums (`SessionStatus`, `RecordingAccessPolicy`, `OrderType`, `OrderItemType`, `OrderStatus`, `BookingStatus`, `AttendanceStatus`, `AttendanceReviewStatus`, `RecordingStatus`, `EncodingStatus`) in `app/Enums/`.
  - Created 9 Eloquent models (`InstructorProfile`, `TrainingSession`, `SessionInstructor`, `SessionRecording`, `Order`, `OrderItem`, `Booking`, `Attendance`, `SystemSetting`) and updated `User` and `Module`.
  - Created `InstructorProfilePolicy`, `TrainingSessionPolicy`, and `BookingPolicy`, updated `ModulePolicy::delete()` to block deleting modules with existing sessions, and registered all policies in `AppServiceProvider`.
- **Concurrency-Safe Booking Engine & Seat Reconciliation (`D-019`, `D-033`, `D-034`, `BR-16`):**
  - Implemented `app/Services/Booking/SessionBookingService.php` using `DB::transaction` + `SELECT ... FROM training_sessions WHERE id = ? FOR UPDATE`, inline sweep of elapsed 15-minute `pending_payment` holds, registration deadline & capacity checks, atomic creation of `Order` (`session_booking`), `OrderItem` (`training_session`, `quantity = 1`), and `Booking` (`pending_payment`, `reservation_expires_at = +15m`), authoritative counter recomputation directly from active `bookings` (`D-033`), and `cancelSessionByAdmin()` (`BR-16`, `D-021`).
  - Created `app/Console/Commands/ReconcileSessionSeatsCommand.php` (`etms:reconcile-session-seats`) and scheduled it every minute (`withoutOverlapping(2)`) in `routes/console.php`.
- **Filament 5 Administration Resources (`ADM-06`, `ADM-11`, `ADM-18`):**
  - Created `InstructorProfileResource`, `TrainingSessionResource` (with `InstructorsRelationManager` for multi-instructor assignment and `Publish`, `Close Reg`, `Complete`, `Reconcile Seats`, and `Cancel Session` actions), and `BookingResource` (with `Confirm Seat` and `Cancel Booking` actions), and updated `FoundationOverviewWidget`.
- **Public Experience, Trainee Portal Booking & Instructor Portal (`PUB-03`, `PUB-04`, `PUB-05`, `TRN-04`, `TRN-07`, `TRN-08`, `INS-01`–`INS-07`):**
  - Created `<x-eht.session-card>`, `PublicSessionController` (`/sessions`, `/sessions/{session:slug}`), and `PublicInstructorController` (`/instructors`, `/instructors/{instructor}`), and updated `PublicCatalogueController`, `module-show.blade.php`, `home.blade.php`, and `public.blade.php`.
  - Created `SessionCheckoutController` (`/checkout/session/{session:slug}`) and `BookingController` (`/portal/bookings`, `/portal/bookings/{booking:booking_reference}`), and updated `PortalDashboardController` and `portal/dashboard.blade.php`.
  - Created `InstructorDashboardController`, `InstructorSessionController` (`/instructor/sessions`, `/instructor/sessions/{session:session_code}`, `/attendance`, `/completion`, `/recordings`), and `InstructorProfileController` (`/instructor/profile`), strictly hiding commercial pricing and blocking unassigned instructors with `403 Forbidden`.
  - Created `SessionAndBookingSeeder` and `tests/Feature/Stage5SessionsInstructorsAndBookingsTest.php`.

### Verification result
- **Frontend Build (`npm run build`):** Succeeded via Vite `v8.3.8` (`public/build/assets/app-DmCm9hmL.css`).
- **Migrations & Seeders:** `docker compose exec -T -u www-data app php artisan migrate:fresh --seed` succeeded on MySQL 8.4 (`0001_01_01_000006_create_stage5_sessions_instructors_and_bookings_tables` + `SessionAndBookingSeeder`).
- **Automated Tests (`docker compose exec -T -u www-data app php artisan test`):** `26 passed (206 assertions)` against MySQL 8.4 (`etms_test`), verifying public/hidden instructor profiles, public sessions & module session listings, 15-minute seat reservations, overbooking prevention under full capacity, expired hold release via `etms:reconcile-session-seats`, registration deadline enforcement, instructor portal assignment boundaries (`403` on unassigned sessions), two-step attendance marking/submission (`chk_attendance_marked_before_submit`), completion reports, recording metadata submission (`pending_review`), admin session cancellation (`BR-16`), Filament 5 admin resources (`/admin/instructor-profiles`, `/admin/training-sessions`, `/admin/bookings`), and MySQL 8.4 `CHECK` constraints (`chk_sessions_reg_close`).

### Next planned action
Stage 5 approved; proceed to Stage 6 (Orders, Billplz and payment reconciliation).

## 18 November 2026 — Stage 6: Payment Gateway, Checkout and Order Management (In Progress — Pending Review)

- **Schema & Migrations (`DATABASE-DESIGN.md` parity):**
  - Created `database/migrations/0001_01_01_000007_create_stage6_payments_webhooks_refunds_and_entitlements_tables.php` defining `payments` (`fk_payments_order_user`, `chk_payments_gateway`, `chk_payments_amount`, `chk_payments_refunded_amount`, `chk_payments_status`), `payment_webhook_events` (`fk_webhook_payment_order`, `chk_webhook_processing_status`, `chk_webhook_match_pair`, `chk_webhook_idempotency_scope`, `chk_webhook_state_mapping`, `uq_webhook_gateway_idempotency`), `refunds` (`fk_refunds_payment_order_user`, `fk_refunds_booking_order_user`, `chk_refunds_amount`, `chk_refunds_method`, `chk_refunds_disbursement_status`, `chk_refunds_status`, `chk_refunds_request_source_actor`, `chk_refunds_approval_inputs`), and `module_entitlements` (`is_active_entitlement` virtual generated column, `uq_entitlements_active_user_module`, `fk_entitlements_order_user`, `fk_entitlements_order_item_module`, `chk_entitlements_source`, `chk_entitlements_revocation`).
- **Domain Enums, Models & Policies:**
  - Created `PaymentStatus`, `WebhookProcessingStatus`, `RefundStatus`, `RefundMethod`, and `EntitlementStatus` enums in `app/Enums/`.
  - Created `Payment`, `PaymentWebhookEvent`, `Refund`, and `ModuleEntitlement` models in `app/Models/` and updated `Order`, `OrderItem`, `Booking`, `User`, and `Module`.
  - Created `OrderPolicy`, `PaymentPolicy`, and `RefundPolicy` and registered them in `AppServiceProvider`.
- **Billplz Integration & Webhook Processing (`FR-05`, `D-008`, `D-020`, `D-035`, Section 6.2):**
  - Configured `services.billplz` in `config/services.php` and excluded `webhooks/billplz` from browser CSRF verification in `bootstrap/app.php`.
  - Implemented `app/Services/Payment/BillplzGatewayService.php` supporting Bill creation (`POST /api/v3/bills`), API verification (`GET /api/v3/bills/{id}`), Bill deletion (`DELETE /api/v3/bills/{id}`), canonical X-Signature source construction (sorted case-insensitively, key+value with no `=`, joined with `|`), constant-time HMAC-SHA256 verification (`hash_equals`), verified idempotency key calculation (`D-035`), and non-unique request fingerprinting.
  - Implemented `app/Services/Payment/PaymentOrchestrationService.php` and `app/Http/Controllers/Webhooks/BillplzWebhookController.php` (`POST /webhooks/billplz`) enforcing global lock hierarchy (`training_sessions -> orders -> payments -> bookings/module_entitlements -> refunds`), rejecting invalid signatures with `idempotency_key = NULL`, ignoring duplicate verified callbacks (`ignored_duplicate`), confirming active session bookings, restoring or queuing system refunds (`late_payment_capacity_unavailable`) on late paid callbacks after 15-minute hold expiry, and granting lifetime `ModuleEntitlement` records (`grant_source = 'purchase'`) on independent module purchases.
- **Checkout, Return Page, Order Management & Refunds (`TRN-05`, `TRN-06`, `TRN-09`, `TRN-10`, `ADM-09`, `ADM-10`, `ADM-12`, `D-021`):**
  - Created `ModuleCheckoutController` (`GET/POST /checkout/module/{module:slug}`), `OrderController` (`GET /portal/orders`, `GET /portal/orders/{order:order_number}`, `POST /portal/orders/{order:order_number}/pay`, and read-only `GET /checkout/return`), and updated `SessionCheckoutController`.
  - Implemented `app/Services/Payment/RefundService.php` supporting administrator-controlled full and partial refunds (`recordAndCompleteAdminRefund`, `approveAndCompleteRequestedRefund`, `rejectRefund`), updating `payments.refunded_amount`, transitioning `orders` and `payments` to `partially_refunded` / `refunded`, releasing session seats (`bookings -> refunded`), and revoking `module_entitlements` (`status -> revoked`) upon full module refund.
  - Created Filament 5 `OrderResource`, `PaymentResource` (with `Verify via Billplz API` and `Record Refund` actions), and `RefundResource` (with `Approve & Complete` and `Reject` actions).
  - Created `tests/Feature/Stage6PaymentsOrdersAndRefundsTest.php`.

### Verification result
- **Frontend Build (`npm run build`):** Succeeded via Vite `v8.3.8` (`public/build/assets/app-Bq8n4mo1.css`).
- **Migrations & Seeders:** `docker compose exec -T -u www-data app php artisan migrate:fresh --seed` succeeded on MySQL 8.4 (`0001_01_01_000007_create_stage6_payments_webhooks_refunds_and_entitlements_tables` + `SessionAndBookingSeeder`).
- **Automated Tests (`docker compose exec -T -u www-data app php artisan test`):** `34 passed (284 assertions)` in `112.18s` against MySQL 8.4 (`etms_test`), covering Billplz canonical X-Signature construction and constant-time verification, session checkout + read-only return URL + verified paid webhook fulfilment, idempotent duplicate callbacks, invalid signature rejection (`D-035`), failed payment callbacks + retry bill creation, late paid webhooks after hold expiry (`late_payment_capacity_unavailable` system refund queuing when full), independent module purchase + lifetime `ModuleEntitlement` grant + R2 download unlocking, partial and full refunds (including seat release and entitlement revocation), Filament 5 admin resources (`/admin/orders`, `/admin/payments`, `/admin/refunds`), and MySQL 8.4 composite foreign key ownership enforcement (`fk_payments_order_user`, `D-032`).

### Next planned action
Stage 6 approved; proceed to Stage 7 (Module entitlements, documents and video).

## 18 November 2026 — Stage 7: Learning Materials, Video Recordings and Customer Access (In Progress — Pending Review)

- **Schema & Migrations (`DATABASE-DESIGN.md` parity):**
  - Created `database/migrations/0001_01_01_000008_create_stage7_module_videos_table.php` defining `module_videos` (`uq_module_videos_provider`, `idx_module_videos_module`, `chk_module_videos_provider`, `chk_module_videos_encoding`, `chk_module_videos_status`).
- **Domain Models, Policies & Bunny Stream Service (`D-017`, `D-018`, Section 6.3):**
  - Created `app/Models/ModuleVideo.php`, `app/Policies/ModuleVideoPolicy.php`, `app/Policies/SessionRecordingPolicy.php`, and `app/Policies/ModuleEntitlementPolicy.php`, updated `ModuleMaterialPolicy` (adding assigned-instructor access alongside active `ModuleEntitlement` and administrator access), and registered all policies in `AppServiceProvider`.
  - Configured `services.bunny_stream` in `config/services.php` and implemented `app/Services/Media/BunnyStreamService.php` for Bunny Stream SHA-256 embed token signing (`generateSignedEmbedUrl`, `computeEmbedToken`, `verifyEmbedToken`) and video transcoding metadata synchronization (`fetchVideoMetadata`).
  - Added time-limited signed URL generation (`temporaryDownloadUrl`, `temporaryWatchUrl`) and signature/expiry verification in `MaterialDownloadController` and `LearningPortalController`, writing `module_material.downloaded`, `module_video.watched`, and `session_recording.watched` audit logs.
- **Customer Learning Dashboard & Workspaces (`TRN-11`, `TRN-12`, `TRN-13`):**
  - Created `app/Http/Controllers/Portal/LearningPortalController.php` and Blade views `resources/views/portal/modules/index.blade.php` (`GET /portal/modules`), `resources/views/portal/modules/show.blade.php` (`GET /portal/modules/{module:slug}` and `GET /portal/modules/{module:slug}/videos/{video}/watch`), `resources/views/portal/recordings/index.blade.php` (`GET /portal/recordings`), and `resources/views/portal/recordings/watch.blade.php` (`GET /portal/recordings/{recording}/watch`).
  - Strictly enforced `D-017` (`recording_access_policy = 'attendees_only'` + `confirmed` booking + `present` attendance + `verified` review status) and `D-018` (strict separation between physical session attendance and module entitlements).
- **Filament 5 Administration (`ADM-05`, `ADM-08`, `ADM-16`):**
  - Created `ModuleVideoResource` (with `Publish` and `Sync Bunny` actions), `SessionRecordingResource` (with `Approve & Publish`, `Unpublish`, `Reject`, and `Sync Bunny` actions), and `ModuleEntitlementResource` (supporting manual `admin_grant` creation and `Revoke Access` action).
  - Updated `CatalogueSeeder` and `SessionAndBookingSeeder` and created `tests/Feature/Stage7LearningMaterialsVideosAndRecordingsTest.php`.

### Verification result
- **Frontend Build (`npm run build`):** Succeeded via Vite `v8.3.8` (`public/build/assets/app-DvjbLHTj.css`).
- **Migrations & Seeders:** `docker compose exec -T -u www-data app php artisan migrate:fresh --seed` succeeded on MySQL 8.4 (`0001_01_01_000008_create_stage7_module_videos_table` + `CatalogueSeeder` + `SessionAndBookingSeeder`).
- **Automated Tests (`docker compose exec -T -u www-data app php artisan test`):** `39 passed (326 assertions)` in `122.18s` against MySQL 8.4 (`etms_test`), covering Bunny Stream token signing and expiration verification, module learning workspace & video playback authorization + audit logging, expired signed download/watch URL rejection (`403`), `attendees_only` session recording enforcement (`pending`/`submitted`/`absent` blocked with `403`, `present` + `verified` unlocked with `200 OK` + audit log), `D-018` separation between session attendance and module workspace, Filament 5 admin resources (`/admin/module-videos`, `/admin/session-recordings`, `/admin/module-entitlements`), and MySQL 8.4 `CHECK` constraints (`chk_module_videos_encoding`).

### Next planned action
Stage 7 approved; proceed to Stage 8 (Certificate generation and management).

## 18 November 2026 — Stage 8: Certificate Generation and Management (In Progress — Pending Review)

- **Packages & Schema (`DATABASE-DESIGN.md` & Stage 8 Scope):**
  - Installed `barryvdh/laravel-dompdf` (`v3.1.2` / `dompdf/dompdf` `v3.1.6`) via Composer without requiring `ext-gd` (rendering our vector SVG organisation logo via `dompdf/php-svg-lib`).
  - Created `database/migrations/0001_01_01_000009_create_stage8_certificates_table.php` defining `certificates` (`uq_certificates_number`, `uq_certificates_token`, virtual generated column `active_record_key` + `uq_certificates_active_user_record`, `chk_certificates_record_type`, `chk_certificates_record_alignment`, `chk_certificates_status`, and `chk_certificates_revoked`).
- **Models, Policies & Certificate PDF Engine (`CertificateService`):**
  - Created `app/Enums/CertificateStatus.php`, `app/Models/Certificate.php`, `app/Policies/CertificatePolicy.php` (strictly blocking revoked certificates from trainee downloads while permitting authorized administrator PDF inspection), and updated `User`, `Module`, `TrainingSession`, `ModulePolicy`, `TrainingSessionPolicy`, and `AppServiceProvider`.
  - Created vector emblem logo `public/images/ecohydrotech-logo.svg` and landscape A4 certificate template `resources/views/pdf/certificate.blade.php` containing trainee full name, training/module title & code, certificate reference number, completion date, issue date, organisation name & SVG logo, and authorised signatory name & designation.
  - Implemented `app/Services/Certificate/CertificateService.php` supporting configurable global settings (`certificates.issuing_organisation`, `certificates.signatory_name`, `certificates.signatory_title`, `certificates.logo_path`), issuing certificates for either completed training sessions or modules, enforcing active duplicate prevention, rendering & storing PDFs on the private `'r2'` disk (`certificates/{year}/{certificate_number}.pdf`), mandatory-reason revocation, and immutable `AuditLog` tracking (`certificate.issued`, `certificate.revoked`, `certificate.downloaded`, `certificates.signatory_updated`).
- **Trainee Certificate Portal & Dashboard:**
  - Created `app/Http/Controllers/Portal/CertificateController.php` (`GET /portal/certificates`, `GET /portal/certificates/{certificate:certificate_number}`, and `GET /certificates/{certificate:certificate_number}/download`).
  - Created `resources/views/portal/certificates/index.blade.php` and `resources/views/portal/certificates/show.blade.php`, updated `PortalDashboardController`, `portal/dashboard.blade.php` (adding **My Certificates** card and `Issued Certificates` stat card), and `components/layouts/portal.blade.php`.
- **Filament 5 Administration (`CertificateResource`):**
  - Created `CertificateResource`, `ListCertificates` (with **Configure Global Signatory** action modal), `CreateCertificate`, `ViewCertificate` (with `resources/views/filament/certificates/history.blade.php` audit history table), and updated `FoundationOverviewWidget`.
  - Created `database/seeders/CertificateSeeder.php` and `tests/Feature/Stage8CertificatesTest.php`.

### Verification result
- **Frontend Build (`npm run build`):** Succeeded via Vite `v8.3.8` (`public/build/assets/app-DLmni56x.css`).
- **Migrations & Seeders:** `docker compose exec -T -u www-data app php artisan migrate:fresh --seed` succeeded on MySQL 8.4 (`0001_01_01_000009_create_stage8_certificates_table` + `CertificateSeeder`).
- **Automated Tests (`docker compose exec -T -u www-data app php artisan test`):** `44 passed (380 assertions)` in `362.75s` against MySQL 8.4 (`etms_test`), covering session & module certificate issuance, configurable global signatory snapshots, DomPDF binary generation & private R2 storage, service + MySQL duplicate prevention (`uq_certificates_active_user_record`), trainee dashboard & portal viewing/downloading, signed link expiry rejection (`403`), mandatory-reason revocation & audit history, revoked download blocking (`403`), Filament 5 admin certificate views/actions, and MySQL `chk_certificates_revoked`.

### Next planned action
Await independent inspection and owner approval of Stage 8 before proceeding to Stage 9 (Notifications, reports and dashboards).

## 18 November 2026 — Stage 9: Notifications, Email and Administrative Reports (In Progress — Pending Review)

- **Packages, Config & Schema (`DATABASE-DESIGN.md` Domain F & Stage 9 Scope):**
  - Installed `resend/resend-php` (`v1.18.0`) via Composer and updated `.env.example` with `RESEND_API_KEY=` and `POSTMARK_API_KEY=` placeholders matching `config/services.php` and `config/mail.php`.
  - Created `database/migrations/0001_01_01_000010_create_stage9_notifications_and_announcements_tables.php` defining `notification_deliveries` (`uq_notification_deliveries_key`, `chk_notification_deliveries_status`), `announcements` (`chk_announcements_audience`, `chk_announcements_status`), and `announcement_recipients` (`uq_announcement_recipients_pair`, `chk_announcement_recipients_status`).
  - Created `app/Enums/NotificationDeliveryStatus.php` and `app/Enums/AnnouncementStatus.php`, plus models `NotificationDelivery`, `Announcement`, and `AnnouncementRecipient`.
- **Queued Email Notifications, Branded Templates & Delivery Telemetry:**
  - Created Ecohydrotech-branded transactional HTML email layout `resources/views/emails/layout.blade.php` and `resources/views/emails/etms-notification.blade.php`, plus custom branded `VerifyEmail::toMailUsing` and `ResetPassword::toMailUsing` callbacks in `AppServiceProvider`.
  - Created queued `App\Notifications\EtmsEventNotification` (`ShouldQueue`, `notifications` queue, `database` + `mail` channels, `X-ETMS-Delivery-Key` tracking header, and `failed()` hook), `App\Listeners\TrackNotificationDeliveryListener` (updating `notification_deliveries` on `MessageSent` and `NotificationFailed`), and `App\Services\Notification\EtmsNotificationService` (`notifyUser` and `dispatchAnnouncement`).
  - Wired lifecycle notifications (using `DB::afterCommit` inside database transactions) across registration, password reset & password updates (`RegisteredUserController`, `PasswordResetController`, `SecurityController`), account status changes (`UserResource`), booking confirmation/cancellation/expiry (`SessionBookingService`), payment success/failure & module purchase fulfilment (`PaymentOrchestrationService`), refund completion (`RefundService`), manual module grants (`CreateModuleEntitlement`), certificate issuance & revocation (`CertificateService`), and session update broadcasts (`TrainingSessionResource::notifyParticipants`).
  - Created `App\Console\Commands\SendSessionRemindersCommand` (`etms:send-session-reminders`) and scheduled it hourly in `routes/console.php`.
- **Trainee & Instructor In-App Notification Centres:**
  - Created `App\Http\Controllers\NotificationCentreController`, `resources/views/portal/notifications.blade.php`, and `resources/views/instructor/notifications.blade.php` supporting `All` / `Unread` filtering, mark-individual-read, mark-all-read, deep links to bookings/orders/modules/sessions/certificates, and unread sidebar counters in `components/layouts/portal.blade.php` and `components/layouts/instructor.blade.php`.
- **Administrative Reports, CSV Exports & Dashboard Upgrades:**
  - Created `App\Services\Report\ReportsService`, `App\Filament\Pages\ReportsPage` (`resources/views/filament/pages/reports-page.blade.php` at `/admin/reports`), and `App\Http\Controllers\Admin\ReportExportController` (`/admin/reports/export/{dataset}` supporting `bookings`, `revenue`, `refunds`, `popular_modules`, `instructors`, `certificates`).
  - Created `AnnouncementResource` and read-only `NotificationDeliveryResource` under `Communications & Reports`.
  - Upgraded Filament 5 Dashboard (`AdminPanelProvider`) with `FoundationOverviewWidget` (`MYR` net/gross/refunded revenue, customers, sessions/modules, certificates & email telemetry), `MonthlyRevenueChart` (`bar`), `DeliveryMixChart` (`doughnut`), and `RecentOperationsWidget` (`filament/widgets/recent-operations-widget.blade.php`).
  - Created `tests/Feature/Stage9NotificationsAndReportsTest.php`.

### Verification result
- **Frontend Build (`npm run build`):** Succeeded via Vite `v8.3.8` (`public/build/assets/app-BhY2ypjk.css`).
- **Migrations & Seeders:** `docker compose exec -T -u www-data app php artisan migrate:fresh --seed` succeeded on MySQL 8.4 (`0001_01_01_000010_create_stage9_notifications_and_announcements_tables` + seeders).
- **Automated Tests (`docker compose exec -T -u www-data app php artisan test`):** `48 passed (462 assertions)` in `399.31s` against MySQL 8.4 (`etms_test`), covering branded queued email rendering, `notification_deliveries` queued/sent/failed telemetry, lifecycle triggers across bookings/payments/refunds/modules/certificates/accounts, idempotent `etms:send-session-reminders`, Trainee & Instructor notification centres & ownership isolation, announcements, MYT date-filtered reports & all 6 CSV exports, and Filament 5 Dashboard / `ReportsPage` / `AnnouncementResource` / `NotificationDeliveryResource` rendering.

### Next planned action
Await independent inspection and owner approval of Stage 9 before proceeding to Stage 10 (Security, UAT, deployment and backup).

## 18 November 2026 — Stage 10: Final Testing, Security Hardening and Production Deployment (Complete — Ready for Server Cutover)

- **Company Server Private Storage & Migration Off Cloudflare R2 / Bunny Stream:**
  - Updated `config/filesystems.php` and `config/etms.php` to replace the external `r2` disk with four isolated private local disks under configurable `ETMS_STORAGE_ROOT` (`materials`, `videos`, `recordings`, and `certificates`), all set to `'visibility' => 'private'` and `'serve' => false`. Removed `R2_*` and `bunny_stream` config from `.env.example`, `.env.production.example`, and `config/services.php`, and removed `app/Services/Media/BunnyStreamService.php`.
  - Created `database/migrations/0001_01_01_000011_stage10_server_storage_and_self_hosted_video.php` transitioning default storage disks (`materials`, `certificates`), adding self-hosted storage/HLS columns (`storage_disk`, `source_storage_key`, `hls_manifest_key`, `original_filename`, `file_size_bytes`, `mime_type`, `processing_error`) to `module_videos` and `session_recordings`, and updating `chk_module_videos_provider` / `chk_session_recordings_provider` to default to `'local_hls'`.
  - Updated `ModuleMaterial`, `ModuleMaterialResource`, `MaterialsRelationManager`, `MaterialDownloadController`, `CertificateService`, `CertificateController`, `CatalogueSeeder`, and `SessionAndBookingSeeder` to read/write exclusively on private server storage directories.
- **Self-Hosted FFmpeg HLS Video Pipeline & Authenticated Streaming:**
  - Installed `ffmpeg` (`7:5.1.9-0+deb12u1`) in the base runtime container image and installed `hls.js` in `package.json` + `resources/js/app.js`.
  - Created `app/Services/Media/SelfHostedVideoService.php` (`transcodeToHls`, `inspectDuration`, `rewriteManifestWithSignedAssets`, HMAC token signing, and Nginx `X-Accel-Redirect` / range-capable `BinaryFileResponse` private asset delivery) and queued `app/Jobs/ProcessVideoToHlsJob.php` (`videos` queue).
  - Updated `LearningPortalController`, `portal/modules/show.blade.php`, `portal/recordings/watch.blade.php`, `ModuleVideoResource` (`CreateModuleVideo`, `EditModuleVideo`), `SessionRecordingResource` (`CreateSessionRecording`, `EditSessionRecording`), `InstructorSessionController::storeRecording`, and `instructor/sessions/recordings.blade.php` for direct video uploads (up to 2 GB via `config/livewire.php` + PHP/Nginx config), FFmpeg HLS conversion, signed `.m3u8` / `.ts` segment delivery, and preserved module entitlement and `attendees_only` access policies.
- **Security Hardening, Production Docker & Backups:**
  - Created `app/Http/Middleware/SecurityHeadersMiddleware.php`, configured proxy trust (`trustProxies`), HTTPS scheme forcing in production (`URL::forceScheme('https')`), strict upload allowlists, and named rate limiters (`auth-register`, `password-reset`, `downloads`, `video-streams`, `webhooks`) in `AppServiceProvider`, `bootstrap/app.php`, and `routes/web.php`.
  - Added multi-target `Dockerfile` (`base`, `node_assets`, `production`, `development`), `docker-compose.prod.yml` (`restart: always`, TLS ports `80`/`443`, `etms_storage`, `etms_public_storage`), `docker/nginx/production.conf` (TLSv1.2/TLSv1.3, HSTS, ACME pass-through, internal `/_etms_internal_storage/` X-Accel-Redirect, sensitive file deny rules), `docker/php/php.prod.ini`, `docker/php/entrypoint.prod.sh`, and `.env.production.example`.
  - Implemented `App\Services\Operations\BackupService`, `App\Console\Commands\BackupEtmsCommand` (`etms:backup` scheduled daily at `02:30` MYT in `routes/console.php`), `App\Console\Commands\ProductionReadinessCheckCommand` (`etms:production-check`), standalone `docker/scripts/backup.sh` and `docker/scripts/restore.sh`, and `docs/PRODUCTION-DEPLOYMENT-RUNBOOK.md`.
  - Updated Stages 4, 5, 7, 8, and 9 tests for server storage / HLS streaming and added `tests/Feature/Stage10ProductionAndServerStorageTest.php`.

### Verification result
- **Frontend Asset Build (`npm run build`):** Succeeded via Vite `v8.3.8` (`public/build/assets/app-DOplYFh2.css` `86.88 kB` and `public/build/assets/app-BCSbm_7X.js` `578.62 kB` including `hls.js`).
- **Docker Images & Restart Resilience:** Rebuilt `etms-app:latest` (`development`) and built immutable production image `etms-app:prod` (`docker build --target production` with Composer `--no-dev --optimize-autoloader --classmap-authoritative` and baked Vite assets). Restarted `nginx`, `app`, `queue`, and `scheduler`; verified all 6 containers healthy, `/up` healthy, `/.env` blocked (`403`), and `/_etms_internal_storage/...` blocked (`404`).
- **Production Caching & Readiness (`php artisan optimize` + `filament:cache-components` + `etms:production-check`):** Config, events, routes, views, blade-icons, and Filament component caches compiled cleanly; MySQL, Redis, `ffmpeg`, `ffprobe`, all 4 private server storage disks, and isolated backup directory passed readiness checks.
- **Full Automated Test Suite (`docker compose exec -T -u www-data app php artisan test`):** **`52 passed (538 assertions)`** in `208.67s` across Unit + Stages 3–10 on MySQL 8.4 (`etms_test`).

### Production Server Cutover Note
- Deployment artefacts (`docker-compose.prod.yml`, `.env.production.example`, `docker/nginx/production.conf`, `docker/scripts/backup.sh`, `docker/scripts/restore.sh`, and `docs/PRODUCTION-DEPLOYMENT-RUNBOOK.md`) are prepared and locally verified; live company-server provisioning (domain DNS, TLS certificates in `docker/certs/`, live Billplz keys, Resend API key, and host cron installation) is ready for execution on the target production host.
