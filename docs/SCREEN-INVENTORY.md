# ETMS Screen Inventory & Component Specification

| Property | Value |
|---|---|
| **Project** | Ecohydrotech Training Management System (ETMS) |
| **Stage** | Stage 2 — UI/UX Blueprint & Application Design (Pending Review) |
| **Version** | 1.0 |
| **Date** | 17 November 2026 |
| **Total Screens Mapped** | **51 Screens** (10 Public/Auth, 14 Trainee/Checkout, 7 Instructor, 20 Filament 5 Admin) |
| **Companion Documents** | [UI/UX Blueprint](UI-UX-BLUEPRINT.md), [User Flows & Navigation](USER-FLOWS.md), [Database Design](DATABASE-DESIGN.md) |

> **Implementation Convention:** Named routes, Blade/Livewire 4 classes, Filament 5 resources, and validation bindings below define the canonical Stage 3+ contract. Every database field references exact column names from `docs/DATABASE-DESIGN.md`.

---

## 1. Master Screen Index

| ID | Experience | Screen Name | Route Pattern | Named Route |
|---|---|---|---|---|
| `PUB-01` | Public | Homepage | `/` | `home` |
| `PUB-02` | Public | Training Catalogue | `/training` | `catalogue.index` |
| `PUB-03` | Public | Module Details | `/training/{module:slug}` | `catalogue.modules.show` |
| `PUB-04` | Public | Physical Sessions Directory & Detail | `/sessions` & `/sessions/{session:slug}` | `sessions.index`, `sessions.show` |
| `PUB-05` | Public | Instructor Directory & Profile | `/instructors` & `/instructors/{instructor}` | `instructors.index`, `instructors.show` |
| `PUB-06` | Public | Certificate Verification (Lookup & QR) | `/verify` & `/verify/{token?}` | `certificates.verify` |
| `PUB-07` | Public Auth | Customer Registration | `/register` | `register` |
| `PUB-08` | Public Auth | Login & Admin 2FA Challenge | `/login` & `/two-factor-challenge` | `login`, `two-factor.login` |
| `PUB-09` | Public Auth | Forgot & Reset Password | `/forgot-password` & `/reset-password/{token}` | `password.request`, `password.reset` |
| `PUB-10` | Public Auth | Email Verification Notice | `/email/verify` | `verification.notice` |
| `TRN-01` | Trainee Portal | Trainee Dashboard | `/portal` | `portal.dashboard` |
| `TRN-02` | Trainee Portal | Profile & Company Billing Settings | `/portal/profile` | `portal.profile.edit` |
| `TRN-03` | Trainee Portal | Account Security & Password | `/portal/security` | `portal.security.edit` |
| `TRN-04` | Checkout | Physical Session Checkout | `/checkout/session/{session:slug}` | `checkout.session.create` |
| `TRN-05` | Checkout | Independent Module Checkout | `/checkout/module/{module:slug}` | `checkout.module.create` |
| `TRN-06` | Checkout | Billplz Read-Only Return Status | `/checkout/return` | `checkout.return` |
| `TRN-07` | Trainee Portal | Bookings & Session History | `/portal/bookings` | `portal.bookings.index` |
| `TRN-08` | Trainee Portal | Booking Detail | `/portal/bookings/{booking:booking_reference}` | `portal.bookings.show` |
| `TRN-09` | Trainee Portal | Orders & Payment History | `/portal/orders` | `portal.orders.index` |
| `TRN-10` | Trainee Portal | Order & Receipt Detail | `/portal/orders/{order:order_number}` | `portal.orders.show` |
| `TRN-11` | Trainee Portal | Purchased Modules Library | `/portal/modules` | `portal.modules.index` |
| `TRN-12` | Trainee Portal | Module Learning Workspace | `/portal/modules/{module:slug}` | `portal.modules.learn` |
| `TRN-13` | Trainee Portal | Authorised Session Recordings | `/portal/recordings` | `portal.recordings.index` |
| `TRN-14` | Trainee Portal | Certificates & Notifications | `/portal/certificates` & `/portal/notifications` | `portal.certificates.index`, `portal.notifications.index` |
| `INS-01` | Instructor Portal | Instructor Dashboard | `/instructor` | `instructor.dashboard` |
| `INS-02` | Instructor Portal | Assigned Training Sessions | `/instructor/sessions` | `instructor.sessions.index` |
| `INS-03` | Instructor Portal | Session Detail & Participant Roster | `/instructor/sessions/{session:session_code}` | `instructor.sessions.show` |
| `INS-04` | Instructor Portal | Attendance Recording & Submission | `/instructor/sessions/{session:session_code}/attendance` | `instructor.sessions.attendance` |
| `INS-05` | Instructor Portal | Session Completion Submission | `/instructor/sessions/{session:session_code}/completion` | `instructor.sessions.completion` |
| `INS-06` | Instructor Portal | Session Recording Submission | `/instructor/sessions/{session:session_code}/recordings` | `instructor.sessions.recordings` |
| `INS-07` | Instructor Portal | Instructor Public Profile Editor | `/instructor/profile` | `instructor.profile.edit` |
| `ADM-01` | Admin (Filament 5) | Admin Dashboard | `/admin` | `filament.admin.pages.dashboard` |
| `ADM-02` | Admin (Filament 5) | Categories Resource | `/admin/categories` | `filament.admin.resources.categories.*` |
| `ADM-03` | Admin (Filament 5) | Modules Resource | `/admin/modules` | `filament.admin.resources.modules.*` |
| `ADM-04` | Admin (Filament 5) | Module Materials Resource | `/admin/module-materials` | `filament.admin.resources.module-materials.*` |
| `ADM-05` | Admin (Filament 5) | Module Videos Resource | `/admin/module-videos` | `filament.admin.resources.module-videos.*` |
| `ADM-06` | Admin (Filament 5) | Training Sessions Resource | `/admin/training-sessions` | `filament.admin.resources.training-sessions.*` |
| `ADM-07` | Admin (Filament 5) | Attendance Review Resource | `/admin/attendance` | `filament.admin.resources.attendances.*` |
| `ADM-08` | Admin (Filament 5) | Session Recordings Resource | `/admin/session-recordings` | `filament.admin.resources.session-recordings.*` |
| `ADM-09` | Admin (Filament 5) | Certificates Resource | `/admin/certificates` | `filament.admin.resources.certificates.*` |
| `ADM-10` | Admin (Filament 5) | Verification Telemetry Resource | `/admin/certificate-verifications` | `filament.admin.resources.certificate-verifications.*` |
| `ADM-11` | Admin (Filament 5) | Bookings Resource | `/admin/bookings` | `filament.admin.resources.bookings.*` |
| `ADM-12` | Admin (Filament 5) | Orders Resource | `/admin/orders` | `filament.admin.resources.orders.*` |
| `ADM-13` | Admin (Filament 5) | Payments Resource | `/admin/payments` | `filament.admin.resources.payments.*` |
| `ADM-14` | Admin (Filament 5) | Refunds Resource | `/admin/refunds` | `filament.admin.resources.refunds.*` |
| `ADM-15` | Admin (Filament 5) | Payment Webhooks Resource | `/admin/payment-webhook-events` | `filament.admin.resources.payment-webhook-events.*` |
| `ADM-16` | Admin (Filament 5) | Module Entitlements Resource | `/admin/module-entitlements` | `filament.admin.resources.module-entitlements.*` |
| `ADM-17` | Admin (Filament 5) | Users & Profiles Resource | `/admin/users` | `filament.admin.resources.users.*` |
| `ADM-18` | Admin (Filament 5) | Instructor Profiles Resource | `/admin/instructor-profiles` | `filament.admin.resources.instructor-profiles.*` |
| `ADM-19` | Admin (Filament 5) | Announcements & Reports Hub | `/admin/announcements`, `/admin/reports` | `filament.admin.resources.announcements.*`, `filament.admin.pages.reports` |
| `ADM-20` | Admin (Filament 5) | System Settings & Audit Logs | `/admin/system-settings`, `/admin/audit-logs` | `filament.admin.pages.system-settings`, `filament.admin.resources.audit-logs.*` |

