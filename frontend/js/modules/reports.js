import { api, ApiError } from '../services/api.js';
import { requireSession } from '../services/auth.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, formatDate, filaVacia } from '../utils/helpers.js';

async function load() {
  await requireSession();
  const { data } = await api.get('/reports');
  render(data || []);
  document.getElementById('reportsTableBody')?.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-reporte]');
    if (btn) verReporte(btn.dataset.reporte);
  });
}

function render(items) {
  const tbody = document.getElementById('reportsTableBody');
  if (!tbody) return;
  if (!items.length) {
    tbody.innerHTML = filaVacia(3, 'No hay reportes disponibles');
    return;
  }
  tbody.innerHTML = items.map((r) => `
    <tr>
      <td>${r.nombre}</td>
      <td>${r.descripcion}</td>
      <td><button type="button" class="btn-primary" data-reporte="${r.clave}">Ver</button></td>
    </tr>
  `).join('');
}

async function verReporte(clave) {
  const destino = document.getElementById('reportResult');
  if (destino) destino.innerHTML = '<p style="color:#94a3b8;">Cargando...</p>';
  try {
    const { data } = await api.get(`/reports/${clave}`);
    if (!destino) return;
    if (clave === 'summary') {
      destino.innerHTML = renderResumen(data);
    } else if (clave === 'inventory') {
      destino.innerHTML = renderInventario(data);
    } else if (clave === 'profits') {
      destino.innerHTML = renderGanancias(data);
    } else {
      destino.innerHTML = `<pre>${escapeHtml(JSON.stringify(data, null, 2))}</pre>`;
    }
  } catch (err) {
    showToast(err.message || 'No se pudo cargar el reporte', 'error');
  }
}

function renderResumen(data) {
  return `
    <div class="grid-3">
      <div class="card"><div style="color:#94a3b8;">Productos</div><strong>${data.total_productos}</strong></div>
      <div class="card"><div style="color:#94a3b8;">Stock</div><strong>${data.total_stock}</strong></div>
      <div class="card"><div style="color:#94a3b8;">Valor inventario</div><strong>${formatCurrency(data.valor_inventario)}</strong></div>
      <div class="card"><div style="color:#94a3b8;">Ingresos</div><strong>${formatCurrency(data.total_ingresos)}</strong></div>
      <div class="card"><div style="color:#94a3b8;">Egresos</div><strong>${formatCurrency(data.total_egresos)}</strong></div>
      <div class="card"><div style="color:#94a3b8;">Ganancia</div><strong>${formatCurrency(data.ganancia)}</strong></div>
    </div>`;
}

function renderInventario(items) {
  const filas = (items || []).map((p) => `
    <tr>
      <td>${escapeHtml(p.nombre)}</td>
      <td>${p.stock}${p.alerta_stock ? ' ⚠' : ''}</td>
      <td>${formatCurrency(p.precio)}</td>
      <td>${formatDate(p.fecha_vencimiento)}${p.alerta_vencimiento ? ' ⚠' : ''}</td>
    </tr>`).join('');
  return `<table><thead><tr><th>Producto</th><th>Stock</th><th>Precio</th><th>Vencimiento</th></tr></thead><tbody>${filas || filaVacia(4, 'Sin datos')}</tbody></table>`;
}

function renderGanancias(data) {
  const ventas = (data.ventas || []).map((v) => `
    <tr>
      <td>${formatDate(v.fecha)}</td>
      <td>${escapeHtml(v.cliente || 'Mostrador')}</td>
      <td>${escapeHtml(v.metodo_pago)}</td>
      <td>${formatCurrency(v.total)}</td>
    </tr>`).join('');
  return `${renderResumen(data)}
    <table style="margin-top:16px;">
      <thead><tr><th>Fecha</th><th>Cliente</th><th>Pago</th><th>Total</th></tr></thead>
      <tbody>${ventas || filaVacia(4, 'Sin ventas')}</tbody>
    </table>`;
}

function escapeHtml(texto) {
  return String(texto ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

document.addEventListener('DOMContentLoaded', () => {
  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar reportes', 'error');
  });
});
