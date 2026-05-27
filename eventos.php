<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$flash_ok  = flash('eventos_ok');
$flash_err = flash('eventos_err');

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
  <div class="max-w-7xl mx-auto flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="text-3xl font-extrabold">Calendario de Eventos</h1>
      <p class="text-gris-oscuro mt-2">Conferencias, congresos, jornadas y formaciones del sector en Paraguay y la región.</p>
    </div>
    <button type="button" onclick="document.getElementById('propose-event-modal').classList.remove('hidden')" class="bg-naranja text-white font-semibold px-5 py-2.5 rounded hover:bg-orange-600 transition">+ Proponer evento</button>
  </div>
</section>

<?php if ($flash_ok): ?>
  <div class="max-w-7xl mx-auto px-6 mt-6"><div class="bg-verde/10 border border-verde rounded p-4 text-texto"><?= e($flash_ok) ?></div></div>
<?php endif; ?>
<?php if ($flash_err): ?>
  <div class="max-w-7xl mx-auto px-6 mt-6"><div class="bg-red-50 border border-coral rounded p-4 text-coral"><?= e($flash_err) ?></div></div>
<?php endif; ?>

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

<!-- Modal: Proponer evento -->
<div id="propose-event-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5);">
  <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
      <h2 class="text-xl font-extrabold">Proponer un evento</h2>
      <button type="button" onclick="document.getElementById('propose-event-modal').classList.add('hidden')" class="text-gris-oscuro hover:text-coral text-2xl leading-none">×</button>
    </div>
    <form method="post" action="<?= e(u('/eventos-proponer.php')) ?>" class="p-6 space-y-4">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      <p class="text-sm text-gris-oscuro">Tu evento quedará pendiente de revisión. Nuestro equipo lo revisará y, si corresponde, lo publicará en el calendario.</p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Tu nombre *</label>
          <input name="proposer_name" type="text" required class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Tu email *</label>
          <input name="proposer_email" type="email" required class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Título del evento *</label>
        <input name="title" required class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Descripción</label>
        <textarea name="description" rows="3" class="w-full border border-gray-300 rounded px-3 py-2"></textarea>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Fecha y hora *</label>
          <input name="starts_at" type="datetime-local" required class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Finaliza (opcional)</label>
          <input name="ends_at" type="datetime-local" class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Modalidad</label>
          <select name="modality" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
            <option value="">—</option>
            <?php foreach (['presencial','virtual','hibrido'] as $m): ?>
              <option value="<?= $m ?>"><?= ucfirst($m) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Disciplina</label>
          <select name="discipline_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
            <option value="">—</option>
            <?php foreach ($disciplines as $d): ?>
              <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Ubicación</label>
        <input name="location" placeholder="Asunción — Centro de Convenciones" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">URL externa (web del evento, opcional)</label>
        <input name="url" type="url" placeholder="https://..." class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
        <button type="button" onclick="document.getElementById('propose-event-modal').classList.add('hidden')" class="text-gris-oscuro hover:text-coral text-sm">Cancelar</button>
        <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Enviar para revisión</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
