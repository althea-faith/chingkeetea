<?php // GET ?type=inventory|stock_in|stock_out &from= &to= &rb= (head admin: branch id, 0 = all) &csv=1
require __DIR__ . '/../inc/api.php'; guard([1, 2]); $u = me(); $b = branch();
$type = in_array($_GET['type'] ?? '', ['inventory', 'stock_in', 'stock_out']) ? $_GET['type'] : 'inventory';
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days')); $to = $_GET['to'] ?? date('Y-m-d');
$rb = $u['role'] == 1 ? (int)($_GET['rb'] ?? 0) : $b; $bw = $rb ? ' AND x.branch_id = ' . $rb : '';
if ($type === 'inventory') $data = rows("SELECT b.branch_name AS Branch, p.sku AS SKU, p.product_name AS Product, c.category_name AS Category, x.quantity AS Stock, p.unit AS Unit,
    CASE WHEN x.quantity = 0 THEN 'Out of stock' WHEN x.quantity <= " . LOW . " THEN 'Low stock' ELSE 'In stock' END AS Status
    FROM inventory x JOIN branches b ON b.branch_id = x.branch_id JOIN products p ON p.product_id = x.product_id JOIN categories c ON c.category_id = p.category_id WHERE p.status = 'active'$bw ORDER BY b.branch_name, p.product_name");
else $data = rows("SELECT x.created_at AS `Date and time`, b.branch_name AS Branch, p.product_name AS Product, ABS(x.qty_change) AS Quantity, p.unit AS Unit, x.reason AS Details, u.full_name AS `Processed by`
    FROM stock_movements x JOIN branches b ON b.branch_id = x.branch_id JOIN products p ON p.product_id = x.product_id LEFT JOIN users u ON u.user_id = x.recorded_by
    WHERE x.movement_type = ? AND DATE(x.created_at) BETWEEN ? AND ?$bw ORDER BY x.created_at DESC", [$type, $from, $to]);
q('INSERT INTO reports (report_type, branch_id, date_from, date_to, generated_by) VALUES (?,?,?,?,?)', [$type, $rb ?: null, $from, $to, $u['id']]);
if (isset($_GET['csv'])) {
  header('Content-Type: text/csv'); header("Content-Disposition: attachment; filename=chingkeetea_$type.csv"); $o = fopen('php://output', 'w');
  if ($data) fputcsv($o, array_keys($data[0])); foreach ($data as $r) fputcsv($o, $r); exit;
}
out(['columns' => $data ? array_keys($data[0]) : [], 'rows' => array_map('array_values', $data)]);