---

## 2. Experience A — Public Website & Authentication (`PUB-01` – `PUB-10`)

### `PUB-01` — Homepage
- **Route:** `GET /` (`home`) | **Component:** `HomePageController` (Blade + Livewire quick verify bar) | **Layout:** `layouts.public` | **Access:** Public
- **Tables Bound:** `categories` (`is_active = true`), `modules` (`status = 'published'`), `training_sessions` (`status IN ('published','full')`, `starts_at > UTC_TIMESTAMP()`).
- **Layout Regions:**
  - *Hero Section:* Headline positioning Ecohydrotech Solution's hydrology & environmental engineering training; dual primary CTAs (`Explore Training Catalogue`, `Upcoming Physical Sessions`).
  - *Dual Delivery Explainer Band:* Side-by-side comparison cards clarifying `D-018` (*Physical Training Sessions*: instructor-led in-person, verified attendance, official certificate vs. *Self-Paced Modules*: lifetime R2 documents & Bunny videos, instant access).
  - *Upcoming Physical Sessions Strip:* Top 3 chronological `<x-eht.session-card>` items showing live remaining seats.
  - *Category Directory Grid:* Active `categories` cards linking into `/training?category={slug}`.
  - *Featured Modules Grid:* Top 3–6 `<x-eht.module-card>` items.
  - *Inline Certificate Quick-Verify Strip:* Input for `certificate_number` submitting directly to `/verify`.
- **Empty State:** If zero upcoming physical sessions exist, hide/replace the Upcoming Sessions strip with *"New physical training dates are being scheduled — browse self-paced modules or check back soon."*

### `PUB-02` — Training Catalogue
- **Route:** `GET /training` (`catalogue.index`) | **Component:** `App\Livewire\Public\TrainingCatalogue` | **Layout:** `layouts.public` | **Access:** Public
- **Tables Bound:** `categories` (`id`, `name`, `slug`), `modules` (`code`, `title`, `slug`, `summary`, `cover_image_path`, `is_available_independently`, `independent_price`, `currency`, `status`), aggregated upcoming `training_sessions` count and minimum `price`.
- **Layout & Controls:**
  - *Top Filter Bar + Left Sidebar (`lg+`):* Search input (`wire:model.live.debounce.300ms="search"` matching `code` or `title`), Category radio list (`All Categories` + active categories), Delivery Model checkboxes (`Upcoming Physical Sessions`, `Independent Self-Paced`), Sort dropdown (`Newest`, `Title A–Z`, `Lowest Price`).
  - *Results Grid:* 1 col mobile, 2 col `md`, 3 col `xl` of `<x-eht.module-card>` + Livewire pagination.
  - *Active Filter Chips:* Clear-all reset button when filters reduce results to zero.

