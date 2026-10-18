<?php // GET: recently processed. POST {product_id, qty}: record received stock.
require __DIR__ . '/../inc/api.php'; guard(); $u = me(); $b = branch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post(); $i = input(); $p = (int)($i['product_id'] ?? 0); $n = (int)($i['qty'] ?? 0);
  $pdo = db(); $pdo->beginTransaction();
  $cur = q('SELECT quantity FROM inventory WHERE branch_id = ? AND product_id = ? FOR UPDATE', [$b, $p])->fetchColumn();
  if ($cur === false || $n < 1) { $pdo->rollBack(); fail('Select a product and enter a quantity of at least 1.'); }
  $req = q("SELECT r.request_id FROM stock_requests r JOIN stock_request_items ri ON ri.request_id = r.request_id WHERE r.branch_id = ? AND ri.product_id = ? AND r.status IN ('pending','requested') ORDER BY r.created_at LIMIT 1", [$b, $p])->fetchColumn() ?: null;
  // The database trigger adds this movement to the branch inventory and closes any low-stock alert.
  q('INSERT INTO stock_movements (branch_id, product_id, movement_type, qty_change, reason, request_id, recorded_by) VALUES (?,?,?,?,?,?,?)',
    [$b, $p, 'stock_in', $n, 'Received from supplier (' . $cur . ' → ' . ($cur + $n) . ')', $req, $u['id']]);
  if ($req) q("UPDATE stock_requests SET status = 'received', received_at = NOW() WHERE request_id = ?", [$req]);
  $pdo->commit(); $pr = q('SELECT product_name, unit FROM products WHERE product_id = ?', [$p])->fetch();
  out(['message' => "{$pr['product_name']}: $cur + $n = " . ($cur + $n) . " {$pr['unit']} in stock." . ($req ? " Request #$req marked Received." : '')]);
}
out(['rows' => movements($b, 'stock_in', 8)]);
