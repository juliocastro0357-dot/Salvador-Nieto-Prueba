<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

$latest = row('SELECT * FROM tracks WHERE published = 1 ORDER BY featured DESC, release_date DESC LIMIT 1');
$tracks = rows('SELECT * FROM tracks WHERE published = 1 ORDER BY sort ASC, release_date DESC LIMIT 8');
$next = rows("SELECT * FROM shows WHERE published = 1 AND status != 'finalizado' AND date >= date('now') ORDER BY date ASC LIMIT 4");
$products = rows('SELECT * FROM products WHERE active = 1 ORDER BY featured DESC, id ASC LIMIT 4');
$videos = rows('SELECT * FROM videos WHERE published = 1 ORDER BY sort ASC, id DESC LIMIT 3');
$photo = is_file(ROOT . '/assets/img/artista.jpg') ? 'assets/img/artista.jpg' : null;

$title = null;
$active = 'inicio';
$desc = setting('bio_short');
require __DIR__ . '/inc/header.php';
?>

<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <span class="eyebrow">Música regional mexicana</span>
      <h1><span class="h1-name">Salvador<br>Nieto</span>
        <em><?= e(cfg('artist_alias')) ?></em></h1>
      <p class="lead"><?= e(setting('hero_tagline')) ?></p>
      <div class="hero-cta">
        <?php if ($latest): ?>
          <button class="btn btn-gold" type="button" <?= play_attrs($latest) ?>><?= icon('play', 18) ?> Escuchar “<?= e($latest['title']) ?>”</button>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(url('tienda.php')) ?>">Tienda oficial</a>
      </div>
      <?php if ($next): $n = $next[0]; ?>
        <a class="next-chip" href="<?= e(url('gira.php')) ?>">
          <span class="next-dot"></span>
          <span><b>Próxima fecha</b> · <?= e(fecha_es($n['date'], 'short')) ?> · <?= e($n['city']) ?></span><?= icon('arrow', 16) ?>
        </a>
      <?php endif; ?>
    </div>
    <div class="hero-art" aria-hidden="<?= $photo ? 'false' : 'true' ?>">
      <?php if ($photo): ?>
        <img src="<?= e(asset($photo)) ?>" alt="<?= e(cfg('artist_name')) ?>, <?= e(cfg('artist_alias')) ?>" width="900" height="1100" fetchpriority="high">
      <?php else: ?>
        <img src="<?= e(asset('assets/img/hero-mic.svg')) ?>" alt="" width="900" height="1100" fetchpriority="high">
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="marquee" aria-hidden="true">
  <div class="marquee-track">
    <?php for ($i = 0; $i < 2; $i++): ?>
      <span>SALVADOR NIETO</span><i>◆</i><span>EL NOBLE DE LA BANDA</span><i>◆</i><span>GIRA 2027</span><i>◆</i><span>TIENDA OFICIAL</span><i>◆</i><span>CONTRATACIONES</span><i>◆</i>
    <?php endfor; ?>
  </div>
</div>

