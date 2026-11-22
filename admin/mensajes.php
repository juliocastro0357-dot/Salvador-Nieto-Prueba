<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require __DIR__ . '/_layout.php';
require_cap('content');

if (isset($_GET['export']) && $_GET['export'] === 'subs') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="suscriptores-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Correo', 'Fecha de registro']);
    foreach (rows('SELECT email, created_at FROM subscribers ORDER BY id DESC') as $s) {
        fputcsv($out, [preg_match('/^[=+\-@]/', $s['email']) ? "'" . $s['email'] : $s['email'], $s['created_at']]);
    }
    exit;
}

if (is_post()) {
    csrf_verify();
    $id = (int)post('id');
    match (post('action')) {
        'read' => q('UPDATE messages SET is_read = 1 WHERE id = ?', [$id]),
        'unread' => q('UPDATE messages SET is_read = 0 WHERE id = ?', [$id]),
        'delete' => q('DELETE FROM messages WHERE id = ?', [$id]),
        'del_sub' => q('DELETE FROM subscribers WHERE id = ?', [$id]),
        default => null,
    };
    redirect('admin/mensajes.php');
}

$msgs = rows('SELECT * FROM messages ORDER BY is_read ASC, created_at DESC LIMIT 100');
$subs = rows('SELECT * FROM subscribers ORDER BY id DESC LIMIT 100');
$subCount = (int)val('SELECT COUNT(*) FROM subscribers');
admin_header('Contrataciones y suscriptores', 'mensajes');
?>
<h2 class="h2">Solicitudes de contratación</h2>
<?php foreach ($msgs as $m): ?>
  <article class="card msg <?= $m['is_read'] ? '' : 'unread' ?>">
    <div class="card-head">
      <div><b><?= e($m['name']) ?></b> · <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><?= $m['phone'] ? ' · ' . e($m['phone']) : '' ?></div>
      <small class="muted"><?= e(date('d/m/Y H:i', strtotime($m['created_at']))) ?></small>
    </div>
    <p class="meta"><?= e($m['event_type'] ?: 'Evento') ?><?= $m['event_date'] ? ' · ' . e(fecha_es($m['event_date'])) : '' ?><?= $m['city'] ? ' · ' . e($m['city']) : '' ?></p>
    <p><?= nl2br(e($m['message'])) ?></p>
    <form method="post" class="inline-actions"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
      <button class="link" name="action" value="<?= $m['is_read'] ? 'unread' : 'read' ?>"><?= $m['is_read'] ? 'Marcar como no leído' : 'Marcar como leído' ?></button>
      <button class="link danger" name="action" value="delete" onclick="return confirm('¿Eliminar este mensaje?')">Eliminar</button></form>
  </article>
<?php endforeach; ?>
<?php if (!$msgs): ?><p class="muted">Aún no hay solicitudes.</p><?php endif; ?>

<div class="card-head mt"><h2 class="h2">Círculo del Noble · <?= $subCount ?> suscriptores</h2><a class="btn btn-ghost" href="?export=subs">Exportar CSV</a></div>
<div class="card flush">
  <table class="tbl wide">
    <?php foreach ($subs as $s): ?>
      <tr><td><?= e($s['email']) ?></td><td><small class="muted"><?= e(date('d/m/Y', strtotime($s['created_at']))) ?></small></td>
        <td class="r"><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="del_sub"><button class="link danger" onclick="return confirm('¿Quitar este correo?')">Quitar</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (!$subs): ?><tr><td class="muted center">Sin suscriptores todavía.</td></tr><?php endif; ?>
  </table>
</div>
<?php admin_footer(); ?>
