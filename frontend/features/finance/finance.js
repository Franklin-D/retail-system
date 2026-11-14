import { api, ApiError } from '../../shared/js/api.js';
import { requireSession } from '../../shared/js/auth.js';
import { showToast } from '../../shared/js/toast.js';
import { formatCurrency, formatDate, filaVacia } from '../../shared/js/helpers.js';
import { openModal, closeModal } from '../../shared/js/modal.js';

async function load() {
  await requireSession();
  const [ingresos, egresos] = await Promise.all([
    api.get('/income'),
    api.get('/expenses'),
  ]);
  const items = [...(ingresos.data || []), ...(egresos.data || [])]
    .sort((a, b) => String(b.fecha || '').localeCompare(String(a.fecha || '')));
  render(items);
  renderResumen(ingresos.data || [], egresos.data || []);
}

function renderResumen(ingresos, egresos) {
  const totalIngresos = ingresos.reduce((sum, i) => sum + Number(i.monto || 0), 0);
  const totalEgresos = egresos.reduce((sum, e) => sum + Number(e.monto || 0), 0);
  document.getElementById('totalIngresos').textContent = formatCurrency(totalIngresos);
  document.getElementById('totalEgresos').textContent = formatCurrency(totalEgresos);
  document.getElementById('saldoCaja').textContent = formatCurrency(totalIngresos - totalEgresos);
}

function render(items) {
  const tbody = document.getElementById('financeTableBody');
  if (!tbody) return;
  if (!items.length) {
    tbody.innerHTML = filaVacia(6, 'No hay movimientos financieros');
    return;
  }

  tbody.innerHTML = items.map((m) => {
    const esIngreso = m.tipo === 'ingreso';
    return `
      <tr>
        <td>${formatDate(m.fecha)}</td>
        <td>${escapeHtml(m.descripcion || '')}</td>
        <td>${escapeHtml(m.motivo || '—')}</td>
        <td><span class="badge ${esIngreso ? 'badge-info' : 'badge-danger'}">${esIngreso ? 'Ingreso' : 'Egreso'}</span></td>
        <td>${escapeHtml(m.comprobante || '—')}</td>
        <td style="color:${esIngreso ? '#15803d' : '#dc2626'}; font-weight:600;">${formatCurrency(m.monto)}</td>
      </tr>
    `;
  }).join('');
}

async function guardarIngreso(event) {
  event.preventDefault();
  const form = event.target;
  const boton = form.querySelector('button[type="submit"]');
  boton.disabled = true;
  try {
    await api.post('/income', {
      descripcion: form.descripcion.value.trim(),
      motivo: form.motivo.value.trim(),
      comprobante: form.comprobante.value.trim(),
      monto: Number(form.monto.value),
      metodo_pago: form.metodo_pago.value,
    });
    showToast('Ingreso de caja registrado');
    closeModal('incomeModal');
    form.reset();
    await load();
  } catch (err) {
    showToast(err.message || 'No se pudo registrar el ingreso', 'error');
  } finally {
    boton.disabled = false;
  }
}

async function guardarEgreso(event) {
  event.preventDefault();
  const form = event.target;
  const boton = form.querySelector('button[type="submit"]');
  boton.disabled = true;
  try {
    await api.post('/expenses', {
      descripcion: form.descripcion.value.trim(),
      motivo: form.motivo.value.trim(),
      comprobante: form.comprobante.value.trim(),
      monto: Number(form.monto.value),
    });
    showToast('Egreso de caja registrado');
    closeModal('expenseModal');
    form.reset();
    await load();
  } catch (err) {
    showToast(err.message || 'No se pudo registrar el egreso', 'error');
  } finally {
    boton.disabled = false;
  }
}

function escapeHtml(texto) {
  return String(texto ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('addIncomeBtn')?.addEventListener('click', () => openModal('incomeModal'));
  document.getElementById('addExpenseBtn')?.addEventListener('click', () => openModal('expenseModal'));
  document.getElementById('incomeForm')?.addEventListener('submit', guardarIngreso);
  document.getElementById('expenseForm')?.addEventListener('submit', guardarEgreso);

  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar finanzas', 'error');
  });
});
