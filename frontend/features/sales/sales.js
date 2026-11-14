import { api, ApiError } from '../../shared/js/api.js';
import { requireSession } from '../../shared/js/auth.js';
import { showToast } from '../../shared/js/toast.js';
import { formatCurrency } from '../../shared/js/helpers.js';
import { openModal, closeModal } from '../../shared/js/modal.js';

let productos = [];
let carrito = [];
let searchTimer = null;

async function load() {
  await requireSession();
  bindEvents();
  await cargarProductos();
}

async function cargarProductos(search = '') {
  const query = search ? `?search=${encodeURIComponent(search)}` : '';
  const { data } = await api.get(`/products${query}`);
  productos = data || [];
  renderProductos();
}

function renderProductos() {
  const lista = document.getElementById('productsList');
  if (!lista) return;
  if (!productos.length) {
    lista.innerHTML = '<div class="skeleton">No hay productos disponibles</div>';
    return;
  }
  lista.innerHTML = productos.map((p) => {
    const pct = Number(p.descuento_porcentaje) || 0;
    const precioHtml = pct > 0
      ? `<strong>${formatCurrency(p.precio_con_descuento)}</strong> <s style="color:#94a3b8;">${formatCurrency(p.precio)}</s> <span class="badge badge-warning">−${pct}%</span>`
      : formatCurrency(p.precio);
    const vencidoHtml = p.nivel_vencimiento === 'vencido'
      ? ' <span class="badge badge-danger">Vencido</span>' : '';
    return `
      <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #e2e8f0;">
        <div>
          <strong>${escapeHtml(p.nombre)}</strong>${vencidoHtml}
          <div style="color:#64748b; font-size:13px;">${escapeHtml(p.codigo_barra)} · Stock: ${p.stock} · ${precioHtml}</div>
        </div>
        <button type="button" class="btn-primary btn-agregar" data-id="${p.id}" ${Number(p.stock) <= 0 ? 'disabled' : ''}>
          Agregar
        </button>
      </div>`;
  }).join('');
}

function renderCarrito() {
  const tbody = document.getElementById('cartTable');
  const totalEl = document.getElementById('cartTotal');
  if (!tbody) return;

  if (!carrito.length) {
    tbody.innerHTML = '<tr style="text-align: center; color: #94a3b8;"><td colspan="5">El carrito está vacío</td></tr>';
    if (totalEl) totalEl.textContent = '0.00';
    return;
  }

  tbody.innerHTML = carrito.map((item) => `
    <tr data-id="${item.producto_id}">
      <td>${escapeHtml(item.nombre)}</td>
      <td>
        <input type="number" min="1" max="${item.stock}" value="${item.cantidad}" class="cantidad-item" style="width:80px;">
      </td>
      <td>${Number(item.descuento) > 0
        ? `<strong>${formatCurrency(item.precio)}</strong><div style="font-size:12px;color:#94a3b8;"><s>${formatCurrency(item.precio_base)}</s> −${item.descuento}%</div>`
        : formatCurrency(item.precio)}</td>
      <td>${formatCurrency(item.precio * item.cantidad)}</td>
      <td><button type="button" class="btn-danger btn-quitar">Quitar</button></td>
    </tr>
  `).join('');

  const total = carrito.reduce((acc, item) => acc + item.precio * item.cantidad, 0);
  if (totalEl) totalEl.textContent = total.toFixed(2);
}

function agregarAlCarrito(id) {
  const producto = productos.find((p) => Number(p.id) === Number(id));
  if (!producto) return;
  if (Number(producto.stock) <= 0) {
    showToast('Sin stock disponible', 'error');
    return;
  }
  const existente = carrito.find((item) => Number(item.producto_id) === Number(id));
  if (existente) {
    if (existente.cantidad + 1 > Number(producto.stock)) {
      showToast('No hay más stock para este producto', 'error');
      return;
    }
    existente.cantidad += 1;
  } else {
    const descuento = Number(producto.descuento_porcentaje) || 0;
    carrito.push({
      producto_id: Number(producto.id),
      nombre: producto.nombre,
      precio: descuento > 0 ? Number(producto.precio_con_descuento) : Number(producto.precio),
      precio_base: Number(producto.precio),
      descuento,
      stock: Number(producto.stock),
      cantidad: 1,
    });
  }
  renderCarrito();
}

