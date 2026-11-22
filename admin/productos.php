<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require __DIR__ . '/_layout.php';
require_cap('shop');

if (is_post()) {
    csrf_verify();
    $id = (int)post('id');
    if (post('action') === 'toggle') {
        q('UPDATE products SET active = 1 - active WHERE id = ?', [$id]);
        flash('ok', 'Visibilidad actualizada.');
    } elseif (post('action') === 'delete') {
        q('DELETE FROM products WHERE id = ?', [$id]);
        flash('ok', 'Producto eliminado. Los pedidos anteriores conservan su historial.');
    }
    redirect('admin/productos.php');
}

$list = rows('SELECT p.*, (SELECT COALESCE(SUM(stock),0) FROM variants WHERE product_id = p.id) AS stock,
              (SELECT COUNT(*) FROM variants WHERE product_id = p.id) AS nvar FROM products p ORDER BY p.id');
admin_header('Productos e inventario', 'productos');
?>
<div class="toolbar"><p class="muted">Aquí el proveedor administra el catálogo, precios y existencias de la tienda.</p>
  <a class="btn btn-primary" href="<?= e(url('admin/producto.php')) ?>">+ Nuevo producto</a></div>
<div class="card flush">
  <table class="tbl wide">
    <thead><tr><th></th><th>Producto</th><th>Categoría</th><th class="r">Precio</th><th class="r">Existencias</th><th>Estado</th><th></th></tr></thead>
    <?php foreach ($list as $p): $img = product_images($p)[0]; ?>
      <tr>
        <td class="thumbcell"><img src="<?= e(media($img, 'assets/img/products/p1.svg')) ?>" alt="" width="48" height="48"></td>
        <td><a href="<?= e(url('admin/producto.php?id=' . $p['id'])) ?>"><b><?= e($p['name']) ?></b></a><br><small class="muted"><?= (int)$p['nvar'] ?> opción(es)</small></td>
        <td><?= e($p['category']) ?></td>
        <td class="r"><?= e(money((int)$p['price_cents'])) ?></td>
        <td class="r"><span class="pill <?= (int)$p['stock'] === 0 ? 'st-cancelado' : ((int)$p['stock'] <= 8 ? 'st-pendiente' : 'st-pagado') ?>"><?= (int)$p['stock'] ?> pzas</span></td>
        <td><span class="pill <?= $p['active'] ? 'st-pagado' : 'st-cancelado' ?>"><?= $p['active'] ? 'Visible' : 'Oculto' ?></span></td>
        <td class="r nowrap">
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="link">Mostrar/ocultar</button></form>
          <a href="<?= e(url('admin/producto.php?id=' . $p['id'])) ?>">Editar</a>
          <form method="post" class="inline" onsubmit="return confirm('¿Eliminar este producto definitivamente?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="delete"><button class="link danger">Eliminar</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php admin_footer(); ?>
