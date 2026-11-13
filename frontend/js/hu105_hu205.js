const API_PROD = '../backend/productos.php';
const API_FACT = '../backend/facturacion.php';
let listaProductos = [];

function mostrarMsg(id, texto, ok = true) {
    const el = document.getElementById(id);
    el.textContent = texto;
    el.className = 'msg ' + (ok ? 'msg-ok' : 'msg-err');
    el.style.display = 'block';
}

async function cargarCatalogos() {
    const res = await fetch(`${API_PROD}?action=catalogos`);
    const json = await res.json();
    if (json.status === 'ok') {
        const selCat = document.getElementById('prod_categoria');
        selCat.innerHTML = '<option value="">-- Sin categoría --</option>' +
            json.categorias.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');

        const selUbi = document.getElementById('prod_ubicacion');
        selUbi.innerHTML = '<option value="">-- Sin asignar --</option>' +
            json.ubicaciones.map(u => `<option value="${u.id}">${u.pasillo} - ${u.estante}</option>`).join('');
    }
}

async function cargarProductos() {
    const res = await fetch(`${API_PROD}?action=listar`);
    const json = await res.json();
    if (json.status === 'ok') {
        listaProductos = json.data;
        const tbody = document.getElementById('tablaProductos');
        tbody.innerHTML = listaProductos.map(p => {
            const alerta = Number(p.alerta_stock_bajo) === 1;
            const ubicacion = (p.pasillo && p.estante) ? `${p.pasillo} / ${p.estante}` : 'No asignada';
            return `<tr>
                <td>${p.codigo}</td>
                <td><strong>${p.nombre}</strong></td>
                <td>${p.categoria_nombre || '-'}</td>
                <td>${ubicacion}</td>
                <td>S/ ${Number(p.precio_venta).toFixed(2)}</td>
                <td>${p.stock} / ${p.stock_minimo}</td>
                <td>${alerta
                    ? '<span class="badge-alerta">⚠ Stock Bajo</span>'
                    : '<span class="badge-ok">Normal</span>'}</td>
                <td>
                    <button class="btn-edit" onclick="editarProducto(${p.id})">Editar</button>
                    <button class="btn-danger" onclick="eliminarProducto(${p.id})">Eliminar</button>
                </td>
            </tr>`;
        }).join('');
    }
}

document.getElementById('formProducto').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const id = document.getElementById('prod_id').value;
    formData.append('action', id ? 'editar' : 'crear');

    const res = await fetch(API_PROD, { method: 'POST', body: formData });
    const json = await res.json();
    mostrarMsg('msgProducto', json.msg, json.status === 'ok');
    if (json.status === 'ok') {
        resetFormProducto();
        cargarProductos();
    }
});

function editarProducto(id) {
    const p = listaProductos.find(item => Number(item.id) === Number(id));
    if (!p) return;
    document.getElementById('prod_id').value = p.id;
    document.getElementById('prod_codigo').value = p.codigo;
    document.getElementById('prod_nombre').value = p.nombre;
    document.getElementById('prod_categoria').value = p.categoria_id || '';
    document.getElementById('prod_ubicacion').value = p.ubicacion_id || '';
    document.getElementById('prod_costo').value = p.costo;
    document.getElementById('prod_precio').value = p.precio_venta;
    document.getElementById('prod_stock').value = p.stock;
    document.getElementById('prod_minimo').value = p.stock_minimo;
    document.getElementById('btnGuardarProd').textContent = 'Actualizar Producto';
}

function resetFormProducto() {
    document.getElementById('formProducto').reset();
    document.getElementById('prod_id').value = '';
    document.getElementById('btnGuardarProd').textContent = 'Guardar Producto';
}

async function eliminarProducto(id) {
    if (!confirm('¿Eliminar este producto?')) return;
    const fd = new FormData();
    fd.append('action', 'eliminar');
    fd.append('id', id);
    const res = await fetch(API_PROD, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.status === 'ok') cargarProductos();
}

function ajustarDoc() {
    const tipo = document.getElementById('fact_tipo').value;
    document.getElementById('fact_tipo_doc').value = (tipo === 'FACTURA') ? 'RUC' : 'DNI';
}

document.getElementById('formFactura').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'emitir');

    const res = await fetch(API_FACT, { method: 'POST', body: fd });
    const json = await res.json();
    mostrarMsg('msgFactura', json.msg, json.status === 'ok');

    if (json.status === 'ok') {
        renderComprobante(json.data);
    }
});

function renderComprobante(c) {
    document.getElementById('contenedorPreview').style.display = 'block';
    document.getElementById('areaImpresion').innerHTML = `
        <div style="text-align:center; border-bottom:1px dashed #000; padding-bottom:8px; margin-bottom:8px;">
            <strong>MI TIENDA RETAIL S.A.C.</strong><br>
            RUC: 20601234567<br>
            <strong>${c.tipo_comprobante} ELECTRÓNICA</strong><br>
            ${c.serie}-${c.numero}
        </div>
        <div>
            <strong>Fecha:</strong> ${c.fecha_emision}<br>
            <strong>Cliente:</strong> ${c.nombre_cliente}<br>
            <strong>${c.tipo_documento_cliente}:</strong> ${c.numero_documento_cliente}<br>
            <strong>Ref. Venta:</strong> #${c.venta_id}
        </div>
        <hr>
        <div>
            ${c.cantidad || 1}x ${c.producto_nombre || 'Producto venta'} — S/ ${Number(c.total).toFixed(2)}
        </div>
        <hr>
        <div style="text-align:right;">
            <strong>OP. GRAVADA (Subtotal):</strong> S/ ${Number(c.subtotal).toFixed(2)}<br>
            <strong>IGV (18%):</strong> S/ ${Number(c.igv).toFixed(2)}<br>
            <strong>IMPORTE TOTAL:</strong> S/ ${Number(c.total).toFixed(2)}
        </div>
    `;
}

function cambiarFormato(tipo) {
    const area = document.getElementById('areaImpresion');
    area.className = 'preview-box ' + (tipo === 'ticket' ? 'formato-ticket' : 'formato-a4');
}

cargarCatalogos();
cargarProductos();
