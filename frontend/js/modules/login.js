/**
 * js/modules/login.js
 * -----------------------------------------------------------------------
 * Controla la página login.html. La llamada real al backend depende de
 * auth.js, que se completa en la Etapa 6. Este módulo ya queda
 * completamente cableado: cuando auth.login() se implemente, la página
 * funcionará sin tocar este archivo.
 * -----------------------------------------------------------------------
 */

import { login } from '../services/auth.js';
import { showToast } from '../../components/toast.js';

const form = document.getElementById('loginForm');

form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const email = form.email.value.trim();
  const password = form.password.value;

  const submitButton = form.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.textContent = 'Ingresando...';

  try {
    await login(email, password);
    window.location.href = 'dashboard.html';
  } catch (err) {
    showToast(err.message || 'No se pudo iniciar sesión', 'error');
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Iniciar Sesión';
  }
});
