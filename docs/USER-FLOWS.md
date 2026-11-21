# ETMS Navigation Architecture & User Flows

| Property | Value |
|---|---|
| **Project** | Ecohydrotech Training Management System (ETMS) |
| **Stage** | Stage 2 — UI/UX Blueprint & Application Design (Pending Review) |
| **Version** | 1.0 |
| **Date** | 17 November 2026 |
| **Companion Documents** | [UI/UX Blueprint](UI-UX-BLUEPRINT.md), [Screen Inventory](SCREEN-INVENTORY.md), [Database Design](DATABASE-DESIGN.md) |

---

## 1. Global Navigation Architecture & Multi-Role Context

ETMS routes users into four clean, isolated experience shells while supporting seamless switching for multi-role staff (`D-026`) and maintaining strict role/policy enforcement on every route (`FR-01`, `D-004`).

```mermaid
flowchart TD
    Guest["Public Visitor (/ , /training , /sessions , /instructors , /verify)"]
    Login["Auth Flow (/login , /register , /email/verify , /two-factor-challenge)"]

    subgraph Portals["Authenticated Application Experiences"]
        Trainee["Trainee / Customer Portal (/portal/* , /checkout/*)"]
        Instructor["Instructor Portal (/instructor/*)"]
        Admin["Administrator Portal - Filament 5 (/admin/*)"]
    end

    Guest -->|"Browse Catalogue or Verify Certificate"| Guest
    Guest -->|"Click Book Session / Buy Module / Sign In"| Login

    Login -->|"Role: customer (verified)"| Trainee
    Login -->|"Role: instructor (verified)"| Instructor
    Login -->|"Role: administrator + TOTP MFA"| Admin

    Trainee <-->|"Portal Switcher (if staff holds customer role - D-026)"| Instructor
    Trainee <-->|"Portal Switcher (if admin holds customer role - D-026)"| Admin
    Instructor <-->|"Portal Switcher (if dual instructor/admin)"| Admin
```

---

## 2. Navigation Specification Across the Four Experiences

### 2.1 Experience A — Public Website (`layouts.public`)

| Element | Desktop (`>= 1024px`) | Tablet / Mobile (`< 1024px`) |
|---|---|---|
| **Top Header** | Sticky `h-16` white bar with subtle bottom border (`border-slate-200`). Left: Ecohydrotech brand mark (`/`). Center: Primary nav links (`Training Catalogue`, `Physical Sessions`, `Instructors`, `Verify Certificate`). Right: Guest (`Log In`, primary `Create Account`) or Authenticated (`My Portal` badge + Account Menu). | Sticky `h-16` bar: Left brand mark, Right compact `My Portal` / `Log In` button + `44x44px` Hamburger drawer trigger. Drawer slides from right with primary links, quick `Verify Certificate` CTA, and auth actions. |
| **Breadcrumbs** | Rendered on `/training/{slug}`, `/sessions/{slug}`, `/instructors/{id}` (e.g., `Home / Training / Hydrology / EHT-HYD-101`). | Horizontally truncated single-line breadcrumb with back-to-parent link (`< Back to Training Catalogue`). |
| **Footer** | 4-column structured footer: Brand & Malaysia focus notice, Quick Links (`Catalogue`, `Sessions`, `Instructors`, `Verify Certificate`), Portal Links (`Trainee Sign In`, `Instructor Portal`), Support & Compliance (`Contact`, `Privacy / PDPA Notice`). | Single-column accordion or stacked 2-column grid. |

### 2.2 Experience B — Trainee / Customer Portal (`layouts.portal` & `layouts.checkout`)