### `PUB-03` — Module Details
- **Route:** `GET /training/{module:slug}` (`catalogue.modules.show`) | **Component:** `ModuleShowController` + `layouts.public` | **Access:** Public (404 if `status != 'published'`).
- **Tables Bound:** `modules`, `categories`, `module_materials` (count & titles only where `status = 'published'`, never storage keys), `module_videos` (count, titles & `duration_seconds` sum only where `status = 'published'`, never `provider_video_id`), `training_sessions` (published/full upcoming sessions for this module), `module_entitlements` (checks if authenticated user already holds `active` entitlement).
- **Layout Regions:**
  - *Left 8-Col Content:* Breadcrumb (`Home > Training > {category} > {module.code}`), cover banner, `modules.description`, structured `learning_objectives` checklist, **Module Curriculum Preview** (locked icon rows listing included materials & video durations).
  - *Right 4-Col Sticky Conversion Stack (`D-018` Strict Separation):*
    1. **Physical Session Card:** Lists upcoming scheduled sessions (`session_code`, MYT dates, city/state, seats remaining, `RM {price}`) with `View Session & Book` button per session.
    2. **Independent Self-Paced Card** (if `is_available_independently = true`): Displays `RM {independent_price}`, `Lifetime Access` badge, bullet list of included materials/videos, and CTA:
       - If user already has `active` `module_entitlements`: Emerald badge *"You own Lifetime Access"* + `Go to Learning Workspace` button (`route('portal.modules.learn', $module)`).
       - Else if guest: `Sign In to Purchase Module` -> `/login?intended=...`.
       - Else: `Buy Module (RM {independent_price})` -> `route('checkout.module.create', $module)`.
    - Explicit disclaimer below both cards: *"Purchasing the self-paced module does not register you for a physical session. Module certificate eligibility is managed separately through administrator approval."*

### `PUB-04` — Physical Sessions Directory & Detail
- **Routes:** `GET /sessions` (`sessions.index`), `GET /sessions/{session:slug}` (`sessions.show`) | **Layout:** `layouts.public` | **Access:** Public (404 if `status = 'draft'`).
- **Tables Bound:** `training_sessions`, `modules`, `session_instructors`, `instructor_profiles` (`is_public = true`), `users`, `bookings` (for current user active booking check).
- **Detail Screen Layout:**
  - *Header:* `session_code` badge, `training_sessions.status` badge, title, parent module link.
  - *Left 8-Col Logistics:* Schedule block (`starts_at`–`ends_at` in `Asia/Kuala_Lumpur`, derived `duration_minutes`), Venue block (`venue_name`, `venue_address`, city/state), Session Outline (`training_sessions.description`), and **Assigned Instructors** cards (filtered to `instructor_profiles.is_public = true`, linking to `PUB-05`).
  - *Right 4-Col Booking Summary Card:*
    - Price (`RM {price}`), Seat Occupancy Progress Bar (`reserved + confirmed / capacity`), `registration_closes_at` countdown/deadline (`D-029`), Recording Policy note (`D-017`: *"Includes authorised post-session recording playback upon verified physical attendance"* if `attendees_only`), and Booking CTA:
      - If user already has `confirmed` or `pending_payment` booking: Displays status + link to `TRN-08` or `TRN-04` resume payment.
      - If `status = 'published'` and `UTC_TIMESTAMP() < registration_closes_at` and within `registration_opens_at`: `Book Physical Session` (`route('checkout.session.create', $session)`).
      - If `full`: Disabled `Session Full` badge + note that temporary unpaid 15-min holds may reopen.
      - If `registration_closed` / `in_progress` / `completed` / `cancelled`: Disabled status state explaining registration is closed.

### `PUB-05` — Instructor Directory & Profile
- **Routes:** `GET /instructors` (`instructors.index`), `GET /instructors/{instructor}` (`instructors.show`) | **Layout:** `layouts.public` | **Access:** Public (`404` if `is_public = false` or user lacks active `instructor` role).
- **Tables Bound:** `instructor_profiles`, `users`, `session_instructors`, `training_sessions`.
- **Components:** Profile header (signed/public photo or initials avatar, `display_name` fallback `users.name`, `professional_title`, `qualifications`, `specialisations` chips), full `biography`, and **Upcoming Sessions Taught by This Instructor** list.

### `PUB-06` — Certificate Verification (Lookup & QR)
- **Routes:** `GET /verify` & `GET /verify/{token?}` (`certificates.verify`) | **Component:** `App\Livewire\Public\VerifyCertificate` | **Layout:** `layouts.public` | **Access:** Public (throttled `30/min` per IP).
- **Tables Bound:** `certificates` (`certificate_number`, `verification_token`, `status`, `trainee_name_snapshot`, `training_title_snapshot`, `training_start_date_snapshot`, `training_end_date_snapshot`, `issuing_organisation_snapshot`, `issued_at`, `revoked_at`, `superseded_by_certificate_id`), `certificate_verifications` (write-only audit row on every lookup).
- **Form & Validation:** Single search input `certificate_number` (`required|string|max:50`, e.g., `EHT-2026-000123`) when not arriving via QR `token`.
- **Result Card States (`BR-13` Data Minimisation):**
  - `valid` (`status = 'issued'`): Emerald shield banner **VALID OFFICIAL CERTIFICATE**, showing only `trainee_name_snapshot`, `training_title_snapshot`, training dates, `issuing_organisation_snapshot`, `certificate_number`, `issued_at`.
  - `revoked` (`status = 'revoked'`): Rose warning banner **CERTIFICATE REVOKED**, showing `certificate_number`, `revoked_at`, and notice if a replacement certificate exists (`superseded_by_certificate_id` link). Does **not** expose internal `revocation_reason` publicly unless policy permits; displays *"This certificate is no longer valid."*
  - `pending` (`status = 'pending'`): Amber banner **PROCESSING / NOT YET ISSUED**.
  - `not_found`: Neutral/Rose banner **NO MATCHING CERTIFICATE FOUND** with formatting hint.

