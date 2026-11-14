import { api, ApiError } from '../../shared/js/api.js';
import { requireSession } from '../../shared/js/auth.js';
import { showToast } from '../../shared/js/toast.js';
import { formatCurrency, formatDate, filaVacia } from '../../shared/js/helpers.js';
import { openModal, closeModal } from '../../shared/js/modal.js';

let proveedores = [];
let productos = [];

async function load() {
  await requireSession();
  await cargarCatalogos();
  bindEvents();
  agregarFilaProducto();
  await cargarCompras();
}

async function cargarCatalogos() {
  const [prov, prod] = await Promise.all([
    api.get('/suppliers'),
    api.get('/products'),
  ]);
  proveedores = prov.data || [];
  productos = prod.data || [];

  const select = document.querySelector('#purchaseForm [name="proveedor_id"]');
  if (select) {
    select.innerHTML = '<option value="">Seleccione...</option>' +
      proveedores.map((p) => `<option value="${p.id}">${escapeHtml(p.nombre)}</option>`).join('');
  }
}

async function cargarCompras() {
  const { data } = await api.get('/purchases');
  render(data || []);
}

function render(items) {
  const tbody = document.getElementById('purchasesTableBody');
  if (!tbody) return;
  if (!items.length) {
    tbody.innerHTML = filaVacia(5, 'No hay compras registradas');
    return;
  }

  tbody.innerHTML = items.map((c) => `
    <tr>
      <td>${formatDate(c.fecha)}</td>
      <td>${escapeHtml(c.proveedor || '')}</td>
      <td>${c.total_items ?? '—'}</td>
      <td>${formatCurrency(c.total)}</td>
      <td>
        <button type="button" class="btn-secondary btn-detalle" data-id="${c.id}">Ver detalle</button>
      </td>
    </tr>
  `).join('');
}

function agregarFilaProducto() {
  const contenedor = document.getElementById('purchaseItems');
  if (!contenedor) return;

  const fila = document.createElement('div');
  fila.className = 'purchase-item';
  fila.style.cssText = 'display:grid; grid-template-columns:2fr 90px 120px 90px 36px; gap:10px; align-items:center; margin-bottom:10px;';
  fila.innerHTML = `
    <select class="item-producto" required>
      <option value="">Producto...</option>
      ${productos.map((p) => `<option value="${p.id}" data-precio="${p.precio}">${escapeHtml(p.nombre)}</option>`).join('')}
    </select>
    <input type="number" class="item-cantidad" min="1" step="1" value="1" placeholder="Cant." required>
    <input type="number" class="item-precio" min="0" step="0.01" value="0.00" placeholder="Precio" required>
    <span class="item-subtotal" style="font-weight:600; text-align:right;">S/ 0.00</span>
    <button type="button" class="btn-danger btn-quitar-fila" title="Quitar">&times;</button>
  `;
  contenedor.appendChild(fila);

  const select = fila.querySelector('.item-producto');
  select.addEventListener('change', () => {
    const opcion = select.selectedOptions[0];
    const precio = opcion?.dataset.precio;
    if (precio !== undefined) {
      fila.querySelector('.item-precio').value = Number(precio).toFixed(2);
    }
    recalcularTotal();
  });

  fila.querySelector('.item-cantidad').addEventListener('input', recalcularTotal);
  fila.querySelector('.item-precio').addEventListener('input', recalcularTotal);
  fila.querySelector('.btn-quitar-fila').addEventListener('click', () => {
    const filas = contenedor.querySelectorAll('.purchase-item');
    if (filas.length <= 1) {
      showToast('La compra debe tener al menos un producto', 'error');
      return;
    }
    fila.remove();
    recalcularTotal();
  });

  recalcularTotal();
}

function recalcularTotal() {
  let total = 0;
  document.querySelectorAll('#purchaseItems .purchase-item').forEach((fila) => {
    const cantidad = Number(fila.querySelector('.item-cantidad').value) || 0;
    const precio = Number(fila.querySelector('.item-precio').value) || 0;
    const subtotal = cantidad * precio;
    total += subtotal;
    const celda = fila.querySelector('.item-subtotal');
    if (celda) celda.textContent = formatCurrency(subtotal);
  });
  const totalEl = document.getElementById('purchaseTotal');
  if (totalEl) totalEl.textContent = formatCurrency(total);
}

async function guardarCompra(event) {
  event.preventDefault();
  const form = event.target;
  const boton = form.querySelector('button[type="submit"]');
  const proveedorId = Number(form.proveedor_id.value);

  const items = Array.from(document.querySelectorAll('#purchaseItems .purchase-item')).map((fila) => ({
    producto_id: Number(fila.querySelector('.item-producto').value),
    cantidad: Number(fila.querySelector('.item-cantidad').value),
    precio_unitario: Number(fila.querySelector('.item-precio').value),
  }));

  if (!proveedorId) {
    showToast('Seleccione un proveedor', 'error');
    return;
  }
  if (!items.length || items.some((i) => !i.producto_id || i.cantidad <= 0)) {
    showToast('Complete todos los productos de la compra', 'error');
    return;
  }

  boton.disabled = true;
  try {
    await api.post('/purchases', {
      proveedor_id: proveedorId,
      comprobante: form.comprobante.value.trim(),
      items,
    });
    showToast('Compra registrada y stock actualizado');
    closeModal('addPurchaseModal');
    form.reset();
    document.getElementById('purchaseItems').innerHTML = '';
    agregarFilaProducto();
    await cargarCompras();
  } catch (err) {
    showToast(err.message || 'No se pudo registrar la compra', 'error');
  } finally {
    boton.disabled = false;
  }
}

async function verDetalle(id) {
  try {
    const { data } = await api.get(`/purchases/${id}`);
    const items = data.items || [];
    const filas = items.map((item) => `
      <tr>
        <td>${escapeHtml(item.producto || '')}</td>
        <td>${item.cantidad}</td>
        <td>${formatCurrency(item.precio_unitario)}</td>
        <td>${formatCurrency(item.subtotal)}</td>
      </tr>
    `).join('');

    document.getElementById('purchaseDetailContent').innerHTML = `
      <p><strong>Proveedor:</strong> ${escapeHtml(data.proveedor || '')}</p>
      <p><strong>Fecha:</strong> ${formatDate(data.fecha)}</p>
      <p><strong>Registrado por:</strong> ${escapeHtml(data.usuario || '')}</p>
      <table>
        <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead>
        <tbody>${filas || filaVacia(4, 'Sin productos')}</tbody>
      </table>
      <div style="text-align:right; font-size:18px; font-weight:bold; margin-top:15px;">
        Total: ${formatCurrency(data.total)}
      </div>
    `;
    openModal('purchaseDetailModal');
  } catch (err) {
    showToast(err.message || 'No se pudo cargar el detalle', 'error');
  }
}

function bindEvents() {
  document.getElementById('addPurchaseBtn')?.addEventListener('click', () => openModal('addPurchaseModal'));
  document.getElementById('addItemBtn')?.addEventListener('click', agregarFilaProducto);
  document.getElementById('purchaseForm')?.addEventListener('submit', guardarCompra);
  document.getElementById('purchasesTableBody')?.addEventListener('click', (event) => {
    const boton = event.target.closest('.btn-detalle');
    if (boton) verDetalle(Number(boton.dataset.id));
  });
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
    showToast(err.message || 'Error al cargar compras', 'error');
  });
});
