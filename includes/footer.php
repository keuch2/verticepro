  </main>

  <footer class="bg-gris-oscuro text-white">
    <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
      <div>
        <div class="flex items-center gap-0.5 mb-4">
          <span class="text-lg font-extrabold">VÉRTICE</span>
          <span class="text-lg font-extrabold text-naranja">PRO</span>
        </div>
        <p class="text-gray-400 text-sm leading-relaxed">Plataforma editorial y red profesional para especialistas en calidad, seguridad, salud ocupacional y medio ambiente.</p>
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
          <li><a href="#" class="hover:text-naranja transition">LinkedIn</a></li>
          <li><a href="#" class="hover:text-naranja transition">Twitter / X</a></li>
          <li><a href="#" class="hover:text-naranja transition">YouTube</a></li>
        </ul>
        <h4 class="font-bold text-base mb-3">Legal</h4>
        <ul class="space-y-2 text-gray-300 text-sm">
          <li><a href="<?= e(u('/privacidad')) ?>" class="hover:text-naranja transition">Política de privacidad</a></li>
          <li><a href="#" class="hover:text-naranja transition">Términos de uso</a></li>
          <li><a href="#" class="hover:text-naranja transition">Cookies</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-gray-600 py-6 text-center text-gray-400 text-sm px-6">
      <p class="italic">"La prevención, la sostenibilidad y el bienestar no son opcionales: son decisiones estratégicas."</p>
      <p class="mt-2">© <?= date('Y') ?> Vértice Pro. Todos los derechos reservados.</p>
    </div>
  </footer>

  <script src="<?= e(u('/js/main.js')) ?>"></script>
</body>
</html>
