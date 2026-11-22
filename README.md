# Ecohydrotech Training Management System (ETMS)

A web-based platform for Ecohydrotech Solution to manage physical training sessions, independent training module sales, payments, learning materials, attendance and certificates.

## Current project status

**Stage:** Stage 10 Complete; **Frontend Modernisation v1.2 (FM-1 Foundation & Public Homepage)** In Progress (Checkpoint Review)  
**SRS / Architecture:** [Version 1.1 Approved Baseline](docs/ETMS-SRS-and-System-Architecture-v1.1.md) · [Version 1.2 Frontend Modernisation Revision](docs/ETMS-SRS-and-System-Architecture-v1.2.md)  
**Completed Stages:** Stage 0, Stage 1 (Database Design), Stage 2 (UI/UX Blueprint), Stage 3 (Laravel Foundation, Docker, Auth, RBAC & Design System), Stage 4 (Customer Profiles, Categories & Module Management), Stage 5 (Session Scheduling, Instructor Management & Booking), Stage 6 (Payment Gateway, Checkout & Order Management), Stage 7 (Learning Materials, Video Recordings & Customer Access), Stage 8 (Certificate Generation & Management), Stage 9 (Notifications, Email & Administrative Reports), Stage 10 (Server Storage, Self-Hosted FFmpeg HLS Video, Security Hardening & Production Deployment Pack)  
**Next action:** Review Frontend Modernisation Phase D checkpoint (`FM-1`: Vue 3 + TypeScript + Inertia.js + shadcn-vue foundation and public homepage) before proceeding to `FM-2`, or provision `.env.production`, TLS certificates, and live gateway credentials on the company server per [Production Deployment Runbook](docs/PRODUCTION-DEPLOYMENT-RUNBOOK.md)

## Installed Technology Versions

- **Laravel:** `v13.43.0` (`laravel/framework`)
- **PHP:** `8.3.36` (FPM + CLI with `pdo_mysql`, `redis`, `intl`, `zip`, `bcmath`, `pcntl`, `Zend OPcache`)
- **Modernised Frontend UI (v1.2):** `vue` `v3.5.45` + `TypeScript` `~5.9.3` + `@inertiajs/vue3` `v3.11.0` + `inertiajs/inertia-laravel` `v3.7.0` + `shadcn-vue` (`reka-ui` `v2.11.0`, `lucide-vue-next` `v1.0.0`)
- **Coexisting Legacy UI:** **Livewire** `v4.4.8` (`livewire/livewire`) + Blade templates (gradually superseded by Vue/Inertia across Stages `FM-1`–`FM-5`)
- **Admin Interface (Permanently Separate):** **Filament** `v5.11.4` (`filament/filament`)
- **PDF Engine:** `barryvdh/laravel-dompdf` `v3.1.2` (`dompdf/dompdf` `v3.1.6`)
- **Email Transport:** `resend/resend-php` `v1.18.0` (configured with Resend, Postmark, and SMTP support)
- **Media Processing & Streaming:** FFmpeg (`7:5.1.9`) + `hls.js` (self-hosted private HLS streaming via untouched `resources/js/app.js`, isolated as `vendor-hls` from public Vue/Inertia routes)
- **Tailwind CSS:** `^4.0.0` (`@tailwindcss/vite` + Vite `v8.3.8` with Rolldown code splitting + self-hosted `@fontsource/plus-jakarta-sans` & `@fontsource/jetbrains-mono`)
- **Database:** MySQL `8.4` (`InnoDB`, `utf8mb4_unicode_ci`, UTC)
- **Cache & Queues:** Redis `7-alpine`
- **Containerisation:** Docker & Docker Compose v2 (`nginx`, `app`, `queue`, `scheduler`, `mysql`, `redis`)

## Local Docker Setup & Commands

1. Copy `.env.example` to `.env` and ensure `APP_KEY` is generated (`php artisan key:generate`).
2. Start the 6-service Docker Compose stack:
   ```sh
   docker compose up -d --build
   ```
3. Build or type-check frontend assets (via host Node 22+ or containerized `build` profile):
   ```sh
   npm install && npm run check
   # individual checks: npm run type-check | npm run build | npm run dev
   # containerized build: docker compose --profile build run --rm node
   ```
4. Run migrations and seed RBAC + demo accounts:
   ```sh
   docker compose exec -u www-data app php artisan migrate --seed
   ```
5. Run automated tests against MySQL 8.4 (`etms_test`):
   ```sh
   docker compose exec -u www-data app php artisan test
   ```
6. Open the application at `http://localhost:8080` (Administrator Workspace at `http://localhost:8080/admin-vue`).

### Local Demo Accounts (Password: `Password123!`)

| Account | Email | Role(s) | Notes |
|---|---|---|---|
| **Administrator** | `admin@ecohydrotech.com.my` | `administrator` | TOTP MFA secret: `JBSWY3DPEHPK3PXP` (or recovery codes ``..`0004`) |
| **Multi-Role Staff (D-026)** | `staff.trainee@ecohydrotech.com.my` | `instructor`, `customer` | Demonstrates Portal Switcher across `/instructor` and `/portal` |
| **Instructor** | `instructor@ecohydrotech.com.my` | `instructor` | Scoped to `/instructor` |
| **Trainee / Customer** | `trainee@ecohydrotech.com.my` | `customer` | Scoped to `/portal` |

## Confirmed architecture & business rules

- Three roles only: Administrator, Instructor, Trainee/Customer.
- Individual and company customers use the same customer registration and checkout workflow.
- No corporate portal, organisation/member management or employee assignment in the initial release.
- Staff members (`administrator` or `instructor`) may also hold the `customer` role when purchasing training (`D-026`).
- Administrator accounts require verified email and TOTP Multi-Factor Authentication before accessing `/admin-vue`.
- A training module may have multiple physical sessions; physical session purchases and independent module purchases are separate.
- Purchased modules provide lifetime access; physical certificates require verified attendance and administrator approval.
- Payment confirmation must come from a verified server-side Paystack webhook or server-side transaction verification.

## Project documentation

- [SRS and System Architecture v1.1 (Approved Baseline)](docs/ETMS-SRS-and-System-Architecture-v1.1.md)
- [SRS and System Architecture v1.2 (Frontend Modernisation Revision)](docs/ETMS-SRS-and-System-Architecture-v1.2.md)
- [Database Design & Data Dictionary](docs/DATABASE-DESIGN.md)
- [Entity Relationship Diagram (ERD)](docs/ERD.md)
- [UI/UX Blueprint](docs/UI-UX-BLUEPRINT.md)
- [Screen Inventory](docs/SCREEN-INVENTORY.md)
- [User Flows & Navigation](docs/USER-FLOWS.md)
- [Development Roadmap](docs/DEVELOPMENT-ROADMAP.md)
- [Implementation Log](docs/IMPLEMENTATION-LOG.md)
- [Architecture and Business Decisions](docs/DECISIONS.md)
- [Production Deployment, Storage & Recovery Runbook](docs/PRODUCTION-DEPLOYMENT-RUNBOOK.md)

## Important

Do not commit secrets, `.env` files, real customer data, production database dumps or payment credentials. Do not run destructive database commands against production. The SRS is the approved baseline; propose deviations in the decisions log and wait for approval.
