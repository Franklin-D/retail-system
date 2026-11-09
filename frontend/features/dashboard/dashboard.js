import { api, ApiError } from '../../shared/js/api.js';
import { requireSession } from '../../shared/js/auth.js';
import { showToast } from '../../shared/js/toast.js';
import { formatCurrency } from '../../shared/js/helpers.js';

async function load() {
  await requireSession();
  const { data } = await api.get('/reports/summary');
  render(data);
}

function render(data) {
  const totalProducts = document.getElementById('totalProducts');
  const totalStock = document.getElementById('totalStock');
  const inventoryValue = document.getElementById('inventoryValue');
  if (totalProducts) totalProducts.textContent = data.total_productos ?? 0;
  if (totalStock) totalStock.textContent = data.total_stock ?? 0;
  if (inventoryValue) inventoryValue.textContent = formatCurrency(data.valor_inventario);
}

document.addEventListener('DOMContentLoaded', () => {
  load().catch((err) => {
    if (err instanceof ApiError && err.statusCode === 401) return;
    showToast(err.message || 'Error al cargar el panel', 'error');
  });
});
