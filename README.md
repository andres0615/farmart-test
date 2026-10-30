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

## Acceso a la aplicación

| Servicio | URL |
|----------|-----|
| Frontend | http://localhost:3000 |
| API (backend) | http://localhost:8000/api |

## Modulos

### Dispensaciones

#### Funcionalidades

**1. Previsualización de lotes (no guarda nada)**
- Calcula, sin tocar la base de datos, de qué lotes saldría cada medicamento y cuántas unidades de cada uno.
- Muestra por ítem: lo recetado, lo ya dispensado, lo pendiente y los lotes sugeridos (código, vencimiento y cantidad a tomar).
- Avisa si el stock no alcanza (indica cuántas unidades faltan) y cuántos lotes vencidos se ignoraron.
- Indica si la prescripción incluye medicamentos de control especial y por tanto requiere autorización.

**2. Registro de la dispensación**
- Todo ocurre en una sola transacción: si falla un paso, no se guarda la dispensación ni se descuenta stock.
- Revalida la prescripción con un bloqueo de fila, para que dos confirmaciones simultáneas no dispensen dos veces.
- Vuelve a calcular los lotes al confirmar: si el stock cambió desde la previsualización, rechaza la operación y pide recalcular.
- Descuenta el stock solo si la cantidad sigue disponible (actualización condicional).
- Es idempotente: si el cliente reenvía la misma clave (`Idempotency-Key`), devuelve la dispensación ya creada en lugar de duplicarla.
- Permite dispensación parcial: se puede registrar menos de lo pendiente, incluso si el stock no alcanza para todo.

**3. Listado de dispensaciones**
- Devuelve las dispensaciones con paginación opcional (`per_page`).

**4. Detalle de una dispensación**
- Incluye la prescripción con su paciente, la bodega, el usuario que dispensó, quién autorizó y los lotes consumidos con su cantidad.
