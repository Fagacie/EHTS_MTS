# ETMS Development Roadmap

**Baseline:** SRS and Architecture v1.1  
**Current status:** Stage 9 (Notifications, reports and dashboards) is **Completed**. Stage 10 (Final testing, security hardening and production deployment) is **In Progress** (implemented, hardened, built into immutable production Docker image `etms-app:prod`, and verified cleanly across 52 automated tests; ready for final company-server cutover).

| Stage | Scope | Status | Exit criteria |
|---|---|---|---|
| 0 | Requirements baseline and repository documentation | Completed | SRS, architecture, README, decisions and roadmap files created and verified |
| 1 | Database ERD, data dictionary and state design | Completed | Relationships, fields, constraints and states reviewed and officially approved |
| 2 | UI/UX blueprint and navigation | Completed | Public, trainee, instructor and admin screens, user flows, visual design system and Docker architecture reviewed and approved |
| 3 | Laravel foundation, authentication, roles and design system | Completed | Application boots; Docker services healthy; tests pass; 3 roles, multi-role switcher, Admin MFA and Filament 5 panel verified |
| 4 | Customer profiles, categories and module management | Completed | Customer profiles, categories, modules, R2 learning materials, Filament CRUD and public Livewire catalogue verified |
| 5 | Session scheduling, instructors and booking | Completed | Instructor profiles, physical sessions, 15-minute concurrency-safe booking workflow, seat reconciliation, and Instructor Portal verified |
| 6 | Orders, Billplz and payment reconciliation | Completed | Billplz API & constant-time X-Signature webhook verification, idempotent callbacks, session & module checkout, order history, and admin-controlled full/partial refunds verified |
| 7 | Module entitlements, documents and video | Completed | Lifetime module access, Bunny Stream signed video playback, R2 signed downloads, and `attendees_only` session recording restrictions verified |
| 8 | Certificate generation and management | Completed | Session/module certificate eligibility, DomPDF certificate generation & private R2 storage, duplicate prevention, global signatory config, Trainee Portal certificate view/download, Filament management, and revocation verified |
| 9 | Notifications, reports and dashboards | Completed | Queued Resend/Postmark email notifications, delivery telemetry, Trainee/Instructor notification centres, date-filtered admin reports, CSV exports, and upgraded Filament dashboard KPI/chart widgets verified |
| 10 | Security, UAT, deployment and backup | In Progress | Company server private storage (`materials`, `videos`, `recordings`, `certificates`), FFmpeg HLS self-hosted video playback, rate limiters & security headers, immutable production Docker build, backup/restore automation, restart resilience, and full 52-test suite verified |

## Stage control

Only one stage should be implemented at a time. The user supplies the prompt to the selected coding agent after reviewing it. At the end of each stage, the selected coding agent reports its evidence. Desktop Commander is then used to inspect files, run relevant checks and verify actual behaviour where possible.

Do not mark a stage complete based only on an AI-generated summary. Require test output and independent inspection.

## Current next action

Perform final review of Stage 10 production deployment artefacts (`docker-compose.prod.yml`, `.env.production.example`, `docker/nginx/production.conf`, `docker/scripts/backup.sh`, `docker/scripts/restore.sh`, and `docs/PRODUCTION-DEPLOYMENT-RUNBOOK.md`) alongside the incremental **Frontend Modernisation Track (v1.2)** below before executing downstream frontend stages or production host cutover.

---

## Frontend Modernisation Track (Architecture v1.2 — Vue 3 + TypeScript + Inertia.js + shadcn-vue)

Per [ETMS SRS & System Architecture v1.2](ETMS-SRS-and-System-Architecture-v1.2.md) and decisions `D-037`–`D-040`, the customer-facing and instructor-facing frontend is being incrementally modernised inside the existing Laravel 13 monolith while keeping Blade/Livewire 4 coexisting cleanly and keeping Filament 5 (`/admin`) permanently separate.

