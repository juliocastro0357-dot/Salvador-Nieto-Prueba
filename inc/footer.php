</main>

<footer class="site-footer">
  <div class="wrap">
    <div class="foot-top">
      <div class="foot-brand">
        <span class="brand-name"><?= e(mb_strtoupper(cfg('artist_name'))) ?></span>
        <span class="brand-alias"><?= e(mb_strtoupper(cfg('artist_alias'))) ?></span>
        <p class="foot-line">Una voz. Un escenario. Una familia que canta contigo.</p>
        <div class="socials">
          <?php
          $soc = ['social_instagram' => ['instagram', 'Instagram'], 'social_facebook' => ['facebook', 'Facebook'], 'social_tiktok' => ['tiktok', 'TikTok'],
                  'social_youtube' => ['youtube', 'YouTube'], 'social_spotify' => ['spotify', 'Spotify']];
          foreach ($soc as $k => [$ic, $lab]) {
              $u = setting($k);
              if ($u) {
                  echo '<a href="' . e($u) . '" target="_blank" rel="noopener" aria-label="' . e($lab) . '">' . icon($ic, 22) . '</a>';
              }
          }
          if (setting('whatsapp')) {
              echo '<a href="https://wa.me/' . e(preg_replace('/\D/', '', setting('whatsapp'))) . '" target="_blank" rel="noopener" aria-label="WhatsApp">' . icon('whatsapp', 22) . '</a>';
          }
          ?>
        </div>
      </div>
      <nav class="foot-col" aria-label="Sitio">
        <h4>Sitio</h4>
        <a href="<?= e(url('biografia.php')) ?>">Biografía</a>
        <a href="<?= e(url('musica.php')) ?>">Música</a>
        <a href="<?= e(url('gira.php')) ?>">Gira</a>
        <a href="<?= e(url('contrataciones.php')) ?>">Contrataciones y prensa</a>
      </nav>
      <nav class="foot-col" aria-label="Tienda">
        <h4>Tienda oficial</h4>
        <a href="<?= e(url('tienda.php')) ?>">Merch</a>
        <a href="<?= e(url('pedido.php')) ?>">Seguir mi pedido</a>
        <a href="<?= e(url('legal.php#envios')) ?>">Envíos y devoluciones</a>
        <a href="<?= e(url('legal.php#privacidad')) ?>">Aviso de privacidad</a>
      </nav>
      <div class="foot-col foot-news">
        <h4>Círculo del Noble</h4>
        <p>Entérate primero de fechas, lanzamientos y nuevos productos.</p>
        <form class="news-form" id="newsForm" action="<?= e(url('api/subscribe.php')) ?>" method="post" novalidate>
          <?= csrf_field() ?>
          <label class="sr" for="newsEmail">Correo electrónico</label>
          <input id="newsEmail" type="email" name="email" placeholder="tu@correo.com" required autocomplete="email">
          <button type="submit" class="btn btn-gold btn-sm">Unirme</button>
          <p class="news-msg" id="newsMsg" role="status"></p>
        </form>
      </div>
    </div>
    <div class="foot-bottom">
      <span>© <?= date('Y') ?> <?= e(cfg('artist_name')) ?>. Todos los derechos reservados.</span>
      <span><a href="<?= e(url('legal.php')) ?>">Términos y privacidad</a><?php if (cfg('demo_mode')): ?> · <em>Sitio de demostración</em><?php endif; ?></span>
    </div>
  </div>
</footer>

<!-- Reproductor de vista previa (30 s) -->
<div class="miniplayer" id="miniplayer" hidden>
  <img id="mpCover" src="" alt="" width="52" height="52">
  <div class="mp-info">
    <span class="mp-tag">VISTA PREVIA · 30 SEGUNDOS</span>
    <strong id="mpTitle">—</strong>
  </div>
  <button class="mp-toggle" id="mpToggle" aria-label="Reproducir o pausar"><?= icon('play', 22) ?></button>
  <div class="mp-bar" id="mpBar" role="progressbar" aria-valuemin="0" aria-valuemax="30" aria-valuenow="0"><i id="mpProgress"></i></div>
  <span class="mp-time" id="mpTime">0:00 / 0:30</span>
  <a class="mp-link" id="mpLink" href="#" target="_blank" rel="noopener">Escuchar completa <?= icon('ext', 16) ?></a>
  <button class="mp-close" id="mpClose" aria-label="Cerrar reproductor"><?= icon('close', 20) ?></button>
</div>

<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
</body>
</html>
