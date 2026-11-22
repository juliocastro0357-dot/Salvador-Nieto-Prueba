<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/inc/icons.php';

function admin_header(string $title, string $active = ''): void
{
    $u = auth_user();
    $menu = [
        ['index', 'Resumen', 'admin/index.php', 'reports'],
        ['pedidos', 'Pedidos', 'admin/pedidos.php', 'shop'],
        ['productos', 'Productos e inventario', 'admin/productos.php', 'shop'],
        ['cupones', 'Cupones', 'admin/crud.php?e=coupons', 'shop'],
        ['tracks', 'Música', 'admin/crud.php?e=tracks', 'content'],
        ['shows', 'Gira', 'admin/crud.php?e=shows', 'content'],
        ['videos', 'Videos', 'admin/crud.php?e=videos', 'content'],
        ['mensajes', 'Contrataciones y suscriptores', 'admin/mensajes.php', 'content'],
        ['ajustes', 'Ajustes del sitio', 'admin/ajustes.php', 'settings'],
        ['cuenta', 'Mi cuenta', 'admin/cuenta.php', 'reports'],
    ];
    $unread = can('content') ? (int)val('SELECT COUNT(*) FROM messages WHERE is_read = 0') : 0;
    $toShip = can('shop') ? (int)val("SELECT COUNT(*) FROM orders WHERE status = 'pagado'") : 0;
    ?><!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Panel</title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body>
<aside class="side" id="side">
  <a class="side-brand" href="<?= e(url('admin/index.php')) ?>"><b>SN</b><span>Panel de administración</span></a>
  <nav>
    <?php foreach ($menu as [$key, $label, $href, $cap]):
        if (!can($cap)) continue; ?>
      <a class="<?= $active === $key ? 'on' : '' ?>" href="<?= e(url($href)) ?>">
        <?= e($label) ?>
        <?php if ($key === 'mensajes' && $unread): ?><em><?= $unread ?></em><?php endif; ?>
        <?php if ($key === 'pedidos' && $toShip): ?><em><?= $toShip ?></em><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">Ver sitio ↗</a>
    <a href="<?= e(url('admin/logout.php')) ?>">Cerrar sesión</a>
    <small><?= e($u['name']) ?> · <?= e(['admin' => 'Administrador', 'provider' => 'Proveedor', 'artist' => 'Artista'][$u['role']] ?? $u['role']) ?></small>
  </div>
</aside>
<div class="main">
  <header class="topbar">
    <button class="menu-btn" onclick="document.getElementById('side').classList.toggle('open')" aria-label="Menú">☰</button>
    <h1><?= e($title) ?></h1>
  </header>
  <div class="content">
    <?php if (!empty($u['must_change'])): ?>
      <div class="alert alert-warn">Estás usando una contraseña de demostración. <a href="<?= e(url('admin/cuenta.php')) ?>">Cámbiala ahora</a> antes de entregar el sitio.</div>
    <?php endif; ?>
    <?= flashes_html() ?>
<?php
}

function admin_footer(): void
{
    echo "\n  </div>\n</div>\n</body>\n</html>";
}

function pager(int $total, int $per, int $page, string $baseQuery): string
{
    $pages = (int)ceil($total / $per);
    if ($pages <= 1) {
        return '';
    }
    $h = '<nav class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        $h .= '<a class="' . ($i === $page ? 'on' : '') . '" href="?' . e($baseQuery . '&page=' . $i) . '">' . $i . '</a>';
    }
    return $h . '</nav>';
}

function pesos_to_cents(string $v): int
{
    $v = str_replace([',', '$', ' '], '', $v);
    return (int)round(((float)$v) * 100);
}
function cents_to_pesos(?int $c): string
{
    return $c === null ? '' : rtrim(rtrim(number_format($c / 100, 2, '.', ''), '0'), '.');
}
