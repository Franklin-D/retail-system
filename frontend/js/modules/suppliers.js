import { api, ApiError } from '../services/api.js';
import { requireSession } from '../services/auth.js';
import { showToast } from '../../components/toast.js';
import { filaVacia } from '../utils/helpers.js';

async function load() {
  await requireSession();
  const { data } = await api.get('/suppliers');
  render(data || []);
}

function render(items) {
  const tbody = document.getElementById('suppliersTableBody');
  if (!tbody) return;
  if (!items.length) {
    tbody.innerHTML = filaVacia(3, 'No hay proveedores registrados');
    return;
  }
  tbody.innerHTML = items.map((s) => `
    <tr>
      <td>${s.nombre || ''}</td>
      <td>${s.contacto || '—'}</td>
      <td>—</td>
    </tr>
  `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar proveedores', 'error');
  });
});
