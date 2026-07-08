<?php
// Uso: antes del include, define $page_title, $page_active (filename o slug lógico), opcionalmente $page_description.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth_public.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/ads.php';
$site_name = setting('site.name', 'Vértice Pro') ?: 'Vértice Pro';
$page_title = $page_title ?? $site_name;
$page_active = $page_active ?? current_page_filename();
$page_description = $page_description ?? setting('seo.meta_description', 'Plataforma editorial y red profesional para especialistas en calidad, seguridad, salud ocupacional y medio ambiente.');
$current_user = public_logged_in();
$unread_notifs = $current_user ? Notify::unreadCount((int)$current_user['id']) : 0;
$my_area_url = '/login';
$has_prof = $has_comp = false;
if ($current_user) {
    if (in_array($current_user['role'], ['admin','author'], true)) {
        $my_area_url = '/admin/';
    } else {
        $has_prof = (bool)DB::one('SELECT id FROM professionals WHERE user_id = ? LIMIT 1', [(int)$current_user['id']]);
        $has_comp = (bool)DB::one('SELECT id FROM companies WHERE user_id = ? LIMIT 1', [(int)$current_user['id']]);
        if ($has_prof)      $my_area_url = '/mi-perfil';
        elseif ($has_comp)  $my_area_url = '/mi-organizacion';
        elseif ($current_user['role'] === 'professional') $my_area_url = '/mi-perfil';
        elseif ($current_user['role'] === 'company')      $my_area_url = '/mi-organizacion';
    }
}

// Helper for nav link active state (desktop)
function nav_link(string $href, string $label, string $active_key, string $current): string {
    $is_active = $active_key === $current;
    $classes = $is_active
        ? 'text-naranja border-b-2 border-naranja'
        : 'text-gris-oscuro hover:text-naranja';
    return '<a href="' . e($href) . '" data-page="' . e($active_key) . '" class="px-3 py-1.5 ' . $classes . ' transition duration-150">' . e($label) . '</a>';
}
function m_nav_link(string $href, string $label, string $active_key, string $current): string {
    $cls = $active_key === $current ? 'text-naranja font-semibold' : 'text-gris-oscuro hover:text-naranja';
    return '<a href="' . e($href) . '" class="block py-2 ' . $cls . '">' . e($label) . '</a>';
}
$c = $page_active;
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($page_title) ?></title>
  <meta name="description" content="<?= e($page_description) ?>" />
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            naranja:      '#F58220',
            verde:        '#52B788',
            azul:         '#0078D4',
            coral:        '#E84855',
            violeta:      '#9B59B6',
            'gris-oscuro': '#54636F',
            'gris-claro':  '#F5F5F5',
            texto:        '#1A1A1A',
          },
          fontFamily: { sans: ['Barlow', 'sans-serif'] },
        }
      }
    }
  </script>
