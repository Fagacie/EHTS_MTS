# Ecohydrotech Training Management System (ETMS)
## Software Requirements Specification (SRS) and System Architecture Specification

| Property | Value |
|---|---|
| Project | Ecohydrotech Training Management System |
| Owner | Ecohydrotech Solution |
| Version | 1.1 |
| Baseline date | 17 November 2026 |
| Status | Approved working baseline for detailed design |
| Target market | Malaysia |
| Application | Responsive web platform |

> **Frontend Revision Notice (`v1.2`):** For incremental frontend modernisation (`Vue 3 + TypeScript + Inertia.js + shadcn-vue` alongside coexisting Blade/Livewire views and permanently separate Filament 5 `/admin`), see [SRS & System Architecture v1.2](ETMS-SRS-and-System-Architecture-v1.2.md). This `v1.1` document remains preserved and authoritative for all functional requirements, business rules (`BR-01`–`BR-16`), roles (`D-004`, `D-026`), and database constraints.

> This is the authoritative requirements and architecture baseline. Antigravity and other development tools must read it before proposing or implementing changes. Do not silently change approved business rules or add out-of-scope features.

---

# PART A — SOFTWARE REQUIREMENTS SPECIFICATION

## 1. Introduction

### 1.1 Purpose
The Ecohydrotech Training Management System (ETMS) is a web-based platform for managing Ecohydrotech Solution's training operations. It centralises training publication, physical session scheduling, customer registration, online payments, learning-material delivery, attendance management and certificate issuance.

The system supports two delivery models:

1. **Physical training:** A customer registers and pays for a scheduled in-person training session at a specified venue and time.
2. **Independent module purchase:** A customer purchases an existing training module and accesses its authorised learning materials online without registering for a physical session.

The platform serves both individual customers and customers purchasing on behalf of a company. Both use the same customer account and checkout workflow.

### 1.2 Objectives
- Digitalise training registration and booking.
- Manage reusable training modules and multiple scheduled sessions per module.
- Allow customers to purchase physical sessions or independent modules.
- Process payments through a Malaysian payment gateway.
- Send payment, booking and training notifications.
- Securely distribute module materials and authorised session recordings.
- Record and review physical attendance.
- Generate and verify digital certificates.
- Provide administrative reporting and financial visibility.
- Establish a maintainable foundation for future expansion.

### 1.3 Scope
The initial release includes:
- Public website and training catalogue.
- Customer registration, authentication and profile.
- Administrator and instructor access.
- Training categories and module management.
- Physical training session scheduling.
- Session booking, seat capacity and registration deadlines.
- Online payment integration.
- Independent module purchasing and lifetime access entitlement.
- Module documents and video management.
- Physical session recordings.
- Attendance management.
- Certificate generation, QR verification and revocation.
- Email notifications and announcements.
- Administrative dashboard and reports.
- Production deployment, backups and operational documentation.

### 1.4 Explicitly out of scope for the initial release
- Separate corporate customer portal or dashboard.
- Corporate customer role.
- Organisation account administration.
- Corporate coordinator accounts.
- Employee/member management or employee assignment.
- Bulk employee seat allocation and management.
- Live virtual classroom or video conferencing.
- Native Android/iOS applications.
- Complex examination and assessment functionality.
- Subscription-based learning.
- Multi-tenant company management.
- Advanced marketing automation.
- Accounting software integration.

**Company customer handling:** Companies may purchase using the standard customer workflow. Capture company name, billing contact and billing address where required for an order or receipt. Do not build organisation/member entities, a corporate portal or employee-management workflows in the initial release.

## 2. Users and roles

The system has exactly three primary application roles.

