<?php
require 'inc/layout.php'; need(); $u = me(); $b = branch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  chk(); $p = (int)($_POST['product_id'] ?? 0); $n = (int)($_POST['qty'] ?? 0);
  $pdo = db(); $pdo->beginTransaction();
  $cur = q('SELECT quantity FROM inventory WHERE branch_id = ? AND product_id = ? FOR UPDATE', [$b, $p])->fetchColumn();
  if ($cur === false || $n < 1) { $pdo->rollBack(); flash('Select a product and enter a quantity of at least 1.'); }
  else {
    $req = q("SELECT r.request_id FROM stock_requests r JOIN stock_request_items ri ON ri.request_id = r.request_id WHERE r.branch_id = ? AND ri.product_id = ? AND r.status IN ('pending','requested') ORDER BY r.created_at LIMIT 1", [$b, $p])->fetchColumn() ?: null;
    // The database trigger adds this movement to the branch inventory and closes any low-stock alert.
    q("INSERT INTO stock_movements (branch_id, product_id, movement_type, qty_change, reason, request_id, recorded_by) VALUES (?,?,?,?,?,?,?)",
      [$b, $p, 'stock_in', $n, 'Received from supplier (' . $cur . ' → ' . ($cur + $n) . ')', $req, $u['id']]);
    if ($req) q("UPDATE stock_requests SET status = 'received', received_at = NOW() WHERE request_id = ?", [$req]);
    $pdo->commit();
    $pr = q('SELECT product_name, unit FROM products WHERE product_id = ?', [$p])->fetch();
    flash("{$pr['product_name']}: $cur + $n = " . ($cur + $n) . " {$pr['unit']} in stock." . ($req ? " Request #$req marked Received." : ''));
  }
  go('processing.php');
}
$rc = rows("SELECT m.created_at, m.qty_change, m.reason, p.product_name, p.unit, u.full_name FROM stock_movements m JOIN products p ON p.product_id = m.product_id
  LEFT JOIN users u ON u.user_id = m.recorded_by WHERE m.branch_id = ? AND m.movement_type = 'stock_in' ORDER BY m.created_at DESC, m.movement_id DESC LIMIT 8", [$b]);
top('Inventory Processing', 'processing');
echo '<div class="panel pf"><h2>Inventory Processing</h2><p class="sub">Record products delivered by the supplier.</p><form method="post" data-need>' . field()
  . '<label>Product</label>' . combo($b) . '<label for="qty">Quantity</label><input id="qty" name="qty" type="number" min="1" required placeholder="20"><div class="prev" id="pv"></div>'
  . '<button class="go" type="submit">Add Received Stock</button></form></div><div class="panel"><h2>Recently processed</h2>';
$body = ''; foreach ($rc as $r) $body .= '<tr><td>' . dt($r['created_at']) . '</td><td><b>' . h($r['product_name']) . '</b></td><td>+' . $r['qty_change'] . ' ' . h($r['unit']) . '</td><td>' . h($r['reason']) . '</td><td>' . h($r['full_name']) . '</td></tr>';
echo table(['Date and time', 'Product', 'Quantity', 'Details', 'Processed by'], $body) . '</div>'; bottom();
