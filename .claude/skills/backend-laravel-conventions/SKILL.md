---
name: backend-laravel-conventions
description: "Trigger: Laravel, backend/, PHP, controller, service, repository, migration, seeder, Form Request, Eloquent model, API route, Spatie permission, new module, scaffold module. Apply this repo's non-negotiable Laravel 13 backend conventions and module-generation workflow."
license: MIT
metadata:
  author: gentleman-programming
  version: "1.0"
---

## Activation Contract

Load this skill when creating or editing Laravel backend code under `backend/app/`, `backend/routes/`, or `backend/database/` — controllers, services, repositories, models, resources, Form Requests, migrations, seeders, or Spatie permissions. Also load when the user asks to scaffold a brand-new backend module end-to-end.

## Hard Rules

- Repository pattern: interface in `Contracts/Repositories/I{Name}Repository.php`, Eloquent impl in `Repositories/{Name}Repository.php`, bind in `AppServiceProvider::register()`. Services inject the interface, never the Eloquent model.
- Controllers: thin, `V1` namespace, use `ApiResponseTrait`, receive `string $guid` (never `$id`), validate request → call service → return resource.
- Resources: `V1` namespace, never expose internal `id`, always expose `guid`.
- Models: use `HasGuid` trait, `getRouteKeyName()` returns `'guid'`, `$hidden = ['id']`, `$fillable` excludes `guid`/timestamps, no `softDeletes()` except `Recipe`.
- Form Requests: one per write endpoint (`Store{Name}Request` / `Update{Name}Request`), `authorize()` returns `true`, `messages()` in Spanish.
- Migrations: `guid` as `string(36)->unique()`, foreign keys with `->constrained()->cascadeOnDelete()`, `->comment()` on non-obvious columns.
- Seeders: `WithoutModelEvents` always, set `guid` explicitly via `Str::uuid()->toString()` — never rely on model boot.
- Permissions (Spatie): pattern `{module}.lectura|alta|modificacion|baja`, guard `'web'` always (never `'sanctum'`), register in `PermissionSeeder` and assign in `RoleSeeder`.
- Routes: own file `routes/api/{name}.php` (kebab-case, plural), prefix `v1`, `auth:sanctum` middleware (public recipe routes use `client` middleware instead); auto-included via glob in `api.php`.
- Business logic lives only in `Services/`, typed method signatures, wrap multi-table writes in `DB::transaction()`.
- AI parsing services (`GeminiService`, `ClaudeService`) use exponential backoff (3-4 attempts) on 429/503; image generation goes through `GenerateRecipeImages`.

## Decision Gates

| Artifact | Location |
|---|---|
| Business logic | `app/Services/{Name}Service.php` |
| Data access | `app/Repositories/{Name}Repository.php` + `app/Contracts/Repositories/I{Name}Repository.php` |
| Write validation | `app/Http/Requests/Store{Name}Request.php` / `Update{Name}Request.php` |
| API response shape | `app/Http/Resources/V1/{Name}Resource.php` |
| New endpoint group | `routes/api/{name}.php` |

## Execution Steps

1. Identify the artifact type (controller/service/repository/model/migration/seeder/route/permission).
2. Apply the matching Hard Rule and Decision Gate location.
3. For a new entity, create in order: interface → repository → binding → service → controller → resource → Form Requests → migration → routes file.
4. Verify `guid` (not `id`) is used end-to-end: route model binding, resource output, Form Request references.

## Module Generation Workflow

Trigger: user asks to scaffold a new backend module end-to-end (not a single edit).

1. Confirm the essentials if missing: module name (singular PascalCase), description/relations, full CRUD or subset, whether a seeder is needed.
2. Read a reference module fully first — pick `User` or `Role` — in this order: migration, model, `I{Name}Repository`, `{Name}Repository`, `{Name}Service`, one Form Request, `{Name}Resource`, `{Name}Controller`, its routes file, the `AppServiceProvider::register()` binding, `PermissionSeeder`. Never generate from memory.
3. Show a 10-15 line plan (files to create, fields/columns, endpoints, permissions) and get user confirmation before writing anything. If a similarly-named module already exists, flag it before overwriting.
4. Generate in this exact order, applying the Hard Rules at each step: migration → model → seeder (if requested, registered in `DatabaseSeeder`) → repository interface → repository → `AppServiceProvider` binding → service → Form Requests → resource → controller → routes file (included in `routes/api.php`) → permissions in `PermissionSeeder` + role assignment in `RoleSeeder`. For files you modify rather than create (`AppServiceProvider`, `PermissionSeeder`, `RoleSeeder`, `api.php`), Read before Edit — never blind-overwrite.
5. Verify with `cd backend && php artisan migrate --pretend`; fix errors before reporting.
6. Report: files created, files modified with what changed, verify result, suggested next step (typically the matching frontend module).

## Output Contract

List files created or modified and confirm each against the Hard Rules above (repository binding registered, guid-only exposure, routes file included, permissions seeded where relevant). For module generation, additionally confirm every Final Checklist item: guid unique migration column, `HasGuid` + `getRouteKeyName`, seeder sets guid explicitly, interface + binding registered, service injects repository not model, controller uses `ApiResponseTrait` with `string $guid`, Form Request messages in Spanish, resource hides `id`, routes under `v1`/`auth:sanctum`, 4 permissions seeded, routes file included in `api.php`.

## References

- `backend/app/Providers/AppServiceProvider.php` — repository and gate bindings live here.