### 2.1 Administrator
The administrator has overall platform control and can:
- Manage customer and instructor accounts.
- Create, edit, publish, unpublish and archive training modules.
- Manage categories, descriptions, prices and learning materials.
- Create, schedule, update and cancel physical sessions.
- Assign instructors to sessions.
- View and manage bookings and participant lists.
- Monitor payments and manage authorised refund workflows.
- Upload and publish session recordings.
- Review attendance and approve certificate eligibility.
- Issue and revoke certificates.
- Send announcements and notifications.
- View operational and financial reports.
- Configure permitted system settings.

### 2.2 Instructor
An instructor can:
- View sessions assigned to them.
- View participants registered for assigned sessions.
- View relevant session details.
- Record or submit attendance.
- Submit session completion information.
- Submit session recordings for administrator review/publication.

An instructor must not receive unrestricted access to user administration, payment management, certificate administration or global system configuration.

### 2.3 Trainee / Customer
A trainee/customer can:
- Register, verify email, log in and manage their profile.
- Browse modules and upcoming physical sessions.
- Purchase and book a physical training session.
- Purchase an independent module.
- Make online payments.
- View orders, payment history and booking history.
- Access modules they have purchased or been explicitly granted.
- Watch session recordings when authorised by the session access policy.
- Receive notifications.
- Download certificates that have been issued to them.

Individual and company customers use this same role and workflow. Company details are optional billing/customer information, not a separate role.

## 3. Functional requirements

### FR-01 — Registration and authentication
The system shall:
1. Allow customers to create accounts.
2. Support secure login and logout.
3. Support email verification.
4. Support password reset.
5. Allow customers to update profile information.
6. Enforce role-based access control.
7. Allow administrators to activate, suspend or deactivate accounts.
8. Protect authenticated routes and customer data.

### FR-02 — Training module management
Administrators shall be able to:
1. Create and edit training modules.
2. Archive modules and delete only where safe and permitted.
3. Maintain module code, title, description and learning objectives.
4. Assign categories and cover images.
5. Set an independent module purchase price.
6. Upload or associate PDF and other learning materials.
7. Add and manage module videos.
8. Publish and unpublish modules.
9. View module purchase and entitlement records.

A module should contain, where applicable: unique module code, title, description, learning objectives, category, cover image, independent purchase price, publication status, materials/videos and timestamps.

### FR-03 — Physical session management
Administrators shall be able to:
1. Create physical sessions associated with an existing module.
2. Set session title and description.
3. Set training date, start time and end time.
4. Calculate and display duration.
5. Set venue and location details.
6. Assign one or more instructors where required.
7. Set participant capacity.
8. Set registration opening/closing dates or deadline.
9. Set session price.
10. Publish, close, update or cancel a session.

A single module may have multiple physical sessions. Session price and module self-paced purchase price are separate commercial values.

Suggested session lifecycle: Draft → Published / Registration Open → Full or Registration Closed → In Progress → Completed. Cancellation must be represented explicitly and trigger the configured customer notification/refund workflow.

### FR-04 — Booking and registration
The system shall:
1. Display published sessions and their availability.
2. Allow an authenticated customer to select a session.
3. Create a pending booking/order before payment.
4. Temporarily reserve a seat during checkout.
5. Confirm a booking only after verified payment or an explicitly authorised alternative-payment approval.
6. Prevent overbooking, including concurrent checkout attempts.
7. Expire unpaid temporary reservations after the configured hold period.
8. Show customers their booking history and status.
9. Allow administrators to view and export participant lists.

Suggested booking statuses: Pending Payment, Confirmed, Cancelled, Expired, Refunded, Attended and Absent.

Attendance should be stored in the attendance record, not inferred solely from a booking status. Avoid using one status field to represent unrelated payment, booking and attendance lifecycles.

### FR-05 — Payment integration
The initial proposed provider is **Billplz**, subject to merchant onboarding, product availability and confirmation of current commercial terms.

