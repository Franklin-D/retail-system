import { api, ApiError } from '../../shared/js/api.js';
import { requireSession } from '../../shared/js/auth.js';
import { showToast } from '../../shared/js/toast.js';
import { formatCurrency, formatDate } from '../../shared/js/helpers.js';

async function load() {
  const usuario = await requireSession();
  const [resumen, vencimientos] = await Promise.all([
    api.get('/reports/summary'),
    api.get('/expiry/lots'),
  ]);
  render(resumen.data);
  renderAlertas(vencimientos.data, ['administrador', 'repositor'].includes(String(usuario.rol || '').toLowerCase()));
}

function render(data) {
  const totalProducts = document.getElementById('totalProducts');
  const totalStock = document.getElementById('totalStock');
  const inventoryValue = document.getElementById('inventoryValue');
  if (totalProducts) totalProducts.textContent = data.total_productos ?? 0;
  if (totalStock) totalStock.textContent = data.total_stock ?? 0;
  if (inventoryValue) inventoryValue.textContent = formatCurrency(data.valor_inventario);
}

function renderAlertas(data, puedeGestionar = false) {
  const contenedor = document.getElementById('expiryAlerts');
  if (!contenedor) return;

  const resumen = data?.resumen || {};
  const lotes = data?.lotes || [];
  const etiqueta = document.getElementById('expirySummary');

  if (etiqueta) {
    etiqueta.textContent = lotes.length
      ? `${resumen.vencidos || 0} vencidos · ${resumen.criticos || 0} críticos · ${resumen.proximos || 0} próximos`
      : 'Sin alertas';
    etiqueta.className = 'badge ' + (resumen.vencidos
      ? 'badge-danger'
      : lotes.length ? 'badge-warning' : 'badge-info');
  }

  if (!lotes.length) {
    contenedor.innerHTML = '<p style="color:#94a3b8; font-size:13px;">No hay lotes próximos a vencer.</p>';
    return;
  }

  contenedor.innerHTML = `
    <table>
      <thead>
        <tr><th>Producto</th><th>Lote</th><th>Stock</th><th>Vence</th><th>Alerta</th><th>Descuento actual</th><th>Precio con descuento</th><th>Acción</th></tr>
      </thead>
      <tbody>
        ${lotes.map((lote) => {
          const actual = Number(lote.descuento_porcentaje) || 0;
          const sugerido = Number(lote.descuento_sugerido) || 0;
          const precioDescuento = Number(lote.precio_con_descuento) || 0;
          const puedeAplicar = puedeGestionar && sugerido > 0 && actual !== sugerido;
          return `
          <tr>
            <td>${escapeHtml(lote.nombre)}</td>
            <td>${escapeHtml(lote.codigo_lote)}</td>
            <td>${Number(lote.stock)}</td>
            <td>${formatDate(lote.fecha_vencimiento)}</td>
            <td>${badgeEstado(lote.nivel_vencimiento, lote.dias_restantes, lote.color_alerta)}</td>
            <td>${actual > 0 ? `−${actual}%` : '—'}${sugerido > 0 ? `<div style="font-size:12px;color:#94a3b8;">Sugerido: −${sugerido}%</div>` : ''}</td>
            <td>${actual > 0
              ? `<strong>${formatCurrency(precioDescuento)}</strong> <s style="color:#94a3b8;">${formatCurrency(lote.precio)}</s>`
              : formatCurrency(lote.precio)}</td>
            <td>${puedeAplicar
              ? `<button type="button" class="btn-secondary" data-aplicar-descuento="${Number(lote.lote_id)}">Aplicar −${sugerido}%</button>`
              : actual === sugerido && sugerido > 0 ? '<span class="badge badge-success">Aplicado</span>'
              : lote.nivel_vencimiento === 'vencido' ? '<span class="badge badge-danger">Retirar</span>'
              : puedeGestionar ? '—' : '<span class="badge badge-info">Solo lectura</span>'}</td>
          </tr>`;
        }).join('')}
      </tbody>
    </table>`;

  if (puedeGestionar) {
    contenedor.querySelectorAll('[data-aplicar-descuento]').forEach((boton) => {
      boton.addEventListener('click', async () => {
        boton.disabled = true;
        try {
          await api.post(`/expiry/lots/${Number(boton.dataset.aplicarDescuento)}/discount`, {});
          showToast('Descuento automático aplicado al lote');
          const { data: actualizados } = await api.get('/expiry/lots');
          renderAlertas(actualizados, puedeGestionar);
        } catch (err) {
          showToast(err.message || 'No se pudo aplicar el descuento', 'error');
          boton.disabled = false;
        }
      });
    });
  }
}

function badgeEstado(nivel, dias, color) {
  if (nivel === 'vencido') {
    return `<span class="badge badge-danger">Vencido (${Math.abs(dias)} d)</span>`;
  }
  if (nivel === 'vence_hoy') {
    return '<span class="badge badge-danger">Vence hoy</span>';
  }
  const clase = color === 'rojo' ? 'badge-danger' : 'badge-warning';
  const etiqueta = nivel === 'critico' ? 'Crítico' : 'Próximo';
  return `<span class="badge ${clase}">${etiqueta} (${dias} d)</span>`;
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
