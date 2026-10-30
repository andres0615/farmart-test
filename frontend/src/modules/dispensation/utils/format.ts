// Formatea fechas ISO completas; devuelve el valor original si no es parseable
export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '—';
  // PostgreSQL devuelve "2026-10-29 04:18:54+00"; se normaliza a ISO
  const normalized = value.replace(' ', 'T').replace(/([+-]\d{2})$/, '$1:00');
  const date = new Date(normalized);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short' });
}

// Fechas sin hora (expires_on, valid_until): evita el desfase por zona horaria
export function formatDate(value: string | null | undefined): string {
  if (!value) return '—';
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
  if (match) {
    const [, year, month, day] = match;
    return `${day}/${month}/${year}`;
  }
  return formatDateTime(value);
}