<?php if ($latest): ?>
<section class="section release">
  <div class="wrap release-grid">
    <button class="release-cover" type="button" <?= play_attrs($latest) ?> aria-label="Escuchar vista previa de <?= e($latest['title']) ?>">
      <img src="<?= e(media($latest['cover'])) ?>" alt="Portada de <?= e($latest['title']) ?>" width="900" height="900" loading="lazy">
      <span class="cover-play big"><?= icon('play', 40) ?></span>
      <span class="release-flag">Lanzamiento<br><b><?= e(date('Y', strtotime($latest['release_date']))) ?></b></span>
    </button>
    <div class="release-copy reveal">
      <span class="eyebrow">Lanzamiento más reciente · <?= e(fecha_es($latest['release_date'])) ?></span>
      <h2><?= e($latest['title']) ?></h2>
      <p class="muted">Escucha un fragmento de 30 segundos aquí mismo o continúa en tu plataforma favorita.</p>
      <div class="plat-buttons">
        <?php foreach (platform_links($latest) as $p): ?>
          <a class="btn btn-outline" href="<?= e($p['url']) ?>" target="_blank" rel="noopener"><?= e($p['label']) ?> <?= icon('ext', 16) ?></a>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-gold" type="button" <?= play_attrs($latest) ?>><?= icon('play', 18) ?> Escuchar muestra · 30 s</button>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section about">
  <div class="wrap about-grid">
    <div class="about-quote reveal">
      <span class="eyebrow">El artista</span>
      <blockquote>“<?= e(setting('bio_quote')) ?>”</blockquote>
    </div>
    <div class="about-copy reveal">
      <h2>Una voz con <em>carácter noble.</em></h2>
      <p><?= e(setting('bio_short')) ?></p>
      <a class="link-arrow" href="<?= e(url('biografia.php')) ?>">Conoce su historia <?= icon('arrow', 18) ?></a>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?= section_head('Discografía', 'Un camino hecho', 'canciones.', url('musica.php'), 'Ver toda la música') ?>
    <div class="track-grid">
      <?php foreach ($tracks as $t) echo track_card($t); ?>
    </div>
  </div>
</section>

<?php if ($videos): ?>
<section class="section section-alt">
  <div class="wrap">
    <?= section_head('En pantalla', 'La historia', 'también se mira.', setting('social_youtube'), 'Canal de YouTube') ?>
    <div class="video-grid">
      <?php foreach ($videos as $v): ?>
        <div class="video reveal">
          <div class="video-frame" data-yt="<?= e($v['youtube_id']) ?>" data-title="<?= e($v['title']) ?>">
            <img src="https://i.ytimg.com/vi/<?= e($v['youtube_id']) ?>/hqdefault.jpg" alt="Video: <?= e($v['title']) ?>" loading="lazy">
            <button type="button" class="video-play" aria-label="Reproducir <?= e($v['title']) ?>"><?= icon('play', 30) ?></button>
          </div>
          <h3><?= e($v['title']) ?></h3>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="wrap">
    <?= section_head('Gira', 'Nos vemos', 'en el camino.', url('gira.php'), 'Todas las fechas') ?>
    <?php if ($next): ?>
      <ul class="shows"><?php foreach ($next as $s) echo show_row($s); ?></ul>
    <?php else: ?>
      <p class="muted">Las próximas fechas se anunciarán muy pronto. Suscríbete al Círculo del Noble para enterarte primero.</p>
    <?php endif; ?>
  </div>
</section>

<?php if ($products): ?>
<section class="section section-alt">
  <div class="wrap">
    <?= section_head('Tienda oficial', 'Lleva al Noble', 'contigo.', url('tienda.php'), 'Ver toda la tienda') ?>
    <div class="pgrid">
      <?php foreach ($products as $p) echo product_card($p); ?>
    </div>
    <ul class="trust">
      <li><?= icon('truck', 22) ?> Envíos a todo México</li>
      <li><?= icon('shield', 22) ?> Pago seguro</li>
      <li><?= icon('refresh', 22) ?> Cambios por defecto de fábrica</li>
    </ul>
  </div>
</section>
<?php endif; ?>

<section class="cta-band">
  <div class="wrap cta-inner reveal">
    <span class="eyebrow">Booking · prensa · colaboraciones</span>
    <h2>Hagamos que suceda <em>en vivo.</em></h2>
    <p>Bailes, festivales, ferias, eventos privados y corporativos. Cuéntanos tu evento y te respondemos personalmente.</p>
    <a class="btn btn-gold" href="<?= e(url('contrataciones.php')) ?>">Contratar presentación</a>
  </div>
</section>

<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org', '@type' => 'MusicGroup', 'name' => cfg('artist_name'),
    'alternateName' => cfg('artist_alias'), 'genre' => 'Música regional mexicana',
    'description' => setting('bio_short'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<?php require __DIR__ . '/inc/footer.php'; ?>