### `PUB-07` – `PUB-10` — Authentication & Verification Screens
- **`PUB-07` Registration (`/register`):** Centered card (`max-w-lg`). Fields: `name` (`required|max:150`), `email` (`required|email|unique:users`), `phone` (`nullable|max:30`), `password` (`required|confirmed|min:8`), `customer_type` toggle (`individual` / `company`). If `company`: reveals optional `company_name`, `company_registration_no`, `billing_contact_name`, `billing_email`, `billing_address_line_1..2`, `billing_city`, `billing_state`, `billing_postcode`. Assigns `customer` role and creates `customer_profiles` row; dispatches verification email (`PUB-10`).
- **`PUB-08` Login & 2FA Challenge (`/login`, `/two-factor-challenge`):** Email, Password, Remember Me, Forgot Password link. Rate-limited (`5/min`). Redirects based on `intended` URL or primary role (`/admin`, `/instructor`, `/portal`). If `administrator` has `two_factor_confirmed_at`, routes to TOTP 6-digit code / recovery code challenge (`/two-factor-challenge`). Displays clear error if `users.status` is `suspended` or `deactivated`.
- **`PUB-09` Forgot & Reset Password (`/forgot-password`, `/reset-password/{token}`):** Uses `password_reset_tokens` table; standard Laravel password reset flow.
- **`PUB-10` Email Verification Notice (`/email/verify`):** Prompts user to verify email (`users.email_verified_at`) before checkout or portal access; includes *Resend Verification Email* (`throttle:6,1`).

---

## 3. Experience B — Trainee / Customer Portal & Checkout (`TRN-01` – `TRN-14`)

*Middleware for all `TRN-*`: `auth`, `verified`, `active.account`, `role:customer`.*

### `TRN-01` — Trainee Dashboard
- **Route:** `GET /portal` (`portal.dashboard`) | **Layout:** `layouts.portal`
- **Tables Bound:** `bookings`, `training_sessions`, `orders`, `module_entitlements`, `certificates`, `notifications`.
- **Regions:**
  1. *Active Hold Alert Banner (if `bookings.status = 'pending_payment'` and `reservation_expires_at > now`):* Live Alpine countdown (`MM:SS`) + `Complete Payment on Billplz` button (`payments.gateway_payment_url`).
  2. *4 KPI Cards:* Upcoming Confirmed Sessions, Active Modules (`Lifetime`), Issued Certificates, Unread Notifications.
  3. *Upcoming Physical Sessions Card:* Next 2 confirmed sessions with venue, date/time MYT, and `View Session Details` link.
  4. *Recently Purchased Modules Card:* Quick links into `TRN-12` learning workspace.

### `TRN-02` & `TRN-03` — Profile, Company Billing & Account Security
- **`TRN-02` (`/portal/profile`):** Livewire form updating `users.name`, `customer_profiles.customer_type`, `phone`, `job_title`, and optional company billing fields (`company_name`, `company_registration_no`, `billing_*`). Note clarifying that editing company billing applies to **future** orders (`orders` snapshots remain immutable) and updating `users.name` does not alter already-issued certificates (`certificates.trainee_name_snapshot`).
- **`TRN-03` (`/portal/security`):** Update password (`current_password`, `password`, `password_confirmation`) and active session sign-out across other browsers (`sessions` table).

### `TRN-04` — Physical Session Checkout
- **Route:** `GET /checkout/session/{session:slug}` (`checkout.session.create`) | **Component:** `App\Livewire\Checkout\SessionCheckout` | **Layout:** `layouts.checkout`
- **Tables Bound:** `training_sessions`, `modules`, `customer_profiles`, `orders`, `order_items`, `bookings`, `payments`.
- **Pre-Render Guard:** Rejects with clear state banner if session is not `published`, registration closed (`D-029`), capacity reached, or user already holds a `confirmed` booking (`uq_bookings_active_user_session`). If user already holds an active `pending_payment` booking for this exact session, loads that existing hold & countdown instead of creating a duplicate.
- **Layout (8 / 4 Split):**
  - *Left Column:* Attendee Confirmation Box (locked `users.name` and `users.email` per `D-023` — *"Booking is registered solely for your account"*), Billing Information form (prefilled from `customer_profiles`, toggleable `individual` vs `company`), Terms & Seat Hold Notice (`D-019`: *"Clicking Proceed reserves your seat for {hold_minutes} minutes while completing Billplz payment"*).
  - *Right Sticky Summary:* Module & Session title, `session_code`, schedule (MYT), venue, single quantity (`1 Seat`), total `RM {session.price}` (`MYR`), primary button `Proceed to Billplz Payment` (`wire:loading.attr="disabled"`).
- **Transaction Action:** Executes `SessionCheckoutService` (`SELECT ... FROM training_sessions WHERE id = ? FOR UPDATE`, inline stale-hold sweep, creates `orders` [`session_booking`], `order_items` [`training_session`], `payments` [`pending`, `payment_reference`], `bookings` [`pending_payment`], recomputes counters; commits; calls Billplz Create Bill outside DB lock; redirects to `payments.gateway_payment_url`).

### `TRN-05` — Independent Module Checkout
- **Route:** `GET /checkout/module/{module:slug}` (`checkout.module.create`) | **Component:** `App\Livewire\Checkout\ModuleCheckout` | **Layout:** `layouts.checkout`
- **Tables Bound:** `modules`, `customer_profiles`, `orders`, `order_items`, `payments`, `module_entitlements`.
- **Pre-Render Guard:** Rejects if `is_available_independently = false`, `status != 'published'`, or user already has an active `module_entitlements` row for this module (redirects to `TRN-12` with *"You already own lifetime access to this module"*).
- **Layout:** Same clean checkout shell as `TRN-04` without seat hold countdown. Summary explicitly states `Order Type: Independent Module Purchase`, `Lifetime Access`, and `Does not include physical session registration or certificate` (`D-018`). Commits `orders` (`module_purchase`), `order_items` (`module`), `payments` (`pending`), calls Billplz Create Bill, redirects externally.

