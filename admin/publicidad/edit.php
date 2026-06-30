<?php
require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../../includes/ads.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$a = $id ? Ads::find($id) : null;
$is_new = !$a;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'name'         => trim(post('name','')),
        'slot'         => post('slot','header_top'),
        'target_url'   => trim(post('target_url','')) ?: null,
        'alt'          => trim(post('alt','')) ?: null,
        'html_content' => trim(post('html_content','')) ?: null,
        'sort_order'   => (int)post('sort_order', 0),
        'starts_at'    => post('starts_at') ?: null,
        'ends_at'      => post('ends_at') ?: null,
        'status'       => post('status','active'),
    ];
    if (!$data['name']) { flash('err','Nombre requerido'); redirect('/admin/publicidad/edit.php' . ($id ? "?id=$id" : '')); }
    if (!in_array($data['slot'], ['header_top','sidebar','between_articles','footer','home_hero'], true)) { flash('err','Slot inválido'); redirect('/admin/publicidad/edit.php' . ($id ? "?id=$id" : '')); }

    // Coherencia de fechas (ambas opcionales).
    if ($data['starts_at'] && $data['ends_at'] && strtotime($data['ends_at']) < strtotime($data['starts_at'])) {
        flash_old(); flash('err','La fecha "Hasta" no puede ser anterior a "Desde"');
        redirect('/admin/publicidad/edit.php' . ($id ? "?id=$id" : ''));
    }

    if (!empty($_FILES['image']['name'])) {
        $rel = upload_image($_FILES['image'], 'ads', $data['name']);
        if ($rel) $data['image_path'] = $rel;
    }

    // Un banner sin imagen ni HTML no muestra nada: exigimos al menos uno
    // (considerando una imagen ya cargada al editar).
    if (empty($data['image_path']) && empty($a['image_path']) && empty($data['html_content'])) {
        flash_old(); flash('err','El banner necesita una imagen o contenido HTML');
        redirect('/admin/publicidad/edit.php' . ($id ? "?id=$id" : ''));
    }

    if ($is_new) { $id = DB::insert('ads', $data); flash('ok','Banner creado'); }
    else         { DB::update('ads', $data, ['id'=>$id]); flash('ok','Banner actualizado'); }
    redirect('/admin/publicidad/edit.php?id=' . $id);
}

if (isset($_GET['delete']) && $id) {
    csrf_check();
    DB::delete('ads', ['id'=>$id]);
    flash('ok','Banner eliminado');
    redirect('/admin/publicidad/');
}

$page_title = $is_new ? 'Nuevo banner' : 'Editar banner';
// Tras un error de validación, repoblar con lo que el usuario escribió.
$f = has_old() ? $_SESSION['old'] : ($a ?? []);
old_clear();
include __DIR__ . '/../_layout.php';

$slot_labels = [
    'header_top'        => 'Sobre el header (banner superior, ancho completo)',
    'home_hero'         => 'Bajo el hero del inicio',
    'sidebar'           => 'Aside / sidebar (secciones editoriales)',
    'between_articles'  => 'Entre cards de artículos',
    'footer'            => 'Sobre el footer',
];
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new): ?><a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar banner?')">Eliminar</a><?php endif; ?>
    <a href="<?= e(u('/admin/publicidad/')) ?>" class="btn secondary">Volver</a>
  </div>
</div>
<form method="post" enctype="multipart/form-data" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div class="form-grid cols-2">
      <div><label>Nombre interno</label><input name="name" required value="<?= e($f['name'] ?? '') ?>" placeholder="Ej: Banner home Q1 2026" /></div>
      <div>
        <label>Slot (dónde aparece)</label>
        <select name="slot">
          <?php foreach ($slot_labels as $v => $l): ?>
            <option value="<?= e($v) ?>" <?= ($f['slot'] ?? 'header_top') === $v ? 'selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div>
      <label>Imagen del banner</label>
      <?php if (!empty($a['image_path'])): ?><div><img src="<?= e(img_url($a['image_path'])) ?>" style="max-width:400px;border:1px solid #ddd;" /></div><?php endif; ?>
      <input type="file" name="image" accept="image/*" />
      <p style="font-size:12px;color:#54636F;margin-top:4px;">Recomendado: JPG/PNG/WebP, ancho 728–1200px para banner top y footer; 300×250 para sidebar.</p>
    </div>

    <div class="form-grid cols-2">
      <div><label>URL de destino</label><input name="target_url" value="<?= e($f['target_url'] ?? '') ?>" placeholder="https://..." /></div>
      <div><label>Texto alternativo</label><input name="alt" value="<?= e($f['alt'] ?? '') ?>" /></div>
    </div>

    <div>
      <label>HTML libre (alternativa a la imagen)</label>
      <textarea name="html_content" rows="4"><?= e($f['html_content'] ?? '') ?></textarea>
      <p style="font-size:12px;color:#54636F;margin-top:4px;">Si subes una imagen, esta sección se ignora. Útil para banners HTML/JS embed (AdSense, etc.).</p>
    </div>

    <div class="form-grid cols-2">
      <?php $fmt_dt = fn($v) => !empty($v) ? date('Y-m-d\TH:i', strtotime($v)) : ''; ?>
      <div><label>Desde</label><input type="datetime-local" name="starts_at" value="<?= e($fmt_dt($f['starts_at'] ?? '')) ?>" /></div>
      <div><label>Hasta</label><input type="datetime-local" name="ends_at" value="<?= e($fmt_dt($f['ends_at'] ?? '')) ?>" /></div>
    </div>

    <div class="form-grid cols-2">
      <div>
        <label>Estado</label>
        <?php $cur_status = $f['status'] ?? 'active'; ?>
        <select name="status">
          <option value="active"  <?= $cur_status === 'active'  ? 'selected' : '' ?>>active</option>
          <option value="paused"  <?= $cur_status === 'paused'  ? 'selected' : '' ?>>paused</option>
        </select>
      </div>
      <div><label>Orden (menor = primero)</label><input type="number" name="sort_order" value="<?= (int)($f['sort_order'] ?? 0) ?>" /></div>
    </div>

    <button class="btn" type="submit"><?= $is_new ? 'Crear banner' : 'Guardar' ?></button>
  </div>
</form>
<?php include __DIR__ . '/../_layout_end.php'; ?>
