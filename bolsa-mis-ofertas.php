<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Token simple firmado con secret del config (caduca en 24h).
function sign_company_token(int $company_id, string $email, string $secret): string {
    $exp = time() + 86400;
    $payload = $company_id . '|' . $email . '|' . $exp;
    $sig = hash_hmac('sha256', $payload, $secret);
    return rtrim(strtr(base64_encode($payload . '|' . $sig), '+/', '-_'), '=');
}
function verify_company_token(string $token, string $secret): ?array {
    $raw = base64_decode(strtr($token, '-_', '+/'));
    if (!$raw) return null;
    $parts = explode('|', $raw);
    if (count($parts) !== 4) return null;
    [$cid, $email, $exp, $sig] = $parts;
    $check = hash_hmac('sha256', $cid . '|' . $email . '|' . $exp, $secret);
    if (!hash_equals($check, $sig)) return null;
    if ((int)$exp < time()) return null;
    return ['company_id' => (int)$cid, 'email' => $email];
}

$secret = cfg()['app_secret'] ?? (cfg()['db']['pass'] ?? '') . '::vertice-pro';
$company = null;
$sent = false;
$err = null;

// 1) Solicitud de link
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Email inválido.';
    } else {
        $c = DB::one('SELECT id, name, email, notifications_opt_in FROM companies WHERE email = ? AND status = "active" LIMIT 1', [$email]);
        // Siempre fingimos éxito (no revelar si el email existe).
        if ($c) {
            $token = sign_company_token((int)$c['id'], $c['email'], $secret);
            $link = u('/bolsa-mis-ofertas?t=' . $token);
            Notify::emailOnly($c['email'], $c['name'], 1, 'Acceso a tus ofertas y candidatos', "Hola " . $c['name'] . ",\n\nUsa este enlace (válido 24h) para revisar las ofertas publicadas por tu empresa y los interesados:", $link);
        }
        $sent = true;
    }
}

// 2) Acceso con token
if (!empty($_GET['t'])) {
    $payload = verify_company_token($_GET['t'], $secret);
    if ($payload) {
        $company = DB::one('SELECT * FROM companies WHERE id = ? LIMIT 1', [$payload['company_id']]);
    } else {
        $err = 'El enlace es inválido o expiró. Solicita uno nuevo.';
    }
}

$page_title = 'Mis ofertas — Vértice Pro';
$page_active = 'bolsa-mis-ofertas.php';
include __DIR__ . '/includes/header.php';
?>
<section class="max-w-4xl mx-auto px-6 py-14">
  <h1 class="text-3xl font-extrabold">Mis ofertas e interesados</h1>
  <p class="text-gris-oscuro mt-2">Si tu empresa publicó ofertas en la Bolsa, aquí puedes consultar quién ha marcado interés.</p>

  <?php if ($err): ?>
    <div class="bg-red-50 border border-coral text-coral rounded p-4 mt-6 text-sm"><?= e($err) ?></div>
  <?php endif; ?>

  <?php if ($company): ?>
    <?php
      $offers = DB::all('SELECT * FROM job_offers WHERE company_id = ? ORDER BY created_at DESC', [(int)$company['id']]);
    ?>
    <div class="mt-6 bg-gris-claro border border-gray-200 rounded p-4 text-sm text-gris-oscuro">
      Sesión segura para <strong><?= e($company['name']) ?></strong> (<?= e($company['email']) ?>). Este acceso es válido por 24 horas.
    </div>
    <?php if (!$offers): ?>
      <div class="mt-6 bg-white border border-gray-200 rounded p-6 text-gris-oscuro">
        Todavía no publicaste ofertas. <a href="<?= e(u('/bolsa-publicar-oferta')) ?>" class="text-azul hover:underline">Publica una</a>.
      </div>
    <?php else: foreach ($offers as $o): $interests = DB::all('SELECT * FROM job_interests WHERE offer_id = ? ORDER BY created_at DESC', [(int)$o['id']]); ?>
      <article class="mt-6 bg-white border border-gray-200 rounded-lg p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h3 class="font-bold text-lg"><?= e($o['title']) ?></h3>
            <p class="text-sm text-gris-oscuro"><?= e(ucfirst($o['status'])) ?> · <?= e($o['modality'] ?? '') ?> · publicada <?= e(format_date($o['created_at'])) ?></p>
          </div>
          <span class="text-xs px-2 py-0.5 rounded-full bg-azul text-white font-semibold uppercase"><?= count($interests) ?> interesado<?= count($interests)===1?'':'s' ?></span>
        </div>

        <?php if ($interests): ?>
          <ul class="mt-4 divide-y divide-gray-200">
            <?php foreach ($interests as $i): ?>
              <li class="py-3 flex flex-wrap gap-3 items-start">
                <div class="flex-1 min-w-[200px]">
                  <p class="font-semibold"><?= e($i['guest_name'] ?? '') ?></p>
                  <p class="text-sm text-azul"><a href="mailto:<?= e($i['guest_email']) ?>"><?= e($i['guest_email']) ?></a></p>
                  <?php if ($i['professional_id']): $pr = ProfessionalRepo::find((int)$i['professional_id']); ?>
                    <?php if ($pr): ?><p class="text-xs text-gris-oscuro mt-1">Perfil en la red: <a href="<?= e(profile_url($pr)) ?>" class="text-azul hover:underline" target="_blank"><?= e($pr['name']) ?> →</a></p><?php endif; ?>
                  <?php endif; ?>
                  <?php if (!empty($i['message'])): ?><p class="text-sm text-gris-oscuro mt-2 italic">"<?= e($i['message']) ?>"</p><?php endif; ?>
                </div>
                <p class="text-xs text-gris-oscuro opacity-70"><?= e(format_date($i['created_at'])) ?></p>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="mt-3 text-sm text-gris-oscuro">Aún no hay interesados.</p>
        <?php endif; ?>
      </article>
    <?php endforeach; endif; ?>
  <?php elseif (!$sent): ?>
    <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 mt-6 space-y-4">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      <div>
        <label class="block text-sm font-semibold mb-1">Email registrado de tu empresa</label>
        <input name="email" type="email" required class="w-full border border-gray-300 rounded px-3 py-2" />
        <p class="text-xs text-gris-oscuro mt-1">Te enviaremos un enlace seguro de acceso temporal (válido 24h).</p>
      </div>
      <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Enviarme el enlace</button>
    </form>
  <?php else: ?>
    <div class="bg-verde/10 border border-verde rounded p-4 mt-6 text-texto">
      Si el email coincide con una empresa registrada, te enviamos un enlace de acceso. Revisa tu bandeja de entrada.
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
