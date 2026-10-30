# Farmart Test

Monorepo con backend Laravel 11, frontend React 19 y Docker Compose.

## Stack

| Parte | Tecnología |
|-------|------------|
| `backend/` | Laravel 11 · PHP 8.2+ (Docker 8.3) |
| `frontend/` | React 19 · TypeScript · Vite 8 |
| Base de datos | PostgreSQL 16 |

## Puesta en marcha con Docker

Generar el archivo `.env` en la raíz:
```bash
cp .env.example .env
```

Levantar los contenedores:
```bash
docker compose up --build
```
