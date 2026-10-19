/**
 * components/toast.js
 * -----------------------------------------------------------------------
 * Componente vanilla reutilizable: muestra notificaciones flotantes.
 * No depende de ningún framework ni de la API; es un módulo de UI puro,
 * por eso está completamente implementado desde la Etapa 2.
 *
 * Uso desde cualquier módulo de página:
 *   import { showToast } from '../../components/toast.js';
 *   showToast('Producto guardado correctamente');
 *   showToast('Ocurrió un error', 'error');
 * -----------------------------------------------------------------------
 */

export function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = 'toast';
  if (type === 'error') {
    toast.style.background = 'rgba(239, 68, 68, 0.9)';
  }
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}
