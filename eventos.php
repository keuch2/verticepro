<?php
require_once __DIR__ . '/includes/bootstrap.php';

$year  = isset($_GET['year']) && ctype_digit((string)$_GET['year']) ? (int)$_GET['year'] : null;
$month = isset($_GET['month']) && ctype_digit((string)$_GET['month']) && (int)$_GET['month'] >= 1 && (int)$_GET['month'] <= 12 ? (int)$_GET['month'] : null;
$disc  = $_GET['discipline'] ?? '';

$events = EventRepo::all(['year' => $year, 'month' => $month, 'discipline' => $disc]);
$years  = EventRepo::years();
if (!$years) { $years = [(int)date('Y')]; }
$disciplines = SectionRepo::disciplines();

$months_es = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre',
];

$page_title = 'Calendario de Eventos — Vértice Pro';
$page_active = 'eventos.php';
include __DIR__ . '/includes/header.php';
?>
<section class="bg-gris-claro py-12 px-6">
  <div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-extrabold">Calendario de Eventos</h1>
    <p class="text-gris-oscuro mt-2">Conferencias, congresos, jornadas y formaciones del sector en Paraguay y la región.</p>
  </div>
</section>

<section class="max-w-7xl mx-auto px-6 py-8">
  <form method="get" class="flex flex-wrap gap-3 items-end bg-white border border-gray-200 rounded p-4 mb-6">
    <div>
      <label class="block text-xs font-semibold text-gris-oscuro mb-1">Año</label>
      <select name="year" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        <option value="">Todos</option>
        <?php foreach ($years as $y): ?>
          <option value="<?= (int)$y ?>" <?= $year === (int)$y ? 'selected' : '' ?>><?= (int)$y ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold text-gris-oscuro mb-1">Mes</label>
      <select name="month" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        <option value="">Todos</option>
        <?php foreach ($months_es as $m => $name): ?>
          <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>><?= e($name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold text-gris-oscuro mb-1">Disciplina</label>
      <select name="discipline" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        <option value="">Todas</option>
        <?php foreach ($disciplines as $d): ?>
          <option value="<?= e($d['slug']) ?>" <?= $disc === $d['slug'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="bg-azul text-white font-semibold px-4 py-2 rounded text-sm">Filtrar</button>
    <a href="<?= e(u('/eventos')) ?>" class="text-sm text-naranja font-semibold hover:underline">Limpiar</a>
  </form>

  <?php if (!$events): ?>
    <div class="bg-gris-claro border border-gray-200 rounded p-8 text-center text-gris-oscuro">
      No hay eventos para los filtros seleccionados.
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($events as $ev):
        $color = section_color($ev['discipline_slug'] ?: 'seguridad');
        $ts = strtotime($ev['starts_at']);
      ?>
        <article class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition flex">
          <div class="bg-<?= e($color) ?> text-white px-4 py-3 text-center min-w-[90px] flex flex-col justify-center">
            <p class="text-xs uppercase font-bold opacity-90"><?= e(mb_strtolower($months_es[(int)date('n', $ts)] ?? '')) ?></p>
            <p class="text-3xl font-extrabold leading-none"><?= e(date('d', $ts)) ?></p>
            <p class="text-xs opacity-90 mt-0.5"><?= e(date('Y', $ts)) ?></p>
          </div>
          <div class="flex-1 p-4">
            <?php if ($ev['discipline_name']): ?>
              <p class="text-xs font-semibold uppercase text-<?= e($color) ?>"><?= e($ev['discipline_name']) ?></p>
            <?php endif; ?>
            <h3 class="font-bold text-texto leading-snug mt-1"><?= e($ev['title']) ?></h3>
            <p class="text-xs text-gris-oscuro mt-1">
              <?= e(ucfirst($ev['modality'] ?? '')) ?><?= $ev['location'] ? ' · ' . e($ev['location']) : '' ?>
            </p>
            <?php if ($ev['description']): ?>
              <p class="text-sm text-gris-oscuro mt-2"><?= e(mb_strimwidth($ev['description'], 0, 120, '…')) ?></p>
            <?php endif; ?>
            <?php if ($ev['url']): ?>
              <a href="<?= e($ev['url']) ?>" target="_blank" rel="noopener" class="text-sm text-naranja font-semibold hover:underline mt-3 inline-block">Más info →</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
