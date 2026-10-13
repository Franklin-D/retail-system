/**
 * js/services/auth.js
 * -----------------------------------------------------------------------
 * Funciones de autenticación usadas por js/modules/login.js y por el
 * resto de páginas para verificar sesión activa.
 *
 * Implementación real de cada función: ETAPA 6 (backend de auth) +
 * ETAPA 11 (conexión completa). Por ahora quedan declaradas con su
 * contrato para que login.js ya pueda importarlas sin romper.
 * -----------------------------------------------------------------------
 */

import { api } from './api.js';

export async function login(email, password) {
  // TODO (Etapa 6/11): return api.post('/auth/login', { email, password });
  throw new Error('AuthService.login() aún no implementado (Etapa 6).');
}

export async function logout() {
  // TODO (Etapa 6/11): return api.post('/auth/logout');
  throw new Error('AuthService.logout() aún no implementado (Etapa 6).');
}

export async function getCurrentUser() {
  // TODO (Etapa 6/11): return api.get('/auth/me');
  return null;
}

/** Redirige a login.html si no hay sesión activa. Se usa al inicio de cada página protegida. */
export async function requireSession() {
  // TODO (Etapa 11): activar esta verificación real contra el backend.
  // const user = await getCurrentUser();
  // if (!user) window.location.href = 'login.html';
}
