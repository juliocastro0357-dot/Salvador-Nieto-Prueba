<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require __DIR__ . '/_layout.php';
require_cap('reports');

const PAID = "('pagado','enviado','entregado')";
$month = date('Y-m-01');
$salesMonth = (int)val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN " . PAID . " AND created_at >= ?", [$month]);
$salesAll = (int)val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN " . PAID);
$ordersMonth = (int)val("SELECT COUNT(*) FROM orders WHERE status IN " . PAID . " AND created_at >= ?", [$month]);
$toShip = (int)val("SELECT COUNT(*) FROM orders WHERE status = 'pagado'");
$pending = (int)val("SELECT COUNT(*) FROM orders WHERE status = 'pendiente'");
$subs = (int)val('SELECT COUNT(*) FROM subscribers');
$unread = (int)val('SELECT COUNT(*) FROM messages WHERE is_read = 0');
$avg = $ordersMonth ? intdiv($salesMonth, $ordersMonth) : 0;

$days = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $days[$d] = (int)val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN " . PAID . " AND date(created_at) = ?", [$d]);
}
$max = max(1, max($days));
$recent = rows('SELECT * FROM orders ORDER BY created_at DESC LIMIT 8');
$low = rows("SELECT p.name, p.id, v.label, v.stock FROM variants v JOIN products p ON p.id = v.product_id WHERE p.active = 1 AND v.stock <= 5 ORDER BY v.stock ASC, p.name LIMIT 8");
$top = rows("SELECT oi.name, SUM(oi.qty) q, SUM(oi.qty * oi.price_cents) t FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status IN " . PAID . " GROUP BY oi.name ORDER BY q DESC LIMIT 5");

admin_header('Resumen', 'index');
?>
<div class="kpis">
  <?php if (can('shop')): ?>
  <div class="kpi"><span>Ventas del mes</span><b><?= e(money($salesMonth)) ?></b><small><?= $ordersMonth ?> pedidos · ticket prom. <?= e(money($avg)) ?></small></div>
  <a class="kpi hot" href="<?= e(url('admin/pedidos.php?status=pagado')) ?>"><span>Por enviar</span><b><?= $toShip ?></b><small>pedidos pagados esperando envío</small></a>
  <div class="kpi"><span>Pendientes de pago</span><b><?= $pending ?></b><small>se cancelan solos a las 3 h</small></div>
  <div class="kpi"><span>Ventas totales</span><b><?= e(money($salesAll)) ?></b><small>desde el lanzamiento</small></div>
  <?php endif; ?>
  <div class="kpi"><span>Círculo del Noble</span><b><?= $subs ?></b><small>suscriptores</small></div>
  <a class="kpi" href="<?= e(url('admin/mensajes.php')) ?>"><span>Contrataciones</span><b><?= $unread ?></b><small>mensajes sin leer</small></a>
</div>

<?php if (can('shop')): ?>
<div class="grid-2">
  <section class="card">
    <h2>Ventas · últimos 14 días</h2>
    <div class="bars">
      <?php foreach ($days as $d => $v): ?>
        <div class="bar-col" title="<?= e(fecha_es($d)) ?>: <?= e(money($v)) ?>"><i style="height:<?= max(3, round(100 * $v / $max)) ?>%"></i><span><?= date('j', strtotime($d)) ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="card">
    <h2>Productos más vendidos</h2>
    <?php if ($top): ?>
      <table class="tbl"><?php foreach ($top as $t): ?><tr><td><?= e($t['name']) ?></td><td class="r"><?= (int)$t['q'] ?> pzas</td><td class="r"><?= e(money((int)$t['t'])) ?></td></tr><?php endforeach; ?></table>
    <?php else: ?><p class="muted">Aún no hay ventas.</p><?php endif; ?>
  </section>
</div>

<div class="grid-2">
  <section class="card">
    <div class="card-head"><h2>Últimos pedidos</h2><a href="<?= e(url('admin/pedidos.php')) ?>">Ver todos →</a></div>
    <table class="tbl">
      <?php foreach ($recent as $o): ?>
        <tr><td><a href="<?= e(url('admin/pedido.php?id=' . $o['id'])) ?>"><b><?= e($o['number']) ?></b></a><br><small class="muted"><?= e($o['name']) ?></small></td>
          <td><span class="pill st-<?= e($o['status']) ?>"><?= e(ORDER_STATUSES[$o['status']] ?? $o['status']) ?></span></td>
          <td class="r"><?= e(money((int)$o['total'])) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section>
  <section class="card">
    <div class="card-head"><h2>Inventario bajo</h2><a href="<?= e(url('admin/productos.php')) ?>">Gestionar →</a></div>
    <?php if ($low): ?>
      <table class="tbl"><?php foreach ($low as $l): ?>
        <tr><td><a href="<?= e(url('admin/producto.php?id=' . $l['id'])) ?>"><?= e($l['name']) ?></a> <small class="muted">· <?= e($l['label']) ?></small></td>
          <td class="r"><span class="pill <?= (int)$l['stock'] === 0 ? 'st-cancelado' : 'st-pendiente' ?>"><?= (int)$l['stock'] === 0 ? 'Agotado' : (int)$l['stock'] . ' pzas' ?></span></td></tr>
      <?php endforeach; ?></table>
    <?php else: ?><p class="muted">Todo el inventario está en buen nivel.</p><?php endif; ?>
  </section>
</div>
<?php endif; ?>
<?php admin_footer(); ?>