The system shall:
1. Create payment requests from server-side order records.
2. Redirect customers to the gateway checkout.
3. Receive payment callbacks/webhooks.
4. Verify callback authenticity and transaction details.
5. Check amount, currency and internal order reference.
6. Record gateway identifiers and transaction references.
7. Update payment state.
8. Issue receipts or payment confirmations.
9. Handle failed, cancelled and pending transactions.
10. Support an administrator-controlled refund process where supported.

Payment statuses: Pending, Successful, Failed, Cancelled, Refunded and Partially Refunded.

The verified server-side gateway notification is authoritative. A browser redirect or success page must never independently mark an order as paid. Webhook processing must be idempotent so repeated callbacks cannot create duplicate bookings or entitlements.

Never store customer banking credentials or card details.

### FR-06 — Independent module purchase
Customers shall be able to purchase a module without booking a physical session.

Workflow:
1. Browse the module catalogue.
2. Open module details and price.
3. Add the module to an order.
4. Complete gateway payment.
5. Verify payment server-side.
6. Create a module entitlement.
7. Notify the customer.
8. Allow access to authorised module materials.

**Access policy:** Successful purchase grants lifetime access to that module, subject to lawful content availability and the platform's service terms. Entitlement must be recorded independently from the payment transaction.

### FR-07 — Learning materials and videos
Administrators shall manage PDFs/documents, presentations/images, existing module videos and recorded physical training sessions.

Keep these content types distinct:

- **Module materials:** Belong to a module and are accessible to customers with a valid entitlement or explicit administrator grant.
- **Session recordings:** Belong to a specific physical session and are accessible only to participants authorised under the configured session recording policy.

The system shall support metadata, publication status, content association, access checks and secure delivery. Large video binaries shall not be stored in MySQL or served repeatedly from the application VPS.

### FR-08 — Attendance
Instructors and authorised administrators shall be able to:
1. View participants for a session.
2. Mark each participant present or absent.
3. Submit attendance for review.
4. Correct attendance where authorised, retaining an audit trail.
5. View attendance summaries.

Attendance must be associated with a specific booking/participant and training session.

### FR-09 — Certificates
The agreed certificate rule is **verified physical attendance plus administrator approval**.

Workflow:
1. Session is completed.
2. Instructor/administrator records attendance.
3. Administrator reviews attendance.
4. Administrator approves eligible participants.
5. System generates an individual PDF certificate.
6. Certificate is stored and linked to the trainee and session.
7. Certificate appears in the trainee portal.
8. Notification is sent.

Certificate fields: Ecohydrotech Solution branding/logo, trainee's full name, training title, training date, unique certificate reference, issue date, authorised signatory, QR code and verification URL.

Payment alone does not qualify a trainee. An independent module purchase does not automatically qualify for a physical training certificate. Certificate revocation must be supported.

### FR-10 — Certificate verification
Provide a public verification page supporting QR scan or certificate reference lookup. Display only necessary information: validity/status, trainee name, training title, training date, issuing organisation and certificate reference.

Revoked, invalid or unknown certificates must be clearly distinguished. Do not expose unnecessary personal information.

### FR-11 — Notifications
Email notifications shall cover:
- Account registration and verification.
- Successful payment and receipt.
- Booking confirmation.
- Session changes or cancellation.
- Upcoming session reminders.
- Independent module purchase.
- Learning material availability.
- Certificate issuance.

Administrators shall be able to send announcements to selected users or participants of a session. WhatsApp is a future enhancement, not an MVP dependency.

### FR-12 — Dashboard and reports
The administrator dashboard should show registered customer count, module count, upcoming sessions, booking count, completed sessions, successful payment totals, revenue summaries and certificate issuance statistics.

Reports should include session participant lists, registration/booking reports, attendance reports, payment/refund reports, revenue reports, module sales reports and certificate reports. PDF and spreadsheet exports should be supported where useful.

## 4. Business rules

