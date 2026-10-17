<?php
require 'inc/layout.php'; need(); $b = branch();
$total = q("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$avail = q("SELECT COUNT(*) FROM inventory i JOIN products p ON p.product_id = i.product_id WHERE i.branch_id = ? AND p.status = 'active' AND i.quantity > 0", [$b])->fetchColumn();
$low = count(lowitems($b));
$pend = q("SELECT COUNT(*) FROM stock_requests WHERE branch_id = ? AND status IN ('pending','requested')", [$b])->fetchColumn();
$mv = "SELECT m.created_at, m.movement_type, m.qty_change, m.reason, p.product_name, p.unit, u.full_name FROM stock_movements m
  JOIN products p ON p.product_id = m.product_id LEFT JOIN users u ON u.user_id = m.recorded_by WHERE m.branch_id = ?";
$recv = rows("$mv AND m.movement_type = 'stock_in' ORDER BY m.created_at DESC, m.movement_id DESC LIMIT 5", [$b]);
$tx = rows("$mv ORDER BY m.created_at DESC, m.movement_id DESC LIMIT 8", [$b]);
$pl = rows("SELECT r.created_at, r.status, p.product_name, p.unit, ri.quantity_requested FROM stock_requests r JOIN stock_request_items ri ON ri.request_id = r.request_id
  JOIN products p ON p.product_id = ri.product_id WHERE r.branch_id = ? AND r.status IN ('pending','requested') ORDER BY r.created_at DESC LIMIT 5", [$b]);
top('Dashboard', 'dashboard');
echo '<div class="stats">';
foreach ([['Total products', $total], ['Available stock', $avail . ' in stock'], ['Low stock items', $low], ['Pending stock requests', $pend]] as $s) echo '<div class="stat"><span>' . $s[0] . '</span><b>' . $s[1] . '</b></div>';
echo '</div><div class="grid2"><div class="panel"><h2>Recently received products</h2>';
foreach ($recv as $r) echo '<div class="li"><span><b>' . h($r['product_name']) . '</b> +' . $r['qty_change'] . ' ' . h($r['unit']) . '</span><span class="mu">' . dt($r['created_at']) . '</span></div>';
if (!$recv) echo '<p class="mu">Nothing received yet.</p>';
echo '</div><div class="panel"><h2>Pending stock requests</h2>';
foreach ($pl as $r) echo '<div class="li"><span><b>' . h($r['product_name']) . '</b> ' . $r['quantity_requested'] . ' ' . h($r['unit']) . ' ' . rpill($r['status']) . '</span><span class="mu">' . dt($r['created_at']) . '</span></div>';
if (!$pl) echo '<p class="mu">No pending requests.</p>';
echo '</div></div><div class="panel"><h2>Recent stock transactions</h2>';
$body = ''; foreach ($tx as $r) $body .= '<tr><td>' . dt($r['created_at']) . '</td><td><b>' . h($r['product_name']) . '</b></td><td>' . ucwords(str_replace('_', ' ', $r['movement_type'])) . '</td><td>' . sprintf('%+d', $r['qty_change']) . ' ' . h($r['unit']) . '</td><td>' . h($r['reason']) . '</td><td>' . h($r['full_name']) . '</td></tr>';
echo table(['Date and time', 'Product', 'Type', 'Quantity', 'Details', 'Processed by'], $body) . '</div>';
bottom();
