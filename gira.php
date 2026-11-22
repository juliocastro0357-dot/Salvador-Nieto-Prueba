<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

$title = 'Gira';
$active = 'gira';
$desc = 'Fechas y presentaciones de Salvador Nieto, El Noble de la Banda.';
$next = rows("SELECT * FROM shows WHERE published = 1 AND status != 'finalizado' ORDER BY date ASC");
$past = rows("SELECT * FROM shows WHERE published = 1 AND status = 'finalizado' ORDER BY date DESC LIMIT 12");
require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
  <div class="wrap">
    <span class="eyebrow">Gira</span>
    <h1>Nos vemos <em>en el camino.</em></h1>
    <p class="lead">Las fechas se actualizan en cuanto se confirman. ¿Quieres al Noble en tu ciudad?</p>
    <a class="btn btn-gold" href="<?= e(url('contrataciones.php')) ?>">Contratar presentación</a>
  </div>
</section>

<section class="section">
  <div class="wrap narrow">
    <h2 class="h3">Próximas fechas</h2>
    <?php if ($next): ?>
      <ul class="shows"><?php foreach ($next as $s) echo show_row($s); ?></ul>
    <?php else: ?>
      <p class="muted">Pronto anunciaremos nuevas fechas. Únete al Círculo del Noble (al final de la página) para recibir el aviso primero.</p>
    <?php endif; ?>

    <?php if ($past): ?>
      <h2 class="h3 mt">Presentaciones recientes</h2>
      <ul class="shows"><?php foreach ($past as $s) echo show_row($s); ?></ul>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
