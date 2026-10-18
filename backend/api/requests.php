<?php // GET: sent requests. POST {a: send | cancel | mark}
require __DIR__ . '/../inc/api.php'; guard(); $u = me(); $b = branch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post(); $i = input(); $a = $i['a'] ?? '';
  if ($a === 'send') {
    $p = (int)($i['product_id'] ?? 0); $n = (int)($i['qty'] ?? 0); $m = trim($i['message'] ?? '');
    $pr = q("SELECT product_name, unit FROM products WHERE product_id = ? AND status = 'active'", [$p])->fetch();
    if (!$pr || $n < 1) fail('Select a product and enter a quantity of at least 1.');
    if ($m === '') $m = "Requesting $n {$pr['unit']} of {$pr['product_name']} for branch inventory.";
    $pdo = db(); $pdo->beginTransaction();
    q('INSERT INTO stock_requests (branch_id, requested_by, status, notes) VALUES (?,?,?,?)', [$b, $u['id'], 'pending', substr($m, 0, 255)]);
    $id = $pdo->lastInsertId(); q('INSERT INTO stock_request_items (request_id, product_id, quantity_requested) VALUES (?,?,?)', [$id, $p, $n]); $pdo->commit();
    out(['message' => "Request #$id sent: $n {$pr['unit']} of {$pr['product_name']}. Status: Pending."]);
  }
  $id = (int)($i['id'] ?? 0); $r = q('SELECT * FROM stock_requests WHERE request_id = ? AND branch_id = ?', [$id, $b])->fetch();
  if (!$r || !in_array($r['status'], ['pending', 'requested'])) fail('This request can no longer be changed.');
  if ($a === 'cancel' && ($u['role'] < 3 || $r['requested_by'] == $u['id'])) { q("UPDATE stock_requests SET status = 'cancelled' WHERE request_id = ?", [$id]); out(['message' => "Request #$id cancelled."]); }
  if ($a === 'mark' && $u['role'] < 3 && $r['status'] === 'pending') { q("UPDATE stock_requests SET status = 'requested', reviewed_by = ?, reviewed_at = NOW() WHERE request_id = ?", [$u['id'], $id]); out(['message' => "Request #$id marked Requested (sent to supplier)."]); }
  fail('You are not allowed to do that.', 403);
}
out(['rows' => rows("SELECT r.request_id AS id, r.created_at, r.status, r.notes AS message, r.requested_by, r.received_at, p.product_name AS product, p.unit, ri.quantity_requested AS qty, u.full_name AS by_name
  FROM stock_requests r JOIN stock_request_items ri ON ri.request_id = r.request_id JOIN products p ON p.product_id = ri.product_id JOIN users u ON u.user_id = r.requested_by
  WHERE r.branch_id = ? ORDER BY r.created_at DESC, r.request_id DESC LIMIT 100", [$b])]);
