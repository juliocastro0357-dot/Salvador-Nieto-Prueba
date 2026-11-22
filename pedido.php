<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

$n = trim((string)($_GET['n'] ?? ''));
$em = mb_strtolower(trim((string)($_GET['e'] ?? '')));
$order = null;
$notFound = false;
if ($n !== '' && $em !== '') {
    $order = row('SELECT * FROM orders WHERE number = ? AND lower(email) = ?', [$n, $em]);
    $notFound = !$order;
}
if ($order && !empty($_GET['ok'])) {
    cart_clear();
}
$items = $order ? rows('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]) : [];
$steps = ['pendiente' => 'Pedido recibido', 'pagado' => 'Pago confirmado', 'enviado' => 'Enviado', 'entregado' => 'Entregado'];
$idx = $order ? array_search($order['status'], array_keys($steps), true) : false;

$title = 'Seguir mi pedido';
$active = 'tienda';
require __DIR__ . '/inc/header.php';
?>
<section class="page-head"><div class="wrap"><span class="eyebrow">Tienda oficial</span><h1>Seguir mi <em>pedido.</em></h1></div></section>
<section class="section">
  <div class="wrap narrow">
  <?php if ($order): ?>
    <?php if (!empty($_GET['ok'])): ?>
      <div class="panel panel-ok"><?= icon('check', 34) ?><h2>¡Gracias por tu compra!</h2><p>Recibimos tu pedido <b><?= e($order['number']) ?></b>. Te enviamos un correo con los detalles.</p></div>
    <?php endif; ?>
    <div class="panel mt">
      <div class="order-head">
        <div><span class="eyebrow">Pedido</span><h2 class="h3"><?= e($order['number']) ?></h2></div>
        <span class="badge badge-<?= e($order['status']) ?>"><?= e(ORDER_STATUSES[$order['status']] ?? $order['status']) ?></span>
      </div>
      <?php if ($order['status'] === 'cancelado'): ?>
        <p class="muted">Este pedido fue cancelado.</p>
      <?php else: ?>
        <ol class="steps">
          <?php $i = 0; foreach ($steps as $k => $label): ?>
            <li class="<?= $idx !== false && $i <= $idx ? 'done' : '' ?>"><span><?= $i + 1 ?></span><?= e($label) ?></li>
          <?php $i++; endforeach; ?>
        </ol>
      <?php endif; ?>
      <?php if ($order['tracking']): ?>
        <p class="tracking"><?= icon('truck', 20) ?> Paquetería: <b><?= e($order['carrier']) ?></b> · Guía: <b><?= e($order['tracking']) ?></b></p>
      <?php endif; ?>
      <table class="tbl">
        <?php foreach ($items as $it): ?>
          <tr><td><?= e($it['name']) ?> <small class="muted">· <?= e($it['variant_label']) ?> × <?= (int)$it['qty'] ?></small></td><td class="r"><?= e(money((int)$it['price_cents'] * (int)$it['qty'])) ?></td></tr>
        <?php endforeach; ?>
        <tr><td>Envío</td><td class="r"><?= $order['shipping'] ? e(money((int)$order['shipping'])) : 'Gratis' ?></td></tr>
        <?php if ($order['discount']): ?><tr><td>Descuento</td><td class="r">−<?= e(money((int)$order['discount'])) ?></td></tr><?php endif; ?>
        <tr class="grand"><td>Total</td><td class="r"><?= e(money((int)$order['total'])) ?> MXN</td></tr>
      </table>
      <p class="fine">Enviamos a: <?= e($order['address1']) ?>, <?= e($order['city']) ?>, <?= e($order['state']) ?> · C.P. <?= e($order['zip']) ?></p>
    </div>
  <?php else: ?>
    <form class="form panel" method="get">
      <p class="muted">Escribe el número de pedido (lo recibiste por correo) y el correo con el que compraste.</p>
      <?php if ($notFound): ?><div class="flash flash-error">No encontramos un pedido con esos datos. Revisa el número y el correo.</div><?php endif; ?>
      <div class="grid-2">
        <label>Número de pedido<input name="n" value="<?= e($n) ?>" placeholder="SN-261121-1234" required></label>
        <label>Correo electrónico<input type="email" name="e" value="<?= e($em) ?>" required></label>
      </div>
      <button class="btn btn-gold" type="submit">Consultar</button>
    </form>
  <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
