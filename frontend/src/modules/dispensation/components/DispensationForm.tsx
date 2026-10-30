import { useEffect, useRef, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import {
  DispensationApiError,
  createDispensation,
  createIdempotencyKey,
  previewDispensation,
} from '@/services/dispensationService';
import { fetchWarehouses } from '@/services/warehouseService';
import { useToast } from '@/components/toast/ToastContext';
import type { DispensationPreview } from '@/types/dispensation';
import type { DispensationWarehouse } from '@/types/warehouse';
import { formatDate } from '../utils/format';

interface DispensationFormProps {
  onCreated: (id: number) => void;
}

// Mensajes por código de error del backend
const ERROR_MESSAGES: Record<string, string> = {
  INSUFFICIENT_STOCK: 'No hay stock suficiente en la bodega seleccionada.',
  EXPIRED_BATCH: 'El lote asignado venció. Vuelve a calcular los lotes.',
  PRESCRIPTION_EXPIRED: 'La prescripción no está vigente.',
  PRESCRIPTION_EXCEEDED: 'La cantidad supera lo pendiente por dispensar.',
  REQUIRES_AUTHORIZATION: 'Este medicamento requiere autorización de un regente de farmacia.',
  STOCK_CHANGED: 'El stock cambió desde la previsualización. Vuelve a calcular los lotes.',
  DUPLICATE_IDEMPOTENCY: 'Esta dispensación ya fue registrada.',
};

function toMessage(error: unknown): string {
  if (error instanceof DispensationApiError) {
    const localized = error.code ? ERROR_MESSAGES[error.code] : undefined;
    const base = localized ?? error.message;
    return error.details.length > 0 ? `${base} ${error.details.join(' ')}` : base;
  }
  return error instanceof Error ? error.message : 'Ocurrió un error inesperado';
}

function parseId(value: string): number | null {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}

export default function DispensationForm({ onCreated }: DispensationFormProps) {
  const { showToast } = useToast();

  const [prescriptionId, setPrescriptionId] = useState('');
  const [warehouseId, setWarehouseId] = useState('');
  const [authorizedBy, setAuthorizedBy] = useState('');
  const [preview, setPreview] = useState<DispensationPreview | null>(null);
  const [quantities, setQuantities] = useState<Record<number, number>>({});
  const [previewing, setPreviewing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [warehouses, setWarehouses] = useState<DispensationWarehouse[]>([]);
  const [warehousesLoading, setWarehousesLoading] = useState(true);

  // Carga el catálogo de bodegas al abrir el formulario
  useEffect(() => {
    async function loadWarehouses() {
      try {
        setWarehouses(await fetchWarehouses());
      } catch (err) {
        setError(toMessage(err));
      } finally {
        setWarehousesLoading(false);
      }
    }

    void loadWarehouses();
  }, []);

  // Se usa en el select y en el mensaje de vacío
  const warehousesEmpty = !warehousesLoading && warehouses.length === 0;

  // Se mantiene estable durante los reintentos de la misma solicitud (RN-09)
  const idempotencyKey = useRef<string>(createIdempotencyKey());

  // Una nueva intención (cambio de datos) invalida la clave anterior
  function renewIdempotencyKey() {
    idempotencyKey.current = createIdempotencyKey();
  }

  // Invalida la previsualización cuando cambian los datos de entrada
  function handlePrescriptionChange(value: string) {
    setPrescriptionId(value);
    setPreview(null);
    setError(null);
    renewIdempotencyKey();
  }

  function handleWarehouseChange(value: string) {
    setWarehouseId(value);
    setPreview(null);
    setError(null);
    renewIdempotencyKey();
  }

  async function handlePreview() {
    const prescription = parseId(prescriptionId);
    const warehouse = parseId(warehouseId);

    if (prescription === null) {
      setError('Indica un ID de prescripción válido.');
      return;
    }
    if (warehouse === null) {
      setError('Indica un ID de bodega válido.');
      return;
    }

    setPreviewing(true);
    setError(null);

    try {
      const result = await previewDispensation({
        prescription_id: prescription,
        warehouse_id: warehouse,
      });

      // Precarga lo pendiente, acotado a lo que realmente hay disponible
      const initialQuantities: Record<number, number> = {};
      for (const item of result.items) {
        const available = item.allocations.reduce(
          (total, allocation) => total + allocation.take,
          0,
        );
        initialQuantities[item.prescription_item_id] = Math.max(
          0,
          Math.min(item.remaining, available),
        );
      }

      setPreview(result);
      setQuantities(initialQuantities);
    } catch (err) {
      setPreview(null);
      setError(toMessage(err));
    } finally {
      setPreviewing(false);
    }
  }

  async function handleConfirm() {
    if (!preview) return;

    const warehouse = parseId(warehouseId);
    if (warehouse === null) {
      setError('Indica un ID de bodega válido.');
      return;
    }

    const items = preview.items
      .map((item) => ({
        prescription_item_id: item.prescription_item_id,
        quantity: Number(quantities[item.prescription_item_id] ?? 0),
        remaining: item.remaining,
      }))
      .filter((item) => item.quantity > 0);

    if (items.length === 0) {
      setError('Indica al menos una cantidad a dispensar.');
      return;
    }

    const invalid = items.find(
      (item) => !Number.isInteger(item.quantity) || item.quantity > item.remaining,
    );
    if (invalid) {
      setError('Las cantidades deben ser enteras y no superar lo pendiente.');
      return;
    }

    const authorizedById = parseId(authorizedBy);
    if (preview.requires_authorization && authorizedById === null) {
      setError('Indica el ID del regente que autoriza esta dispensación.');
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      const created = await createDispensation(
        {
          prescription_id: preview.prescription.id,
          warehouse_id: warehouse,
          items: items.map(({ prescription_item_id, quantity }) => ({
            prescription_item_id,
            quantity,
          })),
          ...(authorizedById !== null ? { authorized_by: authorizedById } : {}),
        },
        idempotencyKey.current,
      );

      showToast('Dispensación registrada exitosamente', 'success');
      idempotencyKey.current = createIdempotencyKey();
      onCreated(created.id);
    } catch (err) {
      setError(toMessage(err));
      showToast(toMessage(err), 'error');
    } finally {
      setSubmitting(false);
    }
  }

  function handleReset() {
    setPreview(null);
    setQuantities({});
    setError(null);
    renewIdempotencyKey();
  }

  // El submit del formulario solo calcula lotes; confirmar es una acción explícita
  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (preview || previewing || submitting) return;
    void handlePreview();
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Datos de entrada */}
      <div className="card bg-base-100 shadow-md">
        <div className="card-body">
          <h3 className="card-title text-lg mb-4">Datos de la dispensación</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label className="floating-label">
              <span>Prescripción</span>
              <input
                type="number"
                min={1}
                step={1}
                inputMode="numeric"
                placeholder="ID de prescripción"
                className="input input-md w-full"
                value={prescriptionId}
                onChange={(event) => handlePrescriptionChange(event.target.value)}
                disabled={submitting}
                required
              />
            </label>

            <div>
              <label className="floating-label">
                <span>Bodega</span>
                <select
                  className="select select-md w-full"
                  value={warehouseId}
                  onChange={(event) => handleWarehouseChange(event.target.value)}
                  disabled={submitting || warehousesLoading || warehousesEmpty}
                  required
                >
                  <option value="" disabled>
                    {warehousesLoading ? 'Cargando bodegas…' : 'Selecciona una bodega'}
                  </option>
                  {warehouses.map((warehouse) => (
                    <option key={warehouse.id} value={warehouse.id}>
                      {warehouse.name}
                    </option>
                  ))}
                </select>
              </label>
              {warehousesEmpty && (
                <p role="status" className="text-xs text-error mt-1">
                  No hay bodegas disponibles.
                </p>
              )}
            </div>

            {preview?.requires_authorization && (
              <label className="floating-label">
                <span>Autorizado por</span>
                <input
                  type="number"
                  min={1}
                  step={1}
                  inputMode="numeric"
                  placeholder="ID del regente"
                  className="input input-md w-full"
                  value={authorizedBy}
                  onChange={(event) => setAuthorizedBy(event.target.value)}
                  disabled={submitting}
                  required
                />
              </label>
            )}
          </div>

          <p className="text-xs text-base-content/60 mt-3">
            La asignación se calcula por FEFO y se revalida al confirmar. Si el stock cambió,
            vuelve a calcular los lotes.
          </p>
        </div>
      </div>

      {error && (
        <div role="alert" className="alert alert-error text-sm">
          <span>{error}</span>
        </div>
      )}

      {/* Lotes sugeridos por FEFO */}
      {preview && (
        <div className="card bg-base-100 shadow-md">
          <div className="card-body">
            <h3 className="card-title text-lg">Lotes asignados (FEFO)</h3>
            <p className="text-sm text-base-content/60">
              Prescripción #{preview.prescription.id}
              {preview.prescription.patient ? ` — ${preview.prescription.patient.full_name}` : ''}
            </p>

            {preview.requires_authorization && (
              <div role="status" className="alert alert-warning text-sm">
                <span>Esta prescripción incluye medicamentos de control especial.</span>
              </div>
            )}

            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th scope="col">Producto</th>
                    <th scope="col">Pendiente</th>
                    <th scope="col">Lotes sugeridos</th>
                    <th scope="col">A dispensar</th>
                  </tr>
                </thead>
                <tbody>
                  {preview.items.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="text-center py-8 text-base-content/60">
                        No hay ítems pendientes en esta prescripción.
                      </td>
                    </tr>
                  ) : (
                    preview.items.map((item) => {
                      const available = item.allocations.reduce(
                        (total, allocation) => total + allocation.take,
                        0,
                      );
                      const quantity = quantities[item.prescription_item_id] ?? 0;

                      return (
                        <tr key={item.prescription_item_id}>
                          <td>
                            <div className="font-medium">{item.product.name}</div>
                            {item.product.is_special && (
                              <span className="badge badge-warning badge-sm mt-1">
                                Control especial
                              </span>
                            )}
                          </td>
                          <td className="tabular-nums">{item.remaining}</td>
                          <td>
                            {item.allocations.length === 0 ? (
                              <span className="text-error text-xs">Sin lotes disponibles</span>
                            ) : (
                              <ul className="space-y-1 text-xs">
                                {item.allocations.map((allocation) => (
                                  <li key={allocation.batch_id} className="flex flex-wrap gap-x-2">
                                    <span className="font-medium">{allocation.batch_code}</span>
                                    <span className="text-base-content/60">
                                      vence {formatDate(allocation.expires_on)}
                                    </span>
                                    <span className="tabular-nums">
                                      toma {allocation.take}
                                    </span>
                                  </li>
                                ))}
                              </ul>
                            )}
                            {item.remaining > available && (
                              <p className="text-warning text-xs mt-1">
                                Solo hay {available} disponibles; la dispensación quedará parcial.
                              </p>
                            )}
                          </td>
                          <td>
                            <input
                              type="number"
                              min={0}
                              max={Math.min(item.remaining, available)}
                              step={1}
                              className="input input-sm w-24 tabular-nums"
                              value={quantity}
                              aria-label={`Cantidad a dispensar de ${item.product.name}`}
                              onChange={(event) => {
                                const nextQuantity = Number(event.target.value);
                                renewIdempotencyKey();
                                setQuantities((previous) => ({
                                  ...previous,
                                  [item.prescription_item_id]: nextQuantity,
                                }));
                              }}
                            />
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* Acciones */}
      <div className="flex flex-wrap gap-3">
        {preview ? (
          <>
            <button
              type="button"
              className="btn btn-primary"
              disabled={submitting}
              onClick={() => void handleConfirm()}
            >
              {submitting && <span className="loading loading-spinner loading-sm" />}
              Confirmar dispensación
            </button>
            <button
              type="button"
              className="btn btn-soft"
              onClick={handleReset}
              disabled={submitting}
            >
              Cambiar datos
            </button>
          </>
        ) : (
          <button type="submit" className="btn btn-primary" disabled={previewing || submitting}>
            {previewing && <span className="loading loading-spinner loading-sm" />}
            Calcular lotes
          </button>
        )}
        <Link to="/dispensation" className="btn btn-ghost">
          Cancelar
        </Link>
      </div>
    </form>
  );
}
