import { api, urlImagen, ApiError } from '../services/api.js';
import { requireSession } from '../services/auth.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, formatDate, filaVacia, debounce } from '../utils/helpers.js';
import { openModal, closeModal } from '../../components/modal.js';

let categorias = [];
let proveedores = [];
let productoEditando = null;

async function load() {
  await requireSession();
  await cargarCatalogos();
  bindEvents();
  await cargarProductos();
}

async function cargarCatalogos() {
  const [cat, prov] = await Promise.all([
    api.get('/categories'),
    api.get('/suppliers'),
  ]);
  categorias = cat.data || [];
  proveedores = prov.data || [];
  llenarSelect('categoria_id', categorias);
  llenarSelect('proveedor_id', proveedores);
}

function llenarSelect(name, items) {
  const select = document.querySelector(`#addProductForm [name="${name}"]`);
  if (!select) return;
  select.innerHTML = '<option value="">Seleccione...</option>' +
    items.map((item) => `<option value="${item.id}">${item.nombre}</option>`).join('');
}

async function cargarProductos(search = '') {
  const query = search ? `?search=${encodeURIComponent(search)}` : '';
  const { data } = await api.get(`/products${query}`);
  render(data || []);
}

function render(items) {
  const tbody = document.getElementById('productsTableBody');
  if (!tbody) return;

  if (!items.length) {
    tbody.innerHTML = filaVacia(8, 'No hay productos para mostrar');
    return;
  }

  tbody.innerHTML = items.map((p) => {
    const src = urlImagen(p.imagen);
    const img = src
      ? `<img src="${src}" alt="${escapeHtml(p.nombre)}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">`
      : '—';
    const alerta = Number(p.stock) <= Number(p.stock_minimo)
      ? ' style="color:#dc2626;font-weight:bold;"'
      : '';
    return `<tr data-id="${p.id}">
      <td>${img}</td>
      <td>${escapeHtml(p.nombre)}</td>
      <td>${escapeHtml(p.codigo_barra)}</td>
      <td>${escapeHtml(p.categoria || '')}</td>
      <td${alerta}>${p.stock}</td>
      <td>${formatCurrency(p.precio)}</td>
      <td>${formatDate(p.fecha_vencimiento)}</td>
      <td>
        <button type="button" class="btn-secondary btn-editar">Editar</button>
        <button type="button" class="btn-danger btn-eliminar">Eliminar</button>
      </td>
    </tr>`;
  }).join('');
}

function bindEvents() {
  document.getElementById('addProductBtn')?.addEventListener('click', () => abrirFormulario());

  const search = document.getElementById('searchProduct');
  search?.addEventListener('input', debounce((event) => {
    cargarProductos(event.target.value.trim()).catch((err) => showToast(err.message, 'error'));
  }, 300));

  document.getElementById('productsTableBody')?.addEventListener('click', async (event) => {
    const fila = event.target.closest('tr[data-id]');
    if (!fila) return;
    const id = Number(fila.dataset.id);
    if (event.target.classList.contains('btn-editar')) {
      await abrirEdicion(id);
    } else if (event.target.classList.contains('btn-eliminar')) {
      await eliminarProducto(id);
    }
  });

  document.getElementById('addProductForm')?.addEventListener('submit', guardarProducto);
}

function abrirFormulario(producto = null) {
  productoEditando = producto;
  const form = document.getElementById('addProductForm');
  form?.reset();
  const titulo = document.querySelector('#addProductModal .modal-title');
  if (titulo) titulo.textContent = producto ? 'Editar Producto' : 'Agregar Nuevo Producto';
  if (producto && form) {
    form.nombre.value = producto.nombre || '';
    form.codigo_barra.value = producto.codigo_barra || '';
    form.descripcion.value = producto.descripcion || '';
    form.categoria_id.value = producto.categoria_id || '';
    form.marca.value = producto.marca || '';
    form.proveedor_id.value = producto.proveedor_id || '';
    form.pasillo.value = producto.pasillo || '';
    form.stock.value = producto.stock ?? 0;
    form.precio.value = producto.precio ?? 0;
    form.fecha_vencimiento.value = producto.fecha_vencimiento
      ? String(producto.fecha_vencimiento).slice(0, 10) : '';
  }
  openModal('addProductModal');
}

async function abrirEdicion(id) {
  try {
    const { data } = await api.get(`/products/${id}`);
    abrirFormulario(data);
  } catch (err) {
    showToast(err.message || 'No se pudo cargar el producto', 'error');
  }
}

async function guardarProducto(event) {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  const boton = form.querySelector('button[type="submit"]');
  boton.disabled = true;
  try {
    if (productoEditando) {
      await api.post(`/products/${productoEditando.id}`, formData);
      showToast('Producto actualizado');
    } else {
      await api.post('/products', formData);
      showToast('Producto creado');
    }
    closeModal('addProductModal');
    form.reset();
    productoEditando = null;
    await cargarProductos(document.getElementById('searchProduct')?.value.trim() || '');
  } catch (err) {
    showToast(err.message || 'No se pudo guardar el producto', 'error');
  } finally {
    boton.disabled = false;
  }
}

async function eliminarProducto(id) {
  if (!confirm('¿Eliminar este producto?')) return;
  try {
    await api.del(`/products/${id}`);
    showToast('Producto eliminado');
    await cargarProductos(document.getElementById('searchProduct')?.value.trim() || '');
  } catch (err) {
    showToast(err.message || 'No se pudo eliminar', 'error');
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
    showToast(err.message || 'Error al cargar productos', 'error');
  });
});
