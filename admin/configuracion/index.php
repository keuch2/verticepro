<?php
require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../../includes/settings.php';

$tab = $_GET['tab'] ?? 'marca';
$valid_tabs = ['marca', 'contacto', 'redes', 'smtp', 'seo'];
if (!in_array($tab, $valid_tabs, true)) $tab = 'marca';

$test_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action', 'save');

    if ($action === 'save') {
        $allowed = match ($tab) {
            'marca'    => ['site.name', 'site.tagline'],
            'contacto' => ['contact.email', 'contact.phone', 'contact.address'],
            'redes'    => ['social.linkedin', 'social.twitter', 'social.youtube', 'social.facebook', 'social.instagram'],
            'smtp'     => ['smtp.enabled', 'smtp.host', 'smtp.port', 'smtp.user', 'smtp.pass', 'smtp.from_email', 'smtp.from_name', 'smtp.encryption'],
            'seo'      => ['seo.meta_description'],
            default    => [],
        };
        foreach ($allowed as $k) {
            if ($k === 'smtp.enabled') {
                Settings::set($k, !empty($_POST['smtp.enabled']) ? '1' : '0');
                continue;
            }
            if (array_key_exists($k, $_POST)) {
                Settings::set($k, trim((string)$_POST[$k]));
            }
        }
        flash('ok', 'Configuración guardada.');
        redirect('/admin/configuracion/?tab=' . $tab);
    }

    if ($action === 'test_smtp') {
        $to = trim(post('test_to', ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $test_result = ['ok' => false, 'msg' => 'Email destino inválido.'];
        } else {
            require_once __DIR__ . '/../../includes/notifications.php';
            $sent = Notify::emailOnly($to, 'Test', 1, 'Vértice Pro — Test SMTP', "Este es un correo de prueba enviado desde la configuración del admin.\n\nSi lo recibes, el envío de emails está funcionando correctamente.", null);
            $test_result = $sent
                ? ['ok' => true,  'msg' => 'Email de prueba enviado a ' . $to . '. Revisa la bandeja (y carpeta de spam).']
                : ['ok' => false, 'msg' => 'No se pudo enviar el email de prueba. Revisa la configuración SMTP.'];
        }
    }
}

$page_title = 'Configuración — Admin';
include __DIR__ . '/../_layout.php';

function cfg_tab(string $t, string $current, string $label): string {
    $cls = $t === $current ? 'border-naranja text-naranja' : 'border-transparent text-gris-oscuro hover:text-naranja';
    return '<a href="?tab=' . $t . '" class="px-1 py-3 border-b-2 ' . $cls . ' font-semibold text-sm transition">' . e($label) . '</a>';
}
?>
<div class="toolbar"><h1 style="margin:0;">Configuración del sitio</h1></div>

<nav style="display:flex; gap:24px; border-bottom:1px solid #e5e5e5; margin-bottom:24px;">
  <?= cfg_tab('marca',    $tab, 'Marca') ?>
  <?= cfg_tab('contacto', $tab, 'Contacto') ?>
  <?= cfg_tab('redes',    $tab, 'Redes sociales') ?>
  <?= cfg_tab('smtp',     $tab, 'SMTP / Email') ?>
  <?= cfg_tab('seo',      $tab, 'SEO') ?>
</nav>

<?php if ($test_result): ?>
  <div class="flash <?= $test_result['ok'] ? 'ok' : 'err' ?>"><?= e($test_result['msg']) ?></div>
<?php endif; ?>

