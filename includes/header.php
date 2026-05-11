<?php
// Uso: antes del include, define $page_title, $page_active (filename o slug lógico), opcionalmente $page_description.
require_once __DIR__ . '/helpers.php';
$page_title = $page_title ?? 'Vértice Pro';
$page_active = $page_active ?? current_page_filename();
$page_description = $page_description ?? 'Plataforma editorial y red profesional para especialistas en calidad, seguridad, salud ocupacional y medio ambiente.';

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

  <header id="main-header" class="sticky top-0 z-50 bg-white transition-shadow duration-200">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
      <a href="<?= e(u('/')) ?>"><img src="<?= e(u('/img/logo.png')) ?>" alt="Vértice Pro" class="h-9 w-auto" /></a>
      <nav class="hidden lg:flex items-center gap-0.5 text-sm font-medium">
        <?= nav_link(u('/'), 'Inicio', 'index.php', $c) ?>
        <?= nav_link(u('/seccion/calidad'), 'Calidad', 'seccion.php', $c) ?>
        <?= nav_link(u('/seguridad'), 'Seguridad', 'seguridad.php', $c) ?>
        <?= nav_link(u('/medioambiente'), 'Medio Ambiente', 'medioambiente.php', $c) ?>
        <?= nav_link(u('/salud'), 'Salud Ocupacional', 'salud.php', $c) ?>
        <div class="relative group">
          <button class="px-3 py-1.5 text-gris-oscuro hover:text-naranja transition duration-150 flex items-center gap-1">
            Biblioteca
            <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div class="absolute top-full left-0 mt-1 w-52 bg-white border border-gray-200 rounded-lg shadow-lg py-1 hidden group-hover:block z-50">
            <a href="<?= e(u('/publicaciones')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition">Publicaciones Técnicas</a>
            <a href="<?= e(u('/recursos')) ?>" class="block px-4 py-2.5 text-sm text-gris-oscuro hover:bg-gris-claro hover:text-naranja transition">Recursos Descargables</a>
          </div>
        </div>
        <div class="border-r border-gray-200 h-5 mx-2"></div>
        <?= nav_link(u('/red'), 'Red de Profesionales', 'red.php', $c) ?>
        <?= nav_link(u('/bolsa'), 'Bolsa de Trabajo', 'bolsa.php', $c) ?>
      </nav>
      <div class="flex items-center gap-3">
        <a href="<?= e(u('/registro')) ?>" class="hidden md:inline-flex bg-naranja text-white text-sm font-semibold px-4 py-2 rounded hover:bg-orange-600 transition duration-150">Únete a la red</a>
        <button id="mobile-menu-btn" aria-expanded="false" class="lg:hidden p-2 text-gris-oscuro hover:text-naranja transition">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
      </div>
    </div>
    <div id="mobile-menu" class="hidden lg:hidden bg-white border-t border-gray-100 px-6 py-4 space-y-2 text-sm font-medium">
      <?= m_nav_link(u('/'), 'Inicio', 'index.php', $c) ?>
      <?= m_nav_link(u('/seccion/calidad'), 'Calidad', 'seccion.php', $c) ?>
      <?= m_nav_link(u('/seguridad'), 'Seguridad', 'seguridad.php', $c) ?>
      <?= m_nav_link(u('/medioambiente'), 'Medio Ambiente', 'medioambiente.php', $c) ?>
      <?= m_nav_link(u('/salud'), 'Salud Ocupacional', 'salud.php', $c) ?>
      <?= m_nav_link(u('/publicaciones'), 'Publicaciones', 'publicaciones.php', $c) ?>
      <?= m_nav_link(u('/recursos'), 'Recursos', 'recursos.php', $c) ?>
      <div class="border-t border-gray-100 pt-2 mt-2">
        <?= m_nav_link(u('/red'), 'Red de Profesionales', 'red.php', $c) ?>
        <?= m_nav_link(u('/bolsa'), 'Bolsa de Trabajo', 'bolsa.php', $c) ?>
      </div>
      <div class="border-t border-gray-100 pt-3 mt-2">
        <a href="<?= e(u('/registro')) ?>" class="block text-center bg-naranja text-white font-semibold px-4 py-2 rounded hover:bg-orange-600 transition">Únete a la red</a>
      </div>
    </div>
  </header>

  <main>
