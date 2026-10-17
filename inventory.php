<?php
require 'inc/layout.php'; need(); $b = branch();
$s = trim($_GET['s'] ?? ''); $st = $_GET['st'] ?? '';
$sql = "SELECT p.product_id, p.product_name, c.category_name, p.unit, i.quantity FROM inventory i JOIN products p ON p.product_id = i.product_id
  JOIN categories c ON c.category_id = p.category_id WHERE i.branch_id = ? AND p.status = 'active'"; $a = [$b];
if ($s !== '') { $sql .= ' AND p.product_name LIKE ?'; $a[] = "%$s%"; }
if ($st === 'low') { $sql .= ' AND i.quantity BETWEEN 1 AND ' . LOW; } elseif ($st === 'zero') { $sql .= ' AND i.quantity = 0'; } elseif ($st === 'ok') { $sql .= ' AND i.quantity > ' . LOW; }
$list = rows($sql . ' ORDER BY p.product_name', $a);
top('Inventory', 'inventory');
echo '<div class="panel"><form class="tools" method="get"><input name="s" placeholder="Search items" value="' . h($s) . '"><select name="st">';
foreach (['' => 'All status', 'low' => 'Low stock', 'zero' => 'Out of stock', 'ok' => 'In stock'] as $k => $v) echo '<option value="' . $k . '"' . ($k === $st ? ' selected' : '') . '>' . $v . '</option>';
echo '</select><button class="sm" type="submit">Filter</button></form>';
$body = ''; foreach ($list as $r) $body .= '<tr><td><b>' . h($r['product_name']) . '</b></td><td>' . h($r['category_name']) . '</td><td>' . $r['quantity'] . ' ' . h($r['unit']) . '</td><td>' . spill($r['quantity']) . '</td><td>' . ($r['quantity'] <= LOW ? '<a class="sm" href="requests.php?p=' . $r['product_id'] . '">Request stock</a>' : '') . '</td></tr>';
echo table(['Product', 'Category', 'Stock', 'Status', ''], $body) . '</div>'; bottom();
