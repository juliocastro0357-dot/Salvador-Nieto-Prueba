<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

$title = 'Música';
$active = 'musica';
$desc = 'Escucha 30 segundos de cada canción de Salvador Nieto, El Noble de la Banda, y sigue en Spotify, Apple Music, YouTube Music y más.';

$tipo = $_GET['tipo'] ?? 'todos';
$where = 'published = 1';
$params = [];
if (isset(TRACK_KINDS[$tipo])) {
    $where .= ' AND kind = ?';
    $params[] = $tipo;
} else {
    $tipo = 'todos';
}
$tracks = rows("SELECT * FROM tracks WHERE $where ORDER BY release_date DESC, sort ASC", $params);
$total = (int)val('SELECT COUNT(*) FROM tracks WHERE published = 1');
$tabs = ['todos' => 'Todo'] + ['sencillo' => 'Sencillos', 'vivo' => 'En vivo', 'album' => 'Álbumes'];
require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
  <div class="wrap">
    <span class="eyebrow">Discografía</span>
    <h1>Un camino hecho <em>canciones.</em></h1>
    <p class="lead"><?= $total ?> lanzamientos · 30 segundos de muestra por canción · enlaces a todas las plataformas</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <nav class="chips" aria-label="Filtrar por tipo">
      <?php foreach ($tabs as $k => $label): ?>
        <a class="chip <?= $tipo === $k ? 'on' : '' ?>" href="<?= e(url('musica.php' . ($k === 'todos' ? '' : '?tipo=' . $k))) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php if ($tracks): ?>
      <div class="track-grid">
        <?php foreach ($tracks as $t) echo track_card($t); ?>
      </div>
    <?php else: ?>
      <p class="muted">Aún no hay lanzamientos en esta categoría.</p>
    <?php endif; ?>

    <div class="platform-strip reveal">
      <h3>Escúchalo donde quieras</h3>
      <div class="plat-buttons">
        <?php foreach (['social_spotify' => 'Spotify', 'social_apple' => 'Apple Music', 'social_youtube' => 'YouTube', 'social_amazon' => 'Amazon Music'] as $k => $l):
            if (setting($k)): ?>
          <a class="btn btn-outline" href="<?= e(setting($k)) ?>" target="_blank" rel="noopener"><?= e($l) ?> <?= icon('ext', 16) ?></a>
        <?php endif; endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
