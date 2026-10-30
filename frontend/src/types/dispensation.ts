// Tipos del dominio de dispensaciones.
// Reflejan las tablas `dispensations`, `dispensation_items` y sus relaciones.

export type DispensationStatus = 'PENDIENTE_AUTORIZACION' | 'CONFIRMADA' | 'ANULADA';

export interface DispensationPatient {
  id: number;
  document: string;
  full_name: string;
}

export interface DispensationUser {
  id: number;
  name: string;
}

export interface DispensationWarehouse {
  id: number;
  name: string;
}

export interface DispensationProduct {
  id: number;
  name: string;
  is_special: boolean;
}

export interface DispensationBatch {
  id: number;
  code: string;
  expires_on: string;
}

export interface DispensationPrescription {
  id: number;
  patient_id: number;
  doctor_id: number;
  valid_until: string;
  created_at?: string;
  patient?: DispensationPatient;
}

// Lote propuesto por el backend al simular FEFO
export interface DispensationAllocation {
  batch_id: number;
  batch_code: string;
  expires_on: string;
  available: number;
  take: number;
}

export interface DispensationPreviewItem {
  prescription_item_id: number;
  product: DispensationProduct;
  prescribed: number;
  already_dispensed: number;
  remaining: number;
  allocations: DispensationAllocation[];
  warnings: string[];
}

export interface DispensationPreview {
  prescription: DispensationPrescription;
  warehouse?: DispensationWarehouse;
  items: DispensationPreviewItem[];
  requires_authorization: boolean;
}

export interface DispensationItem {
  id: number;
  dispensation_id: number;
  prescription_item_id: number;
  batch_id: number;
  quantity: number;
  batch?: DispensationBatch;
  prescription_item?: {
    id: number;
    prescription_id: number;
    product_id: number;
    quantity: number;
    product?: DispensationProduct;
  };
}

export interface Dispensation {
  id: number;
  idempotency_key: string;
  prescription_id: number;
  warehouse_id: number;
  dispensed_by: number;
  authorized_by: number | null;
  created_at: string;
  status?: DispensationStatus;
  prescription?: DispensationPrescription;
  warehouse?: DispensationWarehouse;
  dispensed_by_user?: DispensationUser;
  authorized_by_user?: DispensationUser | null;
  items?: DispensationItem[];
  items_count?: number;
}

export type PreviewDispensationPayload = {
  prescription_id: number;
  warehouse_id: number;
}

export interface CreateDispensationItemPayload {
  prescription_item_id: number;
  quantity: number;
}

export type CreateDispensationPayload = {
  prescription_id: number;
  warehouse_id: number;
  items: CreateDispensationItemPayload[];
  authorized_by?: number | null;
}
