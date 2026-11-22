<?php
declare(strict_types=1);

/**
 * Roles:
 *  admin    -> todo (agencia / desarrollador)
 *  provider -> tienda: productos, pedidos, cupones
 *  artist   -> contenido (música, gira, videos, mensajes) + consulta de ventas
 */
const ROLE_CAPS = [
    'admin'    => ['shop', 'content', 'reports', 'settings', 'users'],
    'provider' => ['shop', 'reports'],
    'artist'   => ['content', 'reports'],
];

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function can(string $cap): bool
{
    $u = auth_user();
    return $u && in_array($cap, ROLE_CAPS[$u['role']] ?? [], true);
}

function require_cap(string $cap): void
{
    if (!auth_user()) {
        redirect('admin/login.php');
    }
    if (!can($cap)) {
        http_response_code(403);
        exit('No tienes permiso para ver esta sección.');
    }
}

function too_many_attempts(string $email): bool
{
    q('DELETE FROM login_attempts WHERE at < ?', [time() - 900]);
    return (int)val('SELECT COUNT(*) FROM login_attempts WHERE (ip = ? OR email = ?) AND at > ?', [client_ip(), $email, time() - 900]) >= 6;
}

function login(string $email, string $password): bool
{
    $email = mb_strtolower(trim($email));
    if (too_many_attempts($email)) {
        return false;
    }
    $u = row('SELECT * FROM users WHERE email = ?', [$email]);
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role'], 'must_change' => (int)$u['must_change']];
        q('DELETE FROM login_attempts WHERE ip = ? OR email = ?', [client_ip(), $email]);
        return true;
    }
    q('INSERT INTO login_attempts (ip, email, at) VALUES (?,?,?)', [client_ip(), $email, time()]);
    return false;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}
