<?php
/**
 * Configuración general del sitio.
 * Los secretos (llaves de pago) pueden definirse aquí o como variables de entorno.
 */
return [
    'artist_name'   => 'Salvador Nieto',
    'artist_alias'  => 'El Noble de la Banda',
    'site_tagline'  => 'Música regional mexicana',

    // Mientras el sitio sea una demostración, se pide a los buscadores no indexarlo.
    // Cambiar a false el día del lanzamiento.
    'demo_mode'     => true,

    // Correos (aviso de pedidos al proveedor, confirmaciones al cliente).
    'mail_from'     => 'tienda@salvadornietoelnoble.com',
    'mail_provider' => 'proveedor@ejemplo.com',
    'mail_log_only' => true,   // true = solo guarda en data/mail.log (útil en demo). Poner false en producción.

    // Pagos con tarjeta (Stripe Checkout). Si están vacíos, la tienda funciona en MODO DEMO.
    'stripe_secret'         => getenv('STRIPE_SECRET') ?: '',
    'stripe_webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',

    'session_name'  => 'sn_sess',
    'debug'         => false,
];
