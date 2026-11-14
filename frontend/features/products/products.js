import { api, urlImagen, ApiError } from '../../shared/js/api.js';
import { requireSession } from '../../shared/js/auth.js';
import { showToast } from '../../shared/js/toast.js';
import { formatCurrency, formatDate, filaVacia, debounce } from '../../shared/js/helpers.js';
import { openModal, closeModal } from '../../shared/js/modal.js';

let categorias = [];
let proveedores = [];
let productosInventario = [];
let productoEditando = null;
let puedeGestionarLotes = false;

async function load() {
  const usuario = await requireSession();
  puedeGestionarLotes = ['administrador', 'repositor'].includes(String(usuario.rol || '').toLowerCase());
  if (!puedeGestionarLotes) {
    document.getElementById('lotFormCard')?.remove();
    document.getElementById('scrollToLotForm')?.remove();
  }
  await cargarCatalogos();
  bindEvents();
  await Promise.all([cargarProductos(), cargarAlertasLotes()]);
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
  if (!search) {
    productosInventario = data || [];
    llenarSelectLotes(productosInventario);
  }
  render(data || []);
}

function llenarSelectLotes(productos) {
  const select = document.getElementById('lotProduct');
  if (!select) return;
  select.innerHTML = '<option value="">Seleccione un producto...</option>' + productos
    .map((producto) => `<option value="${Number(producto.id)}">${escapeHtml(producto.nombre)} · stock ${Number(producto.stock)}</option>`)
    .join('');
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
    const pct = Number(p.descuento_porcentaje) || 0;
    const nivel = p.nivel_vencimiento;
    const estiloVencimiento = (nivel === 'vencido' || nivel === 'vence_hoy')
      ? 'color:#dc2626;font-weight:bold;'
      : nivel === 'critico' ? 'color:#d97706;font-weight:bold;'
      : nivel === 'proximo' ? 'color:#b45309;' : '';
    const precioHtml = pct > 0
      ? `<div><strong>${formatCurrency(p.precio_con_descuento)}</strong></div>
         <div style="font-size:12px;color:#94a3b8;"><s>${formatCurrency(p.precio)}</s> −${pct}%</div>`
      : formatCurrency(p.precio);
    const vencimientoHtml = `<span style="${estiloVencimiento}">${formatDate(p.fecha_vencimiento)}</span>
      ${pct > 0 ? `<span class="badge badge-warning">−${pct}%</span>` : ''}`;
    return `<tr data-id="${p.id}">
      <td>${img}</td>
      <td>${escapeHtml(p.nombre)}</td>
      <td>${escapeHtml(p.codigo_barra)}</td>
      <td>${escapeHtml(p.categoria || '')}</td>
      <td${alerta}>${p.stock}</td>
      <td>${precioHtml}</td>
      <td>${vencimientoHtml}</td>
      <td>
        <button type="button" class="btn-secondary btn-editar">Editar</button>
        <button type="button" class="btn-danger btn-eliminar">Eliminar</button>
      </td>
    </tr>`;
  }).join('');
}

