<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require __DIR__ . '/_layout.php';
require_cap('reports');

$u = auth_user();
if (is_post()) {
    csrf_verify();
    $row = row('SELECT * FROM users WHERE id = ?', [$u['id']]);
    $cur = (string)($_POST['current'] ?? '');
    $new = (string)($_POST['new'] ?? '');
    if (!password_verify($cur, $row['password_hash'])) {
        flash('error', 'La contraseña actual no es correcta.');
    } elseif (mb_strlen($new) < 10 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
        flash('error', 'La nueva contraseña debe tener al menos 10 caracteres, con letras y números.');
    } elseif ($new !== (string)($_POST['confirm'] ?? '')) {
        flash('error', 'La confirmación no coincide.');
    } else {
        q('UPDATE users SET password_hash = ?, must_change = 0 WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        $_SESSION['user']['must_change'] = 0;
        flash('ok', 'Contraseña actualizada.');
    }
    redirect('admin/cuenta.php');
}
admin_header('Mi cuenta', 'cuenta');
?>
<form method="post" class="card form narrow-form">
  <?= csrf_field() ?>
  <h2>Cambiar contraseña</h2>
  <p class="muted small"><?= e($u['email']) ?></p>
  <label>Contraseña actual<input type="password" name="current" required autocomplete="current-password"></label>
  <label>Nueva contraseña (mín. 10 caracteres, letras y números)<input type="password" name="new" required autocomplete="new-password" minlength="10"></label>
  <label>Confirmar nueva contraseña<input type="password" name="confirm" required autocomplete="new-password"></label>
  <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
</form>
<?php admin_footer(); ?>
