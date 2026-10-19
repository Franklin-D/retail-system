import { api, ApiError } from './api.js';

export async function login(email, password) {
  return api.post('/auth/login', { email, password });
}

export async function logout() {
  return api.post('/auth/logout');
}

export async function getCurrentUser() {
  try {
    const { data } = await api.get('/auth/me');
    return data;
  } catch {
    return null;
  }
}

export async function requireSession() {
  const user = await getCurrentUser();
  if (!user) {
    window.location.href = 'login.html';
    throw new ApiError('No autenticado', 401, null);
  }
  return user;
}