| ID | Rule |
|---|---|
| BR-01 | A customer account is required before purchasing. |
| BR-02 | A physical booking is confirmed only after verified payment or authorised alternative-payment approval. |
| BR-03 | Session capacity must never be exceeded. |
| BR-04 | Every physical session belongs to a training module. |
| BR-05 | One module may have multiple scheduled sessions. |
| BR-06 | Independent module purchase does not require physical session registration. |
| BR-07 | Purchased modules have lifetime entitlement. |
| BR-08 | Module materials and session recordings have separate access rules. |
| BR-09 | Physical certificates require verified attendance. |
| BR-10 | Administrator approval is required before certificate issuance. |
| BR-11 | Payment alone does not qualify a trainee for certification. |
| BR-12 | Every certificate has a unique reference. |
| BR-13 | Public verification must minimise personal information. |
| BR-14 | Company customers use the normal customer workflow; no corporate portal or employee management is included. |
| BR-15 | Financial records must be retained for audit purposes. |
| BR-16 | Session cancellation triggers appropriate notification and refund handling. |

## 5. Non-functional requirements

### 5.1 Performance
- Public pages should load efficiently under normal network conditions.
- Optimise database queries and indexes.
- Use queues for slow email, media and document-generation work.
- Deliver videos through a dedicated streaming provider.
- Protect seat allocation against concurrent booking race conditions.

### 5.2 Security
- HTTPS in production.
- Secure password hashing.
- Role-based authorisation and policies.
- Email verification.
- Payment webhook verification.
- Private media and document access.
- Upload type/size validation.
- Rate limiting and CSRF protection.
- Administrative audit logs.
- Secrets in environment configuration, never committed to Git.
- Protected backups.

### 5.3 Availability
Target 99.5% monthly application availability, excluding planned maintenance and external provider outages. This is a target, not a guarantee.

### 5.4 Scalability
The application should accommodate growth in customers, modules, sessions, payments and media without requiring an architectural rewrite.

### 5.5 Usability
Responsive desktop, tablet and mobile interfaces; consistent navigation; clear booking and checkout flows; accessible forms; professional corporate visual design.

### 5.6 Maintainability
Modular code organisation, migrations, automated tests, version control, structured logging and current technical documentation.

---

# PART B — SYSTEM ARCHITECTURE SPECIFICATION

## 6. Technology stack

| Layer | Proposed technology | Responsibility |
|---|---|---|
| Backend | Laravel 13 | Business logic, routing, validation, APIs |
| Language | PHP 8.3+ | Application runtime |
| Frontend | Blade + Livewire 4 | Server-driven interactive UI |
| Admin interface | Filament 5 | Administrative resources and dashboards |
| Styling | Tailwind CSS 4 | Responsive design system |
| Database | MySQL 8.4 | Relational business data |
| Payment | Billplz | Malaysian payment checkout and callbacks |
| Video | Bunny Stream | Video storage, processing and streaming |
| Documents | Cloudflare R2 | PDFs, images and downloadable files |
| Email | Resend or Postmark | Transactional email |
| Queue/cache | Redis | Queues and cache |
| Containerisation | Docker and Docker Compose v2 | Consistent development and deployment containers (`app`, `nginx`, `mysql`, `redis`, `queue`, `scheduler`) |
| Web server | Nginx | HTTP reverse proxy and PHP delivery |
| Hosting | Ubuntu VPS | Production runtime |
| DNS/security | Cloudflare | DNS, SSL and edge protection |
| Version control | GitHub | Source control |
| Deployment | GitHub Actions or controlled deployment workflow | Release automation |

Verify framework, PHP, Filament and Livewire compatibility before installation. Do not blindly install incompatible latest versions. Record actual installed versions in the project README and lock files.

### 6.1 Architectural style
Use a **modular monolith**: one Laravel application with clearly separated business domains. Do not introduce microservices, a separate Node backend, or a separate React/Next.js frontend for the initial release. Keep the architecture simple and maintainable.

## 7. High-level architecture

The logical request flow is:

