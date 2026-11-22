<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require __DIR__ . '/_layout.php';
require_cap('shop');

$status = $_GET['status'] ?? '';
$qs = trim((string)($_GET['q'] ?? ''));
$where = '1=1';
$params = [];
if (isset(ORDER_STATUSES[$status])) {
    $where .= ' AND status = ?';
    $params[] = $status;
} else {
    $status = '';
}
if ($qs !== '') {
    $where .= ' AND (number LIKE ? OR name LIKE ? OR email LIKE ?)';
    array_push($params, "%$qs%", "%$qs%", "%$qs%");
}

if (isset($_GET['export'])) {
    $data = rows("SELECT * FROM orders WHERE $where ORDER BY created_at DESC", $params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pedidos-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Pedido', 'Fecha', 'Estado', 'Cliente', 'Correo', 'Teléfono', 'Dirección', 'Ciudad', 'Estado', 'CP', 'Subtotal', 'Envío', 'Descuento', 'Total', 'Paquetería', 'Guía']);
    foreach ($data as $o) {
        $csvSafe = fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v;
        fputcsv($out, array_map($csvSafe, [$o['number'], $o['created_at'], $o['status'], $o['name'], $o['email'], $o['phone'], $o['address1'] . ' ' . $o['address2'], $o['city'], $o['state'], $o['zip'],
            $o['subtotal'] / 100, $o['shipping'] / 100, $o['discount'] / 100, $o['total'] / 100, $o['carrier'], $o['tracking']]));
    }
    exit;
}

$per = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)val("SELECT COUNT(*) FROM orders WHERE $where", $params);
$list = rows("SELECT * FROM orders WHERE $where ORDER BY created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params);
$counts = [];
foreach (rows('SELECT status, COUNT(*) c FROM orders GROUP BY status') as $r) {
    $counts[$r['status']] = (int)$r['c'];
}
$base = http_build_query(['status' => $status, 'q' => $qs]);

admin_header('Pedidos', 'pedidos');
?>
<div class="toolbar">
  <nav class="tabs">
    <a class="<?= $status === '' ? 'on' : '' ?>" href="?">Todos <em><?= array_sum($counts) ?></em></a>
    <?php foreach (ORDER_STATUSES as $k => $l): ?>
      <a class="<?= $status === $k ? 'on' : '' ?>" href="?status=<?= e($k) ?>"><?= e($l) ?> <em><?= $counts[$k] ?? 0 ?></em></a>
    <?php endforeach; ?>
  </nav>
  <form class="search" method="get">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <input name="q" value="<?= e($qs) ?>" placeholder="Buscar pedido, nombre o correo">
    <button class="btn">Buscar</button>
    <a class="btn btn-ghost" href="?<?= e($base) ?>&export=1">Exportar CSV</a>
  </form>
</div>

<div class="card flush">
  <table class="tbl wide">
    <thead><tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th>Guía</th><th class="r">Total</th></tr></thead>
    <?php foreach ($list as $o): ?>
      <tr>
        <td><a href="<?= e(url('admin/pedido.php?id=' . $o['id'])) ?>"><b><?= e($o['number']) ?></b></a></td>
        <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
        <td><?= e($o['name']) ?><br><small class="muted"><?= e($o['city']) ?>, <?= e($o['state']) ?></small></td>
        <td><span class="pill st-<?= e($o['status']) ?>"><?= e(ORDER_STATUSES[$o['status']] ?? $o['status']) ?></span></td>
        <td><small><?= e(trim(($o['carrier'] ?? '') . ' ' . ($o['tracking'] ?? ''))) ?: '—' ?></small></td>
        <td class="r"><b><?= e(money((int)$o['total'])) ?></b></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="6" class="muted center">No hay pedidos con estos filtros.</td></tr><?php endif; ?>
  </table>
</div>
<?= pager($total, $per, $page, $base) ?>
<?php admin_footer(); ?>
