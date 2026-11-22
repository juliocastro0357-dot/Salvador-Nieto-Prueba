<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
logout();
header('Location: ' . url('admin/login.php'));
exit;
