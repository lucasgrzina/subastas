# Convenciones de arquitectura backend la plataforma (Laravel)

Estas convenciones son no negociables para todo código backend en la plataforma.

## Pattern Repository

- Interface en `backend/app/Contracts/Repositories/I{Nombre}Repository.php`.
- Implementación Eloquent en `backend/app/Repositories/{Nombre}Repository.php`.
- Binding en `backend/app/Providers/AppServiceProvider.php` método `register()`.
- El Service inyecta la Interface, **nunca** el Model directamente.

## Service Layer

- Toda lógica de negocio va en `backend/app/Services/`. Los controllers son delgados.
- Métodos tipados: `public function metodo(Tipo $param): TipoRetorno`.
- `DB::transaction()` en operaciones de escritura que toquen múltiples tablas.

## Controllers

- `ApiResponseTrait` en todos los controllers. Respuesta estándar: `{ success, data, message?, errors? }`.
- Namespace `V1`: `backend/app/Http/Controllers/V1/`.
- Reciben `string $guid`, nunca `$id`.
- Mínima lógica: validar request → llamar service → retornar resource.

## Resources

- Namespace `V1`: `backend/app/Http/Resources/V1/`.
- NUNCA exponer `id` interno. Siempre incluir `guid`.
- `$hidden = ['id']` en el modelo.

## Form Requests

- Un Request por endpoint de escritura: `Store{Nombre}Request`, `Update{Nombre}Request`.
- `authorize()` retorna `true`.
- `messages()` con mensajes en español.

## Modelos

- Trait `HasGuid` obligatorio en modelos nuevos.
- `getRouteKeyName()` retorna `'guid'`.
- `$fillable` sin `guid` ni timestamps (los maneja automáticamente).
- `$hidden = ['id']`.
- Sin `softDeletes()` — salvo Recipe que ya lo usa.

## Rutas

- Archivo propio en `backend/routes/api/{nombre}.php` (kebab-case, plural).
- Prefijo `v1`, middleware `auth:sanctum`.
- Rutas públicas de recetas (`/v1/recipes`, `/v1/meta`) bajo middleware `client`.
- Incluir en `backend/routes/api.php` (se cargan automáticamente vía glob).

## Permisos (Spatie)

- Patrón: `{modulo}.lectura`, `{modulo}.alta`, `{modulo}.modificacion`, `{modulo}.baja`.
- Guard `'web'` siempre. NO usar `'sanctum'` para roles/permisos.
- Agregar en `PermissionSeeder` y asignar a roles en `RoleSeeder`.

## Migraciones

- `guid` como `string(36)->unique()`.
- Foreign keys con `->constrained()->cascadeOnDelete()`.
- Sin `softDeletes()` (salvo Recipe).
- `->comment()` en columnas no obvias.

## Seeders

- `WithoutModelEvents` siempre.
- `guid` seteado EXPLÍCITAMENTE con `Str::uuid()->toString()` — NO depender del boot del modelo.

## Servicios de IA

- `GeminiService` y `ClaudeService` para parsing de ingredientes y recetas.
- Exponential backoff retry (3-4 intentos) en respuestas 429/503.
- Generación de imágenes vía `GenerateRecipeImages` (fal.ai Flux Kontext o Google Imagen 4).
