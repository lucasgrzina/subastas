# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repo layout

Monorepo with two independent projects, no root build tooling tying them together (no root README, no docker-compose, no CI config):

- `backend/` — Laravel 13 (PHP ^8.3) API
- `frontend/` — Vue 3 + TypeScript + Vite SPA

## Common commands

### Backend (run from `backend/`)

- Full dev stack (server + queue worker + log tail + Vite): `composer run dev`
- Run all tests: `php artisan test` (or `composer test`, which also clears config first)
- Run a single test file: `php artisan test tests/Feature/Api/RecipeApiTest.php`
- Run tests by name filter: `php artisan test --filter=RecipeApiTest`
- Format code: `vendor/bin/pint`

Test DB is PostgreSQL, database `subastas_test` (see `phpunit.xml`); queue is forced `sync` and cache `array` in tests, broadcasting disabled.

### Frontend (run from `frontend/`)

- Dev server: `npm run dev` (Vite, port 5175)
- Build: `npm run build` (runs `vue-tsc -b` then `vite build` — a type error fails the build)
- Type-check only: `npm run type-check`
- Preview production build: `npm run preview`

No lint script and no unit test runner (Vitest/Jest) are configured. `playwright` is a devDependency but isn't wired into `package.json` scripts or a committed test suite yet.

## Backend architecture (`backend/app/`)

Layered beyond stock Laravel — controllers are thin, most logic lives in services/repositories:

- `Http/Controllers/V1/*` — Sanctum-authenticated admin/back-office endpoints (Auth, User, Role, Permission, Export, Notification, SupportMessage, SystemSetting, UserSetting, TempUpload).
- `Services/` — one service per feature (`AuthService`, `UserService`, `RoleService`, `ExportService`/`ExportResolverService`, etc). Controllers delegate here.
- `Repositories/` + `Contracts/Repositories/` — repository pattern, interfaces bound to Eloquent implementations in `AppServiceProvider`. When adding a new entity, follow this interface+binding pattern rather than querying Eloquent models directly from controllers/services.
- `Criteria/` — query-criteria/specification objects layered on repositories (per entity:`Roles`, `Users`, `Shared`) for composable filtering.
- `AppServiceProvider` is the single service provider — it's the map of all cross-cutting wiring (repository bindings, the `ExportPolicy` gate).
- Routing: `routes/api.php` defines the public `client`-middleware `v1` group inline, then globs in every file under `routes/api/*.php` (one file per feature area), all also prefixed `v1` but under `auth:sanctum`. `routes/web.php` only serves the default welcome view — this is an API-only backend.
- Real-time: Laravel Reverb (self-hosted, Pusher-protocol-compatible WebSocket server) is installed but not enabled by default — `BROADCAST_CONNECTION` must be set explicitly. Used for export-completion and support-message notifications, not a live feed of any kind.
- No `Console/Kernel.php` (Laravel 11+/13 style) and `routes/console.php` has no `->schedule()` calls — there is no cron-driven background processing today. `Console/Commands/` is mostly one-off recipe import/generation tooling (docx/epub/PDF importers).

### Domain model

Admin/back-office layer: `User`, `Role`, `Permission` (Spatie), `SupportMessage`, `Notification`, `SystemSetting`, `Export`.

## Frontend architecture (`frontend/src/`)

Hybrid layered + feature-module structure:

- `src/core/` — shared infrastructure: `api/http.ts` (the single axios instance — request interceptor injects the Pinia auth token as a Bearer header, response interceptor unwraps the backend's `{ success, data, message }` envelope and centrally handles 401/422/500), `services/echo.service.ts` (Laravel Echo/Reverb setup with a custom private-channel authorizer that goes through the app's own axios instance rather than Sanctum cookies), `services/socket.service.ts`, `services/event-bus.service.ts`.
- `src/modules/<feature>/` — one folder per feature (`auth`, `users`, `roles`, `permissions`,`notifications`, `support-messages`, `system-settings`, `user-settings`, `settings`, `exports`, `dashboard`), each internally split into `api/`, `components/`, `pages/`, `router/`, `stores/`, `types/`, `validators/`. Follow this internal shape when adding a new module.
- `src/components/` — cross-cutting presentational components (`atoms`, `auth`, `dashboard`, `filters`, `forms`, `layouts`, `shared`, `tables`).
- `src/router/index.ts` composes each module's route array; guards live in `src/router/guards/` (`auth.guard.ts`, `guest.guard.ts`).
- UI library is **Ant Design Vue** (not a Tailwind-only design), with Tailwind used alongside it for utility styling.
- `src/store/dashboard.ts` is a legacy singular-named store predating the `core/stores` + per-module `stores/` convention — treat new state as belonging in a module's `stores/` folder, not `src/store/`.
- Path alias `@` → `src/` (both `vite.config.ts` and `tsconfig.app.json`). `unplugin-vue-components` auto-imports from `src/components` and `src/layouts/partials` — components in those folders don't need manual imports.
- No Vite dev proxy to the backend — the frontend calls `VITE_API_BASE_URL` directly via axios.

## Repo hygiene notes


- `.claude/agents_sin_usa_aun/` and `.claude/skills_sin_usar/` (root) contain draft agent/skill markdown files that are **not wired into anything active** — don't assume their described behaviors apply.
- `backend/.claude/commands/` and `backend/.claude/skills/` exist but are currently empty.
- `.claude/docs/dominio/` and `.claude/docs/plans/` (root) exist but are empty — no domain documentation has been written there yet.
