const API = '../backend/productos.php';

async function cargarProductos() {
    const res = await fetch(`${API}?action=listar`);
    const json = await res.json();
    const tbody = document.getElementById('tabla-productos');
    tbody.innerHTML = '';
    
    json.data.forEach(p => {
        const ganancia = (parseFloat(p.precio_venta) - parseFloat(p.costo)).toFixed(2);
        tbody.innerHTML += `<tr>
            <td>${p.id}</td><td>${p.nombre}</td><td>${p.codigo}</td>
            <td>S/ ${p.costo}</td><td>S/ ${p.precio_venta}</td><td>${p.stock}</td>
            <td>S/ ${ganancia}</td>
            <td><button class="eliminar" onclick="eliminar(${p.id})">X</button></td>
        </tr>`;
    });
    cargarGanancias();
}

async function cargarGanancias(){
    const res = await fetch(`${API}?action=ganancias`);
    const json = await res.json();
    document.getElementById('total-prod').textContent = json.data.total_productos || 0;
    document.getElementById('total-stock').textContent = json.data.total_stock || 0;
    document.getElementById('total-ganancia').textContent = parseFloat(json.data.ganancia_potencial || 0).toFixed(2);
}

document.getElementById('form-producto').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'crear');
    const res = await fetch(API, { method: 'POST', body: formData });
    const json = await res.json();
    if(json.status == 'ok'){ e.target.reset(); cargarProductos(); } 
    else { alert(json.msg); }
});

async function eliminar(id){
    if(!confirm('¿Eliminar?')) return;
    const fd = new FormData();
    fd.append('action','eliminar'); fd.append('id', id);
    await fetch(API, { method: 'POST', body: fd });
    cargarProductos();
}

cargarProductos();