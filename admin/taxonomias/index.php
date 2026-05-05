<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Taxonomías — Admin';

$tables = [
    'disciplines'         => ['label' => 'Disciplinas', 'cols' => ['slug','name']],
    'sectors'             => ['label' => 'Sectores', 'cols' => ['slug','name']],
    'countries'           => ['label' => 'Países', 'cols' => ['slug','name']],
    'cities'              => ['label' => 'Ciudades', 'cols' => ['slug','name','country_id']],
    'professional_types'  => ['label' => 'Tipos de profesional', 'cols' => ['slug','name']],
    'publication_types'   => ['label' => 'Tipos de publicación', 'cols' => ['slug','name']],
    'sections'            => ['label' => 'Secciones', 'cols' => ['slug','name','color_token']],
];

$current = $_GET['t'] ?? 'disciplines';
if (!isset($tables[$current])) $current = 'disciplines';
$meta = $tables[$current];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $action = post('action');
    $table = post('table');
    if (!isset($tables[$table])) { flash('err','Tabla inválida'); redirect('/admin/taxonomias/'); }
    $cols = $tables[$table]['cols'];
    $data = [];
    foreach ($cols as $c) { $data[$c] = post($c) ?: null; }
    if ($action === 'create') {
        try { DB::insert($table, $data); flash('ok','Creado'); } catch (\Throwable $e) { flash('err',$e->getMessage()); }
    } elseif ($action === 'update') {
        $pk = $table === 'sections' ? 'slug' : 'id';
        DB::update($table, $data, [$pk => post($pk)]);
        flash('ok','Actualizado');
    } elseif ($action === 'delete') {
        $pk = $table === 'sections' ? 'slug' : 'id';
        try { DB::delete($table, [$pk => post($pk)]); flash('ok','Eliminado'); } catch (\Throwable $e) { flash('err','No se pudo eliminar (en uso)'); }
    }
    redirect('/admin/taxonomias/?t=' . $table);
}

$pk = $current === 'sections' ? 'slug' : 'id';
$rows = DB::all("SELECT * FROM $current ORDER BY " . ($current === 'sections' ? 'sort_order' : 'name'));
include __DIR__ . '/../_layout.php';
?>
<h1>Taxonomías</h1>
<div class="card" style="padding:0;">
  <div style="padding:10px 14px;border-bottom:1px solid #e5e7eb;display:flex;flex-wrap:wrap;gap:16px;">
    <?php foreach ($tables as $k => $v): ?>
      <a href="?t=<?= e($k) ?>" style="<?= $current===$k?'font-weight:700;color:#F58220;':'' ?>"><?= e($v['label']) ?></a>
    <?php endforeach; ?>
  </div>
  <table>
    <thead><tr><?php foreach ($meta['cols'] as $c): ?><th><?= e($c) ?></th><?php endforeach; ?><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <form method="post" style="display:contents;">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
          <input type="hidden" name="action" value="update" />
          <input type="hidden" name="table" value="<?= e($current) ?>" />
          <input type="hidden" name="<?= $pk ?>" value="<?= e($r[$pk]) ?>" />
          <?php foreach ($meta['cols'] as $c): ?>
            <td>
              <?php if ($c === 'country_id'): ?>
                <select name="<?= e($c) ?>"><?= opts(SectionRepo::countries(),'id','name',$r[$c]) ?></select>
              <?php else: ?>
                <input name="<?= e($c) ?>" value="<?= e($r[$c] ?? '') ?>" />
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
          <td style="text-align:right;white-space:nowrap;">
            <button class="btn secondary" type="submit">Guardar</button>
            <button class="btn danger" type="submit" formaction="?t=<?= e($current) ?>" onclick="this.form.action.value='delete';return confirm('¿Eliminar?')">×</button>
          </td>
        </form>
      </tr>
      <?php endforeach; ?>
      <tr style="background:#fafafa;">
        <form method="post" style="display:contents;">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
          <input type="hidden" name="action" value="create" />
          <input type="hidden" name="table" value="<?= e($current) ?>" />
          <?php foreach ($meta['cols'] as $c): ?>
            <td>
              <?php if ($c === 'country_id'): ?>
                <select name="<?= e($c) ?>"><option value="">—</option><?= opts(SectionRepo::countries(),'id','name') ?></select>
              <?php else: ?>
                <input name="<?= e($c) ?>" placeholder="Nuevo <?= e($c) ?>" />
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
          <td style="text-align:right;"><button class="btn" type="submit">+ Añadir</button></td>
        </form>
      </tr>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
