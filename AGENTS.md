# AGENTS.md

## Estructura

- Aplicación backend en `backend/`: Laravel 11, PHP `^8.2` (Docker usa 8.3). Sin `routes/api.php` ni Sanctum; solo `/` y health `/up`.
- Frontend en `frontend/`: React 19 + TypeScript + Vite 8 (plantilla base). Sin router, sin state manager, sin tests y sin llamadas a API.
- La raíz solo orquesta Docker. `README.md`, `AI_USAGE.md`, `docs/DEPLOY.md`, `docs/openapi.yaml`, `.github/workflows/ci.yml` y `.env.example` raíz están vacíos (0 bytes).
- `.opencode/` está ignorado por git; su `react-dev.md` asume JS/JSX, pero el frontend real es TS/TSX.

## Comandos

Backend (desde `backend/`):

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

- Tests: `php artisan test`
- Un test: `php artisan test tests/Feature/ExampleTest.php` o `php artisan test --filter=ExampleTest`
- Formato: `./vendor/bin/pint`
- El backend tiene su propio pipeline Vite/Tailwind (`npm install && npm run dev`), separado de `frontend/`.
- `composer dev` lanza servidor + queue + pail + Vite (requiere `npm install`).

Frontend (desde `frontend/`):

- `npm install`
- `npm run dev`
- `npm run build` (= `tsc -b && vite build`; el typecheck va dentro del build)
- `npm run lint` (= `oxlint`)
- `npm run preview`
- `frontend/eslint.config.js` no se usa: ESLint no está instalado ni declarado; no lo invoques.

Docker (desde la raíz):

```bash
docker compose up --build
```

- Requiere `.env` raíz con `APP_KEY`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`; `.env.example` está vacío, no sirve de plantilla.
- Compose construye PostgreSQL 16 + `backend` (target por defecto `app`) + `frontend` (target `app`).
- `backend/.env` local usa SQLite; Compose sobreescribe a PostgreSQL. Los tests usan la DB de `backend/.env`.

## Gotchas

- `backend/Dockerfile` valida `/health`, pero Laravel expone `/up`. Compose sobreescribe el healthcheck de `api` a `/up`, así que solo afecta a ejecuciones directas de la imagen.
- `docker-compose.yml` repite `build` y `depends_on` en `api`; la última clave `build: ./backend` descarta `target: app` (el stage final sigue siendo `app`, pero cuidado al editar).
- Compose pasa `CORS_ALLOWED_ORIGINS` y `LLM_*`, pero no existe `config/cors.php` ni código que los consuma.
- `VITE_API_URL` se define en Compose y en `frontend/Dockerfile`, pero aún no lo lee ningún archivo del frontend.
- `backend/docker/entrypoint.sh` migra si `RUN_MIGRATIONS=true`, pero el `db:seed` está comentado. Ejecutar `db:seed` a mano sigue sin ser idempotente (`test@example.com`).
- `phpunit.xml` tiene comentadas las líneas de SQLite en memoria: tests con DB usan `backend/.env`. Usa `RefreshDatabase` en tests nuevos.
- `welcome.blade.php` usa `@vite`, y el Dockerfile del backend no compila assets (`public/build` no existe): `/` falla en la imagen de producción hasta compilar el frontend del backend.
- No hay Pest, Larastan ni script de typecheck separado. No inventes esos comandos.
- Ignora `tmp/`, los `*:Zone.Identifier` y `.opencode/`.

## Git

- El backend está trackeado; el frontend nuevo sigue mayormente untracked y `docker-compose.yml`/`frontend/Dockerfile` tienen cambios locales. Haz `git add` selectivo.
- Nunca agregues `.env`, `backend/vendor/`, `backend/node_modules/`, `frontend/node_modules/` ni `backend/database/database.sqlite`.