<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <input type="hidden" name="action" value="save" />
  <div class="form-grid">

  <?php if ($tab === 'marca'): ?>
    <div><label>Nombre del sitio</label><input name="site.name" value="<?= e(setting('site.name', '')) ?>" /></div>
    <div><label>Tagline / descripción corta</label><textarea name="site.tagline" rows="3"><?= e(setting('site.tagline', '')) ?></textarea></div>

  <?php elseif ($tab === 'contacto'): ?>
    <div class="form-grid cols-2">
      <div><label>Email de contacto</label><input type="email" name="contact.email" value="<?= e(setting('contact.email', '')) ?>" placeholder="contacto@verticepro.com.py" /></div>
      <div><label>Teléfono</label><input name="contact.phone" value="<?= e(setting('contact.phone', '')) ?>" placeholder="+595 21 XXX XXX" /></div>
    </div>
    <div><label>Dirección</label><input name="contact.address" value="<?= e(setting('contact.address', '')) ?>" placeholder="Asunción, Paraguay" /></div>

  <?php elseif ($tab === 'redes'): ?>
    <div class="form-grid cols-2">
      <div><label>LinkedIn (URL)</label><input name="social.linkedin" value="<?= e(setting('social.linkedin', '')) ?>" placeholder="https://linkedin.com/company/verticepro" /></div>
      <div><label>Twitter / X (URL)</label><input name="social.twitter" value="<?= e(setting('social.twitter', '')) ?>" placeholder="https://x.com/verticepro" /></div>
      <div><label>Facebook (URL)</label><input name="social.facebook" value="<?= e(setting('social.facebook', '')) ?>" placeholder="https://facebook.com/verticepro" /></div>
      <div><label>Instagram (URL)</label><input name="social.instagram" value="<?= e(setting('social.instagram', '')) ?>" placeholder="https://instagram.com/verticepro" /></div>
      <div><label>YouTube (URL)</label><input name="social.youtube" value="<?= e(setting('social.youtube', '')) ?>" placeholder="https://youtube.com/@verticepro" /></div>
    </div>
    <p style="color:#54636F;font-size:13px;">Solo aparecen en el footer las redes con URL definida. Dejar en blanco para ocultar.</p>

  <?php elseif ($tab === 'smtp'): ?>
    <div>
      <label style="display:flex;gap:8px;align-items:center;font-weight:600;">
        <input type="checkbox" name="smtp.enabled" value="1" <?= setting('smtp.enabled') === '1' ? 'checked' : '' ?> style="width:auto;" />
        Usar SMTP en lugar del mail() del sistema
      </label>
      <p style="color:#54636F;font-size:13px;margin-top:6px;">Si está desactivado, los emails se envían con la función mail() de PHP (depende del MTA local). Si activas SMTP, configura los datos abajo.</p>
    </div>
    <div class="form-grid cols-2">
      <div><label>Host</label><input name="smtp.host" value="<?= e(setting('smtp.host', '')) ?>" placeholder="smtp.tudominio.com" /></div>
      <div><label>Puerto</label><input name="smtp.port" value="<?= e(setting('smtp.port', '587')) ?>" placeholder="587" /></div>
      <div><label>Usuario</label><input name="smtp.user" value="<?= e(setting('smtp.user', '')) ?>" /></div>
      <div><label>Contraseña</label><input type="password" name="smtp.pass" value="<?= e(setting('smtp.pass', '')) ?>" /></div>
      <div><label>Email "From"</label><input type="email" name="smtp.from_email" value="<?= e(setting('smtp.from_email', '')) ?>" placeholder="no-reply@verticepro.com.py" /></div>
      <div><label>Nombre "From"</label><input name="smtp.from_name" value="<?= e(setting('smtp.from_name', 'Vértice Pro')) ?>" /></div>
      <div>
        <label>Encriptación</label>
        <select name="smtp.encryption">
          <?php foreach (['', 'tls', 'ssl'] as $enc): $label = $enc === '' ? 'Ninguna' : strtoupper($enc); ?>
            <option value="<?= e($enc) ?>" <?= setting('smtp.encryption', 'tls') === $enc ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

  <?php elseif ($tab === 'seo'): ?>
    <div><label>Meta description (página de inicio)</label><textarea name="seo.meta_description" rows="3"><?= e(setting('seo.meta_description', '')) ?></textarea></div>
  <?php endif; ?>

    <button class="btn" type="submit">Guardar cambios</button>
  </div>
</form>

<?php if ($tab === 'smtp'): ?>
<form method="post" class="card" style="margin-top:24px;">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <input type="hidden" name="action" value="test_smtp" />
  <h2 style="margin-top:0;">Probar envío</h2>
  <p style="color:#54636F;font-size:13px;">Envía un email de prueba con la configuración actual.</p>
  <div class="form-grid cols-2">
    <div><label>Enviar a</label><input type="email" name="test_to" required placeholder="test@ejemplo.com" /></div>
  </div>
  <button class="btn secondary" type="submit">Enviar email de prueba</button>
</form>
<?php endif; ?>

<?php include __DIR__ . '/../_layout_end.php'; ?>