1. Customers, instructors and administrators use the public site or authenticated portals.
2. Cloudflare provides DNS and edge security; Nginx serves the application.
3. Laravel handles authentication, authorisation, validation and business workflows.
4. Domain modules handle training, bookings, payments, learning access, attendance and certificates.
5. MySQL stores structured application data.
6. Redis supports cache and queued jobs.
7. Billplz processes payments; Bunny Stream delivers video; Cloudflare R2 stores documents.
8. Email and background jobs process notifications and other asynchronous work.

## 8. Application modules

1. **Authentication:** registration, login, email verification, password reset, roles and permissions.
2. **Customer profiles:** customer contact/profile information and optional company billing details. No corporate account subsystem.
3. **Training catalogue:** categories, module descriptions, pricing, publication and materials.
4. **Session management:** schedule, venue, capacity, instructor assignment, deadlines and lifecycle.
5. **Booking:** pending reservations, seat allocation, confirmation, cancellation and participant lists.
6. **Orders and payments:** order snapshots, gateway requests, callbacks, payment history and refunds.
7. **Learning access:** module entitlements, document access and video playback authorisation.
8. **Attendance:** participant attendance, review and correction history.
9. **Certificates:** eligibility, approval, PDF generation, QR verification and revocation.
10. **Notifications:** email, reminders and announcements.
11. **Reporting:** revenue, booking, attendance, sales and certificate reports.

## 9. Database architecture

Use MySQL with foreign keys, unique constraints, appropriate indexes and database transactions.

### 9.1 Proposed tables

**Identity and customer**
- users — login and account identity.
- roles / permission tables — role and permission definitions, using the selected RBAC implementation.
- customer_profiles — customer-specific contact information and optional company billing details.

**Training**
- categories
- modules
- module_materials
- module_videos
- training_sessions
- session_instructors
- session_recordings

**Commerce and booking**
- orders
- order_items
- bookings
- payments
- payment_webhook_events
- refunds

**Learning and certification**
- module_entitlements
- attendance
- certificates
- certificate_verifications

**Operations**
- notifications or framework notification tables.
- announcements
- audit_logs

Framework infrastructure may add queue, cache, session and password-reset tables.

Do not create organizations, organization_members, corporate coordinator or employee assignment tables in this initial version.

### 9.2 Relationship principles
- A module has many physical sessions.
- A module has many module materials and module videos.
- A session belongs to one module.
- A session may have multiple assigned instructors.
- A customer has many orders and bookings.
- An order has one or more order items.
- An order may have payment attempts.
- A successful order item for a module creates a module entitlement.
- A physical session booking is associated with a customer and session.
- Attendance belongs to the relevant booking/participant and session.
- A certificate belongs to a trainee and completed session.
- Session recordings belong to a specific session.

The detailed ERD and data dictionary are a separate next-stage deliverable. Do not generate migrations until the ERD and field definitions are reviewed and approved.

### 9.3 Data design rules
- Use decimal types for monetary amounts, never floating point.
- Store currency explicitly (initially MYR).
- Preserve order item name, price and relevant details as purchase snapshots.
- Keep order, payment, booking, attendance and entitlement states distinct.
- Use unique public references for orders, payments and certificates.
- Use database transactions for critical purchase and seat-allocation operations.
- Use timestamps consistently; display in Asia/Kuala_Lumpur.
- Store file metadata and provider identifiers, not large media binaries.
- Preserve historical payment and certificate records.
- Apply soft deletion only where business retention rules permit.

## 10. Payment and booking workflows

### 10.1 Physical session purchase
1. Customer selects a session.
2. System checks registration window and capacity.
3. System creates a pending booking and order.
4. System temporarily reserves a seat.
5. System creates a Billplz payment request.
6. Customer completes payment.
7. Billplz sends a server callback.
8. Server verifies signature, amount, currency and order.
9. System idempotently records successful payment.
10. System confirms booking and consumes the seat.
11. System queues receipt and confirmation email.

