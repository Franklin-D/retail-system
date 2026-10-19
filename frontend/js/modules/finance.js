import { api, ApiError } from '../services/api.js';
import { requireSession } from '../services/auth.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, formatDate, filaVacia } from '../utils/helpers.js';

async function load() {
  await requireSession();
  const [ingresos, egresos] = await Promise.all([
    api.get('/income'),
    api.get('/expenses'),
  ]);
  const items = [...(ingresos.data || []), ...(egresos.data || [])]
    .sort((a, b) => String(b.fecha || '').localeCompare(String(a.fecha || '')));
  render(items);
}

function render(items) {
  const tbody = document.getElementById('financeTableBody');
  if (!tbody) return;
  if (!items.length) {
    tbody.innerHTML = filaVacia(4, 'No hay movimientos financieros');
    return;
  }
  tbody.innerHTML = items.map((m) => `
    <tr>
      <td>${formatDate(m.fecha)}</td>
      <td>${m.descripcion || ''}</td>
      <td>${m.tipo || ''}</td>
      <td>${formatCurrency(m.monto)}</td>
    </tr>
  `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar finanzas', 'error');
  });
});
