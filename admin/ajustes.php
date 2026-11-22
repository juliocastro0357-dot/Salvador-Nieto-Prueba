<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require __DIR__ . '/_layout.php';
require_cap('settings');

$text = [
    'Contenido del sitio' => [
        ['hero_tagline', 'Frase del inicio', 'text'],
        ['bio_short', 'Biografía corta (inicio, prensa y buscadores)', 'textarea'],
        ['bio_long', 'Biografía completa (separa los párrafos con una línea en blanco)', 'textarea', 10],
        ['bio_quote', 'Frase destacada del artista', 'text'],
    ],
    'Contacto y redes' => [
        ['booking_email', 'Correo de contrataciones', 'email'],
        ['booking_phone', 'Teléfono de management', 'text'],
        ['whatsapp', 'WhatsApp (con lada, ej. 5216860000000)', 'text'],
        ['social_instagram', 'Instagram (URL)', 'url'], ['social_facebook', 'Facebook (URL)', 'url'], ['social_tiktok', 'TikTok (URL)', 'url'],
        ['social_youtube', 'YouTube (URL)', 'url'], ['social_spotify', 'Spotify (URL del artista)', 'url'],
        ['social_apple', 'Apple Music (URL del artista)', 'url'], ['social_amazon', 'Amazon Music (URL del artista)', 'url'],
    ],
    'Tienda' => [
        ['store_notice', 'Aviso en la tienda', 'text'],
        ['shipping_flat', 'Costo de envío (pesos)', 'money'],
        ['free_shipping_min', 'Envío gratis desde (pesos, 0 = nunca)', 'money'],
    ],
];

if (is_post()) {
    csrf_verify();
    foreach ($text as $fields) {
        foreach ($fields as $f) {
            [$k, , $type] = $f;
            $v = trim((string)($_POST[$k] ?? ''));
            if ($type === 'url' && $v !== '' && !preg_match('#^https?://#i', $v)) {
                flash('error', 'El enlace de “' . $f[1] . '” debe empezar con http:// o https://');
                redirect('admin/ajustes.php');
            }
            if ($type === 'money') {
                $v = (string)pesos_to_cents($v);
            }
            set_setting($k, mb_substr($v, 0, $type === 'textarea' ? 6000 : 500));
        }
    }
    flash('ok', 'Ajustes guardados.');
    redirect('admin/ajustes.php');
}

admin_header('Ajustes del sitio', 'ajustes');
?>
<form method="post" class="settings">
  <?= csrf_field() ?>
  <?php foreach ($text as $title => $fields): ?>
    <section class="card form">
      <h2><?= e($title) ?></h2>
      <?php foreach ($fields as $f): [$k, $label, $type] = $f;
          $v = setting($k);
          if ($type === 'money') $v = cents_to_pesos((int)$v); ?>
        <label><?= e($label) ?>
          <?php if ($type === 'textarea'): ?>
            <textarea name="<?= e($k) ?>" rows="<?= (int)($f[3] ?? 3) ?>"><?= e($v) ?></textarea>
          <?php else: ?>
            <input type="<?= $type === 'money' ? 'text' : e($type) ?>" name="<?= e($k) ?>" value="<?= e($v) ?>">
          <?php endif; ?>
        </label>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
  <div class="sticky-save"><button class="btn btn-primary" type="submit">Guardar ajustes</button></div>
</form>
<?php admin_footer(); ?>
