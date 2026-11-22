<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

$title = 'Tienda oficial';
$active = 'tienda';
$desc = 'Merch oficial de Salvador Nieto, El Noble de la Banda: playeras, gorras, hoodies, música y coleccionables con envío a todo México.';

$cat = $_GET['cat'] ?? '';
$sort = $_GET['orden'] ?? 'destacados';
$cats = array_column(rows('SELECT DISTINCT category FROM products WHERE active = 1 ORDER BY category'), 'category');
$where = 'active = 1';
$params = [];
if ($cat !== '' && in_array($cat, $cats, true)) {
    $where .= ' AND category = ?';
    $params[] = $cat;
} else {
    $cat = '';
}
$order = match ($sort) {
    'precio-asc' => 'price_cents ASC',
    'precio-desc' => 'price_cents DESC',
    'nuevos' => 'created_at DESC, id DESC',
    default => 'featured DESC, id ASC',
};
$products = rows("SELECT * FROM products WHERE $where ORDER BY $order", $params);
$qs = fn(array $o) => url('tienda.php') . '?' . http_build_query(array_filter(array_merge(['cat' => $cat, 'orden' => $sort === 'destacados' ? '' : $sort], $o)));
require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
  <div class="wrap">
    <span class="eyebrow">Tienda oficial</span>
    <h1>Lleva al Noble <em>contigo.</em></h1>
    <p class="lead"><?= e(setting('store_notice')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="shop-bar">
      <nav class="chips" aria-label="Categorías">
        <a class="chip <?= $cat === '' ? 'on' : '' ?>" href="<?= e($qs(['cat' => ''])) ?>">Todo</a>
        <?php foreach ($cats as $c): ?>
          <a class="chip <?= $cat === $c ? 'on' : '' ?>" href="<?= e($qs(['cat' => $c])) ?>"><?= e($c) ?></a>
        <?php endforeach; ?>
      </nav>
      <form class="sort" method="get">
        <?php if ($cat): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
        <label class="sr" for="orden">Ordenar</label>
        <select id="orden" name="orden" onchange="this.form.submit()">
          <?php foreach (['destacados' => 'Destacados', 'nuevos' => 'Más nuevos', 'precio-asc' => 'Precio: menor a mayor', 'precio-desc' => 'Precio: mayor a menor'] as $k => $l): ?>
            <option value="<?= e($k) ?>" <?= $sort === $k ? 'selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <?php if ($products): ?>
      <div class="pgrid">
        <?php foreach ($products as $p) echo product_card($p); ?>
      </div>
    <?php else: ?>
      <p class="muted">No hay productos en esta categoría por ahora.</p>
    <?php endif; ?>

    <ul class="trust">
      <li><?= icon('truck', 22) ?> Envíos a todo México</li>
      <li><?= icon('shield', 22) ?> Pago seguro con tarjeta</li>
      <li><?= icon('refresh', 22) ?> Cambios por defecto de fábrica</li>
    </ul>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
