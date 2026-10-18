<?php require __DIR__ . '/../inc/api.php'; guard(); $b = branch();
out(['stats' => [
    'total' => (int)q("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn(),
    'available' => (int)q("SELECT COUNT(*) FROM inventory i JOIN products p ON p.product_id = i.product_id WHERE i.branch_id = ? AND p.status = 'active' AND i.quantity > 0", [$b])->fetchColumn(),
    'low' => count(lowitems($b)),
    'pending' => (int)q("SELECT COUNT(*) FROM stock_requests WHERE branch_id = ? AND status IN ('pending','requested')", [$b])->fetchColumn()],
  'received' => movements($b, 'stock_in', 5), 'transactions' => movements($b, null, 8),
  'pending' => rows("SELECT r.created_at, r.status, p.product_name AS product, p.unit, ri.quantity_requested AS qty FROM stock_requests r
    JOIN stock_request_items ri ON ri.request_id = r.request_id JOIN products p ON p.product_id = ri.product_id
    WHERE r.branch_id = ? AND r.status IN ('pending','requested') ORDER BY r.created_at DESC LIMIT 5", [$b])]);