function bindEvents() {
  document.getElementById('productsList')?.addEventListener('click', (event) => {
    const btn = event.target.closest('.btn-agregar');
    if (btn) agregarAlCarrito(btn.dataset.id);
  });

  document.getElementById('cartTable')?.addEventListener('click', (event) => {
    const btn = event.target.closest('.btn-quitar');
    if (!btn) return;
    const id = Number(btn.closest('tr').dataset.id);
    carrito = carrito.filter((item) => item.producto_id !== id);
    renderCarrito();
  });

  document.getElementById('cartTable')?.addEventListener('change', (event) => {
    if (!event.target.classList.contains('cantidad-item')) return;
    const id = Number(event.target.closest('tr').dataset.id);
    const item = carrito.find((c) => c.producto_id === id);
    if (!item) return;
    let cantidad = Number(event.target.value);
    if (cantidad < 1) cantidad = 1;
    if (cantidad > item.stock) {
      cantidad = item.stock;
      showToast('La cantidad supera el stock', 'error');
    }
    item.cantidad = cantidad;
    renderCarrito();
  });

  const searchInput = document.getElementById('searchProduct');
  searchInput?.addEventListener('input', (event) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      cargarProductos(event.target.value.trim()).catch((err) => showToast(err.message, 'error'));
    }, 300);
  });
  searchInput?.addEventListener('keydown', async (event) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    clearTimeout(searchTimer);
    const codigo = searchInput.value.trim();
    if (!codigo) return;

    try {
      const { data } = await api.get(`/products?search=${encodeURIComponent(codigo)}`);
      const exacto = (data || []).find((producto) => String(producto.codigo_barra).toLowerCase() === codigo.toLowerCase());
      if (!exacto) {
        productos = data || [];
        renderProductos();
        showToast('No se encontró un código de barras exacto; elige un resultado de búsqueda', 'error');
        return;
      }
      productos = [exacto];
      agregarAlCarrito(exacto.id);
      searchInput.value = '';
      await cargarProductos();
      searchInput.focus();
    } catch (err) {
      showToast(err.message || 'No se pudo buscar el código', 'error');
    }
  });

  document.getElementById('scanBarcodeBtn')?.addEventListener('click', () => {
    const input = document.getElementById('searchProduct');
    input?.focus();
    showToast('Escanea el código y presiona Enter para agregar el producto');
  });

  document.getElementById('checkoutBtn')?.addEventListener('click', () => {
    if (!carrito.length) {
      showToast('El carrito está vacío', 'error');
      return;
    }
    openModal('paymentModal');
  });

  document.getElementById('confirmPaymentBtn')?.addEventListener('click', confirmarPago);
}

async function confirmarPago() {
  const metodo = document.getElementById('paymentMethod')?.value || 'Efectivo';
  const boton = document.getElementById('confirmPaymentBtn');
  boton.disabled = true;
  try {
    const { data } = await api.post('/sales/checkout', {
      items: carrito.map((item) => ({
        producto_id: item.producto_id,
        cantidad: item.cantidad,
      })),
      metodo_pago: metodo,
    });
    const numero = data?.factura?.numero ? ` Factura ${data.factura.numero}.` : '';
    showToast(`Venta registrada.${numero}`);
    carrito = [];
    renderCarrito();
    closeModal('paymentModal');
    await cargarProductos(document.getElementById('searchProduct')?.value.trim() || '');
  } catch (err) {
    showToast(err.message || 'No se pudo registrar la venta', 'error');
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
  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar ventas', 'error');
  });
});
