/**
 * js/modules/finance.js
 * -----------------------------------------------------------------------
 * Controla la página finance.html (Finanzas).
 *
 * Implementación real: Etapa 9, una vez exista el endpoint /api/income
 * en el backend. Ya importa api.js y los helpers que va a necesitar, para
 * que conectar los datos reales sea solo llenar las funciones TODO.
 * -----------------------------------------------------------------------
 */

import { api } from '../services/api.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, formatDate } from '../utils/helpers.js';

async function load() {
  // TODO (Etapa 9):
  // const { data } = await api.get('/income');
  // render(data);
}

function render(items) {
  // TODO (Etapa 9): pintar `items` en el DOM de finance.html
}

document.addEventListener('DOMContentLoaded', load);
