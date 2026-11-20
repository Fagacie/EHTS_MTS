# ETMS Agent Guidelines (Architecture Revision v1.2)

## Project Overview
Ecohydrotech Training Management System (**ETMS**) is a **Laravel 13 (PHP 8.3)** modular monolith (`D-009`) running in Docker Compose (`nginx`, `app`, `queue`, `scheduler`, `mysql` 8.4, `redis` 7).

Before proposing or implementing changes, read:
- [docs/ETMS-SRS-and-System-Architecture-v1.1.md](docs/ETMS-SRS-and-System-Architecture-v1.1.md) (approved baseline for business rules `BR-01`–`BR-16`, RBAC, schema, and Stage 10 self-hosted storage/HLS)
- [docs/ETMS-SRS-and-System-Architecture-v1.2.md](docs/ETMS-SRS-and-System-Architecture-v1.2.md) (incremental Vue 3 + TypeScript + Inertia.js v3 + shadcn-vue frontend modernisation & coexistence rules)
- [docs/DECISIONS.md](docs/DECISIONS.md) & [docs/UI-UX-BLUEPRINT.md](docs/UI-UX-BLUEPRINT.md)

## Non-Negotiable Architecture & Business Rules
1. **No Backend Rewrites:** Keep all business logic, validation, policies, bookings, 15-minute holds (`D-019`), Billplz webhook verification (`D-035`), module entitlements (`D-018`), attendance verification (`D-017`, `D-025`), certificates (`D-007`, `D-034`), and FFmpeg HLS security on the Laravel 13 backend.
2. **Filament 5 Permanently Separate (`D-038`):** Do **not** modify `app/Filament/`, `resources/views/filament/`, or published Filament assets unless an administrative feature change is explicitly requested.
3. **Blade/Livewire + Vue/Inertia Coexistence (`D-039`):**
   - Migrated Inertia routes render through `resources/views/app.blade.php` + `resources/js/inertia.ts` (`pages/**/*.vue`).
   - Legacy Blade/Livewire 4 pages render through `resources/js/app.js` (keep `resources/js/app.js` untouched for Blade HLS playback).
   - Cross-boundary links between Inertia routes and unmigrated Blade/Livewire or `/admin` routes must use standard HTML `<a href>` links.
   - Never expose sensitive server data (secrets, TOTP fields, private storage keys, unredacted user emails) in `HandleInertiaRequests` or Inertia page props.
4. **Three Roles Only (`D-004`, `D-026`):** `administrator`, `instructor`, `customer`. No separate corporate portal or employee-management subsystem (`D-001`–`D-003`, `D-023`).
5. **Minimalist UI Discipline:** Use 90% light neutrals (`#FFFFFF`, `#FAFAFA`, `neutral-200`, `neutral-900`) plus Ecohydrotech's single primary accent (`#0F4C81`), `6px–8px` max radii, `150ms` transitions, and `lucide-vue-next` (`16px`) only for functional UI clarity. Avoid heavy shadows, gradients, decorative icon boxes, or generic SaaS templates.
6. **Do NOT Install Unsolicited Packages:** Do not install `laravel/boost` or unnecessary Composer packages. Run PHP/Artisan/Composer commands inside the Docker `app` container (`docker compose exec -T app ...`) and run Node/Vite checks on the host (`npm run type-check`, `npm run build`, `npm run check`).
