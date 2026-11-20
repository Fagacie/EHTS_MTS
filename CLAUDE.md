# CLAUDE.md — ETMS Architecture & Engineering Rules (v1.2)

This repository contains the **Ecohydrotech Training Management System (ETMS)** — a Laravel 13 modular monolith undergoing controlled incremental frontend modernisation to **Vue 3 + TypeScript + Inertia.js v3 + shadcn-vue** (`ETMS-SRS-and-System-Architecture-v1.2.md`).

## Authoritative Documentation
- `docs/ETMS-SRS-and-System-Architecture-v1.1.md` — Approved baseline requirements, business rules (`BR-01`–`BR-16`), roles, and security architecture.
- `docs/ETMS-SRS-and-System-Architecture-v1.2.md` — Frontend modernisation ADR, Blade/Livewire coexistence strategy, Filament 5 isolation, and `FM-1`–`FM-6` migration phases.
- `docs/DECISIONS.md` — Confirmed decisions `D-001`–`D-040` and remaining clarifications `R-01`–`R-08`.
- `docs/UI-UX-BLUEPRINT.md` — Design system tokens and minimalist Section 9 addendum.

## Runtime & Verification Commands
- **PHP / Artisan / PHPUnit (Docker `app` container):**
  - `docker compose exec -T -u www-data app php artisan test`
  - `docker compose exec -T -u www-data app php artisan view:clear`
- **Frontend TypeScript & Vite 8 (Host Node 22+):**
  - `npm run type-check` (`vue-tsc --noEmit`)
  - `npm run build` (`vite build`)
  - `npm run check` (`npm run type-check && npm run build`)

## Hard Guardrails
1. **No Git Mutations without explicit permission:** Do not run `git commit`, `git push`, `git merge`, or create branches unless explicitly instructed.
2. **No Backend Rewrites & Keep Filament 5 Untouched:** Preserve all existing Laravel services, policies, webhooks, HLS security, and `/admin` Filament 5 resources.
3. **Incremental Coexistence Only:** Use `resources/views/app.blade.php` + `resources/js/inertia.ts` (`pages/`, `layouts/`, `components/`, `composables/`, `types/`, `lib/`) for migrated Inertia views while keeping `resources/js/app.js` and legacy Blade/Livewire templates intact until verified migration completion.
4. **Minimalist UI & Strict Data Minimisation:** Follow `docs/UI-UX-BLUEPRINT.md` Section 9 (`#FFFFFF`/`#FAFAFA`/`neutral-200`, single `#0F4C81` accent, `6px–8px` radii, zero AI chrome) and pass only explicit minimal DTO props via Inertia.
5. **No Unsolicited Composer Packages:** Do not install `laravel/boost` or extra PHP packages without prior explanation and approval.
