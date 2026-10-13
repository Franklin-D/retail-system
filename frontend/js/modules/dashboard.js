/**
 * js/modules/dashboard.js
 * -----------------------------------------------------------------------
 * Controla la página dashboard.html (Panel Principal).
 *
 * Implementación real: Etapa 10, una vez exista el endpoint /api/reports/summary
 * en el backend. Ya importa api.js y los helpers que va a necesitar, para
 * que conectar los datos reales sea solo llenar las funciones TODO.
 * -----------------------------------------------------------------------
 */

import { api } from '../services/api.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, formatDate } from '../utils/helpers.js';

async function load() {
  // TODO (Etapa 10):
  // const { data } = await api.get('/reports/summary');
  // render(data);
}

function render(items) {
  // TODO (Etapa 10): pintar `items` en el DOM de dashboard.html
}

document.addEventListener('DOMContentLoaded', load);
