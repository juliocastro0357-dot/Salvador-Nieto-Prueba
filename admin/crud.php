<?php
/** CRUD genérico para: música, gira, videos y cupones. */
require dirname(__DIR__) . '/inc/bootstrap.php';
require __DIR__ . '/_layout.php';
require_once dirname(__DIR__) . '/inc/partials.php';

$F = fn(string $n, string $l, string $t = 'text', array $o = []) => ['n' => $n, 'l' => $l, 't' => $t] + $o;

$ENT = [
    'tracks' => [
        'title' => 'Música', 'cap' => 'content', 'active' => 'tracks', 'order' => 'sort ASC, release_date DESC',
        'help' => 'Cada canción se reproduce 30 segundos en el sitio. Sube solo el fragmento de 30 s (el sistema lo recorta si el servidor tiene ffmpeg) para que nadie pueda descargar la canción completa.',
        'list' => ['title' => 'Título', 'kind' => 'Tipo', 'release_date' => 'Lanzamiento', 'published' => 'Publicada'],
        'fields' => [
            $F('title', 'Título', 'text', ['req' => 1]),
            $F('kind', 'Tipo', 'select', ['opts' => TRACK_KINDS]),
            $F('release_date', 'Fecha de lanzamiento', 'date'),
            $F('cover', 'Portada (cuadrada, JPG/PNG)', 'image'),
            $F('audio', 'Preview en MP3 (30 segundos)', 'audio'),
            $F('spotify', 'Link de Spotify', 'url'), $F('apple', 'Link de Apple Music', 'url'), $F('youtube', 'Link de YouTube Music', 'url'),
            $F('amazon', 'Link de Amazon Music', 'url'), $F('deezer', 'Link de Deezer', 'url'),
            $F('featured', 'Destacada en el inicio (solo una)', 'check'), $F('published', 'Publicada', 'check', ['def' => 1]), $F('sort', 'Orden manual (0 = primero)', 'number'),
        ],
    ],
    'shows' => [
        'title' => 'Gira', 'cap' => 'content', 'active' => 'shows', 'order' => 'date DESC',
        'help' => 'Las fechas se muestran en el inicio y en la página de Gira. Cambia el estado a “Finalizado” después del evento.',
        'list' => ['date' => 'Fecha', 'city' => 'Ciudad', 'venue' => 'Sede', 'status' => 'Estado'],
        'fields' => [
            $F('date', 'Fecha', 'date', ['req' => 1]), $F('city', 'Ciudad y estado', 'text', ['req' => 1]), $F('venue', 'Sede / evento', 'text'),
            $F('ticket_url', 'Link de boletos', 'url'),
            $F('status', 'Estado', 'select', ['opts' => ['proximo' => 'Próximo', 'agotado' => 'Boletos agotados', 'finalizado' => 'Finalizado']]),
            $F('published', 'Publicada', 'check', ['def' => 1]),
        ],
    ],
    'videos' => [
        'title' => 'Videos', 'cap' => 'content', 'active' => 'videos', 'order' => 'sort ASC, id DESC',
        'help' => 'Pega el ID del video de YouTube (lo que va después de “v=” en el enlace). Si no hay videos publicados, la sección se oculta sola.',
        'list' => ['title' => 'Título', 'youtube_id' => 'ID de YouTube', 'published' => 'Publicado'],
        'fields' => [
            $F('title', 'Título del video', 'text', ['req' => 1]), $F('youtube_id', 'ID de YouTube (ej. dQw4w9WgXcQ)', 'text', ['req' => 1]),
            $F('published', 'Publicado', 'check', ['def' => 1]), $F('sort', 'Orden manual', 'number'),
        ],
    ],
    'coupons' => [
        'title' => 'Cupones', 'cap' => 'shop', 'active' => 'cupones', 'order' => 'id DESC',
        'help' => 'Tipo “Porcentaje”: el valor es el % de descuento (ej. 10). Tipo “Monto fijo”: el valor es en pesos (ej. 100).',
        'list' => ['code' => 'Código', 'type' => 'Tipo', 'value' => 'Valor', 'min_total' => 'Compra mínima', 'active' => 'Activo'],
        'fields' => [
            $F('code', 'Código', 'text', ['req' => 1]), $F('type', 'Tipo', 'select', ['opts' => ['pct' => 'Porcentaje (%)', 'fixed' => 'Monto fijo ($)']]),
            $F('value', 'Valor', 'number', ['req' => 1]), $F('min_total', 'Compra mínima (pesos, opcional)', 'number'), $F('active', 'Activo', 'check', ['def' => 1]),
        ],
    ],
];