### `TRN-06` — Billplz Read-Only Return Status
- **Route:** `GET /checkout/return` (`checkout.return`) | **Component:** `App\Livewire\Checkout\CheckoutReturnStatus` | **Layout:** `layouts.checkout`
- **Tables Bound (Read-Only):** `orders`, `payments`, `bookings`, `module_entitlements`.
- **Strict Security Contract (`FR-05`, Sec. 6.2):** **Never updates database status from GET redirect parameters.** Looks up order/payment owned by `auth()->id()` matching Billplz query `billplz[id]` (`gateway_bill_id`).
- **UI States (`wire:poll.3s` while `orders.status = 'pending_payment'`):**
  - `pending_payment`: Teal spinner card *"Confirming payment with Billplz..."* + auto-poll up to 10 cycles (30s) + manual Refresh button.
  - `paid` (Session): Emerald success card with `order_number`, `booking_reference`, session details, and CTAs (`View Booking`, `View Receipt`).
  - `paid` (Module): Emerald success card with `order_number`, `Lifetime Access Activated` badge, and CTA (`Start Learning Now` -> `TRN-12`).
  - `failed` / `expired` / `cancelled`: Clear amber/rose card explaining why payment was not completed, noting seat hold release if expired, and offering `Return to Session/Module` to retry if still eligible.
  - `late payment -> refund requested`: Informative card if `paid` order resulted in `refunds.status = 'requested'` due to expired hold & full/closed session.

### `TRN-07` & `TRN-08` — Bookings & Booking Detail
- **`TRN-07` (`/portal/bookings`):** Filterable tabs (`All`, `Upcoming Confirmed`, `Pending Payment`, `Past / Attended`, `Expired / Cancelled / Refunded`). Columns (`md+`) / Stacked Cards (`< md`): `booking_reference`, Session Title & Code, Date (MYT), **Booking Status Badge**, **Attendance Status Badge** (`present`/`absent`/`excused`/`pending`), **Certificate Status** (if issued), Actions (`View Details`, `Resume Payment` if `pending_payment`).
- **`TRN-08` (`/portal/bookings/{booking:booking_reference}`):** 2-column detail page showing Booking timeline (`reserved_at`, `confirmed_at`, `expired_at`/`cancelled_at`), Linked Order (`order_number`), Session Logistics (venue, map URL, PPE notes, assigned public instructors), **Attendance & Verification Panel** (`attendance_status` + `review_status` clearly separated per `D-025`), Direct links to **Session Recordings** (`TRN-13`, unlocked if eligible) and **Certificate** (`TRN-14`, if issued). Explicit note: *"Booking cancellations are managed by Ecohydrotech administration (`D-021`)."*

### `TRN-09` & `TRN-10` — Orders, Payments & Printable Receipt
- **`TRN-09` (`/portal/orders`):** Table of `orders` showing `order_number`, `order_type` badge (`Physical Session` vs `Self-Paced Module`), item title snapshot, `total_amount` (`MYR`), `orders.status`, `created_at` (MYT), and `View Receipt` action.
- **`TRN-10` (`/portal/orders/{order:order_number}`):** Printable formal Receipt layout showing Ecohydrotech Solution header, `order_number`, `paid_at`, Buyer & Company Billing Snapshot (`orders.customer_*`, `orders.company_name`, `orders.billing_*`), Itemised Line Table (`order_items.*_snapshot`), Payment Attempts log (`payments.payment_reference`, `gateway_bill_id`, `payment_method`, `status`), Refund history (`refunds.refund_reference`, `amount`, `status` if any), and `Print / Save Receipt` button (`window.print()` with `@media print` stylesheet).

### `TRN-11` & `TRN-12` — Purchased Modules & Learning Workspace
- **`TRN-11` (`/portal/modules`):** Grid of active `module_entitlements` cards (`Lifetime Access` badge, `granted_at` MYT, `grant_source` pill: `Purchased` vs `Admin Grant`, material/video counts, `Open Module` button).
- **`TRN-12` (`/portal/modules/{module:slug}`):** Policy checks active `module_entitlements` for `auth()->id()` & `module_id` (`403` otherwise).
  - *Main Stage:* Currently selected `module_videos` Bunny Stream signed embed player (`encoding_status = 'ready'`) OR document preview card.
  - *Curriculum Sidebar:* Ordered `module_videos` (`status = 'published'`, formatted duration) and `module_materials` (`status = 'published'`, file type icon, size in MB, `Download` button calling short-lived signed Cloudflare R2 route if `is_downloadable = true`).

### `TRN-13` — Authorised Session Recordings
- **Route:** `GET /portal/recordings` (`portal.recordings.index`) | **Layout:** `layouts.portal`
- **Tables Bound:** `bookings`, `training_sessions`, `attendance`, `session_recordings`.
- **Policy & UX (`D-017`):** Displays confirmed past/completed sessions that have `session_recordings.status = 'published'`.
  - **Unlocked State** (`recording_access_policy = 'attendees_only'` AND `attendance.attendance_status = 'present'` AND `attendance.review_status = 'verified'`): Renders playable Bunny Stream signed video card(s) with duration and title.
  - **Locked State** (confirmed booking exists, but attendance is `pending`/`absent`/`excused` or `review_status != 'verified'`, or `recording_access_policy = 'none'`): Displays locked preview card explaining exact eligibility requirement (`D-017`).

### `TRN-14` — Certificates & Notifications
- **Certificates (`/portal/certificates`):** Lists `certificates` for `auth()->id()`. Each card displays `certificate_number`, `training_title_snapshot`, `training_start_date_snapshot`–`end_date_snapshot`, `status` + `pdf_generation_status` badges, `Download PDF` (signed R2 route when `status = 'issued'`), `View QR Code` modal (renders QR + public `/verify/{token}` copy link), and replacement link if `revoked` & superseded (`superseded_by_certificate_id`).
- **Notifications (`/portal/notifications`):** Combined feed of `notifications` and `announcement_recipients` with `Unread` / `All` toggle, deep links (e.g., to newly issued certificate or session update), and *Mark as Read* / *Mark All Read* actions.

---

## 4. Experience C — Instructor Portal (`INS-01` – `INS-07`)