| Element | Desktop (`>= 1024px`) | Tablet / Mobile (`< 1024px`) |
|---|---|---|
| **Left Sidebar (`260px`)** | Fixed dark navy/white contrast sidebar (`bg-eht-navy-900 text-slate-100`) divided into 3 labelled groups: **Overview** (`Dashboard`), **Training & Learning** (`My Bookings`, `Purchased Modules`, `Session Recordings`, `Certificates`), **Account & Finance** (`Orders & Receipts`, `Notifications` with unread badge, `Profile & Company Billing`, `Security`). Bottom utility link: `Browse Catalogue (/)`. | Collapsible slide-over drawer triggered by top-left menu button **plus** a fixed bottom quick-nav bar (4 icons: `Dashboard`, `Bookings`, `Modules`, `Certificates`) for single-thumb mobile access. |
| **Top Utility Bar** | Left: Dynamic Breadcrumb (`Portal / Bookings / BKG-20261117-000123`). Right: Notification Bell dropdown (last 5 unread alerts + link to `/portal/notifications`), **Portal Switcher** (shown only if user holds `instructor` or `administrator` role per `D-026`), and **Account Menu** (`Profile`, `Security`, `Sign Out`). | Compact page title, Notification Bell icon (`44x44px`), and Avatar Account Menu. |
| **Checkout Header (`layouts.checkout`)** | Distraction-free bar (`h-16`): Brand mark + `Secure Checkout (Billplz)` lock pill + Live 15-minute Seat Hold Countdown badge (for session checkouts) + `Exit to Session` link. Sidebar is omitted so user focuses on checkout. | Identical compact top header; hold countdown remains sticky below header on mobile scroll. |

### 2.3 Experience C — Instructor Portal (`layouts.instructor`)

| Element | Desktop (`>= 1024px`) | Tablet / Mobile (`< 1024px`) |
|---|---|---|
| **Left Sidebar (`240px`)** | Fixed slate/navy operational sidebar: `Dashboard`, `Assigned Sessions`, `My Public Profile`, plus `Browse Public Catalogue (/)`. | Slide-over drawer triggered from header menu. Designed for one-handed tablet use in classrooms. |
| **Session Workspace Sub-Nav** | Horizontal underlined tab strip inside `/instructor/sessions/{code}`: `Overview`, `Participants & Roster`, `Attendance` (with unmarked count pill), `Session Completion`, `Recordings`. | Horizontally scrollable snap-tab bar (`overflow-x-auto`) with `min-h-11` tap targets. |
| **Top Utility Bar** | Breadcrumb (`Instructor / SES-2026-001 / Attendance`), Portal Switcher (if user also holds `customer` or `administrator` role), and Account Menu. | Back button + Session Code pill + Account Menu. |

### 2.4 Experience D — Administrator Portal (`Filament 5` at `/admin`)

| Element | Desktop (`>= 1024px`) | Tablet / Mobile (`< 1024px`) |
|---|---|---|
| **Filament Sidebar** | Native Filament 5 sidebar with a visible desktop collapse toggle and independently collapsible sections: **Overview**, **Training & Catalogue**, **Sessions & Delivery**, **Commerce & Finance**, **Users & Access**, **Communications & Reports**, and **System & Governance**. | Native Filament 5 responsive slide-over drawer. |
| **Top Bar** | Filament Global Search (`Ctrl+K` across users, `order_number`, `booking_reference`, `certificate_number`, `session_code`), Database Notifications bell (async PDF failures, webhook alerts), Custom **Portal Switcher** action (if admin holds `customer`/`instructor` role), and Profile/Sign Out menu. | Global Search modal icon, Notifications bell, and User Avatar menu. |
| **Breadcrumbs** | Native Filament resource/action breadcrumbs (`Training Sessions > SES-2026-001 > Edit`). | Compact parent back link. |

---

## 3. Core End-to-End User Journeys

### 3.1 Journey 1 — Customer Registration, Email Verification, Login & Password Recovery

Covers `FR-01`, `D-001`, `D-002`, `D-026`, `D-028` (no NRIC/passport collected).

