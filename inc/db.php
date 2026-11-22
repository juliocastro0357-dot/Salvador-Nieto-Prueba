<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $file = ROOT . '/data/store.sqlite';
    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    migrate($pdo);
    if (!(int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()) {
        seed($pdo);
    }
    return $pdo;
}

function migrate(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS settings (k TEXT PRIMARY KEY, v TEXT NOT NULL DEFAULT '');
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'artist', must_change INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS login_attempts (id INTEGER PRIMARY KEY, ip TEXT, email TEXT, at INTEGER);
CREATE TABLE IF NOT EXISTS tracks (
  id INTEGER PRIMARY KEY, title TEXT NOT NULL, kind TEXT NOT NULL DEFAULT 'sencillo', release_date TEXT,
  cover TEXT, audio TEXT, spotify TEXT, apple TEXT, youtube TEXT, amazon TEXT, deezer TEXT,
  featured INTEGER NOT NULL DEFAULT 0, published INTEGER NOT NULL DEFAULT 1, sort INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS shows (
  id INTEGER PRIMARY KEY, date TEXT NOT NULL, city TEXT NOT NULL, venue TEXT, ticket_url TEXT,
  status TEXT NOT NULL DEFAULT 'proximo', published INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS videos (
  id INTEGER PRIMARY KEY, title TEXT NOT NULL, youtube_id TEXT NOT NULL, published INTEGER NOT NULL DEFAULT 1, sort INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS products (
  id INTEGER PRIMARY KEY, slug TEXT NOT NULL UNIQUE, name TEXT NOT NULL, description TEXT, category TEXT NOT NULL DEFAULT 'Ropa',
  price_cents INTEGER NOT NULL, compare_cents INTEGER, images TEXT NOT NULL DEFAULT '[]',
  active INTEGER NOT NULL DEFAULT 1, featured INTEGER NOT NULL DEFAULT 0, is_new INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS variants (
  id INTEGER PRIMARY KEY, product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  label TEXT NOT NULL, sku TEXT, stock INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS coupons (
  id INTEGER PRIMARY KEY, code TEXT NOT NULL UNIQUE, type TEXT NOT NULL DEFAULT 'pct', value INTEGER NOT NULL,
  min_total INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY, number TEXT NOT NULL UNIQUE, email TEXT NOT NULL, name TEXT NOT NULL, phone TEXT,
  address1 TEXT, address2 TEXT, city TEXT, state TEXT, zip TEXT, country TEXT NOT NULL DEFAULT 'México',
  subtotal INTEGER NOT NULL, shipping INTEGER NOT NULL DEFAULT 0, discount INTEGER NOT NULL DEFAULT 0, total INTEGER NOT NULL,
  coupon TEXT, status TEXT NOT NULL DEFAULT 'pendiente', payment_method TEXT, payment_ref TEXT,
  carrier TEXT, tracking TEXT, notes TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, paid_at TEXT
);
CREATE TABLE IF NOT EXISTS order_items (
  id INTEGER PRIMARY KEY, order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id INTEGER, variant_id INTEGER, name TEXT NOT NULL, variant_label TEXT, price_cents INTEGER NOT NULL, qty INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS subscribers (id INTEGER PRIMARY KEY, email TEXT NOT NULL UNIQUE, whatsapp TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS messages (
  id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL, phone TEXT, event_type TEXT, event_date TEXT, city TEXT,
  message TEXT, is_read INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_variants_product ON variants(product_id);
SQL);
}

function seed(PDO $db): void
{
    $artist = 'Salvador Nieto El Noble de la Banda';
    $s = rawurlencode($artist);

    /* ---------- usuarios (contraseña demo: cambiar al entregar) ---------- */
    $pw = password_hash('Demo2027!', PASSWORD_DEFAULT);
    $ins = $db->prepare('INSERT INTO users (name, email, password_hash, role, must_change) VALUES (?,?,?,?,1)');
    $ins->execute(['Administrador (agencia)', 'admin@salvadornieto.demo', $pw, 'admin']);
    $ins->execute(['Proveedor de tienda', 'proveedor@salvadornieto.demo', $pw, 'provider']);
    $ins->execute(['Equipo del artista', 'artista@salvadornieto.demo', $pw, 'artist']);

    /* ---------- ajustes ---------- */
    $settings = [
        'shipping_flat' => '9900',
        'free_shipping_min' => '150000',
        'store_notice' => 'Envíos a todo México. Pedidos preparados y enviados por nuestro proveedor oficial en 3 a 5 días hábiles.',
        'hero_tagline' => 'La voz que se hizo noble en cada tarima.',
        'bio_short' => 'Salvador Nieto, conocido como “El Noble de la Banda”, es un cantante de música regional mexicana que ha hecho del escenario su casa y del público su familia.',
        'bio_long' => "Salvador Nieto, conocido en el medio artístico como “El Noble de la Banda”, es un cantante de música regional mexicana. Su voz, cálida y potente, ha encontrado en el género de banda el vehículo ideal para contar historias de amor, desamor, orgullo y fiesta.\n\nSobre el escenario combina el carácter de la música tradicional con una presencia cercana, de las que hacen que cada presentación se sienta como una reunión entre amigos. Esa conexión con la gente es el sello que lo acompaña en cada plaza.\n\nHoy trabaja en nuevo material, prepara presentaciones para 2027 y mantiene abiertas las puertas a colaboraciones, contrataciones privadas, festivales y eventos masivos.",
        'bio_quote' => 'Cantar es mi forma de agradecerle a la gente que me escucha.',
        'booking_email' => 'contrataciones@salvadornietoelnoble.com',
        'booking_phone' => '',
        'whatsapp' => '',
        'social_instagram' => '',
        'social_facebook' => '',
        'social_tiktok' => '',
        'social_youtube' => 'https://www.youtube.com/results?search_query=' . $s,
        'social_spotify' => 'https://open.spotify.com/search/' . $s,
        'social_apple' => 'https://music.apple.com/mx/search?term=' . $s,
        'social_amazon' => 'https://music.amazon.com.mx/search/' . $s,
    ];
    $st = $db->prepare('INSERT INTO settings (k, v) VALUES (?, ?)');
    foreach ($settings as $k => $v) {
        $st->execute([$k, $v]);
    }

    /* ---------- música (títulos y fechas de muestra) ---------- */
    $links = [
        'spotify' => 'https://open.spotify.com/search/' . $s,
        'apple' => 'https://music.apple.com/mx/search?term=' . $s,
        'youtube' => 'https://music.youtube.com/search?q=' . $s,
        'amazon' => 'https://music.amazon.com.mx/search/' . $s,
        'deezer' => 'https://www.deezer.com/search/' . $s,
    ];
    $tracks = [
        ['Noble de Corazón', 'sencillo', '2026-11-13', 1, 1],
        ['Ya Me Cansé de Esperarte', 'sencillo', '2026-09-18', 2, 0],
        ['La Banda Sigue Sonando', 'sencillo', '2026-06-05', 3, 0],
        ['Dos Copas de Más (En vivo)', 'vivo', '2026-02-20', 4, 0],
        ['Mi Último Brindis', 'sencillo', '2025-10-10', 5, 0],
        ['Cartas Sin Remitente', 'sencillo', '2025-05-23', 6, 0],
        ['Camino a Casa (En vivo)', 'vivo', '2024-12-06', 7, 0],
        ['Con Tu Nombre en la Piel', 'sencillo', '2024-08-16', 8, 0],
    ];
    $st = $db->prepare('INSERT INTO tracks (title, kind, release_date, cover, audio, spotify, apple, youtube, amazon, deezer, featured, sort) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($tracks as $i => [$title, $kind, $date, $n, $feat]) {
        $st->execute([$title, $kind, $date, "assets/img/covers/c$n.svg", "assets/audio/demo-$n.mp3",
            $links['spotify'], $links['apple'], $links['youtube'], $links['amazon'], $links['deezer'], $feat, $i]);
    }

    /* ---------- gira (fechas de muestra) ---------- */
    $shows = [
        ['+19 days', 'Mexicali, B.C.', 'Salón de eventos (por confirmar)', 'proximo'],
        ['+33 days', 'Tijuana, B.C.', 'Foro al aire libre (por confirmar)', 'proximo'],
        ['+61 days', 'Los Mochis, Sin.', 'Palenque (por confirmar)', 'proximo'],
        ['+96 days', 'Culiacán, Sin.', 'Gran baile (por confirmar)', 'proximo'],
        ['-35 days', 'Hermosillo, Son.', 'Presentación privada', 'finalizado'],
        ['-62 days', 'Guasave, Sin.', 'Feria regional', 'finalizado'],
    ];
    $st = $db->prepare('INSERT INTO shows (date, city, venue, status) VALUES (?,?,?,?)');
    foreach ($shows as [$d, $c, $v, $stt]) {
        $st->execute([date('Y-m-d', strtotime($d)), $c, $v, $stt]);
    }

    /* ---------- tienda ---------- */
    $tallas = ['CH', 'M', 'G', 'XG'];
    $products = [
        [1, 'playera-noble-negra', 'Playera “Noble” negra', 'Ropa', 45000, null, 1, 1, 'Playera de algodón peinado 100% con estampado dorado de alta durabilidad. Corte regular, suave al tacto y hecha para las noches largas de baile.', $tallas],
        [2, 'playera-gira-2027', 'Playera Gira 2027', 'Ropa', 49000, null, 1, 1, 'Edición especial de la gira 2027 en color crema, con el escudo del Noble en vino. Cantidad limitada.', $tallas],
        [3, 'gorra-bordada-sn', 'Gorra bordada SN', 'Accesorios', 38000, 42000, 0, 1, 'Gorra negra con monograma SN bordado en hilo dorado. Ajuste trasero para cualquier talla.', ['Única']],
        [4, 'hoodie-el-noble', 'Hoodie “El Noble de la Banda”', 'Ropa', 95000, null, 1, 1, 'Sudadera con capucha en color vino, interior afelpado y escudo bordado. Perfecta para las madrugadas.', $tallas],
        [5, 'cd-edicion-firmada', 'CD edición firmada', 'Música', 25000, null, 0, 0, 'Disco compacto con las canciones más recientes, autografiado por Salvador Nieto. Pieza de colección.', ['Única']],
        [6, 'poster-edicion-limitada', 'Póster edición limitada', 'Coleccionables', 22000, null, 0, 0, 'Póster 45 × 60 cm en papel couché mate de 250 g. Se envía en tubo protector.', ['Única']],
        [7, 'taza-el-noble', 'Taza “El Noble”', 'Accesorios', 32000, null, 0, 0, 'Taza de cerámica de 350 ml con acabado brillante. Apta para microondas y lavavajillas.', ['Única']],
        [8, 'bandana-noble', 'Bandana Noble', 'Accesorios', 18000, null, 0, 0, 'Bandana 100% algodón con el escudo del Noble en dorado. 55 × 55 cm.', ['Única']],
    ];
    $sp = $db->prepare('INSERT INTO products (slug, name, description, category, price_cents, compare_cents, images, featured, is_new) VALUES (?,?,?,?,?,?,?,?,?)');
    $sv = $db->prepare('INSERT INTO variants (product_id, label, sku, stock) VALUES (?,?,?,?)');
    foreach ($products as [$n, $slug, $name, $cat, $price, $cmp, $new, $feat, $desc, $vars]) {
        $sp->execute([$slug, $name, $desc, $cat, $price, $cmp, json_encode(["assets/img/products/p$n.svg"]), $feat, $new]);
        $pid = (int)$db->lastInsertId();
        foreach ($vars as $j => $label) {
            $stock = ($n === 4 && $label === 'XG') ? 0 : [18, 24, 20, 8][$j] ?? 15;
            if ($n === 5) {
                $stock = 3;
            }
            $sv->execute([$pid, $label, 'SN' . $n . '-' . strtoupper($label), $stock]);
        }
    }
    $db->prepare('INSERT INTO coupons (code, type, value, min_total) VALUES (?,?,?,?)')->execute(['BIENVENIDO10', 'pct', 10, 0]);
    $db->prepare('INSERT INTO coupons (code, type, value, min_total) VALUES (?,?,?,?)')->execute(['NOBLE100', 'fixed', 10000, 80000]);

    /* ---------- pedidos de muestra (para que el panel se vea vivo) ---------- */
    $demo = [
        ['Ana Torres', 'ana.torres@example.com', 'Mexicali', 'Baja California', [[1, 'M', 1], [3, 'Única', 1]], 'entregado', -26],
        ['Luis Ramírez', 'luis.ramirez@example.com', 'Tijuana', 'Baja California', [[4, 'G', 1]], 'enviado', -9],
        ['María Gómez', 'maria.gomez@example.com', 'Culiacán', 'Sinaloa', [[2, 'CH', 2], [8, 'Única', 1]], 'pagado', -3],
        ['Jorge Valdez', 'jorge.valdez@example.com', 'Los Mochis', 'Sinaloa', [[5, 'Única', 1], [6, 'Única', 1]], 'pagado', -2],
        ['Carla Ibarra', 'carla.ibarra@example.com', 'Hermosillo', 'Sonora', [[1, 'G', 1]], 'pendiente', -1],
        ['Pedro Soto', 'pedro.soto@example.com', 'Guadalajara', 'Jalisco', [[7, 'Única', 2]], 'pagado', 0],
        ['Lucía Ortega', 'lucia.ortega@example.com', 'CDMX', 'Ciudad de México', [[4, 'M', 1], [2, 'M', 1]], 'enviado', -14],
    ];
    $so = $db->prepare('INSERT INTO orders (number, email, name, phone, address1, city, state, zip, subtotal, shipping, discount, total, status, payment_method, payment_ref, carrier, tracking, notes, created_at, paid_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $si = $db->prepare('INSERT INTO order_items (order_id, product_id, variant_id, name, variant_label, price_cents, qty) VALUES (?,?,?,?,?,?,?)');
    foreach ($demo as $i => [$name, $mail, $city, $state, $items, $status, $off]) {
        $created = date('Y-m-d H:i:s', strtotime("$off days " . (9 + $i) . ':15'));
        $sub = 0;
        $lines = [];
        foreach ($items as [$pn, $lab, $qty]) {
            $p = $db->query('SELECT id, name, price_cents FROM products WHERE id = ' . (int)$pn)->fetch();
            $v = $db->query('SELECT id FROM variants WHERE product_id = ' . (int)$pn . " AND label = '" . $lab . "'")->fetch();
            $sub += $p['price_cents'] * $qty;
            $lines[] = [$p, $v, $lab, $qty];
        }
        $ship = $sub >= 150000 ? 0 : 9900;
        $so->execute([
            'SN-' . date('ymd', strtotime($created)) . '-' . str_pad((string)(1000 + $i * 37), 4, '0', STR_PAD_LEFT),
            $mail, $name, '6860000000', 'Calle Ejemplo 123', $city, $state, '21000', $sub, $ship, 0, $sub + $ship,
            $status, $status === 'pendiente' ? null : 'demo', $status === 'pendiente' ? null : 'DEMO-' . ($i + 1),
            in_array($status, ['enviado', 'entregado'], true) ? 'Estafeta' : null,
            in_array($status, ['enviado', 'entregado'], true) ? 'DEMO' . (88000 + $i) : null,
            'Pedido de muestra', $created, $status === 'pendiente' ? null : $created,
        ]);
        $oid = (int)$db->lastInsertId();
        foreach ($lines as [$p, $v, $lab, $qty]) {
            $si->execute([$oid, $p['id'], $v['id'] ?? null, $p['name'], $lab, $p['price_cents'], $qty]);
        }
    }

    $db->prepare('INSERT INTO subscribers (email) VALUES (?)')->execute(['fan.demo@example.com']);
    $db->prepare('INSERT INTO messages (name, email, phone, event_type, event_date, city, message) VALUES (?,?,?,?,?,?,?)')
        ->execute(['Contacto de muestra', 'evento@example.com', '6860000000', 'Baile / evento privado', date('Y-m-d', strtotime('+120 days')), 'Mexicali, B.C.', 'Mensaje de ejemplo para mostrar la bandeja de contrataciones.']);
}
