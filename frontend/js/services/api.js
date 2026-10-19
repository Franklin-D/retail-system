/**
 * Único módulo que hace fetch() hacia el backend.
 * Respuesta esperada: { success, message, data }
 */

function resolverUrlBase() {
  const { origin, pathname } = window.location;
  const marcador = '/retail-system/';
  const indice = pathname.indexOf(marcador);
  if (indice !== -1) {
    return `${origin}${pathname.slice(0, indice)}/retail-system/backend/public/index.php/api`;
  }
  return 'http://localhost:8000/api';
}

const BASE_URL = resolverUrlBase();

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
    credentials: 'include',
    headers: {},
  };

  if (body instanceof FormData) {
    options.body = body;
  } else if (body !== undefined) {
    options.headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(body);
  }

  let response;
  try {
    response = await fetch(`${BASE_URL}${path}`, options);
  } catch {
    throw new ApiError('No se pudo conectar con el servidor', 0, null);
  }

  let payload = null;
  try {
    payload = await response.json();
  } catch {
    payload = null;
  }

  if (!response.ok) {
    const message = payload?.message || payload?.error || `Error ${response.status}`;
    throw new ApiError(message, response.status, payload?.data ?? null);
  }

  return payload;
}

export function urlImagen(nombreArchivo) {
  if (!nombreArchivo) return '';
  if (/^https?:\/\//i.test(nombreArchivo)) return nombreArchivo;
  const publico = BASE_URL.replace(/\/index\.php\/api$/, '').replace(/\/api$/, '');
  return `${publico}/uploads/${nombreArchivo}`;
}

export const api = {
  get: (path) => request('GET', path),
  post: (path, body) => request('POST', path, body),
  put: (path, body) => request('PUT', path, body),
  del: (path) => request('DELETE', path),
};

export { ApiError, BASE_URL };
