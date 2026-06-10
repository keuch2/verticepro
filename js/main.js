// ============================================================
// VÉRTICE PRO — main.js
// ============================================================

// 1. HEADER SHADOW ON SCROLL
window.addEventListener('scroll', () => {
  const header = document.getElementById('main-header');
  if (header) header.classList.toggle('shadow-md', window.scrollY > 10);
});

// 2. SMOOTH SCROLL FOR ANCHOR LINKS
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
  });
});

// 3. ACTIVE NAV LINK DETECTION — works with .html, .php, and clean URLs
document.addEventListener('DOMContentLoaded', () => {
  const path = window.location.pathname;
  let currentPage = path.split('/').filter(Boolean).pop() || 'index.php';
  // Map clean URLs back to .php filenames so existing data-page matches work.
  const cleanUrlMap = {
    '': 'index.php',
    'articulo': 'articulo.php',
    'perfil': 'perfil.php',
    'empresa': 'empresa.php',
    'oferta': 'oferta.php',
    'seccion': 'seccion.php',
    'publicacion': 'publicacion.php',
  };
  // If the first segment matches a known clean-url prefix, use that as the active key.
  const firstSeg = path.split('/').filter(Boolean)[0] || '';
  if (cleanUrlMap[firstSeg]) currentPage = cleanUrlMap[firstSeg];
  // Accept both .html (legacy) and .php versions of the same link.
  const normalize = (p) => p.replace(/\.html$/, '.php');
  currentPage = normalize(currentPage);
  document.querySelectorAll('nav a[data-page]').forEach(link => {
    if (normalize(link.getAttribute('data-page')) === currentPage) {
      link.classList.add('!text-naranja', 'border-b-2', 'border-naranja', 'pb-0.5');
      link.classList.remove('text-gris-oscuro');
    }
  });
});

// 4. MULTI-AXIS FILTER SYSTEM (directorio.php & empresas.php)
// Cards must have data-card attribute. Each axis filter compared against data-<axis>.
// Special axis "search" does a substring match (accent-insensitive) against data-search.
// Pill button containers: data-filter-group="axis"
// Individual pill buttons:  data-filter="value"
// Select elements:           data-filter-axis="axis"
// Inputs (search):           data-filter-axis="search"
// Dependent select:          data-depends-on="<parentAxis>" — its <option> rows can carry
//                            data-<parentAxis> to be hidden when not matching the parent.
// Clear button: id="clear-filters"
document.addEventListener('DOMContentLoaded', () => {
  const filterArea = document.getElementById('filter-area');
  if (!filterArea) return;

  const activeFilters = {};
  const norm = (s) => (s || '').toString().toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').trim();

  // Pill button groups
  const pillGroups = filterArea.querySelectorAll('[data-filter-group]');
  pillGroups.forEach(group => {
    const axis = group.getAttribute('data-filter-group');
    activeFilters[axis] = 'todos';
    const buttons = group.querySelectorAll('[data-filter]');
    buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        activeFilters[axis] = btn.getAttribute('data-filter');
        buttons.forEach(b => {
          b.classList.remove('bg-naranja', 'text-white', 'border-naranja');
          b.classList.add('border-gray-300', 'text-gris-oscuro');
        });
        btn.classList.add('bg-naranja', 'text-white', 'border-naranja');
        btn.classList.remove('border-gray-300', 'text-gris-oscuro');
        applyFilters();
      });
    });
  });

  // Select elements
  filterArea.querySelectorAll('select[data-filter-axis]').forEach(sel => {
    const axis = sel.getAttribute('data-filter-axis');
    activeFilters[axis] = '';
    sel.addEventListener('change', () => {
      activeFilters[axis] = sel.value;
      syncDependentSelects();
      applyFilters();
    });
  });

  // Input (search) elements — also support data-filter-axis on <input> tags
  filterArea.querySelectorAll('input[data-filter-axis]').forEach(inp => {
    const axis = inp.getAttribute('data-filter-axis');
    activeFilters[axis] = '';
    inp.addEventListener('input', () => {
      activeFilters[axis] = inp.value;
      applyFilters();
    });
  });

  // Hide options in dependent selects when their parent's value doesn't match.
  function syncDependentSelects() {
    filterArea.querySelectorAll('select[data-depends-on]').forEach(sel => {
      const parentAxis = sel.getAttribute('data-depends-on');
      const parentVal = activeFilters[parentAxis] || '';
      let changed = false;
      sel.querySelectorAll('option').forEach(opt => {
        if (!opt.value) return; // keep "Todas las..."
        const optParent = opt.getAttribute('data-' + parentAxis) || '';
        const show = !parentVal || optParent === parentVal;
        opt.hidden = !show;
        if (!show && sel.value === opt.value) { sel.value = ''; changed = true; }
      });
      if (changed) {
        activeFilters[sel.getAttribute('data-filter-axis')] = '';
      }
    });
  }

  // Clear button
  const clearBtn = document.getElementById('clear-filters');
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      pillGroups.forEach(group => {
        const axis = group.getAttribute('data-filter-group');
        activeFilters[axis] = 'todos';
        const buttons = group.querySelectorAll('[data-filter]');
        buttons.forEach(b => {
          b.classList.remove('bg-naranja', 'text-white', 'border-naranja');
          b.classList.add('border-gray-300', 'text-gris-oscuro');
        });
        const todosBtn = group.querySelector('[data-filter="todos"]');
        if (todosBtn) {
          todosBtn.classList.add('bg-naranja', 'text-white', 'border-naranja');
          todosBtn.classList.remove('border-gray-300', 'text-gris-oscuro');
        }
      });
      filterArea.querySelectorAll('select[data-filter-axis]').forEach(sel => {
        sel.value = '';
        activeFilters[sel.getAttribute('data-filter-axis')] = '';
      });
      filterArea.querySelectorAll('input[data-filter-axis]').forEach(inp => {
        inp.value = '';
        activeFilters[inp.getAttribute('data-filter-axis')] = '';
      });
      syncDependentSelects();
      applyFilters();
    });
  }

  // Initial sync (in case page loaded with a preselected parent)
  syncDependentSelects();

  function applyFilters() {
    const searchTerm = norm(activeFilters['search'] || '');
    document.querySelectorAll('[data-card]').forEach(card => {
      let visible = true;
      for (const [axis, filterVal] of Object.entries(activeFilters)) {
        if (!filterVal || filterVal === 'todos') continue;
        if (axis === 'search') {
          if (!searchTerm) continue;
          const haystack = norm(card.getAttribute('data-search') || '');
          if (!haystack.includes(searchTerm)) { visible = false; break; }
        } else {
          // El card puede tener un único valor o varios separados por espacio (multi-valor).
          const cardVal = card.getAttribute('data-' + axis) || '';
          const tokens = cardVal.split(/\s+/).filter(Boolean);
          if (!tokens.includes(filterVal)) { visible = false; break; }
        }
      }
      if (visible) {
        card.style.display = '';
        requestAnimationFrame(() => {
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        });
      } else {
        card.style.opacity = '0';
        card.style.transform = 'translateY(8px)';
        setTimeout(() => { if (card.style.opacity === '0') card.style.display = 'none'; }, 220);
      }
    });
  }
});