| Phase | Scope | Status | Exit / Acceptance Criteria |
|---|---|---|---|
| **FM-1** | **Phase A–D: Docs Alignment, Vue/Inertia/shadcn-vue Foundation & Public Homepage (`/`)** | **Completed** | Docs reconciled (`v1.2` SRS, `v1.1` pointer, `DECISIONS.md`, `UI-UX-BLUEPRINT.md`, `DEVELOPMENT-ROADMAP.md`, `README.md`, `AGENTS.md`, `CLAUDE.md`, `IMPLEMENTATION-LOG.md`); `inertiajs/inertia-laravel`, Vue 3, TypeScript, `@inertiajs/vue3`, `reka-ui` (`shadcn-vue`) installed; `resources/js/app.js` untouched & `vendor-hls` isolated; `GET /` serves polished `pages/Public/Home.vue` with real DB data and topographic programme banners; TypeScript, Vite build, and tests pass. |
| **FM-2** | **Public Catalogue, Sessions & Instructor Pages (`/training`, `/sessions`, `/instructors` + details)** | **Completed** | Replaced public catalogue Livewire view and public session/instructor Blade templates with Vue 3 / Inertia pages (`Catalogue/Index`, `Catalogue/Show`, `Sessions/Index`, `Sessions/Show`, `Instructors/Index`, `Instructors/Show`) with URL-synced partial reloads, 404 publication guards, seat reconciliation, strict `D-018` separation, and zero private leakage. |
| **FM-3** | **Trainee Portal Shell, Analytics Dashboard & Sub-Pages (`/portal`, `/portal/bookings`, `/portal/orders`, `/portal/certificates`, `/portal/modules`, `/portal/recordings`, `/portal/notifications`, `/portal/profile`, `/portal/security`)** | **Completed** | Stage 3A: Built collapsible `PortalLayout.vue` shell + `pages/Portal/Dashboard.vue` backed by real MySQL 6-month Chart.js activity/spend analytics (`vendor-charts`, isolated from all other pages), pure `#FFFFFF` + `#272d38` minimalist UI, and Times New Roman (`font-classic`). Stage 3B: Created `app/Support/PortalPresenter.php`, migrated all 11 remaining non-HLS Trainee Portal routes (`Portal/Bookings/{Index,Show}`, `Portal/Orders/{Index,Show}`, `Portal/Certificates/{Index,Show}`, `Portal/Modules/Index`, `Portal/Recordings/Index`, `Portal/Notifications/Index`, `Portal/Profile`, `Portal/Security`), preserved Blade HLS watch workspaces (`showModule`, `watchModuleVideo`, `watchRecording`) and PDF downloads across `<a href>` boundaries, handled external Billplz redirects via `Inertia::location()`, and passed all 73 PHPUnit tests (`1753 assertions`). |
| **FM-4** | **Checkout, Auth & Self-Hosted HLS Player (`/checkout/*`, `/login`, `/register`, `/forgot-password`, `/reset-password/*`, `/email/verify`, `/two-factor-*`, `/portal/modules/{slug}`, `/portal/recordings/{id}/watch`)** | **Completed** | Built `AuthLayout.vue`, lazy `HlsVideoPlayer.vue` (`hls.js/light` isolated to `vendor-hls-light` solely on `Portal/Modules/Show.vue` & `Portal/Recordings/Watch.vue`), and all 12 Vue 3/Inertia pages (`Checkout/{Session,Module,Return}`, `Portal/Modules/Show`, `Portal/Recordings/Watch`, `Auth/{Login,Register,ForgotPassword,ResetPassword,VerifyEmail,TwoFactorSetup,TwoFactorChallenge}`); preserved 15-minute holds, Billplz redirects via `Inertia::location()`, read-only `/checkout/return`, `D-017`/`D-018` access enforcement, Admin TOTP MFA, untouched `resources/js/app.js` (`vendor-hls`), and passed all 77 PHPUnit tests (`1996 assertions`). |
| **FM-5** | Instructor Portal (`/instructor/*`) | Completed | Migrated assigned sessions, tablet-friendly attendance marking (`44px` tap targets + `chk_attendance_marked_before_submit` UI guard), completion submission, and recording upload. |
| **FM-6** | Legacy Blade/Livewire Cleanup | Planned | Repo-wide verification confirms zero references before removing superseded Blade templates, `public/css/home.css`, and `components/eht/*` (`livewire/livewire` retained for Filament 5). |
