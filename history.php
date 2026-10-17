<?php
require 'inc/layout.php'; need(); $b = branch();
$list = rows("SELECT m.created_at, m.movement_type, m.qty_change, m.reason, p.product_name, p.unit, u.full_name FROM stock_movements m JOIN products p ON p.product_id = m.product_id
  LEFT JOIN users u ON u.user_id = m.recorded_by WHERE m.branch_id = ? ORDER BY m.created_at DESC, m.movement_id DESC LIMIT 300", [$b]);
top('History', 'history'); echo '<div class="panel"><h2>Stock transaction history</h2>';
$body = ''; foreach ($list as $r) $body .= '<tr><td>' . dt($r['created_at']) . '</td><td><b>' . h($r['product_name']) . '</b></td><td>' . ucwords(str_replace('_', ' ', $r['movement_type'])) . '</td><td>' . sprintf('%+d', $r['qty_change']) . ' ' . h($r['unit']) . '</td><td>' . h($r['reason']) . '</td><td>' . h($r['full_name']) . '</td></tr>';
echo table(['Date and time', 'Product', 'Type', 'Quantity', 'Details', 'Processed by'], $body) . '</div><p class="mu">Showing the latest 300 transactions.</p>'; bottom();
