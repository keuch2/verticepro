<?php
require_once __DIR__ . '/../_helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = $id ? ArticleRepo::find($id) : null;
$is_new = !$article;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'slug'          => slugify(post('slug') ?: post('title')),
        'title'         => trim(post('title', '')),
        'subtitle'      => trim(post('subtitle', '')) ?: null,
        'excerpt'       => trim(post('excerpt', '')) ?: null,
        'body'          => post('body', ''),
        'author_id'     => post_int('author_id'),
        'discipline_id' => post_int('discipline_id'),
        'section_slug'  => post('section_slug') ?: null,
        'read_time_min' => post_int('read_time_min') ?? 5,
        'featured'      => post_bool('featured'),
        'status'        => post('status', 'draft'),
        'published_at'  => post('published_at') ?: null,
    ];
    if (!$data['title']) { flash('err','Título requerido'); redirect('/admin/articulos/edit.php' . ($id?"?id=$id":'')); }

    // Handle image upload
    if (!empty($_FILES['hero_image']['name'])) {
        $rel = upload_image($_FILES['hero_image'], 'articles', $data['slug']);
        if ($rel) { $data['hero_image'] = $rel; $data['thumb_image'] = $rel; }
    }

    if ($is_new) {
        $id = DB::insert('articles', $data);
        flash('ok', 'Artículo creado');
    } else {
        DB::update('articles', $data, ['id' => $id]);
        flash('ok', 'Artículo actualizado');
    }

    // Tags
    DB::run('DELETE FROM article_tags WHERE article_id = ?', [$id]);
    foreach (array_filter(array_map('trim', explode(',', post('tags', '')))) as $t) {
        try { DB::insert('article_tags', ['article_id' => $id, 'tag' => $t]); } catch (\Throwable $e) {}
    }
    redirect('/admin/articulos/edit.php?id=' . $id);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete']) && $id) {
    csrf_check();
    DB::delete('articles', ['id' => $id]);
    flash('ok', 'Artículo eliminado');
    redirect('/admin/articulos/');
}

$tags_csv = $id ? implode(', ', ArticleRepo::tags($id)) : '';
$page_title = $is_new ? 'Nuevo artículo' : 'Editar artículo';
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new): ?>
      <a href="<?= e(u('/articulo/' . $article['slug'])) ?>" target="_blank" class="btn secondary">Ver publicado</a>
      <a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar definitivamente?')">Eliminar</a>
    <?php endif; ?>
    <a href="<?= e(u('/admin/articulos/')) ?>" class="btn secondary">Volver</a>
  </div>
</div>

<form method="post" enctype="multipart/form-data" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div>
      <label>Título</label>
      <input name="title" type="text" required value="<?= e($article['title'] ?? '') ?>" />
    </div>
    <div class="form-grid cols-2">
      <div>
        <label>Slug (URL)</label>
        <input name="slug" type="text" value="<?= e($article['slug'] ?? '') ?>" placeholder="se generará desde el título" />
      </div>
      <div>
        <label>Estado</label>
        <select name="status">
          <option value="draft" <?= ($article['status']??'')==='draft'?'selected':'' ?>>Borrador</option>
          <option value="published" <?= ($article['status']??'')==='published'?'selected':'' ?>>Publicado</option>
        </select>
      </div>
    </div>
    <div>
      <label>Subtítulo</label>
      <input name="subtitle" type="text" value="<?= e($article['subtitle'] ?? '') ?>" />
    </div>
    <div>
      <label>Extracto (resumen)</label>
      <textarea name="excerpt" rows="3"><?= e($article['excerpt'] ?? '') ?></textarea>
    </div>
    <div>
      <label>Cuerpo</label>
      <textarea id="body-editor" name="body" rows="16"><?= e($article['body'] ?? '') ?></textarea>
    </div>
    <div class="form-grid cols-2">
      <div>
        <label>Sección</label>
        <select name="section_slug">
          <option value="">—</option>
          <?= opts(SectionRepo::all(), 'slug', 'name', $article['section_slug'] ?? '') ?>
        </select>
      </div>
      <div>
        <label>Disciplina</label>
        <select name="discipline_id">
          <option value="">—</option>
          <?php foreach (SectionRepo::disciplines() as $d): ?>
            <option value="<?= (int)$d['id'] ?>" <?= ($article['discipline_id']??0) == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-grid cols-2">
      <div>
        <label>Autor</label>
        <select name="author_id">
          <option value="">—</option>
          <?php foreach (DB::all("SELECT id, name FROM users WHERE role IN ('admin','author') ORDER BY name") as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= ($article['author_id']??0) == $u['id'] ? 'selected':'' ?>><?= e($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Tiempo de lectura (min)</label>
        <input name="read_time_min" type="number" min="1" max="120" value="<?= e($article['read_time_min'] ?? '5') ?>" />
      </div>
    </div>
    <div class="form-grid cols-2">
      <div>
        <label>Fecha de publicación</label>
        <input name="published_at" type="datetime-local" value="<?= $article ? date('Y-m-d\TH:i', strtotime($article['published_at'] ?? 'now')) : '' ?>" />
      </div>
      <div>
        <label style="display:flex;align-items:center;gap:8px;margin-top:24px;">
          <input type="checkbox" name="featured" value="1" <?= !empty($article['featured']) ? 'checked':'' ?> style="width:auto;"/> Destacado (home hero)
        </label>
      </div>
    </div>
    <div>
      <label>Tags (separadas por coma)</label>
      <input name="tags" type="text" value="<?= e($tags_csv) ?>" />
    </div>
    <div>
      <label>Imagen hero</label>
      <?php if (!empty($article['hero_image'])): ?>
        <div style="margin-bottom:8px;"><img src="<?= e(img_url($article['hero_image'])) ?>" style="max-height:120px;border-radius:4px;" /></div>
      <?php endif; ?>
      <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" />
    </div>
    <div>
      <button class="btn" type="submit"><?= $is_new ? 'Crear artículo' : 'Guardar cambios' ?></button>
    </div>
  </div>
</form>

<script src="<?= e(u('/admin/assets/vendor/tinymce/js/tinymce/tinymce.min.js')) ?>"></script>
<script>
tinymce.init({
  selector: '#body-editor',
  license_key: 'gpl',
  promotion: false,
  branding: false,
  height: 520,
  menubar: 'file edit view insert format tools table help',
  plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
  toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | removeformat | code fullscreen',
  content_style: "body{font-family:'Barlow',sans-serif;font-size:15px;line-height:1.7;color:#1A1A1A} blockquote{border-left:4px solid #F58220;padding-left:1rem;color:#54636F;font-style:italic}",
  block_formats: 'Párrafo=p; Título 2=h2; Título 3=h3; Cita=blockquote',
  images_upload_url: '<?= e(u('/admin/articulos/upload_image.php')) ?>',
  automatic_uploads: true,
  images_reuse_filename: true,
  relative_urls: false,
  convert_urls: false,
});
</script>
<?php include __DIR__ . '/../_layout_end.php'; ?>
