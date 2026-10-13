/**
 * js/modules/suppliers.js
 * -----------------------------------------------------------------------
 * Controla la página suppliers.html (Proveedores).
 *
 * Implementación real: Etapa 8, una vez exista el endpoint /api/suppliers
 * en el backend. Ya importa api.js y los helpers que va a necesitar, para
 * que conectar los datos reales sea solo llenar las funciones TODO.
 * -----------------------------------------------------------------------
 */

import { api } from '../services/api.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, formatDate } from '../utils/helpers.js';

async function load() {
  // TODO (Etapa 8):
  // const { data } = await api.get('/suppliers');
  // render(data);
}

function render(items) {
  // TODO (Etapa 8): pintar `items` en el DOM de suppliers.html
}

document.addEventListener('DOMContentLoaded', load);
