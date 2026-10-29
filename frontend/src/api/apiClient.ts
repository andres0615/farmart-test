import type { ApiClientOptions } from '@/types/api-client';

const BASE_URL = import.meta.env.VITE_API_URL || '/api';

let isRefreshing = false;
let refreshSubscribers: Array<(token: string) => void> = [];

/**
 * Encola un callback que se ejecutará cuando el token se refresque
 * (mientras otro refresh está en curso).
 */
function subscribeTokenRefresh(callback: (token: string) => void) {
  refreshSubscribers.push(callback);
}

/**
 * Notifica a todos los callbacks en cola que el token se refrescó
 * y vacía la cola de suscriptores.
 */
function onTokenRefreshed(newToken: string) {
  refreshSubscribers.forEach((callback) => callback(newToken));
  refreshSubscribers = [];
}

/**
 * Solicita un nuevo access token al endpoint `/refresh` y lo guarda en
 * `localStorage`. Lanza un error si la petición falla.
 */
async function refreshToken(): Promise<string> {
  const response = await fetch(`${BASE_URL}/refresh`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('access_token')}`,
    },
  });

  if (!response.ok) {
    throw new Error('No se pudo refrescar el token');
  }

  const data: { access_token: string } = await response.json();
  localStorage.setItem('access_token', data.access_token);
  return data.access_token;
}

/**
 * Wrapper de `fetch` que agrega headers JSON + token, serializa el body,
 * maneja el refresh de token ante 401 (con reintento único) y devuelve
 * el `Response` crudo para que el servicio que lo llama lo procese.
 */
async function apiClient(
  endpoint: string,
  options: ApiClientOptions = {},
  retry = true,
): Promise<Response> {
  const token = localStorage.getItem('access_token');

  const config: RequestInit = {
    ...options,
    body: undefined, // lo asignamos abajo ya serializado
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers as Record<string, string> | undefined),
    },
  };

  // serialización del body
  config.body = JSON.stringify(options.body) as string;

  const response = await fetch(`${BASE_URL}${endpoint}`, config);

  // Si el token expiró y aún no hemos reintentado
  if (response.status === 401 && retry) {
    if (!isRefreshing) {
      isRefreshing = true;
      try {
        const newToken = await refreshToken();
        isRefreshing = false;
        onTokenRefreshed(newToken);
        // Reintenta la petición original con el token nuevo
        return apiClient(endpoint, options, false);
      } catch (err) {
        isRefreshing = false;
        localStorage.removeItem('access_token');
        window.location.href = '/login';
        throw err;
      }
    }

    // Si ya hay un refresh en curso, espera a que termine y reintenta
    return new Promise((resolve, reject) => {
      subscribeTokenRefresh((newToken) => {
        apiClient(endpoint, options, false).then(resolve).catch(reject);
      });
    });
  }

  return response;
}

export default apiClient;