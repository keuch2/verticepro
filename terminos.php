<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page_title = 'Términos y condiciones — Vértice Pro'; $page_active = 'terminos.php';
include __DIR__ . '/includes/header.php';
?>
  <section class="max-w-3xl mx-auto px-6 py-14 prose-article">
    <h1 class="text-3xl font-extrabold mb-6">Términos y condiciones</h1>
    <p class="text-gris-oscuro">Última actualización: <?= e(date('d/m/Y')) ?></p>

    <h2 class="text-xl font-bold mt-8 mb-3">1. Aceptación</h2>
    <p>El uso de la plataforma Vértice Pro implica la aceptación íntegra de estos términos. Si no estás de acuerdo con alguna de las condiciones, no debes utilizar el servicio.</p>

    <h2 class="text-xl font-bold mt-8 mb-3">2. Servicio</h2>
    <p>Vértice Pro es una plataforma editorial y red profesional para especialistas en calidad, seguridad, salud ocupacional, ergonomía, legislación y medio ambiente, con foco principal en Paraguay.</p>

    <h2 class="text-xl font-bold mt-8 mb-3">3. Registro y contenidos del usuario</h2>
    <p>El registro como profesional o organización requiere información veraz y actualizada. Vértice Pro revisa cada solicitud antes de publicar el perfil. El usuario es responsable de los contenidos que aporta y de mantener su información al día.</p>

    <h2 class="text-xl font-bold mt-8 mb-3">4. Bolsa de Trabajo y aportes</h2>
    <p>Los oferentes pueden publicar ofertas de empleo y servicios profesionales, inicialmente sin costo. Los aportes de archivos por parte de suscriptores quedan sujetos a revisión previa por parte del equipo de Vértice Pro.</p>

    <h2 class="text-xl font-bold mt-8 mb-3">5. Reserva de modificación</h2>
    <p><strong>Vértice Pro se reserva el derecho de modificar estos términos y condiciones en cualquier momento.</strong> Los cambios serán comunicados a los usuarios registrados con razonable antelación a través de los canales habituales (correo electrónico y la propia plataforma). El uso continuado del servicio tras la notificación implica la aceptación de los nuevos términos.</p>

    <h2 class="text-xl font-bold mt-8 mb-3">6. Propiedad intelectual</h2>
    <p>Los contenidos editoriales, marcas y elementos gráficos de Vértice Pro están protegidos por derechos de propiedad intelectual. Su reproducción requiere autorización previa.</p>

    <h2 class="text-xl font-bold mt-8 mb-3">7. Privacidad</h2>
    <p>El tratamiento de datos personales se rige por nuestra <a href="<?= e(u('/privacidad')) ?>" class="text-azul hover:underline">política de privacidad</a>.</p>

    <h2 class="text-xl font-bold mt-8 mb-3">8. Contacto</h2>
    <p>Para consultas sobre estos términos, escribe a <a href="mailto:legal@verticepro.com.py" class="text-azul hover:underline">legal@verticepro.com.py</a>.</p>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
