  </main>

  <?php
    $ad_footer = ad_slot('footer', ['wrap_class' => 'max-w-7xl mx-auto px-6 py-4 text-center']);
    if ($ad_footer !== '') echo $ad_footer;
    $contact_email   = setting('contact.email', '');
    $contact_phone   = setting('contact.phone', '');
    $contact_address = setting('contact.address', '');
    $social_linkedin = setting('social.linkedin', '');
    $social_twitter  = setting('social.twitter', '');
    $social_youtube  = setting('social.youtube', '');
    $social_facebook = setting('social.facebook', '');
    $social_instagram= setting('social.instagram', '');
    $site_tagline    = setting('site.tagline', 'Plataforma editorial y red profesional para especialistas en calidad, seguridad, salud ocupacional y medio ambiente.');
  ?>
  <footer class="bg-gris-oscuro text-white">
    <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
      <div>
        <div class="flex items-center gap-0.5 mb-4">
          <span class="text-lg font-extrabold">VÉRTICE</span>
          <span class="text-lg font-extrabold text-naranja">PRO</span>
        </div>
        <p class="text-gray-400 text-sm leading-relaxed"><?= e($site_tagline) ?></p>
        <?php if ($contact_email || $contact_phone || $contact_address): ?>
          <div class="mt-4 text-sm text-gray-300 space-y-1">
            <?php if ($contact_email): ?><p>✉️ <a href="mailto:<?= e($contact_email) ?>" class="hover:text-naranja"><?= e($contact_email) ?></a></p><?php endif; ?>
            <?php if ($contact_phone): ?><p>📞 <a href="tel:<?= e($contact_phone) ?>" class="hover:text-naranja"><?= e($contact_phone) ?></a></p><?php endif; ?>
            <?php if ($contact_address): ?><p>📍 <?= e($contact_address) ?></p><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <div>
        <h4 class="font-bold text-base mb-4">Contenido Editorial</h4>
        <ul class="space-y-2 text-gray-300 text-sm">
          <li><a href="<?= e(u('/seccion/calidad')) ?>" class="hover:text-naranja transition">Calidad</a></li>
          <li><a href="<?= e(u('/seguridad')) ?>" class="hover:text-naranja transition">Seguridad</a></li>
          <li><a href="<?= e(u('/medioambiente')) ?>" class="hover:text-naranja transition">Medio Ambiente</a></li>
          <li><a href="<?= e(u('/salud')) ?>" class="hover:text-naranja transition">Salud Ocupacional</a></li>
          <li><a href="<?= e(u('/publicaciones')) ?>" class="hover:text-naranja transition">Publicaciones Técnicas</a></li>
          <li><a href="<?= e(u('/recursos')) ?>" class="hover:text-naranja transition">Recursos Descargables</a></li>
        </ul>
      </div>
      <div>
        <h4 class="font-bold text-base mb-4">Red de Profesionales</h4>
        <ul class="space-y-2 text-gray-300 text-sm">
          <li><a href="<?= e(u('/red')) ?>" class="hover:text-naranja transition">Red de Profesionales</a></li>
          <li><a href="<?= e(u('/directorio')) ?>" class="hover:text-naranja transition">Directorio</a></li>
          <li><a href="<?= e(u('/empresas')) ?>" class="hover:text-naranja transition">Empresas</a></li>
          <li><a href="<?= e(u('/bolsa')) ?>" class="hover:text-naranja transition">Bolsa de Trabajo</a></li>
        </ul>
      </div>
      <div>
        <h4 class="font-bold text-base mb-4">Redes sociales</h4>
        <ul class="space-y-2 text-gray-300 text-sm mb-6">
          <?php if ($social_linkedin):  ?><li><a href="<?= e($social_linkedin)  ?>" target="_blank" rel="noopener" class="hover:text-naranja transition">LinkedIn</a></li><?php endif; ?>
          <?php if ($social_twitter):   ?><li><a href="<?= e($social_twitter)   ?>" target="_blank" rel="noopener" class="hover:text-naranja transition">Twitter / X</a></li><?php endif; ?>
          <?php if ($social_facebook):  ?><li><a href="<?= e($social_facebook)  ?>" target="_blank" rel="noopener" class="hover:text-naranja transition">Facebook</a></li><?php endif; ?>
          <?php if ($social_instagram): ?><li><a href="<?= e($social_instagram) ?>" target="_blank" rel="noopener" class="hover:text-naranja transition">Instagram</a></li><?php endif; ?>
          <?php if ($social_youtube):   ?><li><a href="<?= e($social_youtube)   ?>" target="_blank" rel="noopener" class="hover:text-naranja transition">YouTube</a></li><?php endif; ?>
          <?php if (!$social_linkedin && !$social_twitter && !$social_facebook && !$social_instagram && !$social_youtube): ?>
            <li class="text-gray-500 italic">Pronto en redes</li>
          <?php endif; ?>
        </ul>
        <h4 class="font-bold text-base mb-3">Legal</h4>
        <ul class="space-y-2 text-gray-300 text-sm">
          <li><a href="<?= e(u('/privacidad')) ?>" class="hover:text-naranja transition">Política de privacidad</a></li>
          <li><a href="<?= e(u('/terminos')) ?>" class="hover:text-naranja transition">Términos y condiciones</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-gray-600 py-6 px-6 text-gray-400 text-sm">
      <div class="max-w-7xl mx-auto space-y-2 text-center">
        <p class="italic">"La prevención, la sostenibilidad y el bienestar no son opcionales: son decisiones estratégicas."</p>
        <p class="text-xs opacity-80">Vértice Pro se reserva el derecho de modificar los <a href="<?= e(u('/terminos')) ?>" class="underline hover:text-naranja">términos y condiciones</a> del servicio en cualquier momento. Los cambios serán notificados con razonable antelación.</p>
        <p>© <?= date('Y') ?> <?= e(setting('site.name', 'Vértice Pro')) ?>. Todos los derechos reservados.</p>
      </div>
    </div>
  </footer>

  <script src="<?= e(u('/js/main.js')) ?>"></script>
</body>
</html>
