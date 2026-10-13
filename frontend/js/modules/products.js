/**
 * js/modules/products.js
 * -----------------------------------------------------------------------
 * Controla la página products.html (Productos e Inventario).
 *
 * Implementación real: Etapa 7, una vez exista el endpoint /api/products
 * en el backend. Ya importa api.js y los helpers que va a necesitar, para
 * que conectar los datos reales sea solo llenar las funciones TODO.
 * -----------------------------------------------------------------------
 */

import { api } from '../services/api.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, formatDate } from '../utils/helpers.js';

async function load() {
  // TODO (Etapa 7):
  // const { data } = await api.get('/products');
  // render(data);
}

function render(items) {
  // TODO (Etapa 7): pintar `items` en el DOM de products.html
}

document.addEventListener('DOMContentLoaded', load);
