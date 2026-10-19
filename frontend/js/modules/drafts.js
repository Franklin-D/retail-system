/**
 * js/modules/drafts.js
 * -----------------------------------------------------------------------
 * Pre-carga de productos (borradores). Los empleados adelantan datos básicos
 * y la foto; el producto queda con estado 'borrador' y NO aparece en
 * Productos ni en Ventas hasta que se "da de alta".
 *
 * Un solo formulario sirve para 3 modos:
 *   'crear'    -> POST /products/drafts                 (nuevo borrador)
 *   'editar'   -> POST /products/drafts/{id}            (corregir borrador)
 *   'publicar' -> POST /products/drafts/{id}/publish    (dar de alta)
 * En 'publicar' se abre el MISMO registro con los datos ya cargados, por lo
 * que al usuario le parece un alta nueva pero solo completa lo que falta.
 * -----------------------------------------------------------------------
 */
import { api, urlImagen, ApiError } from '../services/api.js';
import { requireSession } from '../services/auth.js';
import { showToast } from '../../components/toast.js';
import { formatCurrency, filaVacia, debounce } from '../utils/helpers.js';
import { openModal, closeModal } from '../../components/modal.js';

const MAX_IMAGEN_BYTES = 5 * 1024 * 1024;
const MODAL_ID = 'draftModal';

let modo = 'crear';
let borradorActual = null;
let urlPreviewLocal = null;

async function load() {
  const usuario = await requireSession();
  await cargarCatalogos();
  bindEvents();
  await cargarBorradores();
  return usuario;
}

async function cargarCatalogos() {
  const [cat, prov] = await Promise.all([api.get('/categories'), api.get('/suppliers')]);
  llenarSelect('categoria_id', cat.data || []);
  llenarSelect('proveedor_id', prov.data || []);
}

function llenarSelect(name, items) {
  const select = document.querySelector(`#draftForm [name="${name}"]`);
  if (!select) return;
  select.innerHTML = '<option value="">Seleccione...</option>' +
    items.map((item) => `<option value="${item.id}">${escapeHtml(item.nombre)}</option>`).join('');
}

async function cargarBorradores(search = '') {
  const query = search ? `?search=${encodeURIComponent(search)}` : '';
  const { data } = await api.get(`/products/drafts${query}`);
  render(data || []);
}

function render(items) {
  const tbody = document.getElementById('draftsTableBody');
  if (!tbody) return;
  if (!items.length) {
    tbody.innerHTML = filaVacia(7, 'No hay borradores pendientes');
    return;
  }
  tbody.innerHTML = items.map((p) => {
    const src = urlImagen(p.imagen);
    const img = src
      ? `<img src="${src}" alt="${escapeHtml(p.nombre)}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">`
      : '—';
    const precio = Number(p.precio) > 0 ? formatCurrency(p.precio) : '<span style="color:#94a3b8;">Pendiente</span>';
    return `<tr data-id="${p.id}">
      <td>${img}</td>
      <td>${escapeHtml(p.nombre)}</td>
      <td>${escapeHtml(p.codigo_barra)}</td>
      <td>${escapeHtml(p.categoria || '')}</td>
      <td>${escapeHtml(p.proveedor || '')}</td>
      <td>${precio}</td>
      <td>
        <button type="button" class="btn-secondary btn-editar">Editar</button>
        <button type="button" class="btn-primary btn-publicar">Dar de alta</button>
        <button type="button" class="btn-danger btn-descartar">Descartar</button>
      </td>
    </tr>`;
  }).join('');
}

function bindEvents() {
  document.getElementById('addDraftBtn')?.addEventListener('click', () => abrirFormulario('crear'));

  document.getElementById('searchDraft')?.addEventListener('input', debounce((event) => {
    cargarBorradores(event.target.value.trim()).catch((err) => showToast(err.message, 'error'));
  }, 300));

  document.getElementById('draftsTableBody')?.addEventListener('click', async (event) => {
    const fila = event.target.closest('tr[data-id]');
    if (!fila) return;
    const id = Number(fila.dataset.id);
    if (event.target.classList.contains('btn-editar')) await abrirBorrador(id, 'editar');
    else if (event.target.classList.contains('btn-publicar')) await abrirBorrador(id, 'publicar');
    else if (event.target.classList.contains('btn-descartar')) await descartar(id);
  });

  document.getElementById('draftForm')?.addEventListener('submit', guardar);
  document.getElementById('draftImagen')?.addEventListener('change', onImagenElegida);
}

// ---------------------------------------------------------------- formulario