function bindEvents() {
  document.getElementById('addProductBtn')?.addEventListener('click', () => abrirFormulario());
  document.getElementById('scrollToLotForm')?.addEventListener('click', () => {
    document.getElementById('lotFormCard')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  document.getElementById('lotForm')?.addEventListener('submit', registrarLote);

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

  document.getElementById('lotAlertsList')?.addEventListener('click', async (event) => {
    const boton = event.target.closest('[data-aplicar-descuento-lote]');
    if (!boton) return;
    boton.disabled = true;
    try {
      await api.post(`/expiry/lots/${Number(boton.dataset.aplicarDescuentoLote)}/discount`, {});
      showToast('Descuento automático aplicado al lote');
      await cargarAlertasLotes();
    } catch (err) {
      showToast(err.message || 'No se pudo aplicar el descuento', 'error');
      boton.disabled = false;
    }
  });

  document.getElementById('addProductForm')?.addEventListener('submit', guardarProducto);
}

async function registrarLote(event) {
  event.preventDefault();
  const form = event.target;
  const boton = form.querySelector('button[type="submit"]');
  const payload = Object.fromEntries(new FormData(form).entries());
  payload.producto_id = Number(payload.producto_id);
  payload.stock = Number(payload.stock);
  boton.disabled = true;
  try {
    await api.post('/expiry/lots', payload);
    showToast('Lote registrado');
    form.reset();
    await cargarProductos();
    await cargarAlertasLotes();
  } catch (err) {
    showToast(err.message || 'No se pudo registrar el lote', 'error');
  } finally {
    boton.disabled = false;
  }
}

async function cargarAlertasLotes() {
  const contenedor = document.getElementById('lotAlertsList');
  if (!contenedor) return;
  try {
    const { data } = await api.get('/expiry/lots');
    renderAlertasLotes(data);
  } catch (err) {
    contenedor.innerHTML = `<p style="color:#dc2626;">${escapeHtml(err.message || 'No se pudieron cargar las alertas')}</p>`;
  }
}

function renderAlertasLotes(data) {
  const contenedor = document.getElementById('lotAlertsList');
  const etiqueta = document.getElementById('lotAlertsSummary');
  if (!contenedor) return;
  const lotes = data?.lotes || [];
  const resumen = data?.resumen || {};
  if (etiqueta) {
    etiqueta.textContent = lotes.length
      ? `${resumen.vencidos || 0} vencidos · ${resumen.criticos || 0} críticos · ${resumen.proximos || 0} próximos`
      : 'Sin alertas';
    etiqueta.className = `badge ${resumen.vencidos ? 'badge-danger' : lotes.length ? 'badge-warning' : 'badge-info'}`;
  }
  if (!lotes.length) {
    contenedor.innerHTML = '<p style="color:#94a3b8;font-size:13px;">No hay lotes con vencimiento dentro de los próximos 30 días.</p>';
    return;
  }

  contenedor.innerHTML = `<table>
    <thead><tr><th>Producto</th><th>Lote</th><th>Stock</th><th>Vence</th><th>Estado</th><th>Descuento</th><th>Acción</th></tr></thead>
    <tbody>${lotes.map((lote) => {
      const actual = Number(lote.descuento_porcentaje) || 0;
      const sugerido = Number(lote.descuento_sugerido) || 0;
      const claseAlerta = lote.color_alerta === 'rojo' ? 'badge-danger' : 'badge-warning';
      const estado = lote.nivel_vencimiento === 'vencido'
        ? `Vencido (${Math.abs(Number(lote.dias_restantes))} d)`
        : lote.nivel_vencimiento === 'vence_hoy' ? 'Vence hoy'
        : `${lote.color_alerta === 'rojo' ? 'Crítico' : 'Próximo'} (${Number(lote.dias_restantes)} d)`;
      const accion = puedeGestionarLotes && sugerido > 0 && actual !== sugerido
        ? `<button type="button" class="btn-secondary" data-aplicar-descuento-lote="${Number(lote.lote_id)}">Aplicar −${sugerido}%</button>`
        : actual === sugerido && sugerido > 0 ? '<span class="badge badge-success">Aplicado</span>'
        : lote.nivel_vencimiento === 'vencido' ? '<span class="badge badge-danger">Retirar</span>'
        : puedeGestionarLotes ? '—' : '<span class="badge badge-info">Solo lectura</span>';
      return `<tr>
        <td>${escapeHtml(lote.nombre)}</td><td>${escapeHtml(lote.codigo_lote)}</td>
        <td>${Number(lote.stock)}</td><td>${formatDate(lote.fecha_vencimiento)}</td>
        <td><span class="badge ${claseAlerta}">${estado}</span></td>
        <td>${actual > 0 ? `−${actual}%` : '—'}${sugerido > 0 ? `<div style="font-size:12px;color:#94a3b8;">Sugerido: −${sugerido}%</div>` : ''}</td>
        <td>${accion}</td>
      </tr>`;
    }).join('')}</tbody>
  </table>`;
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
