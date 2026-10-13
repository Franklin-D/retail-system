/**
 * js/modules/reports.js
 * -----------------------------------------------------------------------
 * Controla la página reports.html (Reportes).
 *
 * Implementación real: Etapa 10, una vez exista el endpoint /api/reports
 * en el backend. Ya importa api.js y los helpers que va a necesitar, para
 * que conectar los datos reales sea solo llenar las funciones TODO.
 * -----------------------------------------------------------------------
 */

import { api } from '../services/api.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, formatDate } from '../utils/helpers.js';

async function load() {
  // TODO (Etapa 10):
  // const { data } = await api.get('/reports');
  // render(data);
}

function render(items) {
  // TODO (Etapa 10): pintar `items` en el DOM de reports.html
}

document.addEventListener('DOMContentLoaded', load);
