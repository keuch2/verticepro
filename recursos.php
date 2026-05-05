<?php
require_once __DIR__ . '/includes/bootstrap.php';
$res = ResourceRepo::all();
$page_title = 'Recursos Descargables — Vértice Pro'; $page_active = 'recursos.php';
include __DIR__ . '/includes/header.php';
?>
  <section class="bg-gris-claro py-12 px-6">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold">Recursos Descargables</h1>
      <p class="text-gris-oscuro mt-2">Plantillas, checklists y formatos para tu práctica profesional.</p>
    </div>
  </section>
  <section class="max-w-7xl mx-auto px-6 py-10">
    <?php if (!$res): ?>
      <p class="text-gris-oscuro">Próximamente más recursos disponibles para descarga.</p>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($res as $r): ?>
          <article class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="font-bold"><?= e($r['title']) ?></h3>
            <p class="text-sm text-gris-oscuro mt-2"><?= e($r['description']) ?></p>
            <a href="#" class="text-sm text-naranja font-semibold mt-3 inline-block hover:underline">Descargar →</a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