```mermaid
flowchart TD
    Start["Visitor opens /register"]
    Form["Fill Name, Email, Phone, Password & Customer Type (Individual or Company)"]
    OptComp{"Customer Type = Company?"}
    CompFields["Reveal optional Company Name, SSM Reg No, Billing Contact & Billing Address"]
    SubmitReg["Submit Registration"]
    Persist["Create users (status='active'), assign 'customer' role_user, create customer_profiles, send Verification Email"]
    VerifyNotice["Show /email/verify notice"]
    ClickVerify["User clicks signed email verification link"]
    Verified["Set users.email_verified_at = UTC_TIMESTAMP() -> Redirect to /portal (or intended checkout URL)"]

    Login["Existing User opens /login"]
    AuthCheck{"Credentials valid & status = 'active'?"}
    Blocked["Show suspended/deactivated reason or generic auth error"]
    RoleCheck{"User holds 'administrator' role with 2FA?"}
    MFA["Show /two-factor-challenge (TOTP code or recovery code)"]
    Redirect["Redirect by intended URL or primary portal (/admin, /instructor, or /portal)"]

    Forgot["Forgot Password (/forgot-password) -> Email token in password_reset_tokens -> /reset-password/{token}"]

    Start --> Form --> OptComp
    OptComp -->|"Yes"| CompFields --> SubmitReg
    OptComp -->|"No"| SubmitReg
    SubmitReg --> Persist --> VerifyNotice --> ClickVerify --> Verified

    Login --> AuthCheck
    AuthCheck -->|"No"| Blocked
    AuthCheck -->|"Yes"| RoleCheck
    RoleCheck -->|"Yes"| MFA --> Redirect
    RoleCheck -->|"No"| Redirect
    Login -.->|"Forgot link"| Forgot
```

---

### 3.2 Journey 2 — Physical Session Booking, 15-Minute Seat Hold & Billplz Checkout

Covers `FR-03`, `FR-04`, `FR-05`, `D-019`, `D-020`, `D-023`, `D-029`, `D-030`, `D-031`, `D-032`, `D-033`, `D-035`.

```mermaid
sequenceDiagram
    actor Customer
    participant UI as Checkout UI (/checkout/session/{slug})
    participant Service as SessionCheckoutService
    participant DB as MySQL 8.4 (InnoDB)
    participant Billplz as Billplz V4 API
    participant Queue as Redis Queue Worker

    Customer->>UI: Click "Proceed to Billplz Payment"
    UI->>Service: Validate customer_profiles snapshot & terms

    Note over Service,DB: Global Lock Hierarchy: training_sessions -> orders -> payments -> bookings
    Service->>DB: BEGIN TRANSACTION & SELECT training_sessions FOR UPDATE
    Service->>DB: Check status IN ('published','full') & UTC_TIMESTAMP < registration_closes_at
    Service->>DB: Sweep expired holds (reservation_expires_at <= UTC_TIMESTAMP -> expired)
    Service->>DB: Recompute active_reserved + active_confirmed < capacity
    Service->>DB: Insert orders ('session_booking'), order_items ('training_session', qty=1)
    Service->>DB: Insert payments (stable payment_reference, status='pending', bill_id=NULL)
    Service->>DB: Insert bookings (status='pending_payment', reservation_expires_at=+15 min)
    Service->>DB: Recompute training_sessions counters (+1 reserved; 'full' if at capacity)
    Service->>DB: COMMIT TRANSACTION

    Note over Service,Billplz: External HTTP call strictly outside DB locks
    Service->>Billplz: POST Create Bill (amount cents MYR, reference_1=payment_reference, callback_url, redirect_url)
    Billplz-->>Service: Return Bill ID (gateway_bill_id) & checkout URL
    Service->>DB: Short locked update: save gateway_bill_id & gateway_payment_url on payments
    UI-->>Customer: Redirect browser to Billplz hosted checkout

    Customer->>Billplz: Completes FPX / online payment
    par Browser Return (Informational Only)
        Billplz-->>Customer: GET /checkout/return?billplz[id]=...
        Customer->>UI: Poll GET /checkout/return (read-only until webhook settles)
    and Server-to-Server Callback (Authoritative)
        Billplz->>UI: POST /webhooks/billplz (form-urlencoded + x_signature)
        UI->>UI: Verify HMAC-SHA256 X-Signature (canonical sorted keys without '=')
        UI->>DB: Persist payment_webhook_events (unique idempotency_key if verified)
        UI-->>Billplz: Return HTTP 200 within 20s & dispatch fulfilment job
    end

    Queue->>DB: Lock hierarchy (training_sessions -> orders -> payments -> bookings -> refunds)
    alt Paid within 15-min hold OR late callback while session open & seat free
        Queue->>DB: payments -> successful, orders -> paid, bookings -> confirmed
        Queue->>DB: Seed attendance row (attendance_status='pending', review_status='draft')
        Queue->>DB: Recompute session reserved & confirmed counters
        Queue-->>Customer: Queue Booking Confirmation & Payment Receipt emails
    else Late callback after hold expired AND session full/closed/started/cancelled
        Queue->>DB: payments -> successful, orders -> paid, bookings stays expired
        Queue->>DB: Insert refunds (status='requested', request_source='system', source_event='late_payment_capacity_unavailable')
        Queue-->>Customer: Notify customer & escalate to Admin Refund queue
    end
```

