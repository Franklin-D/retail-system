import { api, ApiError } from '../services/api.js';
import { requireSession } from '../services/auth.js';
import { showToast } from '../../components/toast.js';
import { filaVacia } from '../utils/helpers.js';

async function load() {
  await requireSession();
  const { data } = await api.get('/customers');
  render(data || []);
}

function render(items) {
  const tbody = document.getElementById('customersTableBody');
  if (!tbody) return;
  if (!items.length) {
    tbody.innerHTML = filaVacia(5, 'No hay clientes registrados');
    return;
  }
  tbody.innerHTML = items.map((c) => `
    <tr>
      <td>${c.nombre || ''}</td>
      <td>${c.documento || '—'}</td>
      <td>${c.email || '—'}</td>
      <td>${c.telefono || '—'}</td>
      <td>—</td>
    </tr>
  `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar clientes', 'error');
  });
});
