import apiClient from '@/api/apiClient';
import type { PaginatedResult } from '@/types/pagination';
import type {
  CreateDispensationPayload,
  Dispensation,
  DispensationPreview,
  PreviewDispensationPayload,
} from '@/types/dispensation';

// Respuesta estándar del backend: { success, data, message, code, errors }
interface ApiEnvelope<T> {
  success: boolean;
  data?: T;
  message?: string;
  code?: string;
  errors?: string[] | Record<string, string[]>;
}

// Paginador de Laravel (snake_case) antes de mapearlo a camelCase
interface LaravelPaginator<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

/**
 * Error de negocio con el código que devuelve el backend.
 * Permite a la UI distinguir stock insuficiente, lote vencido, etc.
 */
export class DispensationApiError extends Error {
  readonly code?: string;
  readonly details: string[];

  constructor(message: string, code?: string, details: string[] = []) {
    super(message);
    this.name = 'DispensationApiError';
    this.code = code;
    this.details = details;
  }
}

// Normaliza los errores de validación (array u objeto de Laravel) a string[]
function normalizeErrors(errors?: string[] | Record<string, string[]>): string[] {
  if (!errors) return [];
  return Array.isArray(errors) ? errors : Object.values(errors).flat();
}

// Lee el body aunque el servidor devuelva HTML o una respuesta vacía
async function readEnvelope<T>(response: Response): Promise<ApiEnvelope<T>> {
  try {
    return (await response.json()) as ApiEnvelope<T>;
  } catch {
    return { success: false, message: `Error ${response.status}: respuesta inválida del servidor` };
  }
}

function toApiError(body: ApiEnvelope<unknown>, fallback: string): DispensationApiError {
  return new DispensationApiError(body.message ?? fallback, body.code, normalizeErrors(body.errors));
}

// Listado paginado (endpoint existente GET /dispensations)
export async function fetchDispensationsPaginated(
  page: number = 1,
  perPage: number = 10,
): Promise<PaginatedResult<Dispensation>> {
  const params = new URLSearchParams();
  params.set('page', String(page));
  params.set('per_page', String(perPage));

  const response = await apiClient(`/dispensations?${params.toString()}`);
  const body = await readEnvelope<{ dispensations: LaravelPaginator<Dispensation> }>(response);

  if (!body.success || !body.data) {
    throw toApiError(body, 'Error al obtener las dispensaciones');
  }

  const paginated = body.data.dispensations;

  return {
    data: paginated.data,
    pagination: {
      currentPage: paginated.current_page,
      lastPage: paginated.last_page,
      perPage: paginated.per_page,
      total: paginated.total,
      from: paginated.from,
      to: paginated.to,
    },
  };
}

// GET /dispensations/{id} — detalle con ítems
export async function fetchDispensation(id: number): Promise<Dispensation> {
  const response = await apiClient(`/dispensations/${id}`);
  const body = await readEnvelope<{ dispensation: Dispensation }>(response);

  if (!body.success || !body.data) {
    throw toApiError(body, 'Error al obtener la dispensación');
  }

  return body.data.dispensation;
}

// POST /dispensations/preview — simulación FEFO sin efectos secundarios
export async function previewDispensation(
  payload: PreviewDispensationPayload,
): Promise<DispensationPreview> {
  const response = await apiClient('/dispensations/preview', {
    method: 'POST',
    body: payload,
  });
  const body = await readEnvelope<DispensationPreview>(response);

  if (!body.success || !body.data) {
    throw toApiError(body, 'No se pudo calcular la asignación de lotes');
  }

  return body.data;
}

// POST /dispensations — creación idempotente
export async function createDispensation(
  payload: CreateDispensationPayload,
  idempotencyKey: string,
): Promise<Dispensation> {
  const response = await apiClient('/dispensations', {
    method: 'POST',
    headers: { 'Idempotency-Key': idempotencyKey },
    body: payload,
  });
  const body = await readEnvelope<{ dispensation: Dispensation }>(response);

  if (!body.success || !body.data) {
    throw toApiError(body, 'Error al registrar la dispensación');
  }

  return body.data.dispensation;
}

/**
 * Clave de idempotencia para reintentos seguros (RN-09).
 * Debe mantenerse estable mientras el usuario reintenta la misma solicitud.
 */
export function createIdempotencyKey(): string {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }
  return `disp-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}
