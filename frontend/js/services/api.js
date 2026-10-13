/**
 * js/services/api.js
 * -----------------------------------------------------------------------
 * Único módulo que hace fetch() hacia el backend. Todos los módulos de
 * página (js/modules/*.js) importan esto en vez de llamar fetch()
 * directamente, para mantener consistente el manejo de:
 *   - base URL del backend
 *   - credenciales de sesión (cookie)
 *   - parseo de JSON y de errores
 *
 * El formato de respuesta esperado del backend (ver utils/Response.php)
 * es siempre: { success: bool, message: string, data: any }
 *
 * Nota: la URL base y los endpoints reales se confirman en la Etapa 5
 * (API REST) y la Etapa 11 (conexión completa frontend-API). Por ahora
 * este archivo ya expone la interfaz get/post/put/del que usarán los
 * módulos, para no tener que reescribirlos después.
 * -----------------------------------------------------------------------
 */

const BASE_URL = 'http://localhost:8000/api';

class ApiError extends Error {
  constructor(message, statusCode, data) {
    super(message);
    this.statusCode = statusCode;
    this.data = data;
  }
}

async function request(method, path, body) {
  const options = {
    method,
    credentials: 'include', // envía/recibe la cookie de sesión PHP
    headers: {},
  };

  if (body instanceof FormData) {
    options.body = body; // no seteamos Content-Type: el browser pone el boundary
  } else if (body !== undefined) {
    options.headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(body);
  }

  let response;
  try {
    response = await fetch(`${BASE_URL}${path}`, options);
  } catch (networkError) {
    throw new ApiError('No se pudo conectar con el servidor', 0, null);
  }

  let payload = null;
  try {
    payload = await response.json();
  } catch {
    // respuesta sin body JSON (poco común, pero no debe romper el flujo)
  }

  if (!response.ok) {
    const message = payload?.message || `Error ${response.status}`;
    throw new ApiError(message, response.status, payload?.data ?? null);
  }

  return payload; // { success, message, data }
}

export const api = {
  get: (path) => request('GET', path),
  post: (path, body) => request('POST', path, body),
  put: (path, body) => request('PUT', path, body),
  del: (path) => request('DELETE', path),
};

export { ApiError };
