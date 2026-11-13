import { api, ApiError } from '../../shared/js/api.js';
import { requireSession } from '../../shared/js/auth.js';
import { showToast } from '../../shared/js/toast.js';
import { formatCurrency, formatDate } from '../../shared/js/helpers.js';

async function load() {
  await requireSession();
  const [resumen, vencimientos] = await Promise.all([
    api.get('/reports/summary'),
    api.get('/products/expiring'),
  ]);
  render(resumen.data);
  renderAlertas(vencimientos.data);
}

function render(data) {
  const totalProducts = document.getElementById('totalProducts');
  const totalStock = document.getElementById('totalStock');
  const inventoryValue = document.getElementById('inventoryValue');
  if (totalProducts) totalProducts.textContent = data.total_productos ?? 0;
  if (totalStock) totalStock.textContent = data.total_stock ?? 0;
  if (inventoryValue) inventoryValue.textContent = formatCurrency(data.valor_inventario);
}

function renderAlertas(data) {
  const contenedor = document.getElementById('expiryAlerts');
  if (!contenedor) return;

  const resumen = data?.resumen || {};
  const productos = data?.productos || [];
  const etiqueta = document.getElementById('expirySummary');

  if (etiqueta) {
    etiqueta.textContent = productos.length
      ? `${resumen.vencidos || 0} vencidos · ${resumen.criticos || 0} críticos · ${resumen.proximos || 0} próximos`
      : 'Sin alertas';
    etiqueta.className = 'badge ' + (resumen.vencidos
      ? 'badge-danger'
      : productos.length ? 'badge-warning' : 'badge-info');
  }

  if (!productos.length) {
    contenedor.innerHTML = '<p style="color:#94a3b8; font-size:13px;">No hay productos próximos a vencer.</p>';
    return;
  }

  contenedor.innerHTML = `
    <table>
      <thead>
        <tr><th>Producto</th><th>Vence</th><th>Estado</th><th>Descuento</th><th>Precio final</th></tr>
      </thead>
      <tbody>
        ${productos.map((p) => `
          <tr>
            <td>${escapeHtml(p.nombre)}</td>
            <td>${formatDate(p.fecha_vencimiento)}</td>
            <td>${badgeEstado(p.nivel_vencimiento, p.dias_restantes)}</td>
            <td>${Number(p.descuento_porcentaje) > 0 ? `−${p.descuento_porcentaje}%` : '—'}</td>
            <td>${Number(p.descuento_porcentaje) > 0
              ? `<strong>${formatCurrency(p.precio_con_descuento)}</strong> <s style="color:#94a3b8;">${formatCurrency(p.precio)}</s>`
              : formatCurrency(p.precio)}</td>
          </tr>`).join('')}
      </tbody>
    </table>`;
}

function badgeEstado(nivel, dias) {
  if (nivel === 'vencido') {
    return `<span class="badge badge-danger">Vencido (${Math.abs(dias)} d)</span>`;
  }
  if (nivel === 'vence_hoy') {
    return '<span class="badge badge-danger">Vence hoy</span>';
  }
  if (nivel === 'critico') {
    return `<span class="badge badge-warning">Crítico (${dias} d)</span>`;
  }
  return `<span class="badge badge-info">Próximo (${dias} d)</span>`;
}

function escapeHtml(texto) {
  return String(texto ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', () => {
  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar el panel', 'error');
  });
});
