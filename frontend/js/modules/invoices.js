import { api, ApiError } from '../services/api.js';
import { requireSession } from '../services/auth.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, formatDate, filaVacia } from '../utils/helpers.js';

async function load() {
  await requireSession();
  const { data } = await api.get('/invoices');
  render(data || []);
}

function render(items) {
  const tbody = document.getElementById('invoicesTableBody');
  if (!tbody) return;
  if (!items.length) {
    tbody.innerHTML = filaVacia(5, 'No hay comprobantes registrados');
    return;
  }
  tbody.innerHTML = items.map((f) => `
    <tr>
      <td>${f.tipo || ''}</td>
      <td>${f.numero || ''}</td>
      <td>${formatDate(f.fecha)}</td>
      <td>${formatCurrency(f.monto)}</td>
      <td>${f.estado || ''}</td>
    </tr>
  `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar facturas', 'error');
  });
});