</head>
<body class="bg-white font-sans text-texto antialiased">

  <?php $__ad_top = ad_slot('header_top', ['wrap_class' => 'text-center py-2 bg-gris-claro border-b border-gray-200']);
        if ($__ad_top !== '') echo $__ad_top; ?>

  <header id="main-header" class="sticky top-0 z-50 bg-white transition-shadow duration-200">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
      <a href="<?= e(u('/')) ?>"><img src="<?= e(u('/img/logo.png')) ?>" alt="Vértice Pro" class="h-9 w-auto" /></a>
      <nav class="hidden lg:flex items-center gap-0.5 text-sm font-medium">
        <?= nav_link(u('/seccion/calidad'), 'Calidad', 'seccion.php', $c) ?>
        <?= nav_link(u('/seguridad'), 'Seguridad', 'seguridad.php', $c) ?>
        <?= nav_link(u('/medioambiente'), 'Medio Ambiente', 'medioambiente.php', $c) ?>
        <?= nav_link(u('/salud'), 'Salud Ocupacional', 'salud.php', $c) ?>
        <div class="relative group">
          <button class="px-3 py-1.5 text-gris-oscuro hover:text-naranja transition duration-150 flex items-center gap-1">
            Biblioteca
            <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div class="absolute top-full left-0 mt-1 w-56 bg-white border border-gray-200 rounded-lg shadow-lg py-1 hidden group-hover:block z-50">
            <a href="<?= e(u('/publicaciones')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition">Publicaciones Técnicas</a>
            <a href="<?= e(u('/recursos')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition">Recursos Descargables</a>
            <a href="<?= e(u('/legislacion')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition">Legislación</a>
            <a href="<?= e(u('/clipping')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition">Clipping de Noticias</a>
            <a href="<?= e(u('/aportar')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition border-t border-gray-100">Aportar contenido</a>
          </div>
        </div>
        <div class="border-r border-gray-200 h-5 mx-2"></div>
        <?= nav_link(u('/red'), 'Red Vértice Pro', 'red.php', $c) ?>
        <?= nav_link(u('/bolsa'), 'Bolsa de Trabajo', 'bolsa.php', $c) ?>
        <?= nav_link(u('/eventos'), 'Eventos', 'eventos.php', $c) ?>
      </nav>
      <div class="flex items-center gap-3">
        <?php if ($current_user): ?>
          <a href="<?= e(u('/notificaciones')) ?>" class="hidden md:inline-flex relative text-gris-oscuro hover:text-naranja transition p-2" title="Notificaciones">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <?php if ($unread_notifs > 0): ?><span class="absolute top-1 right-1 bg-coral text-white text-[10px] font-bold rounded-full px-1.5"><?= (int)$unread_notifs ?></span><?php endif; ?>
          </a>
          <div class="hidden md:block relative group">
            <button class="bg-azul text-white text-sm font-semibold px-4 py-2 rounded hover:bg-blue-700 transition duration-150 flex items-center gap-1">
              <?= e(mb_strimwidth($current_user['name'], 0, 18, '…')) ?>
              <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="absolute right-0 top-full mt-1 w-56 bg-white border border-gray-200 rounded-lg shadow-lg py-1 hidden group-hover:block z-50">
              <?php if ($has_prof): ?>
                <a href="<?= e(u('/mi-perfil')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition">Mi perfil profesional</a>
              <?php endif; ?>
              <?php if ($has_comp): ?>
                <a href="<?= e(u('/mi-organizacion')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition">Mi organización</a>
              <?php endif; ?>
              <?php if (!$has_prof && !$has_comp && !in_array($current_user['role'], ['admin','author'], true)): ?>
                <a href="<?= e(u($my_area_url)) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition">Mi cuenta</a>
              <?php endif; ?>
              <?php if (in_array($current_user['role'], ['admin','author'], true)): ?>
                <a href="<?= e(u('/admin/')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition">Panel admin</a>
              <?php endif; ?>
              <?php if (!$has_comp && !in_array($current_user['role'], ['admin','author'], true)): ?>
                <a href="<?= e(u('/crear-organizacion')) ?>" class="block px-4 py-2.5 text-xs text-azul hover:bg-blue-50 transition border-t border-gray-100">+ Crear perfil de organización</a>
              <?php endif; ?>
              <?php if (!$has_prof && !in_array($current_user['role'], ['admin','author'], true)): ?>
                <a href="<?= e(u('/crear-perfil')) ?>" class="block px-4 py-2.5 text-xs text-azul hover:bg-blue-50 transition <?= !$has_comp ? '' : 'border-t border-gray-100' ?>">+ Crear perfil profesional</a>
              <?php endif; ?>
              <a href="<?= e(u('/notificaciones')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition border-t border-gray-100">Notificaciones<?php if ($unread_notifs > 0): ?> <span class="text-coral font-bold">(<?= (int)$unread_notifs ?>)</span><?php endif; ?></a>
              <a href="<?= e(u('/logout')) ?>" class="block px-4 py-2.5 text-sm text-coral hover:bg-red-50 transition border-t border-gray-100">Cerrar sesión</a>
            </div>
          </div>
        <?php else: ?>
          <a href="<?= e(u('/login')) ?>" class="hidden md:inline-flex text-gris-oscuro hover:text-naranja text-sm font-semibold px-3 py-2 transition duration-150">Iniciar sesión</a>
          <a href="<?= e(u('/registro')) ?>" class="hidden md:inline-flex bg-naranja text-white text-sm font-semibold px-4 py-2 rounded hover:bg-orange-600 transition duration-150">Únete a la red</a>
        <?php endif; ?>
        <button id="mobile-menu-btn" aria-expanded="false" class="lg:hidden p-2 text-gris-oscuro hover:text-naranja transition">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
      </div>
    </div>
    <div id="mobile-menu" class="hidden lg:hidden bg-white border-t border-gray-100 px-6 py-4 space-y-2 text-sm font-medium">
      <?= m_nav_link(u('/seccion/calidad'), 'Calidad', 'seccion.php', $c) ?>
      <?= m_nav_link(u('/seguridad'), 'Seguridad', 'seguridad.php', $c) ?>
      <?= m_nav_link(u('/medioambiente'), 'Medio Ambiente', 'medioambiente.php', $c) ?>
      <?= m_nav_link(u('/salud'), 'Salud Ocupacional', 'salud.php', $c) ?>
      <?= m_nav_link(u('/publicaciones'), 'Publicaciones', 'publicaciones.php', $c) ?>
      <?= m_nav_link(u('/recursos'), 'Recursos', 'recursos.php', $c) ?>
      <?= m_nav_link(u('/legislacion'), 'Legislación', 'seccion.php', $c) ?>
      <?= m_nav_link(u('/clipping'), 'Clipping de Noticias', 'clipping.php', $c) ?>
      <?= m_nav_link(u('/aportar'), 'Aportar contenido', 'aportar.php', $c) ?>
      <div class="border-t border-gray-100 pt-2 mt-2">
        <?= m_nav_link(u('/red'), 'Red Vértice Pro', 'red.php', $c) ?>
        <?= m_nav_link(u('/bolsa'), 'Bolsa de Trabajo', 'bolsa.php', $c) ?>
        <?= m_nav_link(u('/eventos'), 'Eventos', 'eventos.php', $c) ?>
      </div>
      <div class="border-t border-gray-100 pt-3 mt-2 space-y-2">
        <?php if ($current_user): ?>
          <a href="<?= e(u($my_area_url)) ?>" class="block text-center bg-azul text-white font-semibold px-4 py-2 rounded hover:bg-blue-700 transition">Mi cuenta</a>
          <a href="<?= e(u('/notificaciones')) ?>" class="block text-center border border-azul text-azul font-semibold px-4 py-2 rounded hover:bg-blue-50 transition">Notificaciones<?php if ($unread_notifs > 0): ?> (<?= (int)$unread_notifs ?>)<?php endif; ?></a>
          <a href="<?= e(u('/logout')) ?>" class="block text-center text-coral font-semibold px-4 py-2">Cerrar sesión</a>
        <?php else: ?>
          <a href="<?= e(u('/login')) ?>" class="block text-center border border-gray-300 text-gris-oscuro font-semibold px-4 py-2 rounded hover:bg-gris-claro transition">Iniciar sesión</a>
          <a href="<?= e(u('/registro')) ?>" class="block text-center bg-naranja text-white font-semibold px-4 py-2 rounded hover:bg-orange-600 transition">Únete a la red</a>
          <a href="<?= e(u('/registro-organizacion')) ?>" class="block text-center border border-naranja text-naranja font-semibold px-4 py-2 rounded hover:bg-orange-50 transition">Registra tu organización</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main>