---

### 3.3 Journey 3 — Independent Module Purchase, Lifetime Entitlement & Learning Access

Covers `FR-06`, `FR-07`, `D-006`, `D-018`, `D-030`, `D-031`, `D-032`.

```mermaid
flowchart TD
    Browse["Customer views Module Detail (/training/{slug})"]
    Eligible{"is_available_independently = true & no active entitlement?"}
    AlreadyOwned["Show 'Lifetime Access Owned' -> Open /portal/modules/{slug}"]
    Checkout["/checkout/module/{slug}: confirm MYR price & D-018 notice (module certificate eligible; no physical session)"]
    LockTx["Transaction (orders -> payments): insert orders ('module_purchase'), order_items ('module', qty=1), payments ('pending')"]
    Billplz["Call Billplz Create Bill -> Redirect Customer to Billplz"]
    Webhook["Billplz POST /webhooks/billplz -> HMAC verified -> HTTP 200 -> Dispatch Fulfilment Job"]
    Fulfil["Lock orders -> payments -> module_entitlements: mark paid/successful & insert module_entitlements (grant_source='purchase', status='active', no expiry)"]
    Notify["Send Purchase Receipt & Module Access email"]
    Learn["Customer opens /portal/modules/{slug} (Policy verifies active module_entitlements)"]
    R2["Download PDF/Worksheet -> App issues short-lived signed Cloudflare R2 URL"]
    Bunny["Watch Module Video -> App issues signed Bunny Stream HLS embed URL"]

    Browse --> Eligible
    Eligible -->|"Already owned"| AlreadyOwned
    Eligible -->|"Eligible"| Checkout --> LockTx --> Billplz --> Webhook --> Fulfil --> Notify --> Learn
    Learn --> R2
    Learn --> Bunny
```

---

### 3.4 Journey 4 — Instructor Attendance Recording, Submission & Recording Upload

Covers `FR-08`, `D-017`, `D-022`, `D-025`, SRS Sec. 2.2.

