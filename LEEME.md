# Salvador Nieto · El Noble de la Banda — sitio web + tienda administrada

Sitio en HTML5, CSS, JavaScript y PHP (sin frameworks, sin dependencias que instalar).

## Cómo verlo en tu computadora
Requiere PHP 8.1 o superior con las extensiones `pdo_sqlite` y `fileinfo` (`gd` y `curl` recomendadas).

```
cd "web nueva"
php -S localhost:8080 router.php
```
Abre http://localhost:8080 — la base de datos con contenido de muestra se crea sola al primer arranque (carpeta `data/`).

## Accesos al panel (demostración) → /admin/login.php
| Rol | Correo | Contraseña | Puede |
|---|---|---|---|
| Agencia (admin) | admin@salvadornieto.demo | Demo2027! | Todo |
| Proveedor de tienda | proveedor@salvadornieto.demo | Demo2027! | Productos, inventario, pedidos, cupones, envíos |
| Equipo del artista | artista@salvadornieto.demo | Demo2027! | Música, gira, videos, contrataciones, reportes |

**Cambia las tres contraseñas antes de entregar** (panel → Mi cuenta).

## Qué incluye
**Sitio público:** inicio, biografía, música (preview de 30 s con reproductor + links a Spotify, Apple Music, YouTube Music, Amazon Music y Deezer), gira, videos, contrataciones/EPK, avisos legales, suscripción “Círculo del Noble”.

**Tienda:** catálogo con filtros, producto con tallas e inventario, carrito, cupones, envío gratis por monto, checkout para México, seguimiento de pedido por número + correo, correos automáticos.

**Administración por el proveedor:** el proveedor gestiona catálogo, precios, existencias, pedidos, guías de rastreo (al marcar “Enviado” el cliente recibe correo con la guía), cancelaciones (devuelven inventario) y exporta pedidos a CSV. El artista no necesita tocar la tienda.

**Panel del artista:** alta de canciones (portada, MP3 de 30 s, links), fechas de gira, videos, bandeja de contrataciones, suscriptores y textos del sitio.

**Seguridad:** contraseñas con hash, protección CSRF, límite de intentos de acceso, roles con permisos, subidas validadas por tipo real, base de datos y código bloqueados al público, inventario con transacciones.

## Antes de publicar (checklist)
1. Subir la foto del artista como `assets/img/artista.jpg` (se usa automáticamente en inicio y biografía).
2. Reemplazar el contenido de muestra desde el panel: canciones, portadas, previews MP3, links reales de plataformas, fechas de gira, biografía, redes y correo/teléfono de contrataciones. **Todos los títulos, fechas y textos actuales son de ejemplo.**
3. Subir los previews de 30 s (MP3). Si el servidor tiene `ffmpeg`, recorta a 30 s automáticamente; si no, sube el fragmento ya recortado.
4. `inc/config.php`: `demo_mode` → `false`, `mail_log_only` → `false`, correos reales del proveedor y de la tienda.
5. Cambiar contraseñas de los tres usuarios; borrar pedidos y mensajes de muestra desde el panel.
6. Activar HTTPS y descomentar la redirección en `.htaccess`.
7. Revisar con un abogado los textos de `legal.php`.

## Pagos con tarjeta (Stripe)
Mientras no haya llaves, la tienda funciona en **modo demostración** (pago simulado, sin cargos). Para cobrar de verdad:
1. Crear cuenta en Stripe y definir las variables de entorno `STRIPE_SECRET` y `STRIPE_WEBHOOK_SECRET` (o escribirlas en `inc/config.php`).
2. En Stripe, apuntar el evento `checkout.session.completed` a `https://TU-DOMINIO/webhook-stripe.php`.
El pedido se marca pagado únicamente cuando Stripe lo confirma por webhook, validando firma y monto.

## Estructura
```
index.php biografia.php musica.php gira.php tienda.php producto.php
carrito.php checkout.php pedido.php contrataciones.php legal.php
admin/        panel (login, resumen, pedidos, productos, música, gira, ajustes…)
api/          carrito y suscripción (JSON)
inc/          núcleo (BD, auth, carrito, pagos, correo, plantillas)
assets/       css, js, imágenes, audio de muestra
data/         base SQLite y logs (bloqueado al público)
uploads/      archivos subidos desde el panel
```
Hosting: cualquier hosting con PHP 8.1+ y Apache (la mayoría de los compartidos). La carpeta `data/` y `uploads/` necesitan permiso de escritura.

## Nota sobre el material de muestra
Las portadas, fotos de producto y los audios de 30 s son **marcadores de posición generados** para la demostración; se sustituyen por material real desde el panel.
