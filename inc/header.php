<?php
/** Variables esperadas: $title, $desc (opcional), $active (opcional), $og (opcional) */
require_once __DIR__ . '/icons.php';
$artist = cfg('artist_name');
$alias = cfg('artist_alias');
$title = isset($title) ? $title . ' · ' . $artist : $artist . ' · ' . $alias;
$desc = $desc ?? setting('bio_short');
$active = $active ?? '';
$og = $og ?? asset('assets/img/hero-mic.svg');
$nav = [
    'inicio' => ['Inicio', 'index.php'],
    'biografia' => ['Biografía', 'biografia.php'],
    'musica' => ['Música', 'musica.php'],
    'gira' => ['Gira', 'gira.php'],
    'tienda' => ['Tienda', 'tienda.php'],
    'contrataciones' => ['Contrataciones', 'contrataciones.php'],
];
$cartCount = cart_count();
?><!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if (cfg('demo_mode')): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<meta name="theme-color" content="#0c0907">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:image" content="<?= e($og) ?>">
<link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Inter:wght@400;500;600&family=Playfair+Display:ital,wght@0,500;0,700;1,500;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
<script>document.documentElement.classList.add('js');window.SN={base:<?= json_encode(BASE) ?>,csrf:<?= json_encode(csrf()) ?>};</script>
</head>
<body class="page-<?= e($active) ?>">
<a class="skip" href="#main">Saltar al contenido</a>
<header class="site-header" id="header">
  <div class="wrap bar">
    <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="<?= e($artist) ?> — inicio">
      <span class="brand-name"><?= e(mb_strtoupper($artist)) ?></span>
      <span class="brand-alias"><?= e(mb_strtoupper($alias)) ?></span>
    </a>
    <nav class="nav" id="nav" aria-label="Principal">
      <?php foreach ($nav as $key => [$label, $href]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $active === $key ? 'on' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="bar-actions">
      <a class="cart-btn" href="<?= e(url('carrito.php')) ?>" aria-label="Carrito (<?= $cartCount ?>)">
        <?= icon('cart', 22) ?><span class="cart-count" id="cartCount" <?= $cartCount ? '' : 'hidden' ?>><?= $cartCount ?></span>
      </a>
      <button class="burger" id="burger" aria-label="Abrir menú" aria-expanded="false" aria-controls="nav"><?= icon('menu', 26) ?></button>
    </div>
  </div>
</header>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<main id="main">
<?= flashes_html() ?>