```mermaid
flowchart TD
    Start["Assigned Instructor opens /instructor/sessions/{code}/attendance"]
    StatusGate{"Session status is 'in_progress' or 'completed' AND review_status in ('draft','correction_requested')?"}
    ReadOnly["Show Read-Only Roster + Verification Status Badge"]
    CorrectionBanner{"review_status = 'correction_requested'?"}
    ShowNotes["Display Admin verification_notes banner explaining needed changes"]
    MarkLoop["Tap Present / Absent / Excused per confirmed trainee (updates recorded_by, recorded_at, check_in_at)"]
    AllMarked{"Any trainee still 'pending' (Unmarked)?"}
    DisableSubmit["Disable Submit button (enforces chk_attendance_marked_before_submit)"]
    Submit["Click 'Submit Attendance for Admin Review' -> review_status = 'submitted', log audit_logs"]
    Completion["Navigate to Completion tab -> Enter completion_notes -> completion_submitted_at recorded"]
    Recording["Navigate to Recordings tab -> Upload/link Bunny Stream video -> session_recordings (status='pending_review')"]

    Start --> StatusGate
    StatusGate -->|"No"| ReadOnly
    StatusGate -->|"Yes"| CorrectionBanner
    CorrectionBanner -->|"Yes"| ShowNotes --> MarkLoop
    CorrectionBanner -->|"No"| MarkLoop
    MarkLoop --> AllMarked
    AllMarked -->|"Yes"| DisableSubmit -.->|"Mark remaining"| MarkLoop
    AllMarked -->|"No (0 pending)"| Submit --> Completion --> Recording
```

---

### 3.5 Journey 5 — Administrator Attendance Verification, Certificate Issuance & Public QR Verification

Covers `FR-09`, `FR-10`, `BR-09`–`BR-13`, `D-007`, `D-017`, `D-027`, `D-034`.

```mermaid
sequenceDiagram
    actor Admin
    participant Filament as Filament 5 (/admin)
    participant DB as MySQL 8.4
    participant Queue as Queue Worker (GenerateCertificatePdfJob)
    participant R2 as Cloudflare R2
    actor Verifier as Public Verifier / Employer

    Admin->>Filament: Open Training Session -> Click "Confirm Completion"
    Filament->>DB: Update training_sessions.status = 'completed', completed_by, completed_at

    Admin->>Filament: Open Attendance Review (filter: review_status = 'submitted')
    alt Discrepancy found in roster
        Admin->>Filament: Click "Request Correction" with verification_notes
        Filament->>DB: Update review_status = 'correction_requested' & notify Instructor
    else Attendance roster accurate
        Admin->>Filament: Bulk Action "Verify Selected Attendance"
        Filament->>DB: Update review_status = 'verified', verified_by, verified_at, log audit_logs
        Note over DB: Present + Verified immediately unlocks attendees_only Session Recordings (D-017)
    end

    Admin->>Filament: Open Certificates -> "Eligible Attendees Queue"
    Admin->>Filament: Select eligible attendees -> Click "Approve & Generate Certificates"
    Filament->>DB: Validate system_settings (signatory_name & signatory_title non-empty - D-027)
    Filament->>DB: Insert certificates (status='pending', pdf_generation_status='queued', approved_by, immutable *_snapshot)
    Filament->>Queue: Dispatch GenerateCertificatePdfJob

    Queue->>DB: Set pdf_generation_status = 'generating', increment attempts
    Queue->>Queue: Render PDF from immutable snapshots + QR URL (/verify/{verification_token})
    Queue->>R2: Upload private PDF to R2 & compute SHA-256 checksum
    alt Upload succeeds
        Queue->>DB: Update pdf_generation_status = 'completed', status = 'issued', issued_at, pdf_storage_key
        Queue-->>Admin: Notify Trainee via Email & Portal Notification
    else Rendering or R2 failure
        Queue->>DB: Set pdf_generation_status = 'failed', record pdf_generation_error (Admin can Retry without new cert row)
    end

    Verifier->>Filament: Scan QR or search /verify/{token_or_number}
    Filament->>DB: Log lookup in certificate_verifications
    Filament-->>Verifier: Display BR-13 minimised result (VALID / REVOKED / PENDING / NOT FOUND)
```

---

### 3.6 Journey 6 — Physical Session Cancellation & Administrator-Managed Refund Workflow

Covers `FR-03`, `FR-05`, `BR-15`, `BR-16`, `D-021`, `D-033`, `R-01`, `R-08`.