$key = $_GET['e'] ?? '';
if (!isset($ENT[$key])) {
    redirect('admin/index.php');
}
$E = $ENT[$key];
require_cap($E['cap']);
$table = $key;
$id = (int)($_GET['id'] ?? 0);
$mode = isset($_GET['new']) ? 'form' : ($id ? 'form' : 'list');
$self = url('admin/crud.php?e=' . $key);

/* Conversión de pesos <-> centavos en cupones */
$toForm = function (array $r) use ($key) {
    if ($key === 'coupons') {
        $r['min_total'] = (int)$r['min_total'] / 100;
        if ($r['type'] === 'fixed') $r['value'] = (int)$r['value'] / 100;
    }
    return $r;
};

if (is_post()) {
    csrf_verify();
    if (post('delete')) {
        $rid = (int)post('delete');
        $old = row("SELECT * FROM $table WHERE id = ?", [$rid]);
        q("DELETE FROM $table WHERE id = ?", [$rid]);
        foreach (['cover', 'audio'] as $f) {
            if ($old && isset($old[$f]) && str_starts_with((string)$old[$f], 'uploads/') && is_file(ROOT . '/' . $old[$f])) @unlink(ROOT . '/' . $old[$f]);
        }
        flash('ok', 'Elemento eliminado.');
        redirect('admin/crud.php?e=' . $key);
    }
    $cur = $id ? row("SELECT * FROM $table WHERE id = ?", [$id]) : null;
    try {
        $vals = [];
        foreach ($E['fields'] as $f) {
            $n = $f['n'];
            switch ($f['t']) {
                case 'check': $vals[$n] = isset($_POST[$n]) ? 1 : 0; break;
                case 'number': $vals[$n] = (int)post($n); break;
                case 'image': $vals[$n] = (isset($_FILES[$n]) ? upload_image($_FILES[$n]) : null) ?? ($cur[$n] ?? null); break;
                case 'audio': $vals[$n] = (isset($_FILES[$n]) ? upload_audio($_FILES[$n]) : null) ?? ($cur[$n] ?? null); break;
                case 'url':
                    $u = post($n);
                    if ($u !== '' && !preg_match('#^https?://#i', $u)) throw new RuntimeException('Los links deben empezar con http:// o https://');
                    $vals[$n] = $u !== '' ? mb_substr($u, 0, 500) : null; break;
                case 'select':
                    $vals[$n] = array_key_exists(post($n), $f['opts']) ? post($n) : array_key_first($f['opts']); break;
                default:
                    $vals[$n] = mb_substr(post($n), 0, 300);
                    if (!empty($f['req']) && $vals[$n] === '') throw new RuntimeException('Completa el campo: ' . $f['l']);
            }
        }
        if ($key === 'coupons') {
            $vals['code'] = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $vals['code']));
            if ($vals['code'] === '') throw new RuntimeException('El código solo puede tener letras, números y guiones.');
            if ($vals['value'] <= 0) throw new RuntimeException('El valor debe ser mayor a cero.');
            if ($vals['type'] === 'pct' && $vals['value'] > 100) throw new RuntimeException('El porcentaje no puede pasar de 100.');
            if ($vals['type'] === 'fixed') $vals['value'] *= 100;
            $vals['min_total'] *= 100;
        }
        if ($key === 'videos' && !preg_match('/^[\w-]{6,20}$/', (string)$vals['youtube_id'])) {
            throw new RuntimeException('El ID de YouTube no es válido. Copia solo el código del video.');
        }
        if ($key === 'tracks' && !empty($vals['featured'])) {
            q('UPDATE tracks SET featured = 0 WHERE id != ?', [$id]);
        }
        $cols = array_keys($vals);
        if ($id) {
            q("UPDATE $table SET " . implode(', ', array_map(fn($c) => "$c = ?", $cols)) . ' WHERE id = ?', [...array_values($vals), $id]);
        } else {
            q("INSERT INTO $table (" . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($vals));
        }
        flash('ok', 'Guardado correctamente.');
        redirect('admin/crud.php?e=' . $key);
    } catch (PDOException $ex) {
        flash('error', str_contains($ex->getMessage(), 'UNIQUE') ? 'Ya existe un registro con ese valor.' : 'No se pudo guardar.');
        $mode = 'form';
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
        $mode = 'form';
    }
}

admin_header($E['title'], $E['active']);
if ($mode === 'list'):
    $list = rows("SELECT * FROM $table ORDER BY {$E['order']}");
