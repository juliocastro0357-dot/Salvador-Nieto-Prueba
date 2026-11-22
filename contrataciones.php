<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

$title = 'Contrataciones y prensa';
$active = 'contrataciones';
$desc = 'Contrata a Salvador Nieto, El Noble de la Banda, para bailes, festivales, ferias y eventos privados. Kit de prensa y contacto de management.';
$ok = false;
$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'event_type' => '', 'event_date' => '', 'city' => '', 'message' => ''];

if (is_post()) {
    csrf_verify();
    foreach ($old as $k => $_) {
        $old[$k] = post($k);
    }
    $_SESSION['bk'] = array_filter($_SESSION['bk'] ?? [], fn($t) => $t > time() - 3600);
    if (post('website') !== '') {            // honeypot
        $ok = true;
    } elseif (count($_SESSION['bk']) >= 5) {
        $errors[] = 'Has enviado varias solicitudes seguidas. Inténtalo de nuevo en un rato.';
    } else {
        if (mb_strlen($old['name']) < 2) $errors[] = 'Escribe tu nombre.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Escribe un correo válido.';
        if (mb_strlen($old['message']) < 10) $errors[] = 'Cuéntanos un poco más sobre tu evento (mínimo 10 caracteres).';
        if (!$errors) {
            q('INSERT INTO messages (name, email, phone, event_type, event_date, city, message) VALUES (?,?,?,?,?,?,?)', [
                mb_substr($old['name'], 0, 120), mb_substr($old['email'], 0, 160), mb_substr($old['phone'], 0, 30),
                mb_substr($old['event_type'], 0, 60), mb_substr($old['event_date'], 0, 20), mb_substr($old['city'], 0, 120), mb_substr($old['message'], 0, 3000),
            ]);
            $_SESSION['bk'][] = time();
            send_mail(setting('booking_email', cfg('mail_from')), 'Nueva solicitud de contratación', '<p><b>' . e($old['name']) . '</b> (' . e($old['email']) . ') solicitó información para: ' . e($old['event_type']) . ' en ' . e($old['city']) . '.</p><p>' . nl2br(e($old['message'])) . '</p>');
            $ok = true;
        }
    }
}
require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
  <div class="wrap">
    <span class="eyebrow">Booking · prensa · colaboraciones</span>
    <h1>Hagamos que suceda <em>en vivo.</em></h1>
    <p class="lead">Cuéntanos de tu evento. El equipo de management te responde personalmente.</p>
  </div>
</section>

<section class="section">
  <div class="wrap book-grid">
    <div class="book-form reveal">
      <?php if ($ok): ?>
        <div class="panel panel-ok">
          <?= icon('check', 34) ?>
          <h2>¡Solicitud enviada!</h2>
          <p>Gracias por escribirnos. Revisaremos la información y te contactaremos lo antes posible.</p>
        </div>
      <?php else: ?>
        <form method="post" novalidate class="form" id="bookForm">
          <?= csrf_field() ?>
          <?php if ($errors): ?><div class="flash flash-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
          <div class="hp" aria-hidden="true"><label>Sitio web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="grid-2">
            <label>Nombre completo*<input name="name" value="<?= e($old['name']) ?>" required autocomplete="name"></label>
            <label>Correo electrónico*<input type="email" name="email" value="<?= e($old['email']) ?>" required autocomplete="email"></label>
            <label>Teléfono / WhatsApp<input name="phone" value="<?= e($old['phone']) ?>" autocomplete="tel"></label>
            <label>Tipo de evento
              <select name="event_type">
                <?php foreach (['Baile / evento privado', 'Feria o festival', 'Palenque / teatro', 'Evento corporativo', 'Boda / XV años', 'Otro'] as $o): ?>
                  <option <?= $old['event_type'] === $o ? 'selected' : '' ?>><?= e($o) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Fecha tentativa<input type="date" name="event_date" value="<?= e($old['event_date']) ?>"></label>
            <label>Ciudad y estado<input name="city" value="<?= e($old['city']) ?>" placeholder="Ej. Mexicali, B.C."></label>
          </div>
          <label>Cuéntanos de tu evento*<textarea name="message" rows="5" required><?= e($old['message']) ?></textarea></label>
          <button class="btn btn-gold" type="submit">Enviar solicitud</button>
          <p class="fine">Tus datos se usan únicamente para responder tu solicitud. Consulta el <a href="<?= e(url('legal.php#privacidad')) ?>">aviso de privacidad</a>.</p>
        </form>
      <?php endif; ?>
    </div>

    <aside class="book-side">
      <div class="panel reveal" id="epk">
        <h3>Kit de prensa (EPK)</h3>
        <p class="muted">Biografía oficial para medios, programadores y organizadores.</p>
        <p class="epk-bio" id="epkBio"><?= e(setting('bio_short')) ?></p>
        <button class="btn btn-outline btn-sm" type="button" data-copy="#epkBio">Copiar biografía</button>
      </div>
      <div class="panel reveal">
        <h3>Contacto directo</h3>
        <ul class="contact-list">
          <?php if (setting('booking_email')): ?><li><?= icon('mail', 20) ?> <a href="mailto:<?= e(setting('booking_email')) ?>"><?= e(setting('booking_email')) ?></a></li><?php endif; ?>
          <?php if (setting('booking_phone')): ?><li><?= icon('user', 20) ?> <?= e(setting('booking_phone')) ?></li><?php endif; ?>
          <?php if (setting('whatsapp')): ?><li><?= icon('whatsapp', 20) ?> <a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting('whatsapp'))) ?>" target="_blank" rel="noopener">WhatsApp</a></li><?php endif; ?>
        </ul>
      </div>
    </aside>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