```mermaid
flowchart TD
    AdminCancel["Admin opens Training Session in Filament -> Click 'Cancel Session'"]
    Modal["Modal displays active bookings count & requires mandatory cancellation_reason"]
    LockAll["Transaction Lock Order: training_sessions -> orders -> payments -> bookings -> refunds (ASC IDs)"]
    UpdateSession["Set training_sessions.status = 'cancelled', cancelled_at, cancelled_by, reserved=0, confirmed=0"]
    UnpaidBkg["Unpaid holds (pending_payment): bookings -> cancelled, orders -> cancelled, queue Delete Bill job"]
    PaidBkg["Paid seats (confirmed): bookings -> cancelled (keep confirmed_at), insert refunds (status='requested', request_source='system', source_event='session_cancelled')"]
    NotifyCancel["Queue Session Cancellation & Refund Initiated emails + in-app notifications"]

    AdminRefund["Admin opens /admin/refunds (Requested queue badge)"]
    SelectMethod["Admin clicks 'Approve Refund' -> selects refund_method per R-08 (Payment Order / Manual Bank Transfer) & confirms amount"]
    Approve["refunds -> approved (approved_by, approved_at)"]
    Disburse["Execute Payment Order API or record manual transfer -> refunds -> processing"]
    Complete["Confirm payout -> set disbursement_reference, refunds -> completed, update payments.refunded_amount"]
    SyncStatus{"Full or Partial Refund?"}
    FullSync["orders -> refunded, payments -> refunded, bookings -> refunded (or module_entitlements -> revoked)"]
    PartSync["orders -> partially_refunded, payments -> partially_refunded"]
    FinalNotify["Log audit_logs & email Trainee Refund Confirmation Receipt"]

    AdminCancel --> Modal --> LockAll --> UpdateSession
    UpdateSession --> UnpaidBkg
    UpdateSession --> PaidBkg
    UnpaidBkg --> NotifyCancel
    PaidBkg --> NotifyCancel
    NotifyCancel --> AdminRefund --> SelectMethod --> Approve --> Disburse --> Complete --> SyncStatus
    SyncStatus -->|"Full"| FullSync --> FinalNotify
    SyncStatus -->|"Partial"| PartSync --> FinalNotify
```

---

## 4. Cross-Experience Role & Permission Matrix

Enforced server-side via Laravel Policies and Middleware (`D-026`, SRS Sec. 2 & 23), never solely by hiding UI navigation links:

| Capability / Experience Area | Guest | Customer (`customer`) | Instructor (`instructor`) | Administrator (`administrator`) |
|---|---|---|---|---|
| Browse public pages & verify certificates (`/verify`) | Yes | Yes | Yes | Yes |
| Register account & verify email | Yes | — | — | — |
| Book physical session / Buy independent module | Redirect to Login | Yes (self only, `D-023`) | Only if also holds `customer` role (`D-026`) | Only if also holds `customer` role (`D-026`) |
| View own bookings, orders, receipts & certificates (`/portal`) | No | Yes | Only if also holds `customer` role | Only if also holds `customer` role |
| Download `module_materials` & stream `module_videos` | No | Only with active `module_entitlements` | Only for modules of assigned sessions (`Sec. 6.3`) | Yes |
| Stream `session_recordings` (`attendees_only`) | No | Only if `confirmed` + `present` + `verified` (`D-017`) | Only for assigned sessions | Yes |
| View assigned sessions & participant names (`/instructor`) | No | No | Assigned sessions only (`session_instructors`) | Yes (via `/admin`) |
| Record & submit attendance (`draft` / `correction_requested`) | No | No | Assigned sessions only (`in_progress`/`completed`) | Yes |
| Verify attendance, override attendance, approve certificates | No | No | No | Yes (via `/admin` + TOTP MFA + `audit_logs`) |
| Submit session completion notes & recordings (`pending_review`) | No | No | Assigned sessions only | Yes (can publish directly) |
| Publish modules/sessions, cancel sessions, manage refunds | No | No | No | Yes (via `/admin` + TOTP MFA + `audit_logs`) |
| Configure `system_settings`, RBAC roles, view `audit_logs` | No | No | No | Yes (via `/admin` + TOTP MFA) |
