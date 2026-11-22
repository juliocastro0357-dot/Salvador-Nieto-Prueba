<?php
declare(strict_types=1);

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $v;
}

/** Ruta de medios guardada en BD (relativa a la raíz del sitio) -> URL */
function media(?string $path, string $fallback = 'assets/img/covers/c1.svg'): string
{
    if ($path && preg_match('#^https?://#', $path)) {
        return $path;
    }
    if ($path && is_file(ROOT . '/' . ltrim($path, '/'))) {
        return url($path);
    }
    return url($fallback);
}

function money(int $cents): string
{
    $d = $cents % 100 === 0 ? 0 : 2;
    return '$' . number_format($cents / 100, $d, '.', ',');
}

function redirect(string $to): void
{
    header('Location: ' . (preg_match('#^https?://#', $to) ? $to : url($to)));
    exit;
}

function slugify(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'item';
}

function fecha_es(string $date, string $fmt = 'long'): string
{
    $t = strtotime($date);
    if (!$t) {
        return $date;
    }
    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $mc = ['', 'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    $m = (int)date('n', $t);
    if ($fmt === 'short') {
        return date('j', $t) . ' ' . $mc[$m];
    }
    return date('j', $t) . ' de ' . $meses[$m] . ' de ' . date('Y', $t);
}

/* ------------------------------ BD helpers ------------------------------ */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}
function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r ?: null;
}
function val(string $sql, array $params = [])
{
    return q($sql, $params)->fetchColumn();
}

function setting(string $key, string $default = ''): string
{
    static $all = null;
    if ($all === null) {
        $all = [];
        foreach (rows('SELECT k, v FROM settings') as $r) {
            $all[$r['k']] = $r['v'];
        }
    }
    return array_key_exists($key, $all) && $all[$key] !== '' ? $all[$key] : $default;
}

function set_setting(string $key, string $value): void
{
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v', [$key, $value]);
}

/* ------------------------------ CSRF / flash ------------------------------ */
function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf()) . '">';
}
function csrf_verify(): void
{
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($t) || !hash_equals(csrf(), $t)) {
        http_response_code(419);
        exit('La sesión expiró. Recarga la página e inténtalo de nuevo.');
    }
}
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['t' => $type, 'm' => $msg];
}
function flashes_html(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $out .= '<div class="flash flash-' . e($f['t']) . '" role="status">' . e($f['m']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
function post(string $k, string $d = ''): string
{
    return trim((string)($_POST[$k] ?? $d));
}
function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ------------------------------ Subida de archivos ------------------------------ */
function upload_image(array $file, string $dir = 'uploads'): ?string
{
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > 6 * 1024 * 1024) {
        throw new RuntimeException('La imagen pesa más de 6 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) {
        throw new RuntimeException('Formato no permitido. Usa JPG, PNG o WEBP.');
    }
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    $dest = ROOT . '/' . $dir . '/' . $name;
    if (!is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0775, true);
    }
    // Redimensiona a máx. 1800 px si GD está disponible
    if (function_exists('imagecreatefromjpeg')) {
        [$w, $h] = getimagesize($file['tmp_name']);
        $src = match ($ext) {
            'jpg' => @imagecreatefromjpeg($file['tmp_name']),
            'png' => @imagecreatefrompng($file['tmp_name']),
            default => @imagecreatefromwebp($file['tmp_name']),
        };
        if ($src && $w > 1800) {
            $nh = (int)round($h * 1800 / $w);
            $dst = imagecreatetruecolor(1800, $nh);
            if ($ext !== 'jpg') {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, 1800, $nh, $w, $h);
            match ($ext) {
                'jpg' => imagejpeg($dst, $dest, 86),
                'png' => imagepng($dst, $dest, 7),
                default => imagewebp($dst, $dest, 86),
            };
            return $dir . '/' . $name;
        }
    }
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    return $dir . '/' . $name;
}

/** Sube un MP3 de preview. Si hay ffmpeg en el servidor, lo recorta a 30 s con fade-out. */
function upload_audio(array $file, string $dir = 'uploads/audio'): ?string
{
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > 25 * 1024 * 1024) {
        throw new RuntimeException('El audio pesa más de 25 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg', 'application/octet-stream'], true)
        || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'mp3') {
        throw new RuntimeException('El preview debe ser un archivo MP3.');
    }
    $name = bin2hex(random_bytes(10)) . '.mp3';
    $dest = ROOT . '/' . $dir . '/' . $name;
    if (!is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0775, true);
    }
    $trimmed = false;
    if (function_exists('exec')) {
        $ff = trim((string)@shell_exec('command -v ffmpeg 2>/dev/null'));
        if ($ff !== '') {
            $cmd = escapeshellarg($ff) . ' -y -loglevel error -i ' . escapeshellarg($file['tmp_name'])
                . ' -t 30 -af "afade=t=out:st=28.5:d=1.5" -b:a 128k ' . escapeshellarg($dest) . ' 2>&1';
            @exec($cmd, $o, $rc);
            $trimmed = ($rc === 0 && is_file($dest));
        }
    }
    if (!$trimmed && !move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('No se pudo guardar el audio.');
    }
    return $dir . '/' . $name;
}

function platform_links(array $t): array
{
    $defs = [
        'spotify' => 'Spotify', 'apple' => 'Apple Music', 'youtube' => 'YouTube Music',
        'amazon' => 'Amazon Music', 'deezer' => 'Deezer',
    ];
    $out = [];
    foreach ($defs as $k => $label) {
        if (!empty($t[$k])) {
            $out[$k] = ['label' => $label, 'url' => $t[$k]];
        }
    }
    return $out;
}
