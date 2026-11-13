import { api, ApiError } from '../../shared/js/api.js';
import { requireSession } from '../../shared/js/auth.js';
import { showToast } from '../../shared/js/toast.js';
import { formatCurrency, formatDate, filaVacia } from '../../shared/js/helpers.js';

const estado = {
  filtros: { desde: '', hasta: '', categoria: '' },
  categoriaNombre: '',
  resumen: null,
  serie: [],
  topProductos: [],
  alertas: [],
  movimientos: [],
};

let grafico = null;
let corrida = 0;

/* ---------- utilidades ---------- */

function escapeHtml(texto) {
  return String(texto ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function fechaLocal(d) {
  const mes = String(d.getMonth() + 1).padStart(2, '0');
  const dia = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${mes}-${dia}`;
}

const dia = (fecha) => String(fecha || '').slice(0, 10);
const el = (id) => document.getElementById(id);

function avisar(texto) {
  const aviso = el('filtrosAviso');
  aviso.textContent = texto || '';
  aviso.hidden = !texto;
}

function nombreCategoriaActual() {
  const select = el('filtroCategoria');
  if (!select.value) return '';
  return select.selectedOptions[0]?.textContent?.trim() || '';
}

function rangoTexto(filtros = estado.filtros) {
  const desde = filtros.desde ? formatDate(filtros.desde) : 'inicio';
  const hasta = filtros.hasta ? formatDate(filtros.hasta) : 'hoy';
  const categoria = estado.categoriaNombre ? ` · Categoría: ${estado.categoriaNombre}` : '';
  return `Período: ${desde} al ${hasta}${categoria}`;
}

/* ---------- carga de datos ---------- */

async function cargarCategorias() {
  try {
    const { data } = await api.get('/categories');
    const select = el('filtroCategoria');
    (data || []).forEach((c) => select.add(new Option(c.nombre, String(c.id))));
  } catch (err) {
    showToast(err.message || 'No se pudieron cargar las categorías', 'error');
  }
}

async function load() {
  await requireSession();
  await cargarCategorias();

  const hoy = new Date();
  el('filtroDesde').value = fechaLocal(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
  el('filtroHasta').value = fechaLocal(hoy);

  await aplicarFiltros();
}

function leerFiltros() {
  return {
    desde: el('filtroDesde').value,
    hasta: el('filtroHasta').value,
    categoria: el('filtroCategoria').value,
  };
}

async function aplicarFiltros() {
  const f = leerFiltros();
  const categoriaNombre = nombreCategoriaActual();
  const id = ++corrida;

  if (f.desde && f.hasta && f.desde > f.hasta) {
    avisar('La fecha de inicio no puede ser mayor que la fecha fin.');
    return;
  }
  avisar('');

  const params = new URLSearchParams();
  if (f.desde) params.set('desde', f.desde);
  if (f.hasta) params.set('hasta', f.hasta);
  if (f.categoria) params.set('categoria_id', f.categoria);

  try {
    const { data } = await api.get(`/finance/dashboard?${params.toString()}`);
    if (id !== corrida) return;

    estado.filtros = { ...f };
    estado.categoriaNombre = categoriaNombre;
    estado.resumen = data.resumen || {};
    estado.serie = data.serie || [];
    estado.topProductos = data.top_productos || [];
    estado.alertas = data.alertas_stock || [];
    estado.movimientos = data.movimientos || [];

    pintarRango();
    pintarTarjetas();
    pintarGrafico();
    pintarTopProductos();
    pintarAlertas();
    pintarMovimientos();
  } catch (err) {
    if (id !== corrida) return;
    if (err instanceof ApiError && err.statusCode === 401) return;
    const mensaje = err.message || 'No se pudo cargar el dashboard financiero';
    avisar(mensaje);
    showToast(mensaje, 'error');
  }
}

/* ---------- pintado ---------- */

function pintarRango() {
  el('rangoTexto').textContent = rangoTexto();
}

function pintarTarjetas() {
  const r = estado.resumen || {};
  const balance = Number(r.balance_periodo) || 0;

  el('kpiVentasHoy').textContent = formatCurrency(r.ventas_hoy);
  el('kpiVentasPeriodo').textContent = formatCurrency(r.ventas_periodo);
  el('kpiIngresos').textContent = formatCurrency(r.ingresos_periodo);
  el('kpiEgresos').textContent = formatCurrency(r.egresos_periodo);

  const kpiBalance = el('kpiBalance');
  kpiBalance.textContent = formatCurrency(balance);
  kpiBalance.classList.toggle('rep-positivo', balance >= 0);
  kpiBalance.classList.toggle('rep-negativo', balance < 0);
}

function pintarGrafico() {
  if (grafico) {
    grafico.destroy();
    grafico = null;
  }

  const serie = estado.serie || [];
  el('graficoVacio').hidden = serie.length > 0;
  if (!serie.length) return;

  if (typeof Chart === 'undefined') {
    avisar('No se pudo cargar la librería de gráficos (revisa tu conexión a internet).');
    return;
  }

  grafico = new Chart(el('graficoFinanzas'), {
    type: 'bar',
    data: {
      labels: serie.map((x) => formatDate(x.fecha)),
      datasets: [
        {
          label: 'Ingresos',
          data: serie.map((x) => Number(x.ingresos) || 0),
          backgroundColor: '#16a34a',
        },
        {
          label: 'Egresos',
          data: serie.map((x) => Number(x.egresos) || 0),
          backgroundColor: '#dc2626',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: false,
      plugins: {
        legend: { position: 'bottom' },
        tooltip: {
          callbacks: {
            label: (ctx) => `${ctx.dataset.label}: ${formatCurrency(ctx.parsed.y)}`,
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: (valor) => formatCurrency(valor) },
        },
      },
    },
  });
}

function pintarTopProductos() {
  const lista = el('masVendidos');
  const nota = el('masVendidosNota');
  lista.replaceChildren();

  if (!estado.topProductos.length) {
    const li = document.createElement('li');
    li.textContent = 'Sin ventas en el período';
    lista.appendChild(li);
    nota.textContent = '';
    return;
  }

  estado.topProductos.forEach((p) => {
    const li = document.createElement('li');
    li.textContent = `${p.nombre} · ${p.categoria} (${p.cantidad} u.) — ${formatCurrency(p.total)}`;
    lista.appendChild(li);
  });
  nota.textContent = 'Top 5 por unidades vendidas en el período filtrado.';
}

function pintarAlertas() {
  const lista = el('alertasStock');
  lista.replaceChildren();

  if (!estado.alertas.length) {
    const li = document.createElement('li');
    li.textContent = 'Sin alertas de stock';
    lista.appendChild(li);
    return;
  }

  estado.alertas.forEach((p) => {
    const li = document.createElement('li');
    li.textContent = `${p.nombre} (${p.categoria}): quedan ${p.stock} (mínimo ${p.stock_minimo})`;
    lista.appendChild(li);
  });
}

function pintarMovimientos() {
  const tbody = el('financeTableBody');
  if (!estado.movimientos.length) {
    tbody.innerHTML = filaVacia(4, 'No hay movimientos en el período');
    return;
  }

  tbody.innerHTML = estado.movimientos.map((m) => `
    <tr>
      <td>${formatDate(m.fecha)}</td>
      <td>${escapeHtml(m.descripcion)}</td>
      <td>${escapeHtml(m.tipo)}</td>
      <td>${formatCurrency(m.monto)}</td>
    </tr>
  `).join('');
}

/* ---------- exportación ---------- */

function celdaCsv(valor) {
  let texto = String(valor ?? '');
  if (!/^-?\d+(\.\d+)?$/.test(texto) && /^[=+\-@\t\r]/.test(texto)) {
    texto = `'${texto}`;
  }
  return `"${texto.replace(/"/g, '""')}"`;
}

function filaCsv(valores) {
  return valores.map(celdaCsv).join(',');
}

function descargarBlob(contenido, tipo, nombre) {
  const blob = new Blob([contenido], { type: tipo });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = nombre;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 0);
}

