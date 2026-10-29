/**
 * Igual que `RequestInit`, pero permite pasar un objeto plano (o array) como
 * `body`; `apiClient` se encarga de serializarlo a JSON internamente.
 */
export type ApiClientOptions = Omit<RequestInit, 'body'> & {
  body?: BodyInit | Record<string, unknown> | unknown[] | null;
};