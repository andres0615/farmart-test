import apiClient from '@/api/apiClient';
import type { ApiResponse } from '@/types/api-response';
import type { DispensationWarehouse } from '@/types/warehouse';

// Forma exacta de la respuesta de GET /warehouses
type WarehouseListResponse = ApiResponse<{ warehouses: DispensationWarehouse[] }>;

// GET /warehouses — devuelve la lista plana de bodegas
export async function fetchWarehouses(): Promise<DispensationWarehouse[]> {
  const response = await apiClient('/warehouses');
  const body: WarehouseListResponse = await response.json();

  if (!body.success || !body.data) {
    throw new Error(body.message ?? 'Error al obtener las bodegas');
  }

  return body.data.warehouses;
}