*Middleware for all `INS-*`: `auth`, `verified`, `active.account`, `role:instructor`. Every session sub-route verifies `session_instructors` membership (`SessionInstructorPolicy`).*

### `INS-01` — Instructor Dashboard
- **Route:** `GET /instructor` (`instructor.dashboard`) | **Layout:** `layouts.instructor`
- **Tables Bound:** `session_instructors`, `training_sessions`, `attendance`, `session_recordings`.
- **Widgets:**
  1. *Attention Required Banner:* Highlights any session with `attendance.review_status = 'correction_requested'` or `in_progress` today.
  2. *3 Stat Cards:* Assigned Upcoming/Active Sessions, Attendance Rosters Pending Submission, Recordings Awaiting Admin Review.
  3. *Assigned Sessions Quick List* with direct jump links to `Mark Attendance`.

### `INS-02` — Assigned Training Sessions
- **Route:** `GET /instructor/sessions` (`instructor.sessions.index`) | **Layout:** `layouts.instructor`
- **Controls:** Filter tabs (`Upcoming & Active`, `Needs Attendance / Completion`, `Completed`, `Cancelled`), search by `session_code` or title.
- **Columns:** `session_code`, Title & Module, Date Range (MYT), Venue City, Confirmed Trainees Count (`confirmed_seats_count`), `role_label` (`Lead` / `Co-Instructor`), Session Status, Attendance Review Summary badge, and `Open Workspace` CTA.

### `INS-03` — Session Detail & Participant Roster
- **Route:** `GET /instructor/sessions/{session:session_code}` (`instructor.sessions.show`) | **Layout:** `layouts.instructor` + Sub-Tab Header (`Overview`, `Participants`, `Attendance`, `Completion`, `Recordings`).
- **Privacy Guard (SRS Sec. 2.2):** Displays confirmed participants (`bookings.status = 'confirmed'`) with `#`, Trainee Name (`users.name`), Optional Company (`customer_profiles.company_name`), `booking_reference`, and current `attendance_status`. **Never** displays `orders.total_amount`, `payments`, `refunds`, or billing addresses. Includes **Print Sign-In Sheet** (`@media print` table with signature column).

### `INS-04` — Attendance Recording & Submission
- **Route:** `GET /instructor/sessions/{session:session_code}/attendance` (`instructor.sessions.attendance`) | **Component:** `App\Livewire\Instructor\SessionAttendanceSheet` | **Layout:** `layouts.instructor`
- **Tables Bound:** `training_sessions`, `bookings` (`confirmed`), `attendance`, `audit_logs`.
- **Interactive Controls:**
  - *Session Gate:* Editable only when `training_sessions.status IN ('in_progress', 'completed')` and rows have `review_status IN ('draft', 'correction_requested')`.
  - *Live Summary Bar:* `Confirmed: N` | `Present: X` | `Absent: Y` | `Excused: Z` | `Unmarked (Pending): W`.
  - *Correction Alert Banner:* Shown if any row has `review_status = 'correction_requested'`, displaying Admin's `verification_notes`.
  - *Per-Trainee Row (`md+`) / Touch Card (`< md`):* Trainee Name + `booking_reference` + 3 large segmented touch buttons (`min-h-11`): **Present** (`emerald`), **Absent** (`rose`), **Excused** (`amber`) — auto-saves via Livewire (`wire:click="markAttendance({{ $row->id }}, 'present')"`), setting `recorded_by`, `recorded_at`, and `check_in_at` (when `present`). Optional `remarks` input (`max:500`). Bulk shortcut action: **Mark Remaining Unmarked as Present** (with confirmation).
  - *Submission Bar (Sticky Bottom):* **Submit Attendance for Admin Review** button. Disabled with clear inline counter (`"Mark {W} remaining trainee(s) before submitting"`) until `W == 0` (`chk_attendance_marked_before_submit`). Submitting transitions rows to `review_status = 'submitted'`, sets `submitted_by` / `submitted_at`, logs `attendance.submitted` in `audit_logs`, and locks the sheet read-only.

### `INS-05` — Session Completion Submission
- **Route:** `GET /instructor/sessions/{session:session_code}/completion` (`instructor.sessions.completion`) | **Layout:** `layouts.instructor`
- **Tables Bound:** `training_sessions`.
- **Form Fields:** `completion_notes` (`required|string|min:10|max:2000`) summarising delivery highlights, practical exercises covered, or incidents. Displays read-only summary of attendance submission status and read-only confirmation state once an Administrator confirms completion (`completed_at`, `completed_by`).

### `INS-06` — Session Recording Submission
- **Route:** `GET /instructor/sessions/{session:session_code}/recordings` (`instructor.sessions.recordings`) | **Layout:** `layouts.instructor`
- **Tables Bound:** `session_recordings`.
- **Components:**
  - *Upload / Register Recording Form:* `title` (`required|max:200`), `description` (`nullable|max:1000`), `sort_order` (`integer|min:0`), Bunny Stream upload widget / GUID attachment (`provider_video_id`). Submits with `status = 'pending_review'`, `submitted_by = auth()->id()`, `submitted_at = now`.
  - *Submitted Recordings List:* Shows `encoding_status`, `status` (`pending_review`, `published`, `rejected`), and Admin `review_notes` if rejected so instructor can resubmit/replace.

### `INS-07` — Instructor Public Profile Editor
- **Route:** `GET /instructor/profile` (`instructor.profile.edit`) | **Layout:** `layouts.instructor`
- **Tables Bound:** `instructor_profiles`, `users`.
- **Form Fields:** `display_name` (`nullable|max:150`), `professional_title` (`nullable|max:150`), `qualifications` (`nullable|max:255`), `short_bio` (`nullable|max:500`), `biography` (`nullable|max:5000`), `specialisations` (tag/chip array input), profile photograph upload (`image|mimes:jpg,jpeg,png,webp|max:2048` stored on R2/public disk per `R-03`). Displays read-only badge indicating whether public visibility (`is_public`) is currently enabled or hidden by administration.

