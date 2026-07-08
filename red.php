<?php
require_once __DIR__ . '/includes/bootstrap.php';
$featured_pros = ProfessionalRepo::featured(4);
$page_title = 'Red Vértice Pro — Vértice Pro'; $page_active = 'red.php';
include __DIR__ . '/includes/header.php';
?>
  <section class="relative min-h-[380px] flex items-center px-6" style="background: linear-gradient(135deg, rgba(0,120,212,0.9), rgba(24,50,70,0.8)); color: #fff;">
    <div class="max-w-6xl mx-auto">
      <h1 class="text-4xl font-extrabold">Red Vértice Pro</h1>
      <p class="text-lg mt-3 opacity-90 max-w-2xl">Conecta con consultores, auditores y expertos verificados en calidad, seguridad, salud ocupacional y medio ambiente en Paraguay.</p>
      <div class="flex flex-wrap gap-3 mt-6">
        <a href="<?= e(u('/registro')) ?>" class="bg-naranja text-white font-semibold px-5 py-2 rounded hover:bg-orange-600 transition">Únete a la red</a>
        <a href="<?= e(u('/directorio')) ?>" class="bg-white text-azul font-semibold px-5 py-2 rounded hover:bg-blue-50">Explorar directorio</a>
        <a href="<?= e(u('/organizaciones')) ?>" class="border border-white text-white font-semibold px-5 py-2 rounded hover:bg-white hover:text-azul transition">Ver organizaciones</a>
      </div>
    </div>
  </section>

  <section class="max-w-6xl mx-auto px-6 py-14">
    <h2 class="text-2xl font-extrabold mb-6">Profesionales destacados</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
      <?php foreach ($featured_pros as $p): $pc = section_color(ProfessionalRepo::primaryDisciplineSlug((int)$p['id'])); ?>
        <a href="<?= e(profile_url($p)) ?>" class="bg-white rounded-lg border border-gray-200 p-5 text-center hover:shadow-md transition">
          <div class="w-16 h-16 rounded-full bg-<?= e($pc) ?> mx-auto flex items-center justify-center text-white font-bold text-xl">
            <?= e(mb_substr($p['name'], 0, 1)) ?><?= e(mb_substr(strstr($p['name'], ' ') ?: ' ', 1, 1)) ?>
          </div>
          <h3 class="font-bold mt-3"><?= e($p['name']) ?></h3>
          <p class="text-xs text-gris-oscuro mt-1 leading-snug"><?= e($p['title']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="bg-gris-claro py-12 px-6">
    <div class="max-w-4xl mx-auto text-center">
      <h2 class="text-2xl font-extrabold">¿Eres profesional del sector?</h2>
      <p class="text-gris-oscuro mt-2">Crea tu perfil gratuito y aparece ante organizaciones y colegas en Paraguay. Las solicitudes se revisan antes de publicarse.</p>
      <a href="<?= e(u('/registro')) ?>" class="inline-block mt-5 bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition">Crear mi perfil profesional</a>
    </div>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
