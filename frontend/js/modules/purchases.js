import { api, ApiError } from '../services/api.js';
import { requireSession } from '../services/auth.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, formatDate, filaVacia } from '../utils/helpers.js';

async function load() {
  await requireSession();
  const { data } = await api.get('/purchases');
  render(data || []);
}

function render(items) {
  const tbody = document.getElementById('purchasesTableBody');
  if (!tbody) return;
  if (!items.length) {
    tbody.innerHTML = filaVacia(4, 'No hay compras registradas');
    return;
  }
  tbody.innerHTML = items.map((c) => `
    <tr>
      <td>${formatDate(c.fecha)}</td>
      <td>${c.proveedor || ''}</td>
      <td>${formatCurrency(c.total)}</td>
      <td>—</td>
    </tr>
  `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar compras', 'error');
  });
});