---

## 5. Experience D — Administrator Portal (`ADM-01` – `ADM-20`, Filament 5)

*Base path `/admin`. Middleware: `auth`, `verified`, `active.account`, `role:administrator`, `EnsureTwoFactorAuthenticated` (`two_factor_confirmed_at IS NOT NULL`).*

| ID | Filament Class / Page | Navigation Group | Key Columns / Form Sections / Custom Actions & Policies |
|---|---|---|---|
| `ADM-01` | `AdminDashboard` (`/admin`) | Overview | StatsOverview (`Registered Customers`, `Upcoming Sessions`, `Confirmed Net Revenue MYR`, `Issued Certificates`); Monthly Revenue Stacked Bar Chart; Delivery Mix Doughnut Chart; Operational Queue Widget (links directly to `requested` refunds, `submitted` attendance, `pending_review` recordings, `unmatched_pending` webhooks, `failed` cert PDFs). |
| `ADM-02` | `CategoryResource` | Training & Catalogue | Form: `name`, `slug` (auto-generated), `description`, `sort_order`, `is_active`. Table: reorderable by `sort_order`, modules count badge. Delete prevented (`RESTRICT`) if `modules_count > 0`. |
| `ADM-03` | `ModuleResource` | Training & Catalogue | Form Tabs: (1) General (`category_id` single select per `D-024`, `code`, `title`, `slug`, `summary`, `description`, `learning_objectives` repeater, cover image upload), (2) Pricing & Availability (`is_available_independently` toggle revealing required `independent_price` in MYR), (3) Status (`draft`/`published`/`unpublished`/`archived`). RelationManagers: `Materials`, `Videos`, `Sessions`, `Entitlements`. Actions: Publish, Unpublish, Archive, Soft-delete (blocked if linked sessions/orders/entitlements/certificates exist). |
| `ADM-04` | `ModuleMaterialResource` | Training & Catalogue | Global view across all `module_materials`. Form: `module_id`, `title`, `description`, `material_type` (`pdf`, `presentation`, `document`, `image`, `worksheet`, `other`), R2 FileUpload (`max:51200` KB, records `original_filename`, `mime_type`, `file_size_bytes`, `checksum_sha256`), `is_downloadable`, `sort_order`, `status`. |
| `ADM-05` | `ModuleVideoResource` | Training & Catalogue | Global view across all `module_videos`. Form: `module_id`, `title`, `description`, private server video upload, `sort_order`, `status`. FFmpeg processing derives duration and HLS metadata internally. |
| `ADM-06` | `TrainingSessionResource` | Physical Sessions & Delivery | Form: (1) Basic & Module (`module_id`, `title`, `description`), (2) Schedule (`starts_at`, `ends_at` entered in `Asia/Kuala_Lumpur`, auto-calculated `duration_minutes`, `registration_opens_at`, `registration_closes_at < starts_at`), (3) Venue (`venue_name`, `venue_address`, `venue_city`, `venue_state`), (4) Capacity & Commercial (`capacity`, `price` MYR, `recording_access_policy` defaulting to `attendees_only` per `D-017`). Session codes, slugs and timezone are generated internally. RelationManager: `SessionInstructors` (assigns users with `instructor` role + `role_label`). Header Actions: **Publish**, **Close Registration**, **Confirm Completion** (locks session & sets `completed_at`), **Cancel Session** (modal requiring `cancellation_reason`, triggers `BR-16` bulk booking cancellation + refund queue + notifications), **Reconcile Seat Counters** (`D-033`). |
| `ADM-07` | `AttendanceResource` | Physical Sessions & Delivery | Table defaults to `review_status = 'submitted'` tab (also tabs: `All`, `Correction Requested`, `Verified`, `Draft`). Shows Trainee, Session, `booking_reference`, `attendance_status` badge, `review_status` badge, `recorded_by`, `submitted_at`. Bulk Action: **Verify Selected Attendance** (for `completed` sessions, transitions `submitted -> verified`, unlocks `attendees_only` recordings & cert eligibility). Row Actions: **Verify**, **Request Correction** (requires `verification_notes`), **Admin Correct Status** (modal requiring mandatory `audit_logs.reason`; blocks moving away from `present` if an active non-revoked certificate exists). |
| `ADM-08` | `SessionRecordingResource` | Administrator navigation | Table shows Session, Title, Uploaded date, and review status. Encoding/HLS state remains internal because FFmpeg processing and secure playback depend on it. Private storage metadata remains internal; Actions: **View**, **Approve & Publish** (`published`), **Reject** (requires `review_notes`), **Archive**. |
| `ADM-09` | `CertificateResource` | Physical Sessions & Delivery | **Tab 1 — Eligible Attendees Queue:** Queries `attendance` where `bookings.status = 'confirmed'`, `attendance_status = 'present'`, `review_status = 'verified'`, `training_sessions.status = 'completed'`, and no active cert (`is_active_certificate`). Bulk Action: **Approve & Generate Certificates** (checks global `system_settings` signatory fields are populated per `D-027`/`R-04`, creates `certificates` rows & dispatches `GenerateCertificatePdfJob`). **Tab 2 — Certificates Ledger (`Pending`, `Issued`, `Revoked`):** Shows `certificate_number`, snapshots, `status`, `pdf_generation_status`, `pdf_generation_attempts`. Row Actions: **Download PDF**, **Retry Failed PDF**, **Revoke** (mandatory `revocation_reason`), **Issue Replacement** (only on `revoked` cert, links `superseded_by_certificate_id`). |
| `ADM-10` | `CertificateVerificationResource` | Physical Sessions & Delivery | Read-only security/audit table of `certificate_verifications` (`verified_at`, `lookup_method`, `lookup_value`, `result_status`, `certificate_number`, `verifier_ip`). Export CSV for compliance. |
| `ADM-11` | `BookingResource` | Commerce & Finance | Tabs: `All`, `Seat Held (15m)`, `Confirmed`, `Expired`, `Cancelled`, `Refunded`. Shows `booking_reference`, Trainee, Session, `order_number`, `reservation_expires_at`, `bookings.status`. Actions: **Cancel Booking** (modal requiring `cancellation_reason`, recomputes session seats under `D-033`), **Create Refund** (if `confirmed`/`cancelled` paid booking), **Export Participant List (CSV/XLSX/PDF)**. |
| `ADM-12` | `OrderResource` | Commerce & Finance | Read-only resource (`canCreate = false`, `canEdit = false`, `canDelete = false`). Table: `order_number`, `order_type`, Buyer, Company Name, `total_amount` MYR, `orders.status`, `paid_at`. Infolist View: Immutable customer/company billing snapshot, `order_items` table, linked `payments`, `bookings` / `module_entitlements`, and `refunds`. Action: **Download Official Receipt PDF**. |
| `ADM-13` | `PaymentResource` | Commerce & Finance | Read-only ledger (`canCreate = false`). Displays `payment_reference`, `order_number`, `gateway` (`billplz`), `payment_method`, `amount`, `refunded_amount`, `gateway_bill_id`, `gateway_transaction_id`, `status`, `paid_at`. Action: **Reconcile with Billplz (Get a Bill)** for `pending` / `expired` attempts to check live gateway state safely. |
| `ADM-14` | `RefundResource` | Commerce & Finance | Nav badge shows `status = 'requested'` count (from late payments & cancelled sessions). Table: `refund_reference`, `order_number`, Customer, `amount`, `request_source` (`system` vs `administrator`), `source_event`, `refund_method`, `status`, `disbursement_status`. Custom Actions: **Approve Refund** (requires selecting `refund_method` per `R-08` & verifies `amount <= refundable_balance`), **Reject Refund** (requires `failure_reason`), **Mark Processing / Record Disbursement**, **Complete Refund** (records `disbursement_reference`, transitions `refunds -> completed`, updates `payments.refunded_amount`, updates `orders`/`bookings` or revokes `module_entitlements`). Create Action for admin-initiated refunds (`request_source = 'administrator'`). |
| `ADM-15` | `PaymentWebhookEventResource` | Commerce & Finance | Read-only forensic log (`canCreate = false`). Tabs: `Unmatched Pending`, `Processed`, `Rejected (Invalid Sig / Mismatch)`, `Duplicates`, `All`. Shows `received_at`, `gateway_bill_id`, `signature_valid`, `reported_paid`, `reported_amount`, `processing_status`, `rejection_reason`. Modal **Inspect Sanitized Payload & Headers**. Action on `unmatched_pending`/`failed`: **Retry Matching / Get a Bill Reconciliation**. |
| `ADM-16` | `ModuleEntitlementResource` | Commerce & Finance | Shows Customer, Module, `grant_source` (`purchase` / `admin_grant`), `order_number`, `status`, `granted_at`. Header Action: **Grant Manual Module Access** (selects `user_id`, `module_id`, mandatory `grant_reason`). Row Action: **Revoke Entitlement** (mandatory `revocation_reason`, transitions `status -> revoked`). |
| `ADM-17` | `UserResource` | Users & Access | Tabs: `All`, `Customers`, `Instructors`, `Administrators`, `Suspended / Deactivated`. Form/Infolist: `name`, `email`, `status`, `two_factor_confirmed_at` indicator, **Roles CheckboxList** (`administrator`, `instructor`, `customer` — supports `D-026`), `CustomerProfile` section (`customer_type`, `phone`, company billing details). Actions: **Suspend / Deactivate / Reactivate** (modal requiring `status_reason`). |
| `ADM-18` | `InstructorProfileResource` | Users & Access | Lists users holding `instructor` role and their `instructor_profiles`. Form: `display_name`, `professional_title`, `qualifications`, `short_bio`, `biography`, `specialisations`, photo upload, **`is_public` toggle** (`D-027`, `R-03`). Action: **Toggle Public Visibility**. |
| `ADM-19` | `AnnouncementResource` & `ReportsPage` | Communications & Reports | **Announcements:** Form with `title`, rich `body`, `audience_type` selector dynamically showing `training_session_id`, `module_id`, `target_role`, or multi-select `selected_users`; `send_email` & `send_in_app` toggles; `scheduled_at`; Action **Send Now**; RelationManager showing `announcement_recipients` delivery status. **Reports Hub (`/admin/reports`):** Filter bar (Date Range, Category, Module, Session) + Export buttons (PDF / CSV / XLSX) for all 7 FR-12 reports: Participant Lists, Booking Report, Attendance Summary, Payment & Refund Report, Revenue Summary, Module Sales, Certificate Issuance. |
| `ADM-20` | `SystemSettingsPage` & `AuditLogResource` | System & Governance | **System Settings (`/admin/system-settings`):** Grouped tabs matching `system_settings.setting_group`: (1) *Booking* (`booking.reservation_hold_minutes`, default `15` per `D-019`), (2) *Recordings* (`recordings.default_access_policy`, default `attendees_only` per `D-017`), (3) *Certificates* (`certificates.issuing_organisation`, `certificates.signatory_name`, `certificates.signatory_title`, `certificates.signatory_signature_key` R2 upload per `D-027`, `R-04`). **Audit Logs (`/admin/audit-logs`):** Immutable read-only table showing `created_at`, Actor & `actor_role`, `event`, `auditable_type` + `#ID`, `reason`, `ip_address`, and **JSON Diff Modal** (`old_values` vs `new_values`). |
