<?php // Head admin only. GET list, POST {a: create | toggle}
require __DIR__ . '/../inc/api.php'; guard([1]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post(); $i = input(); $a = $i['a'] ?? '';
  if ($a === 'create') {
    $sku = strtoupper(trim($i['sku'] ?? '')); $n = trim($i['name'] ?? ''); $unit = trim($i['unit'] ?? '');
    if ($sku === '' || $n === '' || $unit === '') fail('Enter the SKU, name and unit.');
    try { q('INSERT INTO products (sku, product_name, category_id, unit, par_level, reorder_level) VALUES (?,?,?,?,?,?)', [$sku, $n, (int)($i['category'] ?? 0), $unit, max(0, (int)($i['par'] ?? 0)), LOW]); }
    catch (PDOException $e) { fail('That SKU already exists, or the category is invalid.'); }
    out(['message' => "$n added. It now appears in every branch inventory at 0."]); // a trigger creates the inventory rows
  }
  if ($a !== 'toggle') fail('Unknown action.');
  q("UPDATE products SET status = IF(status = 'active', 'inactive', 'active') WHERE product_id = ?", [(int)($i['id'] ?? 0)]); out(['message' => 'Product updated.']);
}
out(['rows' => rows('SELECT p.product_id AS id, p.sku, p.product_name AS name, c.category_name AS category, p.unit, p.status FROM products p JOIN categories c ON c.category_id = p.category_id ORDER BY c.category_name, p.product_name'),
  'categories' => rows('SELECT category_id AS id, category_name AS name FROM categories ORDER BY category_name')]);
