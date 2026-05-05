<?php
// Public pages bootstrap — include once at the top of every .php page.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/repositories/Article.php';
require_once __DIR__ . '/repositories/Professional.php';
require_once __DIR__ . '/repositories/Company.php';
require_once __DIR__ . '/repositories/JobOffer.php';
require_once __DIR__ . '/repositories/Publication.php';
require_once __DIR__ . '/repositories/Section.php';

// A minimal helper for section pills color mapping (used across pages).
function section_color(string $slug): string {
    return match ($slug) {
        'seguridad', 'salud', 'calidad' => 'naranja',
        'ergonomia' => 'azul',
        'medioambiente', 'medio-ambiente' => 'verde',
        'legislacion' => 'gris-oscuro',
        default => 'naranja',
    };
}

function article_url(array $a): string { return u('/articulo/' . ($a['slug'] ?? '')); }
function profile_url(array $p): string { return u('/perfil/' . ($p['slug'] ?? '')); }
function company_url(array $c): string { return u('/empresa/' . ($c['slug'] ?? '')); }
function section_url(string $slug): string { return u('/seccion/' . $slug); }
