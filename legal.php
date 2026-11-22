<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

$title = 'Términos, envíos y privacidad';
$active = '';
$desc = 'Condiciones de compra, política de envíos y devoluciones, y aviso de privacidad.';
require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
  <div class="wrap"><span class="eyebrow">Información legal</span><h1>Términos, envíos <em>y privacidad.</em></h1></div>
</section>
<section class="section">
  <div class="wrap narrow prose">
    <p class="fine"><em>Texto base de referencia. Debe ser revisado y ajustado por el responsable legal del artista antes del lanzamiento.</em></p>

    <h2 id="envios">Envíos y devoluciones</h2>
    <p>Los pedidos son preparados y enviados por nuestro proveedor oficial de merch. El tiempo de preparación es de 1 a 3 días hábiles y el de entrega de 2 a 6 días hábiles según tu ubicación, dentro de México.</p>
    <p>Recibirás por correo la paquetería y el número de guía en cuanto tu pedido salga. Si un producto llega con defecto de fábrica o dañado, escríbenos dentro de los 7 días naturales posteriores a la entrega con fotografías y te lo cambiaremos sin costo.</p>
    <p>Los productos personalizados, firmados o de edición limitada no tienen cambio por talla ni cancelación una vez enviados.</p>

    <h2 id="terminos">Términos de compra</h2>
    <p>Los precios están expresados en pesos mexicanos (MXN) e incluyen IVA. El inventario es limitado y se confirma al momento de pagar. Los pedidos sin pago confirmado se cancelan automáticamente y el inventario se libera.</p>

    <h2 id="privacidad">Aviso de privacidad</h2>
    <p>Los datos personales que nos proporcionas (nombre, correo, teléfono y dirección de entrega) se usan únicamente para procesar tus pedidos, atender solicitudes de contratación y, si te suscribes, enviarte novedades del artista. Compartimos con nuestro proveedor de logística y de pagos solo lo necesario para completar tu compra.</p>
    <p>Puedes solicitar el acceso, rectificación o eliminación de tus datos escribiendo a <a href="mailto:<?= e(setting('booking_email', cfg('mail_from'))) ?>"><?= e(setting('booking_email', cfg('mail_from'))) ?></a>.</p>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
