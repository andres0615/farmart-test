// Props que recibe del componente padre (ProductListPage)
interface Props {
  currentPage: number;         // Página actualmente activa
  lastPage: number;            // Total de páginas disponibles
  total: number;               // Total de productos (para el texto informativo)
  from: number | null;         // Índice del primer producto mostrado
  to: number | null;           // Índice del último producto mostrado
  onPageChange: (page: number) => void;  // Callback al cambiar de página
}

export default function TablePagination({
  currentPage, lastPage, total, from, to, onPageChange,
}: Props) {
  // No renderizar si no hay productos
  if (total === 0) return null;

  // Generar array con números de página y '...' para páginas no visibles
  const pages = buildPageRange(currentPage, lastPage);

  return (
    <div className="flex justify-between items-center">
      {/* Texto informativo: "Mostrando X–Y de Z productos" */}
      <p className="text-sm text-base-content/60 ml-2">
        Mostrando {from ?? 0} – {to ?? 0} de {total} registros
      </p>

      {/* Botonera de paginación con DaisyUI join */}
      <div className="join">
        {/* Botón "Anterior" - deshabilitado si está en la primera página */}
        <button
          className="join-item btn btn-soft btn-sm"
          disabled={currentPage === 1}
          onClick={() => onPageChange(currentPage - 1)}
        >
          «
        </button>

        {/* Botones de página: número o '...' */}
        {pages.map((page, i) =>
          page === '...' ? (
            <button key={`e${i}`} className="join-item btn btn-soft btn-sm btn-disabled">
              ...
            </button>
          ) : (
            <button
              key={page}
              // btn-active resalta la página actual
              className={`join-item btn btn-soft btn-sm${page === currentPage ? ' btn-active' : ''}`}
              onClick={() => onPageChange(page)}
            >
              {page}
            </button>
          )
        )}

        {/* Botón "Siguiente" - deshabilitado si está en la última página */}
        <button
          className="join-item btn btn-soft btn-sm"
          disabled={currentPage === lastPage}
          onClick={() => onPageChange(currentPage + 1)}
        >
          »
        </button>
      </div>
    </div>
  );
}

/**
 * Genera un array con los números de página a mostrar,
 * insertando '...' cuando hay saltos en la secuencia.
 *
 * Estrategia:
 * - Si hay 7 páginas o menos, se muestran todas.
 * - Siempre se muestra la primera y la última página.
 * - Se muestra un rango de 3 páginas alrededor de la actual.
 * - Los huecos se reemplazan con '...'.
 */
function buildPageRange(current: number, last: number): (number | '...')[] {
  // Pocas páginas: mostrar todas
  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1);
  }

  const pages: (number | '...')[] = [1];

  // Hueco después de la primera página si current está más allá de la página 3
  if (current > 3) {
    pages.push('...');
  }

  // Rango de 3 páginas alrededor de la actual (anterior, actual, siguiente)
  const start = Math.max(2, current - 1);
  const end = Math.min(last - 1, current + 1);

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  // Hueco antes de la última página si current está antes del antepenúltimo
  if (current < last - 2) {
    pages.push('...');
  }

  // Última página siempre visible
  pages.push(last);

  return pages;
}
