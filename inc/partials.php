<?php
declare(strict_types=1);
require_once __DIR__ . '/icons.php';

const TRACK_KINDS = ['sencillo' => 'Sencillo', 'album' => 'Álbum', 'vivo' => 'En vivo'];

function play_attrs(array $t): string
{
    $link = $t['spotify'] ?: ($t['apple'] ?: ($t['youtube'] ?: ''));
    return 'data-play data-src="' . e(media($t['audio'], '')) . '" data-title="' . e($t['title']) . '" data-cover="' . e(media($t['cover'])) . '" data-link="' . e($link) . '"';
}

function track_card(array $t): string
{
    $year = $t['release_date'] ? date('Y', strtotime($t['release_date'])) : '';
    $kind = TRACK_KINDS[$t['kind']] ?? '';
    $plats = '';
    foreach (platform_links($t) as $p) {
        $plats .= '<a href="' . e($p['url']) . '" target="_blank" rel="noopener" title="Escuchar en ' . e($p['label']) . '">' . e($p['label']) . '</a>';
    }
    ob_start(); ?>
    <article class="track reveal">
      <button class="cover" type="button" <?= play_attrs($t) ?> aria-label="Escuchar vista previa de <?= e($t['title']) ?>">
        <img src="<?= e(media($t['cover'])) ?>" alt="Portada de <?= e($t['title']) ?>" loading="lazy" width="600" height="600">
        <span class="cover-play"><?= icon('play', 26) ?></span>
        <span class="cover-badge">30 s</span>
      </button>
      <div class="track-meta">
        <h3><?= e($t['title']) ?></h3>
        <p><?= e(trim($year . ($kind ? ' · ' . $kind : ''), ' ·')) ?></p>
      </div>
      <?php if ($plats): ?><div class="plats"><?= $plats ?></div><?php endif; ?>
    </article>
    <?php
    return (string)ob_get_clean();
}

function product_card(array $p): string
{
    $imgs = product_images($p);
    $stock = product_stock((int)$p['id']);
    ob_start(); ?>
    <a class="pcard reveal" href="<?= e(url('producto.php?slug=' . urlencode($p['slug']))) ?>">
      <span class="pimg">
        <img src="<?= e(media($imgs[0], 'assets/img/products/p1.svg')) ?>" alt="<?= e($p['name']) ?>" loading="lazy" width="600" height="600">
        <?php if ($stock < 1): ?><span class="tag tag-out">Agotado</span>
        <?php elseif ((int)$p['is_new']): ?><span class="tag">Nuevo</span><?php endif; ?>
      </span>
      <span class="pinfo">
        <span class="pcat"><?= e($p['category']) ?></span>
        <strong class="pname"><?= e($p['name']) ?></strong>
        <span class="pprice"><?= e(money((int)$p['price_cents'])) ?>
          <?php if (!empty($p['compare_cents'])): ?><s><?= e(money((int)$p['compare_cents'])) ?></s><?php endif; ?>
        </span>
      </span>
    </a>
    <?php
    return (string)ob_get_clean();
}

function section_head(string $eyebrow, string $title, string $accent = '', string $link = '', string $linkLabel = ''): string
{
    $h = '<div class="sec-head reveal"><div><span class="eyebrow">' . e($eyebrow) . '</span><h2>' . e($title) . ($accent ? ' <em>' . e($accent) . '</em>' : '') . '</h2></div>';
    if ($link) {
        $h .= '<a class="link-arrow" href="' . e($link) . '">' . e($linkLabel) . ' ' . icon('arrow', 18) . '</a>';
    }
    return $h . '</div>';
}

function show_row(array $s): string
{
    $d = strtotime($s['date']);
    $past = $s['status'] === 'finalizado';
    $badge = match ($s['status']) {
        'agotado' => '<span class="badge badge-out">Agotado</span>',
        'finalizado' => '<span class="badge badge-past">Finalizado</span>',
        default => $s['ticket_url'] ? '<a class="btn btn-gold btn-sm" href="' . e($s['ticket_url']) . '" target="_blank" rel="noopener">' . icon('ticket', 16) . ' Boletos</a>' : '<span class="badge">Próximamente</span>',
    };
    ob_start(); ?>
    <li class="show reveal <?= $past ? 'is-past' : '' ?>">
      <time datetime="<?= e($s['date']) ?>" class="show-date"><b><?= date('j', $d) ?></b><span><?= e(substr(fecha_es($s['date'], 'short'), -3)) ?></span></time>
      <div class="show-info"><strong><?= e($s['city']) ?></strong><span><?= e($s['venue'] ?: 'Sede por confirmar') ?> · <?= date('Y', $d) ?></span></div>
      <div class="show-cta"><?= $badge ?></div>
    </li>
    <?php
    return (string)ob_get_clean();
}