function sufijoArchivo() {
  const { desde, hasta, categoria } = estado.filtros;
  return [
    desde || 'inicio',
    hasta || 'hoy',
    categoria ? `cat${categoria}` : '',
  ].filter(Boolean).join('_');
}

function exportarCsv() {
  if (!estado.resumen) {
    showToast('Espera a que el dashboard termine de cargar', 'error');
    return;
  }

  const r = estado.resumen;
  const filas = [];
  const push = (...valores) => filas.push(filaCsv(valores));

  push('Reporte financiero · Planet Color');
  push('Período', `${estado.filtros.desde || 'inicio'} a ${estado.filtros.hasta || 'hoy'}`);
  push('Categoría', estado.categoriaNombre || 'Todas');
  push('Generado', new Date().toLocaleString('es-PE'));
  push('');

  push('RESUMEN');
  push('Indicador', 'Valor');
  push('Ventas de hoy', formatCurrency(r.ventas_hoy));
  push('Ventas del período', formatCurrency(r.ventas_periodo));
  push('Ingresos del período', formatCurrency(r.ingresos_periodo));
  push('Egresos del período', formatCurrency(r.egresos_periodo));
  push('Balance del período', formatCurrency(r.balance_periodo));
  push('');

  push('TOP 5 PRODUCTOS');
  push('Producto', 'Categoría', 'Cantidad', 'Total');
  if (estado.topProductos.length) {
    estado.topProductos.forEach((p) => push(p.nombre, p.categoria, p.cantidad, formatCurrency(p.total)));
  } else {
    push('Sin ventas en el período', '', '', '');
  }
  push('');

  push('ALERTAS DE STOCK');
  push('Producto', 'Categoría', 'Stock', 'Stock mínimo');
  if (estado.alertas.length) {
    estado.alertas.forEach((p) => push(p.nombre, p.categoria, p.stock, p.stock_minimo));
  } else {
    push('Sin alertas de stock', '', '', '');
  }
  push('');

  push('MOVIMIENTOS DEL PERÍODO');
  push('Fecha', 'Descripción', 'Tipo', 'Monto');
  if (estado.movimientos.length) {
    estado.movimientos.forEach((m) => push(dia(m.fecha), m.descripcion, m.tipo, formatCurrency(m.monto)));
  } else {
    push('Sin movimientos en el período', '', '', '');
  }

  descargarBlob(
    '\ufeff' + filas.join('\n'),
    'text/csv;charset=utf-8;',
    `finanzas_${sufijoArchivo()}.csv`
  );
  showToast('Reporte CSV generado');
}

