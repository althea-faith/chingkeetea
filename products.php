<?php
require 'inc/layout.php'; need([1]); $b = branch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  chk(); $a = $_POST['a'] ?? '';
  if ($a === 'create') {
    $sku = strtoupper(trim($_POST['sku'] ?? '')); $n = trim($_POST['name'] ?? ''); $unit = trim($_POST['unit'] ?? ''); $par = max(0, (int)($_POST['par'] ?? 0));
    if ($sku === '' || $n === '' || $unit === '') flash('Enter the SKU, name and unit.');
    else try {
      q('INSERT INTO products (sku, product_name, category_id, unit, par_level, reorder_level) VALUES (?,?,?,?,?,?)', [$sku, $n, (int)$_POST['category'], $unit, $par, LOW]);
      flash("$n added. It now appears in every branch inventory at 0."); // trigger creates the inventory rows
    } catch (PDOException $e) { flash('That SKU already exists.'); }
  } elseif ($a === 'toggle') { q("UPDATE products SET status = IF(status = 'active', 'inactive', 'active') WHERE product_id = ?", [(int)($_POST['id'] ?? 0)]); flash('Product updated.'); }
  go('products.php');
}
$list = rows('SELECT p.*, c.category_name FROM products p JOIN categories c ON c.category_id = p.category_id ORDER BY c.category_name, p.product_name');
top('Products', 'products');
echo '<div class="panel"><h2>Add product</h2><form method="post" class="fr">' . field() . '<input type="hidden" name="a" value="create"><div><label>SKU</label><input name="sku" required placeholder="TOP-010"></div><div><label>Product name</label><input name="name" required></div><div><label>Category</label><select name="category">';
foreach (rows('SELECT * FROM categories ORDER BY category_name') as $c) echo '<option value="' . $c['category_id'] . '">' . h($c['category_name']) . '</option>';
echo '</select></div><div><label>Unit</label><input name="unit" required placeholder="kg, pcs, tub"></div><div><label>Usual full stock</label><input name="par" type="number" min="0" value="20"></div><div><button class="go" type="submit">Add product</button></div></form></div><div class="panel"><h2>All products</h2>';
$body = ''; foreach ($list as $r) $body .= '<tr><td>' . h($r['sku']) . '</td><td><b>' . h($r['product_name']) . '</b></td><td>' . h($r['category_name']) . '</td><td>' . h($r['unit']) . '</td><td>' . pill($r['status'] === 'active' ? 'ok' : 'grey', ucfirst($r['status'])) . '</td><td><form class="inl" method="post">' . field() . '<input type="hidden" name="a" value="toggle"><input type="hidden" name="id" value="' . $r['product_id'] . '"><button class="sm">' . ($r['status'] === 'active' ? 'Deactivate' : 'Activate') . '</button></form></td></tr>';
echo table(['SKU', 'Product', 'Category', 'Unit', 'Status', ''], $body) . '</div>'; bottom();
