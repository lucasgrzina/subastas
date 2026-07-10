# Convenciones de arquitectura frontend la plataforma (Vue 3 + TypeScript)

Estas convenciones son no negociables para todo código frontend en la plataforma.

## Feature Module Pattern

Cada módulo vive en `frontend/src/modules/{nombre}/` con sub-carpetas:
`api/`, `components/`, `composables/`, `pages/`, `router/`, `stores/`, `types/`, `validators/`.

## TypeScript estricto

- Nunca usar `any`. Si el tipo es desconocido, usar `unknown` con type guard.
- Props con `defineProps<{}>()`, emits con `defineEmits<{}>()`.
- Todos los tipos en `types/`.

## Vue Query para server state

- `useQuery` para lecturas, `useMutation` para escrituras.
- El server state NO va en Pinia.
- Toda `useMutation` debe llamar `invalidateQueries` con la key correspondiente al completarse.

## Pinia solo para UI state

- Modales abiertos, filtros activos, estado de drawers.
- Nunca datos de servidor en Pinia.
- Stores en `stores/{nombre}-ui.store.ts`.
- Auth store persistido con `pinia-plugin-persistedstate`.

## Composables

- Prefijo `use` obligatorio.
- Un composable por operación: `useCreate{Nombre}`, `useUpdate{Nombre}`, `useDelete{Nombre}`, `use{Nombre}s`.
- Sin lógica de negocio en componentes — va en composables.

## Componentes

- `<script setup lang="ts">` siempre.
- Usar átomos existentes en `frontend/src/components/atoms/` antes de crear nuevos.
- Subcarpetas de átomos: `buttons/`, `cards/`, `display/`, `feedback/`, `inputs/`, `navigation/`, `overlays/`, `pickers/`, `selects/`.
- Auto-import vía `unplugin-vue-components` — no necesitás importar átomos explícitamente.
- Si el átomo que necesitás no existe, crealo en `atoms/` antes de usarlo.

## PermissionGuard

- Toda acción de escritura (crear, editar, eliminar) envuelta en `<PermissionGuard :permission="'modulo.accion'">`.

## Validación

- Zod + Vee-Validate: schemas en `validators/`, usados con `useForm`.
- Mensajes de error en español.
- Exportar tipo inferido: `export type FormValues = z.infer<typeof schema>`.
- Errores 422 mapeados a campos vía `src/core/composables/use422Errors.ts`.

## API Layer

- Funciones en `api/{nombre}.api.ts`.
- Retornan datos directamente — el interceptor ya desenvuelve `{ success, data }`.
- GUID como identificador en todas las llamadas.

## i18n

- Nunca strings hardcodeados en templates — siempre `$t('clave')`.
- En `<script setup>`: `const { t } = useI18n()`.
- Claves en `frontend/src/i18n/locales/es/`.
- Idioma del sistema: es-AR. Solo español.

## Router

- Lazy loading obligatorio: `component: () => import(...)`.
- `authGuard` en `beforeEnter` de toda ruta protegida.
- Rutas del módulo en `router/{nombre}.routes.ts`, registradas en `frontend/src/router/index.ts`.

## Theming

- Paletas: `green`, `blue`, `red`, `yellow`. Modos: `dark`, `light`.
- Tokens de tema construidos por `src/core/themes/palettes.ts → buildDashTheme(palette, mode)`.
- No hardcodear valores de color en componentes.

## Estilos

- Tailwind para layout/spacing/colores. Sin CSS inline salvo casos puntuales.
- Ant Design Vue 4 para componentes de negocio.
