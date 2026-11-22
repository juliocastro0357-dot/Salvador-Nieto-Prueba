<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require __DIR__ . '/_layout.php';
require_cap('shop');

$id = (int)($_GET['id'] ?? 0);
$o = row('SELECT * FROM orders WHERE id = ?', [$id]);
if (!$o) {
    flash('error', 'Pedido no encontrado.');
    redirect('admin/pedidos.php');
}

if (is_post()) {
    csrf_verify();
    $new = post('status');
    $old = $o['status'];
    if (!isset(ORDER_STATUSES[$new])) {
        $new = $old;
    }
    if ($old === 'cancelado' && $new !== 'cancelado') {
        flash('error', 'Un pedido cancelado no se puede reactivar. Crea uno nuevo si es necesario.');
        redirect('admin/pedido.php?id=' . $id);
    }
    $carrier = mb_substr(post('carrier'), 0, 60);
    $tracking = mb_substr(post('tracking'), 0, 80);
    if ($new === 'enviado' && $tracking === '') {
        flash('error', 'Para marcar como enviado captura la paquetería y el número de guía.');
        redirect('admin/pedido.php?id=' . $id);
    }
    q('UPDATE orders SET status = ?, carrier = ?, tracking = ?, notes = ? WHERE id = ?', [$new, $carrier, $tracking, mb_substr(post('notes'), 0, 1000) ?: null, $id]);
    if ($new === 'pagado' && $old === 'pendiente') {
        q("UPDATE orders SET paid_at = CURRENT_TIMESTAMP, payment_method = COALESCE(payment_method,'manual') WHERE id = ?", [$id]);
    }
    if ($new === 'cancelado' && $old !== 'cancelado') {
        order_restock($id);
        flash('ok', 'Pedido cancelado y el inventario fue devuelto.');
    } else {
        flash('ok', 'Pedido actualizado.');
    }
    if ($new === 'enviado' && $old !== 'enviado') {
        mail_order_shipped(row('SELECT * FROM orders WHERE id = ?', [$id]));
        flash('ok', 'Se avisó al cliente por correo con la guía de rastreo.');
    }
    redirect('admin/pedido.php?id=' . $id);
}

$items = rows('SELECT * FROM order_items WHERE order_id = ?', [$id]);
admin_header('Pedido ' . $o['number'], 'pedidos');
?>
<p><a href="<?= e(url('admin/pedidos.php')) ?>">← Volver a pedidos</a></p>
<div class="grid-2 wide-left">
  <div>
    <section class="card">
      <div class="card-head"><h2>Productos</h2><span class="pill st-<?= e($o['status']) ?>"><?= e(ORDER_STATUSES[$o['status']] ?? $o['status']) ?></span></div>
      <table class="tbl">
        <?php foreach ($items as $it): ?>
          <tr><td><b><?= e($it['name']) ?></b><br><small class="muted"><?= e($it['variant_label']) ?> · <?= e(money((int)$it['price_cents'])) ?> × <?= (int)$it['qty'] ?></small></td><td class="r"><?= e(money((int)$it['price_cents'] * (int)$it['qty'])) ?></td></tr>
        <?php endforeach; ?>
        <tr><td class="muted">Subtotal</td><td class="r"><?= e(money((int)$o['subtotal'])) ?></td></tr>
        <?php if ($o['discount']): ?><tr><td class="muted">Descuento <?= e($o['coupon'] ?? '') ?></td><td class="r">−<?= e(money((int)$o['discount'])) ?></td></tr><?php endif; ?>
        <tr><td class="muted">Envío</td><td class="r"><?= $o['shipping'] ? e(money((int)$o['shipping'])) : 'Gratis' ?></td></tr>
        <tr class="grand"><td>Total</td><td class="r"><?= e(money((int)$o['total'])) ?> MXN</td></tr>
      </table>
      <p class="muted small">Pago: <?= e($o['payment_method'] ?: '—') ?> · Ref: <?= e($o['payment_ref'] ?: '—') ?><?= $o['paid_at'] ? ' · Pagado el ' . e(date('d/m/Y H:i', strtotime($o['paid_at']))) : '' ?></p>
    </section>

    <section class="card">
      <h2>Datos de envío</h2>
      <p><b><?= e($o['name']) ?></b><br>
        <?= e($o['address1']) ?><?= $o['address2'] ? '<br>' . e($o['address2']) : '' ?><br>
        <?= e($o['city']) ?>, <?= e($o['state']) ?> · C.P. <?= e($o['zip']) ?><br><?= e($o['country']) ?></p>
      <p class="muted small">Tel: <?= e($o['phone']) ?> · <a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a><br>Pedido creado el <?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></p>
      <button class="btn btn-ghost no-print" onclick="window.print()">Imprimir hoja de empaque</button>
    </section>
  </div>

  <form class="card form no-print" method="post">
    <?= csrf_field() ?>
    <h2>Gestión del pedido</h2>
    <label>Estado
      <select name="status">
        <?php foreach (ORDER_STATUSES as $k => $l): ?><option value="<?= e($k) ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Paquetería
      <input name="carrier" list="carriers" value="<?= e($o['carrier']) ?>" placeholder="Estafeta, DHL, FedEx, Redpack…">
      <datalist id="carriers"><option>Estafeta</option><option>DHL</option><option>FedEx</option><option>Redpack</option><option>Paquetexpress</option><option>Correos de México</option></datalist>
    </label>
    <label>Número de guía<input name="tracking" value="<?= e($o['tracking']) ?>"></label>
    <label>Notas internas<textarea name="notes" rows="3"><?= e($o['notes']) ?></textarea></label>
    <button class="btn btn-primary" type="submit">Guardar cambios</button>
    <p class="muted small">Al pasar a “Enviado” se envía automáticamente un correo al cliente con la guía. Al “Cancelar”, las piezas regresan al inventario.</p>
  </form>
</div>
<?php admin_footer(); ?>
