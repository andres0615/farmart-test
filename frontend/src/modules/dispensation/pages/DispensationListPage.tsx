import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useLayoutContext } from '@/contexts/LayoutContext';
import { fetchDispensationsPaginated } from '@/services/dispensationService';
import type { Dispensation } from '@/types/dispensation';
import type { PaginationMeta } from '@/types/pagination';
import TablePagination from '@/components/ui/TablePagination';
import { formatDateTime } from '../utils/format';

export default function DispensationListPage() {
  const { setMaxWidth } = useLayoutContext();
  const [dispensations, setDispensations] = useState<Dispensation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [pagination, setPagination] = useState<PaginationMeta | null>(null);
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    setMaxWidth('max-w-[61rem]');
    return () => setMaxWidth('max-w-4xl');
  }, [setMaxWidth]);

  // Cargar dispensaciones
  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);

    fetchDispensationsPaginated(currentPage, 10)
      .then((result) => {
        if (cancelled) return;
        setDispensations(result.data);
        setPagination(result.pagination);
      })
      .catch((err: unknown) => {
        if (cancelled) return;
        setError(err instanceof Error ? err.message : 'Error al obtener las dispensaciones');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [currentPage, reloadKey]);

  return (
    <>
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 className="text-2xl font-bold">Dispensaciones</h2>
          <p className="text-sm text-base-content/60">{pagination?.total ?? 0} registros</p>
        </div>
        <Link to="/dispensation/create" className="btn btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor" className="size-5">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          Nueva dispensación
        </Link>
      </div>

      {error && (
        <div role="alert" className="alert alert-error mb-6">
          <span>{error}</span>
          <button
            type="button"
            className="btn btn-sm"
            onClick={() => setReloadKey((key) => key + 1)}
          >
            Reintentar
          </button>
        </div>
      )}

      {/* Tabla de dispensaciones */}
      <div className="card bg-base-100 shadow-md mb-6">
        <div className="card-body p-0">
          <div className="overflow-x-auto">
            <table className="table">
              <thead>
                <tr>
                  <th scope="col" className="pl-6">Fecha</th>
                  <th scope="col">Prescripción</th>
                  <th scope="col">Bodega</th>
                  <th scope="col">Dispensó</th>
                  <th scope="col">Autorizó</th>
                  <th scope="col"><span className="sr-only">Acciones</span></th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan={6} className="text-center py-8">
                      <span className="loading loading-ring loading-lg text-primary"></span>
                    </td>
                  </tr>
                ) : dispensations.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="text-center py-10">
                      <p className="font-medium">Todavía no hay dispensaciones</p>
                      <p className="text-sm text-base-content/60 mt-1">
                        Registra la primera desde “Nueva dispensación”.
                      </p>
                    </td>
                  </tr>
                ) : (
                  dispensations.map((dispensation) => (
                    <tr key={dispensation.id} className="hover:bg-base-300">
                      <td className="pl-6 whitespace-nowrap">
                        {formatDateTime(dispensation.created_at)}
                      </td>
                      <td>#{dispensation.prescription_id}</td>
                      <td>{dispensation.warehouse?.name ?? dispensation.warehouse_id}</td>
                      <td>{dispensation.dispensed_by_user?.name ?? dispensation.dispensed_by}</td>
                      <td>
                        {dispensation.authorized_by_user?.name ??
                          dispensation.authorized_by ??
                          '—'}
                      </td>
                      <td className="text-right pr-6">
                        <Link
                          to={`/dispensation/${dispensation.id}`}
                          className="btn btn-ghost btn-sm"
                        >
                          Ver
                        </Link>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Paginación */}
      <TablePagination
        currentPage={currentPage}
        lastPage={pagination?.lastPage ?? 1}
        total={pagination?.total ?? 0}
        from={pagination?.from ?? null}
        to={pagination?.to ?? null}
        onPageChange={setCurrentPage}
      />
    </>
  );
}
