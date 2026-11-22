<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

/* Simulación de pasarela. Solo disponible mientras no haya llaves de pago reales configuradas. */
if (stripe_enabled()) {
    redirect('tienda.php');
}
$order = row("SELECT * FROM orders WHERE number = ? AND status = 'pendiente'", [$_GET['n'] ?? ($_POST['n'] ?? '')]);
if (!$order || ($_SESSION['pending_order'] ?? '') !== $order['number']) {
    redirect('tienda.php');
}

if (is_post()) {
    csrf_verify();
    order_mark_paid((int)$order['id'], 'demo', 'DEMO-' . strtoupper(bin2hex(random_bytes(4))));
    cart_clear();
    unset($_SESSION['pending_order']);
    redirect('pedido.php?n=' . urlencode($order['number']) . '&e=' . urlencode($order['email']) . '&ok=1');
}

$title = 'Pago (demostración)';
$active = 'tienda';
require __DIR__ . '/inc/header.php';
?>
<section class="section">
  <div class="wrap narrow">
    <div class="panel demo-pay">
      <span class="badge">MODO DEMOSTRACIÓN</span>
      <h1 class="h3">Pago del pedido <?= e($order['number']) ?></h1>
      <p class="muted">Esta pantalla simula la pasarela de pago. No se solicita ni se guarda ningún dato de tarjeta y no se realiza ningún cargo.</p>
      <p class="demo-total"><?= e(money((int)$order['total'])) ?> <small>MXN</small></p>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="n" value="<?= e($order['number']) ?>">
        <button class="btn btn-gold btn-block" type="submit">Simular pago exitoso</button>
      </form>
      <a class="link-arrow" href="<?= e(url('checkout.php')) ?>">← Volver</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
