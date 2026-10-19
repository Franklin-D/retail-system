import { api, ApiError } from '../services/api.js';
import { requireSession } from '../services/auth.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, debounce } from '../utils/helpers.js';
import { openModal, closeModal } from '../../components/modal.js';

let productos = [];
let carrito = [];

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
  lista.innerHTML = productos.map((p) => `
    <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #e2e8f0;">
      <div>
        <strong>${escapeHtml(p.nombre)}</strong>
        <div style="color:#64748b; font-size:13px;">${escapeHtml(p.codigo_barra)} · Stock: ${p.stock} · ${formatCurrency(p.precio)}</div>
      </div>
      <button type="button" class="btn-primary btn-agregar" data-id="${p.id}" ${Number(p.stock) <= 0 ? 'disabled' : ''}>
        Agregar
      </button>
    </div>
  `).join('');
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
      <td>${formatCurrency(item.precio)}</td>
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
    carrito.push({
      producto_id: Number(producto.id),
      nombre: producto.nombre,
      precio: Number(producto.precio),
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

  document.getElementById('searchProduct')?.addEventListener('input', debounce((event) => {
    cargarProductos(event.target.value.trim()).catch((err) => showToast(err.message, 'error'));
  }, 300));

  document.getElementById('scanBarcodeBtn')?.addEventListener('click', () => {
    const input = document.getElementById('searchProduct');
    input?.focus();
    showToast('Escribe o escanea el código de barras en el buscador');
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
