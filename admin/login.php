<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/icons.php';

if (auth_user()) {
    redirect('admin/index.php');
}
$error = '';
if (is_post()) {
    csrf_verify();
    if (login(post('email'), (string)($_POST['password'] ?? ''))) {
        $u = auth_user();
        redirect($u['role'] === 'artist' ? 'admin/crud.php?e=tracks' : 'admin/index.php');
    }
    $error = too_many_attempts(mb_strtolower(post('email')))
        ? 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.'
        : 'Correo o contraseña incorrectos.';
}
?><!DOCTYPE html>
<html lang="es-MX"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Acceso · Panel</title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="login">
  <form class="login-card" method="post" autocomplete="on">
    <div class="login-brand"><b>SN</b><span>Salvador Nieto<br><small>Panel de administración</small></span></div>
    <?= csrf_field() ?>
    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>
    <label>Correo<input type="email" name="email" required autofocus autocomplete="username" value="<?= e(post('email')) ?>"></label>
    <label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn-primary" type="submit">Entrar</button>
    <a class="back" href="<?= e(url('index.php')) ?>">← Volver al sitio</a>
  </form>
</body></html>