If payment fails or the reservation expires, release the seat. Seat reservation must be concurrency-safe.

### 10.2 Independent module purchase
1. Customer selects module.
2. System creates order and order item.
3. Customer completes Billplz checkout.
4. Server verifies callback.
5. System records successful payment.
6. System creates lifetime module entitlement.
7. System notifies customer and enables authorised access.

### 10.3 Alternative payment
If Ecohydrotech later accepts bank transfer or another manual method, represent it as a distinct payment method with pending verification. Only an authorised administrator may mark it approved, with reference/evidence and an audit log. An uploaded receipt is not automatic proof of payment.

## 11. Media and document storage

### 11.1 Bunny Stream — videos
Use for existing module videos and recorded physical sessions. Store provider video IDs and metadata in MySQL. Use private playback, signed tokens/URLs or equivalent provider-supported access controls. Check user entitlement before issuing playback access.

### 11.2 Cloudflare R2 — documents
Use for PDFs, certificates, images and downloadable materials. Keep buckets private by default. Deliver downloads through authorised application routes or short-lived signed URLs.

### 11.3 Content access rules
- Module materials require a valid module entitlement or explicit authorised grant.
- Session recordings require the configured session-specific eligibility rule.
- Module access and session recording access must not be conflated.
- Validate file type, file size and upload permissions.
- Do not store large video binaries in MySQL or the application web root.

## 12. Certificate architecture

1. Session is marked completed.
2. Attendance is submitted.
3. Administrator reviews attendance.
4. Administrator approves eligible trainee.
5. System generates certificate PDF and unique reference.
6. System generates QR verification URL.
7. System stores PDF securely.
8. Certificate becomes available in trainee portal.
9. System sends notification.

Statuses: Pending, Issued, Revoked. Retain an audit history for issue/revocation actions. Public verification displays only necessary certificate information.

## 13. Deployment architecture

### 13.1 Initial production server
Planning baseline:
- Ubuntu VPS.
- 2 vCPU.
- 4 GB RAM.
- Approximately 80 GB SSD.
- Docker Engine and Docker Compose v2 runtime.
- Nginx and PHP-FPM containers.
- Laravel application (`app`), queue worker (`queue`) and scheduler (`scheduler`) containers.
- MySQL 8.4 container with persistent storage volume.
- Redis container with persistent storage volume.
- Cloudflare DNS/SSL.

This is a starting estimate, not a guaranteed capacity specification. Reassess using actual traffic, queue load, database size and media usage.

#### 13.1.1 Docker container architecture (`D-036`)
The application uses Docker and Docker Compose v2 for consistent local development and production deployment across:
- **`nginx`:** HTTP reverse proxy, static compiled asset serving and FastCGI forwarding to PHP-FPM.
- **`app`:** Laravel 13 modular monolith running on PHP 8.3+-FPM.
- **`queue`:** Background queue worker container processing Redis jobs (payment webhook reconciliation, asynchronous certificate PDF generation, emails and announcements).
- **`scheduler`:** Scheduled task runner container executing Laravel Scheduler (15-minute booking hold expiry, `etms:reconcile-session-seats` and reminder dispatches).
- **`mysql`:** MySQL 8.4 container backed by a persistent volume for relational business data.
- **`redis`:** Redis container backed by a persistent volume for queues, caching and session state.

Persistent storage volumes protect database files, Redis data and required application storage (logs and temporary processing files). External services — **Billplz**, **Bunny Stream**, **Cloudflare R2** and the transactional email provider — remain managed external integrations rather than local containers.

### 13.2 Deployment workflow
1. Developer works locally and runs tests.
2. Changes are committed to a feature branch.
3. Changes are reviewed and merged to the main branch.
4. Build and deployment checks run.
5. Production backup is taken before applicable migrations.
6. Application is deployed.
7. Health checks and release verification are performed.

