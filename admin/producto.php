<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require __DIR__ . '/_layout.php';
require_cap('shop');

$id = (int)($_GET['id'] ?? 0);
$p = $id ? row('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$p) {
    flash('error', 'Producto no encontrado.');
    redirect('admin/productos.php');
}
$images = $p ? product_images($p) : [];
if ($p && ($p['images'] === '[]')) {
    $images = [];
}
$variants = $p ? rows('SELECT * FROM variants WHERE product_id = ? ORDER BY id', [$id]) : [['id' => 0, 'label' => 'Única', 'sku' => '', 'stock' => 0]];

if (is_post()) {
    csrf_verify();
    try {
        $name = mb_substr(post('name'), 0, 140);
        $price = pesos_to_cents(post('price'));
        if (mb_strlen($name) < 2) throw new RuntimeException('Escribe el nombre del producto.');
        if ($price <= 0) throw new RuntimeException('Escribe un precio mayor a cero.');
        $cmp = post('compare') !== '' ? pesos_to_cents(post('compare')) : null;

        // Imágenes: conservar las marcadas + subir nuevas (máx. 5)
        $keep = array_values(array_filter((array)($_POST['keep_img'] ?? []), fn($x) => in_array($x, $images, true)));
        $files = $_FILES['new_img'] ?? null;
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $_) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                $path = upload_image(['tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i], 'name' => $files['name'][$i]]);
                if ($path) $keep[] = $path;
            }
        }
        $keep = array_slice($keep, 0, 5);

        $slugBase = slugify(post('slug') ?: $name);
        $slug = $slugBase;
        for ($n = 2; val('SELECT 1 FROM products WHERE slug = ? AND id != ?', [$slug, $id]); $n++) {
            $slug = $slugBase . '-' . $n;
        }
        $vals = [$slug, $name, post('description'), mb_substr(post('category') ?: 'Ropa', 0, 40), $price, $cmp, json_encode($keep),
                 isset($_POST['active']) ? 1 : 0, isset($_POST['featured']) ? 1 : 0, isset($_POST['is_new']) ? 1 : 0];
        if ($id) {
            q('UPDATE products SET slug=?, name=?, description=?, category=?, price_cents=?, compare_cents=?, images=?, active=?, featured=?, is_new=? WHERE id=?', [...$vals, $id]);
        } else {
            q('INSERT INTO products (slug, name, description, category, price_cents, compare_cents, images, active, featured, is_new) VALUES (?,?,?,?,?,?,?,?,?,?)', $vals);
            $id = (int)db()->lastInsertId();
        }
        // Borra del disco las imágenes subidas que ya no se usan
        foreach ($images as $old) {
            if (!in_array($old, $keep, true) && str_starts_with($old, 'uploads/') && is_file(ROOT . '/' . $old)) {
                @unlink(ROOT . '/' . $old);
            }
        }

        // Variantes
        $ids = (array)($_POST['v_id'] ?? []);
        $labels = (array)($_POST['v_label'] ?? []);
        $stocks = (array)($_POST['v_stock'] ?? []);
        $skus = (array)($_POST['v_sku'] ?? []);
        $seen = [];
        foreach ($labels as $i => $label) {
            $label = mb_substr(trim((string)$label), 0, 40);
            if ($label === '') continue;
            $stock = max(0, (int)($stocks[$i] ?? 0));
            $sku = mb_substr(trim((string)($skus[$i] ?? '')), 0, 40);
            $vid = (int)($ids[$i] ?? 0);
            if ($vid && val('SELECT 1 FROM variants WHERE id = ? AND product_id = ?', [$vid, $id])) {
                q('UPDATE variants SET label=?, sku=?, stock=? WHERE id=?', [$label, $sku, $stock, $vid]);
                $seen[] = $vid;
            } else {
                q('INSERT INTO variants (product_id, label, sku, stock) VALUES (?,?,?,?)', [$id, $label, $sku, $stock]);
                $seen[] = (int)db()->lastInsertId();
            }
        }
        if (!$seen) {
            q("INSERT INTO variants (product_id, label, stock) VALUES (?, 'Única', 0)", [$id]);
        } else {
            $in = implode(',', array_fill(0, count($seen), '?'));
            q("DELETE FROM variants WHERE product_id = ? AND id NOT IN ($in)", [$id, ...$seen]);
        }
        flash('ok', 'Producto guardado.');
        redirect('admin/producto.php?id=' . $id);
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
        $p = array_merge($p ?? [], ['name' => post('name'), 'description' => post('description'), 'category' => post('category'), 'slug' => post('slug'),
            'price_cents' => pesos_to_cents(post('price')), 'compare_cents' => post('compare') !== '' ? pesos_to_cents(post('compare')) : null,
            'active' => isset($_POST['active']), 'featured' => isset($_POST['featured']), 'is_new' => isset($_POST['is_new'])]);
    }
}