function abrirFormulario(nuevoModo, borrador = null) {
  modo = nuevoModo;
  borradorActual = borrador;
  const form = document.getElementById('draftForm');
  form.reset();
  limpiarPreviewLocal();

  const textos = {
    crear:    { titulo: 'Nuevo borrador',        boton: 'Guardar borrador',  ayuda: 'Complete lo que tenga a mano. Precio y stock pueden quedar para el alta.' },
    editar:   { titulo: 'Editar borrador',       boton: 'Guardar cambios',   ayuda: 'Este producto sigue oculto en la tienda.' },
    publicar: { titulo: 'Dar de alta producto',  boton: 'Publicar producto', ayuda: 'Revise los datos y complete precio y stock. Al guardar, el producto será visible y vendible.' },
  }[modo];
  document.getElementById('draftModalTitle').textContent = textos.titulo;
  document.getElementById('draftSubmit').textContent = textos.boton;
  document.getElementById('draftHint').textContent = textos.ayuda;

  // En el alta, precio y stock pasan a ser obligatorios (precio > 0 para poder vender)
  const alta = modo === 'publicar';
  form.precio.required = alta;
  form.stock.required = alta;
  form.precio.min = alta ? '0.01' : '0';
  form.precio.placeholder = alta ? '' : 'Opcional';
  form.stock.placeholder = alta ? '' : 'Opcional';
  document.getElementById('lblPrecio').textContent = alta ? 'Precio (S/) *' : 'Precio (S/)';
  document.getElementById('lblStock').textContent = alta ? 'Stock *' : 'Stock';

  if (borrador) {
    form.nombre.value = borrador.nombre || '';
    form.codigo_barra.value = borrador.codigo_barra || '';
    form.categoria_id.value = borrador.categoria_id || '';
    form.proveedor_id.value = borrador.proveedor_id || '';
    form.marca.value = borrador.marca || '';
    form.pasillo.value = borrador.pasillo || '';
    form.descripcion.value = borrador.descripcion || '';
    form.fecha_vencimiento.value = borrador.fecha_vencimiento ? String(borrador.fecha_vencimiento).slice(0, 10) : '';
    form.precio.value = Number(borrador.precio) > 0 ? borrador.precio : '';
    form.stock.value = Number(borrador.stock) > 0 ? borrador.stock : '';
    mostrarPreview(urlImagen(borrador.imagen));
  }
  openModal(MODAL_ID);
}

async function abrirBorrador(id, nuevoModo) {
  try {
    const { data } = await api.get(`/products/drafts/${id}`);
    abrirFormulario(nuevoModo, data);
  } catch (err) {
    showToast(err.message || 'No se pudo cargar el borrador', 'error');
  }
}

// ------------------------------------------------------------------- imagen

function onImagenElegida(event) {
  const archivo = event.target.files?.[0];
  limpiarPreviewLocal();
  if (!archivo) {
    mostrarPreview(borradorActual ? urlImagen(borradorActual.imagen) : '');
    return;
  }
  if (!archivo.type.startsWith('image/')) {
    showToast('El archivo debe ser una imagen', 'error');
    event.target.value = '';
    return;
  }
  if (archivo.size > MAX_IMAGEN_BYTES) {
    showToast('La imagen supera los 5 MB', 'error');
    event.target.value = '';
    return;
  }
  urlPreviewLocal = URL.createObjectURL(archivo);
  mostrarPreview(urlPreviewLocal);
}

function mostrarPreview(src) {
  const wrap = document.getElementById('draftPreviewWrap');
  const img = document.getElementById('draftPreview');
  if (!wrap || !img) return;
  if (src) {
    img.src = src;
    wrap.style.display = 'block';
  } else {
    img.removeAttribute('src');
    wrap.style.display = 'none';
  }
}

function limpiarPreviewLocal() {
  if (urlPreviewLocal) {
    URL.revokeObjectURL(urlPreviewLocal);
    urlPreviewLocal = null;
  }
  mostrarPreview('');
}

// ------------------------------------------------------------------ acciones

async function guardar(event) {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form); // incluye el archivo de la foto
  const boton = document.getElementById('draftSubmit');
  boton.disabled = true;
  try {
    if (modo === 'crear') {
      await api.post('/products/drafts', formData);
      showToast('Borrador guardado. Aún no es visible en la tienda.');
    } else if (modo === 'editar') {
      await api.post(`/products/drafts/${borradorActual.id}`, formData);
      showToast('Borrador actualizado');
    } else {
      await api.post(`/products/drafts/${borradorActual.id}/publish`, formData);
      showToast('Producto dado de alta. Ya aparece en Productos y Ventas.');
    }
    closeModal(MODAL_ID);
    limpiarPreviewLocal();
    borradorActual = null;
    await cargarBorradores(document.getElementById('searchDraft')?.value.trim() || '');
  } catch (err) {
    showToast(mensajeDeError(err), 'error');
  } finally {
    boton.disabled = false;
  }
}

async function descartar(id) {
  if (!confirm('¿Descartar este borrador? Se borrará junto con su foto.')) return;
  try {
    await api.del(`/products/drafts/${id}`);
    showToast('Borrador descartado');
    await cargarBorradores(document.getElementById('searchDraft')?.value.trim() || '');
  } catch (err) {
    showToast(mensajeDeError(err), 'error');
  }
}

/** Si el backend devolvió errores de validación (422), los muestra campo por campo. */
function mensajeDeError(err) {
  if (err instanceof ApiError && err.statusCode === 422 && err.data && typeof err.data === 'object') {
    const detalle = Object.values(err.data).flat().join(' ');
    if (detalle) return detalle;
  }
  return err.message || 'No se pudo completar la operación';
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
    showToast(err.message || 'Error al cargar la pre-carga de productos', 'error');
  });
});
