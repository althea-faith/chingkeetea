<?php
require 'inc/layout.php'; need(); $u = me(); $b = branch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  chk(); $a = $_POST['a'] ?? '';
  if ($a === 'send') {
    $p = (int)($_POST['product_id'] ?? 0); $n = (int)($_POST['qty'] ?? 0); $m = trim($_POST['message'] ?? '');
    $pr = q("SELECT product_name, unit FROM products WHERE product_id = ? AND status = 'active'", [$p])->fetch();
    if (!$pr || $n < 1) flash('Select a product and enter a quantity of at least 1.');
    else {
      if ($m === '') $m = "Requesting $n {$pr['unit']} of {$pr['product_name']} for branch inventory.";
      $pdo = db(); $pdo->beginTransaction();
      q('INSERT INTO stock_requests (branch_id, requested_by, status, notes) VALUES (?,?,?,?)', [$b, $u['id'], 'pending', substr($m, 0, 255)]);
      $id = $pdo->lastInsertId();
      q('INSERT INTO stock_request_items (request_id, product_id, quantity_requested) VALUES (?,?,?)', [$id, $p, $n]); $pdo->commit();
      flash("Request #$id sent: $n {$pr['unit']} of {$pr['product_name']}. Status: Pending.");
    }
  } elseif ($a === 'cancel' || $a === 'mark') {
    $id = (int)($_POST['id'] ?? 0); $r = q('SELECT * FROM stock_requests WHERE request_id = ? AND branch_id = ?', [$id, $b])->fetch();
    if ($r && in_array($r['status'], ['pending', 'requested'])) {
      if ($a === 'cancel' && ($u['role'] < 3 || $r['requested_by'] == $u['id'])) { q("UPDATE stock_requests SET status = 'cancelled' WHERE request_id = ?", [$id]); flash("Request #$id cancelled."); }
      elseif ($a === 'mark' && $u['role'] < 3 && $r['status'] === 'pending') { q("UPDATE stock_requests SET status = 'requested', reviewed_by = ?, reviewed_at = NOW() WHERE request_id = ?", [$u['id'], $id]); flash("Request #$id marked Requested (sent to supplier)."); }
    }
  }
  go('requests.php');
}
$list = rows("SELECT r.request_id, r.created_at, r.status, r.notes, r.requested_by, r.received_at, p.product_name, p.unit, ri.quantity_requested, u.full_name
  FROM stock_requests r JOIN stock_request_items ri ON ri.request_id = r.request_id JOIN products p ON p.product_id = ri.product_id JOIN users u ON u.user_id = r.requested_by
  WHERE r.branch_id = ? ORDER BY r.created_at DESC, r.request_id DESC LIMIT 100", [$b]);
top('Supplier Requests', 'requests');
echo '<div class="panel pf"><h2>Request to supplier</h2><p class="sub">Send a stock request when supplies are running low.</p><form method="post" data-need>' . field() . '<input type="hidden" name="a" value="send">'
  . '<label>Product</label>' . combo($b, $_GET['p'] ?? 0) . '<div class="note" id="cn"></div><label for="qty">Quantity</label><input id="qty" name="qty" type="number" min="1" required placeholder="10">'
  . '<label for="rm">Message or remarks</label><input id="rm" name="message" maxlength="255" placeholder="Requesting 10 packs of pearls for branch inventory.">'
  . '<button class="go" type="submit">Send request</button></form></div><div class="panel"><h2>Sent requests and status</h2>';
$body = '';
foreach ($list as $r) {
  $open = in_array($r['status'], ['pending', 'requested']); $ac = '';
  if ($open && ($u['role'] < 3 || $r['requested_by'] == $u['id'])) $ac .= '<form class="inl" method="post">' . field() . '<input type="hidden" name="a" value="cancel"><input type="hidden" name="id" value="' . $r['request_id'] . '"><button class="sm">Cancel</button></form> ';
  if ($r['status'] === 'pending' && $u['role'] < 3) $ac .= '<form class="inl" method="post">' . field() . '<input type="hidden" name="a" value="mark"><input type="hidden" name="id" value="' . $r['request_id'] . '"><button class="sm">Mark requested</button></form>';
  $body .= '<tr><td>' . dt($r['created_at']) . '</td><td><b>' . h($r['product_name']) . '</b></td><td>' . $r['quantity_requested'] . ' ' . h($r['unit']) . '</td><td class="msgc">' . h($r['notes']) . '</td><td>' . h($r['full_name']) . '</td><td>' . rpill($r['status']) . ($r['received_at'] ? '<br><small class="mu">' . dt($r['received_at']) . '</small>' : '') . '</td><td>' . $ac . '</td></tr>';
}
echo table(['Date and time', 'Product', 'Quantity', 'Message', 'Requested by', 'Status', ''], $body) . '</div>'; bottom();