Keep local, staging (if provisioned) and production configuration separate. Never commit .env, secrets, production database dumps or customer data.

### 13.3 Backup strategy
- Daily automated database backups.
- Off-site backup copies.
- Defined daily/weekly/monthly retention.
- Backup application-owned uploads and configuration as required.
- Restrict backup access.
- Periodically test restoration.
- Document recovery steps.

A backup is not considered verified until restoration has been tested.

## 14. Security architecture

Implement:
1. HTTPS.
2. Secure password hashing.
3. Role-based authorisation and policies.
4. Email verification.
5. Administrator MFA.
6. Payment callback verification.
7. Rate limiting.
8. CSRF protection.
9. Upload validation.
10. Private media delivery.
11. Restricted database access.
12. Administrative audit logs.
13. Environment-based secrets.
14. Automated protected backups.
15. Appropriate Malaysian PDPA privacy and data-handling practices.

---

# PART C — DEVELOPMENT PLAN AND ACCEPTANCE

## 15. Proposed implementation phases

| Phase | Scope | Exit deliverable |
|---|---|---|
| 0 | Requirements baseline and project setup planning | Approved project baseline |
| 1 | ERD, data dictionary and business-state design | Approved database design |
| 2 | UI/UX blueprint and navigation | Approved page and interaction design |
| 3 | Laravel foundation, authentication, roles and design system | Working application foundation |
| 4 | Customer profiles, categories and module management | Training catalogue/admin |
| 5 | Session scheduling, instructor assignment and booking | Working session workflow |
| 6 | Orders, Billplz and payment reconciliation | Tested payment workflow |
| 7 | Module entitlements, documents and video integration | Learning access |
| 8 | Attendance, certificates and QR verification | Certification workflow |
| 9 | Notifications, reports and operational dashboard | Admin operations |
| 10 | Security, UAT, deployment, backup and handover | Production release |

The earlier 6–8 week estimate is provisional. Actual duration depends on scope validation, merchant onboarding, external integrations and acceptance testing.

## 16. Testing strategy

- **Unit tests:** domain services, calculations, state transitions and access rules.
- **Feature tests:** registration, booking, orders, payments, entitlements, attendance and certificates.
- **Integration tests:** Billplz sandbox, media provider and object storage.
- **Security tests:** role access, webhook verification, upload validation and private content.
- **Concurrency tests:** last-seat booking and duplicate webhook processing.
- **User acceptance testing:** Ecohydrotech staff validate real business workflows.

Essential scenarios:
- Customer registers and purchases a physical session.
- Customer purchases an independent module.
- Company customer purchases through the same standard customer flow.
- Full session rejects additional bookings.
- Failed payment does not confirm a booking.
- Duplicate callback does not duplicate payment effects.
- Expired pending reservation releases its seat.
- Unentitled user cannot access paid materials.
- Instructor records attendance only for assigned session.
- Administrator approves a certificate after verified attendance.
- Public QR verification identifies valid and revoked certificates.
- Session cancellation triggers notification and appropriate refund workflow.

## 17. Acceptance criteria

The initial release is accepted when:
1. Customers can register and authenticate.
2. Administrators can manage modules and sessions.
3. Customers can book available physical sessions.
4. Payment status is verified server-side.
5. Successful payment produces the correct booking or module entitlement.
6. Module purchasers receive lifetime access.
7. Module materials and session recordings have separate access controls.
8. Instructors/admins can record attendance.
9. Certificates require attendance and administrator approval.
10. Certificates can be downloaded and publicly verified.
11. Essential email notifications work.
12. Reports are available to administrators.
13. Production security, backups and restoration procedures are tested.

## 18. Future enhancements

Potential future additions:
- WhatsApp notifications.
- Coupons and discounts.
- DuitNow and additional payment methods.
- Online module assessments.
- Additional certification paths.
- Multi-language interface.
- Instructor analytics.
- Mobile application.
- Accounting integration.
- Customer feedback and evaluation.

