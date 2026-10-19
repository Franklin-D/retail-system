/**
 * js/utils/helpers.js
 * -----------------------------------------------------------------------
 * Funciones auxiliares puras, sin dependencia del backend ni del DOM
 * salvo donde se indica. Completamente funcionales desde la Etapa 2.
 * -----------------------------------------------------------------------
 */

/** Formatea un número como moneda en Soles peruanos, ej: formatCurrency(45.9) -> "S/ 45.90" */
export function formatCurrency(value) {
  const number = Number(value) || 0;
  return `S/ ${number.toFixed(2)}`;
}

/** Formatea una fecha ISO (yyyy-mm-dd) a formato peruano dd/mm/yyyy */
export function formatDate(isoDate) {
  if (!isoDate) return 'No definida';
  const datePart = String(isoDate).slice(0, 10);
  const [year, month, day] = datePart.split('-');
  if (!year || !month || !day) return isoDate;
  return `${day}/${month}/${year}`;
}

export function filaVacia(colspan, texto) {
  return `<tr style="text-align:center; color:#94a3b8;"><td colspan="${colspan}">${texto}</td></tr>`;
}

/** Atajo para document.querySelector */
export function qs(selector, scope = document) {
  return scope.querySelector(selector);
}

/** Atajo para document.querySelectorAll devuelto como array */
export function qsa(selector, scope = document) {
  return Array.from(scope.querySelectorAll(selector));
}

/** Debounce genérico, útil para inputs de búsqueda */
export function debounce(fn, delayMs = 300) {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delayMs);
  };
}
