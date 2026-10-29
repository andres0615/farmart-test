# AGENTS.md

## Estructura

- La aplicación real está en `backend/` (Laravel 11, PHP `^8.2`; Docker usa PHP 8.3).
- La raíz solo orquesta Docker (`docker-compose.yml`).
- `frontend/`, `docs/`, `README.md`, `AI_USAGE.md` y `.github/workflows/ci.yml` están **vacíos**: no asumas frontend React, docs ni CI existentes.
- No existe `routes/api.php` ni Sanctum; solo hay rutas web (`/`) y health (`/up`).

## Comandos

Ejecutar dentro de `backend/`:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

- Tests completos: `php artisan test`
- Un test: `php artisan test tests/Feature/ExampleTest.php` o `php artisan test --filter=ExampleTest`
- Formato: `./vendor/bin/pint`
- Assets: `npm install && npm run dev` (o `npm run build`)
- `composer dev` lanza servidor + queue + pail + Vite (requiere `npm install`)

Docker (desde la raíz):

```bash
docker compose up --build
```

Requiere un `.env` en la raíz (ignorado por git) con `APP_KEY`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`. `docker compose` construye el target `app` de `backend/Dockerfile`; para el target de CI usa `--target dev`.

## Gotchas

- El healthcheck de Docker apunta a `/health`, pero Laravel expone `/up` (`backend/bootstrap/app.php:11`, `backend/Dockerfile:73`). Los contenedores quedarán unhealthy hasta reconciliarlo.
- En `docker-compose.yml`, el servicio `api` tiene `build` y `depends_on` duplicados (líneas 24-26 y 59-62); la segunda clave `build` descarta `target: app`.
- Compose pasa `CORS_ALLOWED_ORIGINS` y `LLM_*`, pero no hay `config/cors.php` ni código que los consuma.
- `phpunit.xml` no fuerza SQLite en memoria (está comentado): los tests usan la DB de `backend/.env` (hoy `sqlite`). Para tests con DB, habilita SQLite en memoria o usa `RefreshDatabase`.
- El entrypoint ejecuta `db:seed` en cada arranque con `RUN_MIGRATIONS=true`, pero `DatabaseSeeder` no es idempotente (crea el email fijo `test@example.com`): puede fallar en reinicios.
- `.env.example` de la raíz está vacío; no sirve como plantilla para Compose.
- No hay Pest, Larastan ni typecheck instalados, aunque el Dockerfile los mencione en comentarios. No inventes comandos de verificación.
- Ignora `tmp/` (copia vieja ignorada por git), los archivos `*:Zone.Identifier` y `.opencode/` (ignorado por git).

## Git

- El esqueleto de `backend/` está mayormente untracked: `git status` es ruidoso. Haz `git add` selectivo.
- Nunca agregues `.env`, `backend/vendor/`, `backend/node_modules/` ni `backend/database/database.sqlite`.
