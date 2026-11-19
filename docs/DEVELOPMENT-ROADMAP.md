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

Perform final review of Stage 10 artefacts (`docker-compose.prod.yml`, `.env.production.example`, `docker/nginx/production.conf`, `docker/scripts/backup.sh`, `docker/scripts/restore.sh`, and `docs/PRODUCTION-DEPLOYMENT-RUNBOOK.md`) and execute production host cutover with live domain TLS certificates, Billplz live credentials, and Resend API key on the company server.