$cats = array_column(rows('SELECT DISTINCT category FROM products ORDER BY category'), 'category');
admin_header($id ? 'Editar producto' : 'Nuevo producto', 'productos');
?>
<p><a href="<?= e(url('admin/productos.php')) ?>">← Volver a productos</a></p>
<form method="post" enctype="multipart/form-data" class="form-grid">
  <?= csrf_field() ?>
  <div>
    <section class="card form">
      <h2>Información</h2>
      <label>Nombre*<input name="name" required value="<?= e($p['name'] ?? '') ?>"></label>
      <label>Descripción<textarea name="description" rows="5"><?= e($p['description'] ?? '') ?></textarea></label>
      <div class="grid-3">
        <label>Categoría<input name="category" list="cats" value="<?= e($p['category'] ?? 'Ropa') ?>"><datalist id="cats"><?php foreach ($cats as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></datalist></label>
        <label>Precio (MXN)*<input name="price" inputmode="decimal" required value="<?= e(cents_to_pesos(isset($p['price_cents']) ? (int)$p['price_cents'] : null)) ?>" placeholder="450"></label>
        <label>Precio anterior (opcional)<input name="compare" inputmode="decimal" value="<?= e(cents_to_pesos(isset($p['compare_cents']) ? (int)$p['compare_cents'] : null)) ?>"></label>
      </div>
      <label>URL amigable (opcional)<input name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="se genera del nombre"></label>
    </section>

    <section class="card form">
      <h2>Opciones e inventario</h2>
      <p class="muted small">Cada fila es una talla/opción con su propio inventario. Para productos sin tallas usa una sola fila (“Única”).</p>
      <table class="tbl" id="varTable">
        <thead><tr><th>Opción</th><th>SKU</th><th style="width:110px">Existencias</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($variants as $v): ?>
            <tr><td><input type="hidden" name="v_id[]" value="<?= (int)$v['id'] ?>"><input name="v_label[]" value="<?= e($v['label']) ?>" placeholder="M"></td>
              <td><input name="v_sku[]" value="<?= e($v['sku']) ?>"></td>
              <td><input name="v_stock[]" type="number" min="0" value="<?= (int)$v['stock'] ?>"></td>
              <td><button type="button" class="link danger" onclick="this.closest('tr').remove()">Quitar</button></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <button type="button" class="btn btn-ghost" id="addVar">+ Agregar opción</button>
    </section>
  </div>

  <div>
    <section class="card form">
      <h2>Publicación</h2>
      <label class="check"><input type="checkbox" name="active" <?= !$p || !empty($p['active']) ? 'checked' : '' ?>> Visible en la tienda</label>
      <label class="check"><input type="checkbox" name="featured" <?= !empty($p['featured']) ? 'checked' : '' ?>> Destacado en inicio</label>
      <label class="check"><input type="checkbox" name="is_new" <?= !empty($p['is_new']) ? 'checked' : '' ?>> Etiqueta “Nuevo”</label>
      <button class="btn btn-primary" type="submit">Guardar producto</button>
    </section>
    <section class="card form">
      <h2>Imágenes (máx. 5)</h2>
      <div class="img-grid">
        <?php foreach ($images as $im): ?>
          <label class="img-item"><img src="<?= e(media($im, 'assets/img/products/p1.svg')) ?>" alt=""><span><input type="checkbox" name="keep_img[]" value="<?= e($im) ?>" checked> Conservar</span></label>
        <?php endforeach; ?>
      </div>
      <label>Agregar imágenes (JPG, PNG o WEBP · máx. 6 MB c/u)<input type="file" name="new_img[]" accept="image/jpeg,image/png,image/webp" multiple></label>
      <p class="muted small">La primera imagen es la portada del producto. Recomendado: cuadradas, 1200 × 1200 px.</p>
    </section>
  </div>
</form>
<script>
document.getElementById('addVar').addEventListener('click', function () {
  var tr = document.createElement('tr');
  tr.innerHTML = '<td><input type="hidden" name="v_id[]" value="0"><input name="v_label[]" placeholder="Talla / opción"></td><td><input name="v_sku[]"></td><td><input name="v_stock[]" type="number" min="0" value="0"></td><td><button type="button" class="link danger" onclick="this.closest(\'tr\').remove()">Quitar</button></td>';
  document.querySelector('#varTable tbody').appendChild(tr);
});
</script>
<?php admin_footer(); ?>
