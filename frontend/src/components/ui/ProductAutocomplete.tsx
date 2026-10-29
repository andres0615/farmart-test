import { fetchProductsPaginated } from '@/services/productService';
import type { Product } from '@/types/product';
import { useState, useEffect, useRef } from 'react';
import type { UseFormRegisterReturn, FieldError } from 'react-hook-form';

interface ProductAutocompleteProps {
  registration: UseFormRegisterReturn;
  defaultProduct?: Product | null;
  onChange: (productId: Product) => void;
  error?: FieldError;
}

export default function ProductAutocomplete({ registration, defaultProduct, onChange, error }: ProductAutocompleteProps) {
  const [query, setQuery] = useState('');
  const [suggestions, setSuggestions] = useState<Product[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);

  const debounceRef = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);
  const containerRef = useRef<HTMLDivElement>(null);

  // Handler para cuando se hace clic en una sugerencia
  const handleSuggestionClick = (product: Product) => {
    console.log('handleSuggestionClick: ', product);
    setSelectedProduct(product);
    onChange(product);
    setQuery(product.name);
    // Cerrar el dropdown
    setIsOpen(false);
  };

  // Manejar cambio en el input
  const handleQueryChange = (e: React.ChangeEvent<HTMLInputElement>): void => {
    registration.onChange(e);
    setQuery(e.target.value);
  };

  // Cuando cambia el query, buscar productos
  useEffect(() => {
    // Si hay un producto seleccionado y el query coincide con su nombre,
    // no buscar (evita disparar búsqueda al mostrar el nombre)
    if (selectedProduct && query === selectedProduct.name) return;

    // Si el query cambió, limpiar selección anterior
    const hadSelection = selectedProduct !== null;
    setSelectedProduct(null);
    if (hadSelection) {
      onChange(null);
    }

    // Con menos de 2 caracteres, no buscar
    if (query.length < 2) {
      setSuggestions([]);
      setIsOpen(false);
      return;
    }

    setLoading(true);
    clearTimeout(debounceRef.current);

    // Esperar 300ms desde el último cambio antes de consultar
    debounceRef.current = setTimeout(async () => {
      try {
        const products = await fetchProductsPaginated(1, 10, { name: query });
        setSuggestions(products.data);
        setIsOpen(true);
      } catch {
        setSuggestions([]);
      } finally {
        setLoading(false);
      }
    }, 300);

    // Limpiar timeout si el query cambia antes de los 300ms
    return () => clearTimeout(debounceRef.current);
  }, [query]);

  // Cuando se carga el componente con un producto por defecto
  useEffect(() => {
    if (defaultProduct) {
      handleSuggestionClick(defaultProduct);
    }
  }, []);

  // Cerrar dropdown al hacer click fuera
  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  return (
    <div className="relative" ref={containerRef}>
      <label className="floating-label">
        <span>Producto</span>
        <input
          type="text"
          placeholder="Producto"
          className={`input input-md w-full ${error ? 'input-error' : ''}`}
          {...registration}
          value={query}
          onChange={e => handleQueryChange(e)}
        />
      </label>
      {error && (
        <p className="text-error text-xs mt-1">{error.message}</p>
      )}
      {/* Suggestions dropdown */}
      {isOpen && (
        <ul className="absolute z-50 top-full mt-1 w-full bg-base-100 shadow-md rounded-box max-h-48 overflow-y-auto">
          {/* Loading state */}
          {loading && <li className="p-2 text-sm opacity-60">Buscando...</li>}
          {/* No results */}
          {!loading && suggestions.length === 0 && (
            <li className="p-2 text-sm opacity-60">Sin resultados</li>
          )}
          {/* Results */}
          {!loading && suggestions.map(product => (
            <li key={product.id}>
              <button
                type="button"
                className="text-sm w-full text-left px-3 py-2 hover:bg-base-200"
                onClick={() => handleSuggestionClick(product)}
              >
                {product.code} — {product.name}
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}