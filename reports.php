<?php
require 'inc/layout.php'; need([1, 2]); $u = me(); $head = $u['role'] == 1; $b = branch();
$type = in_array($_GET['type'] ?? '', ['inventory', 'stock_in', 'stock_out']) ? $_GET['type'] : 'inventory';
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days')); $to = $_GET['to'] ?? date('Y-m-d');
$rb = $head ? (int)($_GET['rb'] ?? 0) : $b; // 0 = all branches (head admin only)
$data = [];
if (isset($_GET['go'])) {
  $bw = $rb ? ' AND x.branch_id = ' . $rb : '';
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
}
top('Reports', 'reports');
echo '<div class="panel"><h2>Generate report</h2><form method="get" class="fr"><input type="hidden" name="go" value="1"><div><label>Report</label><select name="type">';
foreach (['inventory' => 'Inventory report', 'stock_in' => 'Stock-in report', 'stock_out' => 'Stock-out report'] as $k => $v) echo '<option value="' . $k . '"' . ($k === $type ? ' selected' : '') . '>' . $v . '</option>';
echo '</select></div>';
if ($head) { echo '<div><label>Branch</label><select name="rb"><option value="0">All branches</option>'; foreach (rows('SELECT branch_id, branch_name FROM branches') as $r) echo '<option value="' . $r['branch_id'] . '"' . ($r['branch_id'] == $rb ? ' selected' : '') . '>' . h($r['branch_name']) . '</option>'; echo '</select></div>'; }
echo '<div><label>From</label><input type="date" name="from" value="' . h($from) . '"></div><div><label>To</label><input type="date" name="to" value="' . h($to) . '"></div><div><button class="go" type="submit">View report</button></div>'
  . (isset($_GET['go']) ? '<div><button class="go" type="submit" name="csv" value="1">Download CSV</button></div>' : '') . '</form></div>';
if (isset($_GET['go'])) {
  echo '<div class="panel"><h2>' . ucwords(str_replace('_', '-', $type)) . ' report</h2>'; $body = '';
  foreach ($data as $r) $body .= '<tr>' . implode('', array_map(fn($v) => '<td>' . h($v) . '</td>', $r)) . '</tr>';
  echo table($data ? array_map('h', array_keys($data[0])) : ['Result'], $body) . '</div>';
}
bottom();
