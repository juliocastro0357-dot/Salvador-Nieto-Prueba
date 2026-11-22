<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

$title = 'Biografía';
$active = 'biografia';
$desc = setting('bio_short');
$paras = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', setting('bio_long')))));
$photo = is_file(ROOT . '/assets/img/artista.jpg') ? 'assets/img/artista.jpg' : null;
require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
  <div class="wrap">
    <span class="eyebrow">El artista</span>
    <h1>Una voz nacida <em>para el escenario.</em></h1>
  </div>
</section>

<section class="section bio">
  <div class="wrap bio-grid">
    <figure class="bio-art reveal">
      <img src="<?= e(asset($photo ?: 'assets/img/hero-mic.svg')) ?>" alt="<?= $photo ? e(cfg('artist_name')) : '' ?>" width="900" height="1100" loading="lazy">
      <figcaption><?= e(cfg('artist_name')) ?> · <?= e(cfg('artist_alias')) ?></figcaption>
    </figure>
    <div class="bio-text reveal">
      <?php foreach ($paras as $i => $p): ?>
        <p class="<?= $i === 0 ? 'dropcap' : '' ?>"><?= e($p) ?></p>
      <?php endforeach; ?>
      <blockquote class="pull">“<?= e(setting('bio_quote')) ?>”<cite>— <?= e(cfg('artist_name')) ?></cite></blockquote>
      <dl class="facts">
        <div><dt>Género</dt><dd>Regional mexicano</dd></div>
        <div><dt>Formato</dt><dd>Banda</dd></div>
        <div><dt>Contrataciones</dt><dd>Abiertas 2027</dd></div>
      </dl>
      <div class="bio-actions">
        <a class="btn btn-gold" href="<?= e(url('musica.php')) ?>">Escuchar su música</a>
        <a class="btn btn-ghost" href="<?= e(url('contrataciones.php#epk')) ?>">Prensa y EPK</a>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
