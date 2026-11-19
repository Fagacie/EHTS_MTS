# ETMS Entity Relationship Diagram (ERD)

**Project:** Ecohydrotech Training Management System (ETMS)  
**Stage:** Stage 1 — Database Design (Revised after Review & Confirmed Decisions D-017..D-034 — Pending Final Approval)  
**Baseline:** [SRS and System Architecture v1.1](ETMS-SRS-and-System-Architecture-v1.1.md) & [Architecture and Business Decisions](DECISIONS.md)  
**Database Engine:** MySQL 8.4 (InnoDB, `utf8mb4_unicode_ci`, UTC `DATETIME` timestamps)  
**Companion Document:** [Database Design & Data Dictionary](DATABASE-DESIGN.md)

---

## 1. Domain Architecture Overview

The ETMS database comprises **29 domain tables** (the 28 original Stage 1 domain tables plus `instructor_profiles` required by confirmed decision **D-027**) organized into six bounded functional domains, plus **7 standard Laravel 13 infrastructure tables** documented in [DATABASE-DESIGN.md](DATABASE-DESIGN.md#appendix-a-laravel-13-framework-infrastructure-tables).

| Domain | Tables | Count |
|---|---|---:|
| **A. Identity, RBAC, Customer & Instructor Profiles** | `users`, `roles`, `permissions`, `role_user`, `permission_role`, `customer_profiles`, `instructor_profiles` | 7 |
| **B. Training Catalogue, Materials & Videos** | `categories`, `modules`, `module_materials`, `module_videos` | 4 |
| **C. Physical Sessions, Instructors & Recordings** | `training_sessions`, `session_instructors`, `session_recordings` | 3 |
| **D. Commerce, Bookings, Payments & Refunds** | `orders`, `order_items`, `bookings`, `payments`, `payment_webhook_events`, `refunds` | 6 |
| **E. Entitlements, Attendance & Certificates** | `module_entitlements`, `attendance`, `certificates`, `certificate_verifications` | 4 |
| **F. Operations, Communications & Governance** | `notifications`, `announcements`, `announcement_recipients`, `audit_logs`, `system_settings` | 5 |
| **Total Domain Tables** | | **29** |

> **Scope & Constraint Guard:** In strict compliance with SRS Section 1.4, Section 9.1, and Decisions D-003, D-022, D-023, D-024, D-028, and D-030, there are **no** `organizations`, `companies`, `organization_members`, `corporate_coordinators`, `employee_assignments`, `subscriptions`, `assessments`, `session_days`, or `category_module` tables, and no NRIC/passport or speculative tax columns. Company billing details are optional fields on `customer_profiles` and snapshotted onto `orders`.

---

## 2. Complete System ERD

```mermaid
erDiagram
    users ||--o{ role_user : "assigned"
    roles ||--o{ role_user : "granted to"
    roles ||--o{ permission_role : "grants"
    permissions ||--o{ permission_role : "assigned to"
    users ||--o| customer_profiles : "has customer profile"
    users ||--o| instructor_profiles : "has instructor profile"

    categories ||--o{ modules : "classifies"
    modules ||--o{ module_materials : "contains R2 docs"
    modules ||--o{ module_videos : "contains Bunny videos"
    modules ||--o{ training_sessions : "scheduled as"

    training_sessions ||--o{ session_instructors : "taught by"
    users ||--o{ session_instructors : "assigned as instructor"
    training_sessions ||--o{ session_recordings : "recorded as"

    users ||--o{ orders : "places"
    orders ||--|{ order_items : "contains type-matched items"
    training_sessions |o--o{ order_items : "purchased via"
    modules |o--o{ order_items : "purchased via"

    users ||--o{ bookings : "holds"
    training_sessions ||--o{ bookings : "allocates seat for"
    orders ||--o{ bookings : "initiates for same user"
    order_items ||--o| bookings : "fulfills session item"

    orders ||--o{ payments : "paid by same user"
    users ||--o{ payments : "initiates"
    payments |o--o{ payment_webhook_events : "updated via"
    orders |o--o{ payment_webhook_events : "matched to"

    orders ||--o{ refunds : "refunded via"
    payments ||--o{ refunds : "reverses for same user"
    bookings |o--o{ refunds : "associated with"
    users ||--o{ refunds : "receives"

    users ||--o{ module_entitlements : "owns lifetime access"
    modules ||--o{ module_entitlements : "unlocked by"
    orders |o--o{ module_entitlements : "originates from"
    order_items |o--o| module_entitlements : "grants"

    training_sessions ||--o{ attendance : "tracks"
    bookings ||--o| attendance : "verified on"
    users ||--o{ attendance : "recorded for"

    attendance ||--o{ certificates : "qualifies for"
    bookings ||--o{ certificates : "linked to"
    training_sessions ||--o{ certificates : "issued for"
    modules ||--o{ certificates : "references"
    users ||--o{ certificates : "awarded to"
    certificates |o--o{ certificates : "replacement history"
    certificates |o--o{ certificate_verifications : "verified in"

    users ||--o{ announcements : "creates"
    training_sessions |o--o{ announcements : "targeted by"
    modules |o--o{ announcements : "targeted by"
    announcements ||--o{ announcement_recipients : "delivered to"
    users ||--o{ announcement_recipients : "receives"

    users |o--o{ audit_logs : "actor in"
    users |o--o{ system_settings : "updates"
```

---

## 3. Domain-Focused ERDs

### 3.1 Domain A — Identity, RBAC, Customer & Instructor Profiles

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name
        varchar email UK
        datetime email_verified_at
        varchar password
        varchar status
        datetime status_changed_at
        bigint status_changed_by FK
        text two_factor_secret
        text two_factor_recovery_codes
        datetime two_factor_confirmed_at
        datetime last_login_at
        datetime deleted_at
    }

    roles {
        bigint id PK
        varchar slug UK
        varchar name
        varchar description
        boolean is_system
    }

    permissions {
        bigint id PK
        varchar slug UK
        varchar name
        varchar module_group
    }

    role_user {
        bigint user_id PK "composite PK and FK"
        bigint role_id PK "composite PK and FK"
        bigint assigned_by FK
        datetime assigned_at
    }

    permission_role {
        bigint permission_id PK "composite PK and FK"
        bigint role_id PK "composite PK and FK"
    }

    customer_profiles {
        bigint id PK
        bigint user_id FK "unique 1-to-1"
        varchar customer_type
        varchar phone
        varchar job_title
        varchar company_name
        varchar company_registration_no
        varchar billing_contact_name
        varchar billing_email
        varchar billing_phone
        varchar billing_address_line_1
        varchar billing_address_line_2
        varchar billing_city
        varchar billing_state
        varchar billing_postcode
        char billing_country
    }

    instructor_profiles {
        bigint id PK
        bigint user_id FK "unique 1-to-1"
        varchar display_name
        varchar professional_title
        varchar qualifications
        varchar short_bio
        text biography
        json specialisations
        varchar photo_storage_disk
        varchar photo_storage_key
        boolean is_public
        datetime published_at
        bigint updated_by FK
    }

    users ||--o{ role_user : "has roles"
    roles ||--o{ role_user : "assigned to"
    roles ||--o{ permission_role : "has permissions"
    permissions ||--o{ permission_role : "belong to"
    users ||--o| customer_profiles : "optional customer profile"
    users ||--o| instructor_profiles : "optional instructor profile"
    users |o--o{ users : "status changed by"
```

---

### 3.2 Domains B & C — Training Catalogue, Media & Physical Sessions

```mermaid
erDiagram
    categories {
        bigint id PK
        varchar name
        varchar slug UK
        text description
        int sort_order
        boolean is_active
    }

    modules {
        bigint id PK
        bigint category_id FK
        varchar code UK
        varchar title
        varchar slug UK
        varchar summary
        longtext description
        json learning_objectives
        varchar cover_image_disk
        varchar cover_image_path
        boolean is_available_independently
        decimal independent_price
        char currency
        varchar status
        datetime published_at
        datetime archived_at
        bigint created_by FK
    }

    module_materials {
        bigint id PK
        bigint module_id FK
        varchar title
        varchar material_type
        varchar storage_disk
        varchar storage_bucket
        varchar storage_key
        varchar original_filename
        varchar mime_type
        bigint file_size_bytes
        char checksum_sha256
        boolean is_downloadable
        int sort_order
        varchar status
        bigint uploaded_by FK
    }

    module_videos {
        bigint id PK
        bigint module_id FK
        varchar title
        varchar provider
        varchar provider_library_id
        varchar provider_video_id
        int duration_seconds
        varchar encoding_status
        int sort_order
        varchar status
        bigint uploaded_by FK
    }

    training_sessions {
        bigint id PK
        bigint module_id FK
        varchar session_code UK
        varchar title
        varchar slug UK
        datetime starts_at
        datetime ends_at
        int duration_minutes
        varchar timezone
        varchar venue_name
        int capacity
        int reserved_seats_count
        int confirmed_seats_count
        datetime registration_opens_at
        datetime registration_closes_at
        decimal price
        char currency
        varchar recording_access_policy
        varchar status
        bigint completion_submitted_by FK
        bigint completed_by FK
        bigint cancelled_by FK
    }

    session_instructors {
        bigint id PK
        bigint training_session_id FK
        bigint instructor_id FK
        varchar role_label
        bigint assigned_by FK
        datetime assigned_at
    }

    session_recordings {
        bigint id PK
        bigint training_session_id FK
        varchar title
        varchar provider
        varchar provider_library_id
        varchar provider_video_id
        int duration_seconds
        varchar encoding_status
        varchar status
        bigint submitted_by FK
        datetime submitted_at
        bigint reviewed_by FK
        datetime reviewed_at
        datetime published_at
    }

    categories ||--o{ modules : "categorises"
    modules ||--o{ module_materials : "owns R2 files"
    modules ||--o{ module_videos : "owns Bunny videos"
    modules ||--o{ training_sessions : "runs as physical sessions"
    training_sessions ||--o{ session_instructors : "staffed by"
    training_sessions ||--o{ session_recordings : "captures recordings"
```

---

### 3.3 Domain D — Commerce, Orders, Bookings, Payments & Refunds

```mermaid
erDiagram
    orders {
        bigint id PK
        varchar order_number UK
        bigint user_id FK
        varchar order_type
        varchar status
        char currency
        decimal subtotal_amount
        decimal total_amount
        varchar customer_name
        varchar customer_email
        varchar customer_type
        varchar company_name
        datetime expires_at
        datetime paid_at
        datetime cancelled_at
        datetime refunded_at
    }

    order_items {
        bigint id PK
        bigint order_id FK
        varchar order_type
        varchar item_type
        bigint training_session_id FK
        bigint module_id FK
        varchar item_code_snapshot
        varchar item_title_snapshot
        decimal unit_price
        int quantity
        decimal line_total
        char currency
        json metadata_snapshot
    }

    bookings {
        bigint id PK
        varchar booking_reference UK
        bigint user_id FK
        bigint training_session_id FK
        bigint order_id FK
        bigint order_item_id FK
        varchar status
        tinyint is_active_booking
        datetime reserved_at
        datetime reservation_expires_at
        datetime confirmed_at
        datetime cancelled_at
        datetime expired_at
        datetime refunded_at
        bigint cancelled_by FK
    }

    payments {
        bigint id PK
        varchar payment_reference UK
        bigint order_id FK
        bigint user_id FK
        varchar gateway
        varchar payment_method
        decimal amount
        decimal refunded_amount
        char currency
        varchar status
        varchar gateway_bill_id
        varchar gateway_transaction_id
        varchar gateway_reference_1
        varchar gateway_state
        datetime paid_at
        varchar manual_reference
        varchar evidence_storage_key
        bigint verified_by FK
        datetime verified_at
    }

    payment_webhook_events {
        bigint id PK
        varchar gateway
        char idempotency_key
        char request_fingerprint
        varchar gateway_bill_id
        varchar gateway_transaction_id
        varchar gateway_collection_id
        bigint payment_id FK
        bigint order_id FK
        varchar event_type
        boolean signature_valid
        decimal reported_amount
        decimal reported_paid_amount
        boolean reported_paid
        varchar reported_state
        datetime reported_paid_at
        char reported_currency
        json headers
        json payload
        varchar processing_status
        text rejection_reason
        varchar source_ip
        datetime received_at
        datetime processed_at
        datetime created_at
        datetime updated_at
    }

    refunds {
        bigint id PK
        varchar refund_reference UK
        bigint order_id FK
        bigint payment_id FK
        bigint booking_id FK
        bigint user_id FK
        decimal amount
        char currency
        varchar refund_method
        varchar provider_disbursement_id
        varchar disbursement_reference
        varchar disbursement_status
        varchar status
        varchar request_source
        varchar source_event
        bigint requested_by FK
        bigint approved_by FK
        bigint processed_by FK
        datetime processed_at
    }

    orders ||--|{ order_items : "contains type-matched items"
    orders ||--o{ bookings : "creates booking for same user"
    order_items ||--o| bookings : "1-to-1 session booking"
    orders ||--o{ payments : "has payment attempts by same user"
    payments |o--o{ payment_webhook_events : "receives callbacks"
    orders |o--o{ payment_webhook_events : "correlated to"
    orders ||--o{ refunds : "has refunds"
    payments ||--o{ refunds : "refunded from"
    bookings |o--o{ refunds : "optional booking refund"
```

---

### 3.4 Domains E & F — Entitlements, Attendance, Certificates & Operations

```mermaid
erDiagram
    module_entitlements {
        bigint id PK
        bigint user_id FK
        bigint module_id FK
        bigint order_id FK
        bigint order_item_id FK
        varchar grant_source
        varchar status
        tinyint is_active_entitlement
        datetime granted_at
        bigint granted_by FK
        datetime revoked_at
        bigint revoked_by FK
    }

    attendance {
        bigint id PK
        bigint training_session_id FK
        bigint booking_id FK
        bigint user_id FK
        varchar attendance_status
        varchar review_status
        datetime check_in_at
        bigint recorded_by FK
        datetime recorded_at
        bigint submitted_by FK
        datetime submitted_at
        bigint verified_by FK
        datetime verified_at
    }

    certificates {
        bigint id PK
        varchar certificate_number UK
        char verification_token UK
        bigint user_id FK
        bigint training_session_id FK
        bigint module_id FK
        bigint booking_id FK
        bigint attendance_id FK
        varchar status
        varchar pdf_generation_status
        int pdf_generation_attempts
        text pdf_generation_error
        tinyint is_active_certificate
        varchar trainee_name_snapshot
        varchar training_title_snapshot
        date training_start_date_snapshot
        date training_end_date_snapshot
        varchar signatory_name_snapshot
        varchar signatory_signature_key_snapshot
        varchar pdf_storage_key
        varchar qr_verification_url
        bigint approved_by FK
        datetime approved_at
        bigint issued_by FK
        datetime issued_at
        bigint revoked_by FK
        datetime revoked_at
        bigint superseded_by_certificate_id FK
    }

    certificate_verifications {
        bigint id PK
        bigint certificate_id FK
        varchar lookup_value
        varchar lookup_method
        varchar result_status
        varchar verifier_ip
        datetime verified_at
    }

    notifications {
        char id PK
        varchar type
        varchar notifiable_type
        bigint notifiable_id
        json data
        datetime read_at
    }

    announcements {
        bigint id PK
        varchar title
        longtext body
        varchar audience_type
        bigint training_session_id FK
        bigint module_id FK
        varchar target_role
        varchar status
        datetime sent_at
        bigint created_by FK
    }

    announcement_recipients {
        bigint id PK
        bigint announcement_id FK
        bigint user_id FK
        varchar delivery_status
        datetime sent_at
        datetime read_at
    }

    audit_logs {
        bigint id PK
        bigint actor_id FK
        varchar actor_role
        varchar event
        varchar auditable_type
        bigint auditable_id
        json old_values
        json new_values
        text reason
        varchar ip_address
        datetime created_at
    }

    system_settings {
        bigint id PK
        varchar setting_key UK
        json setting_value
        varchar value_type
        varchar setting_group
        boolean is_public
        bigint updated_by FK
    }

    attendance ||--o{ certificates : "verified present attendance required"
    certificates |o--o{ certificate_verifications : "logs public scans"
    certificates |o--o{ certificates : "replacement history"
    announcements ||--o{ announcement_recipients : "delivers to"
```

> **Note on Polymorphic Tables:** `notifications` (`notifiable_type`, `notifiable_id`) and `audit_logs` (`auditable_type`, `auditable_id`) use polymorphic references without MySQL physical foreign keys on the target ID, enabling unified portal notifications and immutable audit coverage across all domain models. Cross-domain user FKs (`users` → `module_entitlements`, `attendance`, `certificates`, `announcements`, `announcement_recipients`, `audit_logs`, `system_settings`) are shown in the Complete System ERD above.

---

## 4. Relationship Cardinality Matrix

| Parent Entity | Child Entity | Cardinality | Foreign Key Column(s) & Composite Integrity Links | On Delete | On Update |
|---|---|---|---|---|---|
| `users` | `role_user` | `1 : 0..*` | `role_user.user_id` → `users.id` | `CASCADE` | `RESTRICT` |
| `roles` | `role_user` | `1 : 0..*` | `role_user.role_id` → `roles.id` | `RESTRICT` | `RESTRICT` |
| `roles` | `permission_role` | `1 : 0..*` | `permission_role.role_id` → `roles.id` | `CASCADE` | `RESTRICT` |
| `permissions` | `permission_role` | `1 : 0..*` | `permission_role.permission_id` → `permissions.id` | `CASCADE` | `RESTRICT` |
| `users` | `customer_profiles` | `1 : 0..1` | `customer_profiles.user_id` (UK) → `users.id` | `CASCADE` | `RESTRICT` |
| `users` | `instructor_profiles` | `1 : 0..1` | `instructor_profiles.user_id` (UK) → `users.id` | `CASCADE` | `RESTRICT` |
| `categories` | `modules` | `1 : 0..*` | `modules.category_id` → `categories.id` (single primary category, D-024) | `RESTRICT` | `RESTRICT` |
| `modules` | `module_materials` | `1 : 0..*` | `module_materials.module_id` → `modules.id` | `RESTRICT` | `RESTRICT` |
| `modules` | `module_videos` | `1 : 0..*` | `module_videos.module_id` → `modules.id` | `RESTRICT` | `RESTRICT` |
| `modules` | `training_sessions` | `1 : 0..*` | `training_sessions.module_id` → `modules.id` | `RESTRICT` | `RESTRICT` |
| `training_sessions` | `session_instructors` | `1 : 0..*` | `session_instructors.training_session_id` → `training_sessions.id` | `CASCADE` | `RESTRICT` |
| `users` | `session_instructors` | `1 : 0..*` | `session_instructors.instructor_id` → `users.id` | `RESTRICT` | `RESTRICT` |
| `training_sessions` | `session_recordings` | `1 : 0..*` | `session_recordings.training_session_id` → `training_sessions.id` | `RESTRICT` | `RESTRICT` |
| `users` | `orders` | `1 : 0..*` | `orders.user_id` → `users.id` | `RESTRICT` | `RESTRICT` |
| `orders` | `order_items` | `1 : 1..*` | `order_items.(order_id, order_type)` → `orders.(id, order_type)` (D-030, D-031) | `RESTRICT` | `RESTRICT` |
| `training_sessions` | `order_items` | `0..1 : 0..*` | `order_items.training_session_id` → `training_sessions.id` | `RESTRICT` | `RESTRICT` |
| `modules` | `order_items` | `0..1 : 0..*` | `order_items.module_id` → `modules.id` | `RESTRICT` | `RESTRICT` |
| `users` | `bookings` | `1 : 0..*` | `bookings.user_id` → `users.id` | `RESTRICT` | `RESTRICT` |
| `training_sessions` | `bookings` | `1 : 0..*` | `bookings.training_session_id` → `training_sessions.id` | `RESTRICT` | `RESTRICT` |
| `orders` | `bookings` | `1 : 0..*` | `bookings.(order_id, user_id)` → `orders.(id, user_id)` (D-032) | `RESTRICT` | `RESTRICT` |
| `order_items` | `bookings` | `1 : 0..1` | `bookings.(order_item_id, order_id, training_session_id)` → `order_items.(id, order_id, training_session_id)` | `RESTRICT` | `RESTRICT` |
| `orders` | `payments` | `1 : 0..*` | `payments.(order_id, user_id)` → `orders.(id, user_id)` (D-032) | `RESTRICT` | `RESTRICT` |
| `users` | `payments` | `1 : 0..*` | `payments.user_id` → `users.id` | `RESTRICT` | `RESTRICT` |
| `payments` | `payment_webhook_events` | `0..1 : 0..*` | `payment_webhook_events.(payment_id, order_id)` → `payments.(id, order_id)` | `RESTRICT` | `RESTRICT` |
| `orders` | `payment_webhook_events` | `0..1 : 0..*` | `payment_webhook_events.order_id` → `orders.id` | `RESTRICT` | `RESTRICT` |
| `orders` | `refunds` | `1 : 0..*` | `refunds.order_id` → `orders.id` | `RESTRICT` | `RESTRICT` |
| `payments` | `refunds` | `1 : 0..*` | `refunds.(payment_id, order_id, user_id)` → `payments.(id, order_id, user_id)` (D-032) | `RESTRICT` | `RESTRICT` |
| `bookings` | `refunds` | `0..1 : 0..*` | `refunds.(booking_id, order_id, user_id)` → `bookings.(id, order_id, user_id)` (D-032) | `RESTRICT` | `RESTRICT` |
| `users` | `refunds` | `1 : 0..*` | `refunds.user_id` → `users.id` | `RESTRICT` | `RESTRICT` |
| `users` | `module_entitlements` | `1 : 0..*` | `module_entitlements.user_id` → `users.id` | `RESTRICT` | `RESTRICT` |
| `modules` | `module_entitlements` | `1 : 0..*` | `module_entitlements.module_id` → `modules.id` | `RESTRICT` | `RESTRICT` |
| `orders` | `module_entitlements` | `0..1 : 0..*` | `module_entitlements.(order_id, user_id)` → `orders.(id, user_id)` (D-032) | `RESTRICT` | `RESTRICT` |
| `order_items` | `module_entitlements` | `0..1 : 0..1` | `module_entitlements.(order_item_id, order_id, module_id)` → `order_items.(id, order_id, module_id)` | `RESTRICT` | `RESTRICT` |
| `training_sessions` | `attendance` | `1 : 0..*` | `attendance.training_session_id` → `training_sessions.id` | `RESTRICT` | `RESTRICT` |
| `bookings` | `attendance` | `1 : 0..1` | `attendance.(booking_id, training_session_id, user_id)` → `bookings.(id, training_session_id, user_id)` | `RESTRICT` | `RESTRICT` |
| `users` | `attendance` | `1 : 0..*` | `attendance.user_id` → `users.id` | `RESTRICT` | `RESTRICT` |
| `attendance` | `certificates` | `1 : 0..*` | `certificates.(attendance_id, training_session_id, booking_id, user_id)` → `attendance.(id, training_session_id, booking_id, user_id)` | `RESTRICT` | `RESTRICT` |
| `bookings` | `certificates` | `1 : 0..*` | `certificates.booking_id` → `bookings.id` | `RESTRICT` | `RESTRICT` |
| `training_sessions` | `certificates` | `1 : 0..*` | `certificates.(training_session_id, module_id)` → `training_sessions.(id, module_id)` | `RESTRICT` | `RESTRICT` |
| `modules` | `certificates` | `1 : 0..*` | `certificates.module_id` → `modules.id` | `RESTRICT` | `RESTRICT` |
| `users` | `certificates` | `1 : 0..*` | `certificates.user_id` → `users.id` | `RESTRICT` | `RESTRICT` |
| `certificates` | `certificates` | `0..* : 0..1` | `certificates.superseded_by_certificate_id` → `certificates.id` (non-unique historical forward link; active uniqueness is per attendance) | `SET NULL` | `RESTRICT` |
| `certificates` | `certificate_verifications` | `0..1 : 0..*` | `certificate_verifications.certificate_id` → `certificates.id` | `SET NULL` | `RESTRICT` |
| `announcements` | `announcement_recipients` | `1 : 0..*` | `announcement_recipients.announcement_id` → `announcements.id` | `CASCADE` | `RESTRICT` |
| `users` | `announcement_recipients` | `1 : 0..*` | `announcement_recipients.user_id` → `users.id` | `CASCADE` | `RESTRICT` |
| `training_sessions` | `announcements` | `0..1 : 0..*` | `announcements.training_session_id` → `training_sessions.id` | `SET NULL` | `RESTRICT` |
| `modules` | `announcements` | `0..1 : 0..*` | `announcements.module_id` → `modules.id` | `SET NULL` | `RESTRICT` |
| `users` | `audit_logs` | `0..1 : 0..*` | `audit_logs.actor_id` → `users.id` | `SET NULL` | `RESTRICT` |
| `users` | `system_settings` | `0..1 : 0..*` | `system_settings.updated_by` → `users.id` | `SET NULL` | `RESTRICT` |

---

## 5. Verification Against Approved Constraints & Decisions (`D-001` – `D-034`)

- **Three roles only (`D-004`, `D-026`):** `roles` contains strictly `administrator`, `instructor`, and `customer`. Native `role_user` and `permission_role` support multiple roles per user so staff members may also hold the `customer` role when purchasing training.
- **No corporate portal or organisation tables (`D-003`, `D-023`):** Zero organisation, member, coordinator, or employee assignment tables exist. Each booking represents the authenticated customer purchasing for themselves (`quantity = 1`).
- **Same customer workflow (`D-001`, `D-002`):** Individuals and company purchasers share `users`, `customer_profiles`, `orders`, `order_items`, and `payments`.
- **Public instructor profiles (`D-027`):** `instructor_profiles` stores instructor public biography, qualifications, photograph key, and `is_public` visibility control.
- **One primary category per module (`D-024`):** `modules.category_id` (`NOT NULL`, `FK -> categories.id`).
- **One module -> multiple sessions (`D-005`, `D-022`, `D-029`):** `modules 1 -- 0..* training_sessions` with a single continuous `starts_at` / `ends_at` range and `CHECK (registration_closes_at < starts_at)`.
- **Strict order & item separation (`D-018`, `D-030`, `D-031`):** `order_items.(order_id, order_type)` references `orders.(id, order_type)` and a table `CHECK` binds `order_type` to `item_type`, making mixed orders or mismatched items impossible.
- **Cross-record ownership integrity (`D-032`):** Composite FKs guarantee `bookings.user_id`, `payments.user_id`, `refunds.user_id`, and purchased `module_entitlements.user_id` always match `orders.user_id`.
- **Billplz-only launch payments (`D-008`, `D-020`):** `payments.gateway` is constrained to `'billplz'` in the initial release; `payment_webhook_events` enforces `(gateway, idempotency_key)` uniqueness.
- **Lifetime module access (`D-006`):** `module_entitlements` has no expiry timestamp.
- **Separate booking, attendance, and review lifecycles (`D-017`, `D-025`):** `bookings.status` tracks seat state; `attendance.attendance_status` tracks participation (`pending`, `present`, `absent`, `excused`); `attendance.review_status` tracks verification (`draft`, `submitted`, `verified`, `correction_requested`).
- **Certificate integrity & global signatory snapshots (`D-007`, `D-027`, `D-034`):** `certificates` requires non-null `training_session_id`, `booking_id`, `attendance_id`, `approved_by`, and `approved_at`, tracks `pdf_generation_status` separately from `status`, and snapshots global signatory settings upon approval.
- **Certificate replacement cardinality:** A revoked certificate remains queryable for QR/history. Its non-unique `superseded_by_certificate_id` forward link records replacement history; `uq_certificates_active_attendance (attendance_id, is_active_certificate)` permits only one active certificate for an attendance. No unique constraint is applied to the replacement link.
- **Billplz callback contract:** The callback is form-encoded and carries `x_signature` in the POST body, not an HTTP `X-Signature` header. The stable callback identity is a hash of the canonical required callback fields, excluding `x_signature` and optional transaction identifiers, so exact retries deduplicate while legitimate status changes remain processable.

**Verification scope:** The relationship matrix and displayed columns were reviewed against the companion data dictionary. This is not a claim of complete bidirectional column parity, Mermaid parser validation, MySQL 8.4 DDL execution, constraint tests, or concurrency tests; those remain implementation-stage checks.
- **PDPA & tax data minimisation (`D-028`):** No NRIC/passport or speculative tax/e-Invoice fields are included.
