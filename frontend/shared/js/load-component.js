/**
 * shared/js/load-component.js
 * -----------------------------------------------------------------------
 * Carga fragmentos HTML reutilizables (ej. sidebar.html) dentro de un
 * contenedor marcado con [data-component], y marca como activo el ítem
 * del sidebar que corresponde a la página actual ([data-page] en <body>).
 *
 * Este es el "patrón de componentes" para JS vanilla sin frameworks:
 * cada fragmento vive en su propio .html, se hidrata por fetch(), y las
 * páginas solo declaran DÓNDE va cada componente.
 *
 * Uso en cada página:
 *   <div data-component="sidebar"></div>
 *   <script type="module" src="../../shared/js/load-component.js"></script>
 * -----------------------------------------------------------------------
 */

async function loadComponents() {
  const slots = document.querySelectorAll('[data-component]');

  for (const slot of slots) {
    const name = slot.dataset.component;
    try {
      const response = await fetch(new URL(`../components/${name}.html`, import.meta.url));
      if (!response.ok) throw new Error(`No se pudo cargar el componente "${name}"`);
      slot.innerHTML = await response.text();
    } catch (err) {
      console.error(err);
      slot.innerHTML = `<div class="skeleton">No se pudo cargar "${name}"</div>`;
    }
  }

  markActiveNavItem();
  enlazarCerrarSesion();
  if (window.lucide) window.lucide.createIcons();
}

function enlazarCerrarSesion() {
  const enlace = document.getElementById('logoutLink');
  if (!enlace) return;
  enlace.addEventListener('click', async (event) => {
    event.preventDefault();
    try {
      const { logout } = await import('./auth.js');
      await logout();
    } catch {
      // si la sesión ya expiró, igual se redirige al login
    }
    window.location.href = new URL('../../features/auth/login.html', import.meta.url).href;
  });
}

function markActiveNavItem() {
  const currentPage = document.body.dataset.page;
  if (!currentPage) return;
  document.querySelectorAll('[data-nav]').forEach((link) => {
    link.classList.toggle('active', link.dataset.nav === currentPage);
  });
}

document.addEventListener('DOMContentLoaded', loadComponents);
