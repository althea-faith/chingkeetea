<?php // Current stock for the branch (also feeds the searchable product dropdowns).
require __DIR__ . '/../inc/api.php'; guard(); $b = branch();
out(['rows' => rows("SELECT p.product_id AS id, p.product_name AS name, c.category_name AS category, p.unit, i.quantity,
  CASE WHEN i.quantity = 0 THEN 'out' WHEN i.quantity <= " . LOW . " THEN 'low' ELSE 'ok' END AS status
  FROM inventory i JOIN products p ON p.product_id = i.product_id JOIN categories c ON c.category_id = p.category_id
  WHERE i.branch_id = ? AND p.status = 'active' ORDER BY p.product_name", [$b])]);