?>
<div class="toolbar"><p class="muted"><?= e($E['help']) ?></p><a class="btn btn-primary" href="<?= e($self . '&new=1') ?>">+ Agregar</a></div>
<div class="card flush">
  <table class="tbl wide">
    <thead><tr><?php foreach ($E['list'] as $l): ?><th><?= e($l) ?></th><?php endforeach; ?><th></th></tr></thead>
    <?php foreach ($list as $r): ?>
      <tr>
        <?php foreach ($E['list'] as $c => $_):
            $v = $r[$c] ?? '';
            if ($key === 'coupons') {
                if ($c === 'value') $v = $r['type'] === 'pct' ? $v . '%' : money((int)$v);
                if ($c === 'min_total') $v = (int)$v ? money((int)$v) : '—';
                if ($c === 'type') $v = $r['type'] === 'pct' ? 'Porcentaje' : 'Monto fijo';
            }
            if ($c === 'kind') $v = TRACK_KINDS[$v] ?? $v;
            if ($c === 'date' || $c === 'release_date') $v = $v ? date('d/m/Y', strtotime($v)) : '—';
            if ($c === 'status') $v = ['proximo' => 'Próximo', 'agotado' => 'Agotado', 'finalizado' => 'Finalizado'][$v] ?? $v;
            if (in_array($c, ['published', 'active'], true)) $v = $v ? '<span class="pill st-pagado">Sí</span>' : '<span class="pill st-cancelado">No</span>';
            else $v = e($v);
        ?>
          <td><?= $c === array_key_first($E['list']) ? '<a href="' . e($self . '&id=' . $r['id']) . '"><b>' . $v . '</b></a>' : $v ?></td>
        <?php endforeach; ?>
        <td class="r nowrap">
          <a href="<?= e($self . '&id=' . $r['id']) ?>">Editar</a>
          <form method="post" class="inline" onsubmit="return confirm('¿Eliminar este elemento?')"><?= csrf_field() ?><input type="hidden" name="delete" value="<?= (int)$r['id'] ?>"><button class="link danger">Eliminar</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="<?= count($E['list']) + 1 ?>" class="muted center">Aún no hay elementos.</td></tr><?php endif; ?>
  </table>
</div>
<?php
else:
    $cur = $id ? row("SELECT * FROM $table WHERE id = ?", [$id]) : null;
    if ($id && !$cur) redirect('admin/crud.php?e=' . $key);
    $cur = $cur ? $toForm($cur) : [];
    if (is_post()) { $cur = array_merge($cur, $_POST); }
?>
<p><a href="<?= e($self) ?>">← Volver</a></p>
<form method="post" enctype="multipart/form-data" class="card form narrow-form">
  <?= csrf_field() ?>
  <h2><?= $id ? 'Editar' : 'Agregar' ?> · <?= e($E['title']) ?></h2>
  <?php foreach ($E['fields'] as $f):
      $n = $f['n'];
      $v = $cur[$n] ?? ($f['def'] ?? ($f['t'] === 'check' ? 0 : '')); ?>
    <?php if ($f['t'] === 'check'): ?>
      <label class="check"><input type="checkbox" name="<?= e($n) ?>" <?= $v ? 'checked' : '' ?>> <?= e($f['l']) ?></label>
    <?php elseif ($f['t'] === 'select'): ?>
      <label><?= e($f['l']) ?><select name="<?= e($n) ?>"><?php foreach ($f['opts'] as $ov => $ol): ?><option value="<?= e($ov) ?>" <?= (string)$v === (string)$ov ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select></label>
    <?php elseif ($f['t'] === 'image'): ?>
      <label><?= e($f['l']) ?>
        <?php if (!empty($cur[$n])): ?><img class="preview" src="<?= e(media($cur[$n])) ?>" alt="" width="120" height="120"><?php endif; ?>
        <input type="file" name="<?= e($n) ?>" accept="image/jpeg,image/png,image/webp"></label>
    <?php elseif ($f['t'] === 'audio'): ?>
      <label><?= e($f['l']) ?>
        <?php if (!empty($cur[$n])): ?><audio controls preload="none" src="<?= e(media($cur[$n], '')) ?>"></audio><?php endif; ?>
        <input type="file" name="<?= e($n) ?>" accept="audio/mpeg,.mp3"></label>
    <?php else: ?>
      <label><?= e($f['l']) ?><?= !empty($f['req']) ? '*' : '' ?><input type="<?= e($f['t']) ?>" name="<?= e($n) ?>" value="<?= e($v) ?>" <?= !empty($f['req']) ? 'required' : '' ?>></label>
    <?php endif; ?>
  <?php endforeach; ?>
  <button class="btn btn-primary" type="submit">Guardar</button>
</form>
<?php endif;
admin_footer(); ?>