function exportarPdf() {
  if (!estado.resumen) {
    showToast('Espera a que el dashboard termine de cargar', 'error');
    return;
  }

  const jsPDFClass = window.jspdf?.jsPDF;
  if (typeof jsPDFClass !== 'function') {
    showToast('No se pudo cargar la librería de PDF. Revisa tu conexión a internet.', 'error');
    return;
  }

  const doc = new jsPDFClass({ orientation: 'portrait', unit: 'mm', format: 'a4' });
  if (typeof doc.autoTable !== 'function') {
    showToast('No se pudo cargar el generador de tablas PDF. Revisa tu conexión a internet.', 'error');
    return;
  }
  const margen = 14;
  const anchoUtil = 210 - margen * 2;
  let y = 18;

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(16);
  doc.text('Planet Color · Reporte financiero', margen, y);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9);
  y += 7;
  doc.text(rangoTexto(), margen, y);
  y += 5;
  doc.text(`Generado: ${new Date().toLocaleString('es-PE')}`, margen, y);

  const r = estado.resumen;
  doc.autoTable({
    startY: y + 5,
    head: [['Indicador', 'Valor']],
    body: [
      ['Ventas de hoy', formatCurrency(r.ventas_hoy)],
      ['Ventas del período', formatCurrency(r.ventas_periodo)],
      ['Ingresos del período', formatCurrency(r.ingresos_periodo)],
      ['Egresos del período', formatCurrency(r.egresos_periodo)],
      ['Balance del período', formatCurrency(r.balance_periodo)],
    ],
    theme: 'grid',
    styles: { fontSize: 9, cellPadding: 2 },
    headStyles: { fillColor: [29, 78, 216], textColor: 255 },
    columnStyles: { 1: { halign: 'right' } },
    margin: { left: margen, right: margen },
  });
  y = doc.lastAutoTable.finalY + 7;

  if (grafico) {
    if (y > 200) {
      doc.addPage();
      y = 18;
    }
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11);
    doc.text('Ingresos vs. egresos por día', margen, y);
    y += 3;
    doc.addImage(grafico.toBase64Image('image/png', 1), 'PNG', margen, y, anchoUtil, 70);
    y += 78;
  }

  if (y > 240) {
    doc.addPage();
    y = 18;
  }
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.text('Top 5 productos', margen, y);
  doc.autoTable({
    startY: y + 3,
    head: [['Producto', 'Categoría', 'Cantidad', 'Total']],
    body: estado.topProductos.length
      ? estado.topProductos.map((p) => [p.nombre, p.categoria, String(p.cantidad), formatCurrency(p.total)])
      : [['Sin ventas en el período', '', '', '']],
    theme: 'grid',
    styles: { fontSize: 8, cellPadding: 2 },
    headStyles: { fillColor: [22, 163, 74], textColor: 255 },
    columnStyles: { 2: { halign: 'right' }, 3: { halign: 'right' } },
    margin: { left: margen, right: margen },
  });
  y = doc.lastAutoTable.finalY + 6;

  if (y > 240) {
    doc.addPage();
    y = 18;
  }
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.text('Alertas de stock', margen, y);
  doc.autoTable({
    startY: y + 3,
    head: [['Producto', 'Categoría', 'Stock', 'Stock mínimo']],
    body: estado.alertas.length
      ? estado.alertas.map((p) => [p.nombre, p.categoria, String(p.stock), String(p.stock_minimo)])
      : [['Sin alertas de stock', '', '', '']],
    theme: 'grid',
    styles: { fontSize: 8, cellPadding: 2 },
    headStyles: { fillColor: [217, 119, 6], textColor: 255 },
    columnStyles: { 2: { halign: 'right' }, 3: { halign: 'right' } },
    margin: { left: margen, right: margen },
  });
  y = doc.lastAutoTable.finalY + 6;

  if (y > 220) {
    doc.addPage();
    y = 18;
  }
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.text('Movimientos del período', margen, y);
  doc.autoTable({
    startY: y + 3,
    head: [['Fecha', 'Descripción', 'Tipo', 'Monto']],
    body: estado.movimientos.length
      ? estado.movimientos.map((m) => [
          formatDate(m.fecha),
          m.descripcion,
          m.tipo,
          formatCurrency(m.monto),
        ])
      : [['', 'Sin movimientos en el período', '', '']],
    theme: 'striped',
    styles: { fontSize: 8, cellPadding: 2 },
    headStyles: { fillColor: [71, 85, 105], textColor: 255 },
    columnStyles: { 0: { cellWidth: 24 }, 2: { cellWidth: 22 }, 3: { cellWidth: 28, halign: 'right' } },
    margin: { left: margen, right: margen },
  });

  const paginas = doc.getNumberOfPages();
  for (let i = 1; i <= paginas; i += 1) {
    doc.setPage(i);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(100);
    doc.text('Planet Color · Reporte financiero', margen, 290);
    doc.text(`Página ${i} de ${paginas}`, 196, 290, { align: 'right' });
  }

  doc.save(`finanzas_${sufijoArchivo()}.pdf`);
  showToast('Reporte PDF generado');
}

/* ---------- arranque ---------- */

document.addEventListener('DOMContentLoaded', () => {
  el('filtrosForm').addEventListener('submit', (e) => {
    e.preventDefault();
    aplicarFiltros().catch((err) => showToast(err.message || 'Error al aplicar filtros', 'error'));
  });
  el('btnCsv').addEventListener('click', exportarCsv);
  el('btnPdf').addEventListener('click', exportarPdf);

  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar finanzas', 'error');
  });
});
