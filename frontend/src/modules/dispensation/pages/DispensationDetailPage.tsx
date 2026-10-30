import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { DispensationApiError, fetchDispensation } from '@/services/dispensationService';
import type { Dispensation } from '@/types/dispensation';
import { formatDate, formatDateTime } from '../utils/format';

function toMessage(error: unknown): string {
  if (error instanceof DispensationApiError) return error.message;
  return error instanceof Error ? error.message : 'Error al obtener la dispensación';
}

export default function DispensationDetailPage() {
  const { id } = useParams<{ id: string }>();
  const numericId = Number(id);
  const isValidId = Boolean(id) && Number.isInteger(numericId) && numericId > 0;

  const [dispensation, setDispensation] = useState<Dispensation | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isValidId) {
      setError('ID de dispensación inválido');
      setLoading(false);
      return;
    }

    let cancelled = false;
    setLoading(true);
    setError(null);

    fetchDispensation(numericId)
      .then((result) => {
        if (!cancelled) setDispensation(result);
      })
      .catch((err: unknown) => {
        if (!cancelled) setError(toMessage(err));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [isValidId, numericId]);

  if (loading) {
    return (
      <div className="flex justify-center py-20">
        <span className="loading loading-ring loading-lg text-primary" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex flex-col items-center gap-4 py-20">
        <p role="alert" className="text-error">{error}</p>
        <Link to="/dispensation" className="btn btn-soft">Volver a dispensaciones</Link>
      </div>
    );
  }

  if (!dispensation) return null;

  const items = dispensation.items ?? [];

  return (
    <>
      {/* Breadcrumbs */}
      <nav className="breadcrumbs text-sm mb-4" aria-label="Breadcrumb">
        <ul>
          <li><Link to="/dispensation">Dispensaciones</Link></li>
          <li className="text-base-content/70">#{dispensation.id}</li>
        </ul>
      </nav>

      <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h2 className="text-2xl font-bold">Dispensación #{dispensation.id}</h2>
        {dispensation.status && (
          <span className="badge badge-soft">{dispensation.status.replace(/_/g, ' ')}</span>
        )}
      </div>

      {/* Información general */}
      <div className="card bg-base-100 shadow-md mb-6">
        <div className="card-body">
          <h3 className="card-title text-lg mb-4">Información General</h3>
          <dl className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div>
              <dt className="text-base-content/60">Fecha</dt>
              <dd className="font-medium">{formatDateTime(dispensation.created_at)}</dd>
            </div>
            <div>
              <dt className="text-base-content/60">Prescripción</dt>
              <dd className="font-medium">#{dispensation.prescription_id}</dd>
            </div>
            <div>
              <dt className="text-base-content/60">Bodega</dt>
              <dd className="font-medium">
                {dispensation.warehouse?.name ?? dispensation.warehouse_id}
              </dd>
            </div>
            <div>
              <dt className="text-base-content/60">Dispensó</dt>
              <dd className="font-medium">
                {dispensation.dispensed_by_user?.name ?? dispensation.dispensed_by}
              </dd>
            </div>
            <div>
              <dt className="text-base-content/60">Autorizó</dt>
              <dd className="font-medium">
                {dispensation.authorized_by_user?.name ?? dispensation.authorized_by ?? '—'}
              </dd>
            </div>
            {dispensation.prescription && (
              <div>
                <dt className="text-base-content/60">Vigente hasta</dt>
                <dd className="font-medium">{formatDate(dispensation.prescription.valid_until)}</dd>
              </div>
            )}
            {dispensation.prescription?.patient && (
              <div>
                <dt className="text-base-content/60">Paciente</dt>
                <dd className="font-medium">{dispensation.prescription.patient.full_name}</dd>
              </div>
            )}
          </dl>
        </div>
      </div>

      {/* Lotes dispensados */}
      <div className="card bg-base-100 shadow-md mb-6">
        <div className="card-body p-0">
          <h3 className="card-title text-lg px-6 pt-6">Lotes dispensados</h3>
          <div className="overflow-x-auto">
            <table className="table">
              <thead>
                <tr>
                  <th scope="col" className="pl-6">Producto</th>
                  <th scope="col">Lote</th>
                  <th scope="col">Vence</th>
                  <th scope="col" className="text-right pr-6">Cantidad</th>
                </tr>
              </thead>
              <tbody>
                {items.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="text-center py-8 text-base-content/60">
                      Esta dispensación no tiene ítems registrados.
                    </td>
                  </tr>
                ) : (
                  items.map((item) => (
                    <tr key={item.id} className="hover:bg-base-300">
                      <td className="pl-6">
                        <div className="font-medium">
                          {item.prescription_item?.product?.name ??
                            `Ítem #${item.prescription_item_id}`}
                        </div>
                        {item.prescription_item?.product?.is_special && (
                          <span className="badge badge-warning badge-sm mt-1">
                            Control especial
                          </span>
                        )}
                      </td>
                      <td>{item.batch?.code ?? `Lote #${item.batch_id}`}</td>
                      <td>{item.batch ? formatDate(item.batch.expires_on) : '—'}</td>
                      <td className="text-right pr-6 tabular-nums">{item.quantity}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <Link to="/dispensation" className="btn btn-soft">Volver a dispensaciones</Link>
    </>
  );
}
