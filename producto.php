<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

$p = row('SELECT * FROM products WHERE slug = ? AND active = 1', [$_GET['slug'] ?? '']);
if (!$p) {
    http_response_code(404);
    $title = 'Producto no encontrado';
    $active = 'tienda';
    require __DIR__ . '/inc/header.php';
    echo '<section class="page-head"><div class="wrap"><h1>Producto <em>no encontrado.</em></h1><a class="btn btn-gold" href="' . e(url('tienda.php')) . '">Volver a la tienda</a></div></section>';
    require __DIR__ . '/inc/footer.php';
    exit;
}
$imgs = product_images($p);
$variants = rows('SELECT * FROM variants WHERE product_id = ? ORDER BY id', [$p['id']]);
$total = array_sum(array_column($variants, 'stock'));
$related = rows('SELECT * FROM products WHERE active = 1 AND id != ? ORDER BY (category = ?) DESC, featured DESC LIMIT 4', [$p['id'], $p['category']]);
$title = $p['name'];
$active = 'tienda';
$desc = mb_substr(strip_tags($p['description']), 0, 155);
$og = absolute_url($imgs[0]);
require __DIR__ . '/inc/header.php';
?>
<section class="section pdp">
  <div class="wrap">
    <nav class="crumbs" aria-label="Ruta"><a href="<?= e(url('tienda.php')) ?>">Tienda</a> / <a href="<?= e(url('tienda.php?cat=' . urlencode($p['category']))) ?>"><?= e($p['category']) ?></a> / <span><?= e($p['name']) ?></span></nav>
    <div class="pdp-grid">
      <div class="gallery">
        <div class="gallery-main"><img id="galMain" src="<?= e(media($imgs[0], 'assets/img/products/p1.svg')) ?>" alt="<?= e($p['name']) ?>" width="900" height="900"></div>
        <?php if (count($imgs) > 1): ?>
          <div class="gallery-thumbs">
            <?php foreach ($imgs as $i => $im): ?>
              <button type="button" class="thumb <?= $i === 0 ? 'on' : '' ?>" data-img="<?= e(media($im)) ?>" aria-label="Ver imagen <?= $i + 1 ?>"><img src="<?= e(media($im)) ?>" alt="" width="120" height="120"></button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="pdp-info">
        <span class="pcat"><?= e($p['category']) ?></span>
        <h1 class="pdp-title"><?= e($p['name']) ?></h1>
        <p class="pdp-price"><?= e(money((int)$p['price_cents'])) ?> <small>MXN</small>
          <?php if (!empty($p['compare_cents'])): ?><s><?= e(money((int)$p['compare_cents'])) ?></s><?php endif; ?></p>
        <p class="pdp-desc"><?= nl2br(e($p['description'])) ?></p>

        <?php if ($total > 0): ?>
        <form class="buy" id="buyForm" method="post" action="<?= e(url('carrito.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <fieldset class="variants">
            <legend><?= count($variants) > 1 || ($variants[0]['label'] ?? '') !== 'Única' ? 'Talla / opción' : 'Opción' ?></legend>
            <div class="variant-list">
              <?php $first = true; foreach ($variants as $v): $out = (int)$v['stock'] < 1; ?>
                <label class="vopt <?= $out ? 'out' : '' ?>">
                  <input type="radio" name="variant_id" value="<?= (int)$v['id'] ?>" <?= $out ? 'disabled' : ($first ? 'checked' : '') ?> required>
                  <span><?= e($v['label']) ?></span>
                </label>
                <?php if (!$out) $first = false; ?>
              <?php endforeach; ?>
            </div>
          </fieldset>
          <div class="buy-row">
            <div class="qty" data-qty>
              <button type="button" data-dec aria-label="Menos">−</button>
              <input type="number" name="qty" value="1" min="1" max="10" aria-label="Cantidad">
              <button type="button" data-inc aria-label="Más">+</button>
            </div>
            <button type="submit" class="btn btn-gold btn-block"><?= icon('cart', 18) ?> Agregar al carrito</button>
          </div>
        </form>
        <?php else: ?>
          <p class="panel">Este producto está agotado por ahora. Únete al <a href="#newsEmail">Círculo del Noble</a> para saber cuándo vuelve.</p>
        <?php endif; ?>

        <ul class="pdp-points">
          <li><?= icon('truck', 20) ?> Envío a todo México · gratis desde <?= e(money((int)setting('free_shipping_min', '150000'))) ?></li>
          <li><?= icon('shield', 20) ?> Pago seguro</li>
          <li><?= icon('refresh', 20) ?> Cambio por defecto de fábrica dentro de 7 días</li>
        </ul>
        <p class="fine">Preparado y enviado por nuestro proveedor oficial de merch.</p>
      </div>
    </div>
  </div>
</section>

<?php if ($related): ?>
<section class="section section-alt">
  <div class="wrap">
    <?= section_head('Te puede gustar', 'Más de la', 'tienda.') ?>
    <div class="pgrid"><?php foreach ($related as $r) echo product_card($r); ?></div>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/inc/footer.php'; ?>