// 5. BOLSA DE TRABAJO TOGGLE (bolsa.html)
document.addEventListener('DOMContentLoaded', () => {
  const btnOfertas = document.getElementById('btn-ofertas');
  const btnServicios = document.getElementById('btn-servicios');
  const ofertasSection = document.getElementById('ofertas-section');
  const serviciosSection = document.getElementById('servicios-section');
  if (!btnOfertas || !btnServicios) return;

  function activateTab(activeBtn, inactiveBtn, showSection, hideSection) {
    showSection.style.display = 'block';
    hideSection.style.display = 'none';
    activeBtn.classList.add('bg-azul', 'text-white');
    activeBtn.classList.remove('bg-white', 'border', 'border-gray-300', 'text-gris-oscuro');
    inactiveBtn.classList.remove('bg-azul', 'text-white');
    inactiveBtn.classList.add('bg-white', 'border', 'border-gray-300', 'text-gris-oscuro');
  }

  btnOfertas.addEventListener('click', () => activateTab(btnOfertas, btnServicios, ofertasSection, serviciosSection));
  btnServicios.addEventListener('click', () => activateTab(btnServicios, btnOfertas, serviciosSection, ofertasSection));
});

// 6. BOLSA — "Me interesa" toggle: expands the inline form for a specific offer.
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-interest-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-interest-toggle');
      const form = document.querySelector('[data-interest-form="' + id + '"]');
      if (!form) return;
      form.classList.toggle('hidden');
      if (!form.classList.contains('hidden')) form.querySelector('input[name="name"]').focus();
    });
  });
});

// 7. MOBILE MENU TOGGLE
document.addEventListener('DOMContentLoaded', () => {
  const menuBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  if (!menuBtn || !mobileMenu) return;
  menuBtn.addEventListener('click', () => {
    const isOpen = mobileMenu.classList.contains('hidden');
    mobileMenu.classList.toggle('hidden', !isOpen);
    menuBtn.setAttribute('aria-expanded', String(isOpen));
  });
});
