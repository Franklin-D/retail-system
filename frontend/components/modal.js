/**
 * components/modal.js
 * -----------------------------------------------------------------------
 * Componente vanilla reutilizable para abrir/cerrar cualquier modal que
 * siga la estructura CSS .modal / .modal.active (ver css/styles.css).
 * Módulo de UI puro, sin dependencia del backend.
 *
 * Uso:
 *   import { openModal, closeModal } from '../../components/modal.js';
 *   openModal('addProductModal');
 *   closeModal('addProductModal');
 * -----------------------------------------------------------------------
 */

export function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  modal.classList.add('active');
  if (window.lucide) window.lucide.createIcons();
}

export function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  modal.classList.remove('active');
}

// Cierra cualquier modal al hacer click en el fondo oscuro (fuera del contenido)
document.addEventListener('click', (event) => {
  if (event.target.classList && event.target.classList.contains('modal')) {
    event.target.classList.remove('active');
  }
});