These are not MVP requirements and must not be implemented without explicit approval.

## 19. Project deliverables

- Public corporate website.
- Trainee portal.
- Administrator portal.
- Instructor portal.
- Training catalogue and module management.
- Physical session scheduling and booking.
- Payment gateway integration.
- Learning material and video integration.
- Attendance management.
- Certificate generation and verification.
- Notifications and reports.
- Production deployment and backups.
- Technical and user documentation.

## 20. Conclusion

ETMS will provide Ecohydrotech Solution with a unified platform for managing training programmes, physical sessions, customer purchases, payments, learning resources, attendance and certification.

The agreed initial architecture has three roles: Administrator, Instructor and Trainee/Customer. Individual and company customers use the same customer workflow. A separate corporate portal, organisation administration and employee management are explicitly excluded.

The modular monolithic Laravel architecture is intended to deliver the initial business requirements with manageable complexity while preserving a clear path for future enhancements.

---

# PART D — PROJECT EXECUTION RULES FOR AI DEVELOPMENT ASSISTANTS

## 21. Source of truth
1. Read this entire specification before implementation.
2. Treat Version 1.1 as the current approved baseline.
3. Do not reintroduce a corporate portal, organisation/member tables, employee management or corporate coordinator role.
4. Do not change the three-role model without explicit user approval.
5. Do not invent business rules where the specification is silent. Record ambiguity and ask for a decision.
6. Do not replace approved technologies without explaining the reason and receiving approval.
7. Do not generate the complete application in one uncontrolled operation.

## 22. Stage-by-stage workflow
Work must proceed in small, reviewable stages.

For each stage:
1. Inspect the existing project and relevant files before editing.
2. Restate the stage objective and identify affected files.
3. Propose an implementation plan.
4. Implement only the approved stage.
5. Run relevant automated tests, static checks and build commands.
6. Inspect the actual resulting files and application behaviour.
7. Report completed work, changed files, test results, errors and unresolved risks.
8. Update project documentation and progress tracking.
9. Stop and wait for the user's next instruction.

Do not silently move to the next stage.

## 23. Engineering rules
- Prefer Laravel conventions and well-separated domain services.
- Use migrations for schema changes; do not edit production data directly.
- Use seeders for controlled reference/demo data.
- Validate all user input server-side.
- Enforce authorisation through policies/gates, not merely hidden UI elements.
- Use database transactions for critical booking/payment workflows.
- Make payment callbacks idempotent.
- Do not trust client-side payment success.
- Keep secrets out of source control.
- Avoid unnecessary packages and duplicated abstractions.
- Keep UI components consistent and responsive.
- Do not use mock data as proof that a production integration works.
- Add automated tests for business-critical behaviour.
- Preserve historical orders, payments and issued certificates.
- Do not run destructive database resets or migrations against production.
- Do not overwrite unrelated files or existing projects.

## 24. Required progress documentation
Maintain these files as development proceeds:
- README.md — project overview, setup and current status.
- docs/ETMS-SRS-and-System-Architecture-v1.1.md — approved requirements and architecture baseline.
- docs/DATABASE-DESIGN.md — approved ERD, data dictionary and constraints.
- docs/DEVELOPMENT-ROADMAP.md — stages, status, decisions and blockers.
- docs/IMPLEMENTATION-LOG.md — dated implementation changes and test evidence.
- docs/DECISIONS.md — approved architectural and business decisions.

Do not alter the approved SRS baseline to conceal deviations. Record proposed changes in the decisions log and request approval first.

## 25. Definition of done for each stage
A stage is complete only when:
- The agreed scope is implemented.
- Relevant tests pass.
- Build/lint checks pass where applicable.
- No known critical regression remains.
- The actual implementation has been inspected.
- Documentation and progress status are updated.
- Remaining limitations are explicitly reported.

**End of SRS and System Architecture Specification — ETMS Version 1.1**
