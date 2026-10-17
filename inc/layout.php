<?php
require __DIR__ . '/auth.php';
function top($title, $active) {
  $u = me(); $b = branch(); $roles = [1 => 'Head admin', 2 => 'Branch admin', 3 => 'Staff'];
  $bn = q('SELECT branch_name FROM branches WHERE branch_id = ?', [$b])->fetchColumn(); $low = lowitems($b);
  $tabs = [['dashboard', 'Dashboard'], ['inventory', 'Inventory'], ['requests', 'Supplier Requests'], ['processing', 'Inventory Processing'], ['history', 'History']];
  if ($u['role'] == 1) $tabs[] = ['products', 'Products'];
  if ($u['role'] < 3) { $tabs[] = ['users', 'Users']; $tabs[] = ['reports', 'Reports']; }
  $sub = [1 => 'Monitor and manage any branch.', 2 => 'Manage stock, supplier requests and deliveries for your branch.', 3 => 'Check stock, request supplies and record deliveries.'][$u['role']];
  echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . h($title) . ' · Chingkeetea IMS</title>'
    . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="assets/css/style.css"></head><body><div id="app"><header>'
    . '<div class="logo"><i class="leaf"></i>Chingkeetea IMS</div><div class="sp"></div>';
  if ($u['role'] == 1) {
    echo '<select aria-label="Branch" onchange="location.search=\'b=\'+this.value">';
    foreach (rows('SELECT branch_id, branch_name FROM branches ORDER BY branch_id') as $r) echo '<option value="' . $r['branch_id'] . '"' . ($r['branch_id'] == $b ? ' selected' : '') . '>' . h($r['branch_name']) . '</option>';
    echo '</select>';
  } else echo '<span>' . h($bn) . ' branch</span>';
  echo '<div class="bw"><button class="out bell" id="bell" type="button" aria-label="Notifications">🔔' . ($low ? '<b class="bd">' . count($low) . '</b>' : '') . '</button><div class="np" id="np" hidden><div class="nh">Notifications</div>';
  foreach ($low as $r) echo '<div class="nt"><b>🔔 Low Stock Alert</b><p><strong>' . h($r['product_name']) . '</strong> is ' . ($r['quantity'] <= 0 ? 'out of stock' : 'low in stock')
    . '.<br>Current quantity: ' . $r['quantity'] . ' ' . h($r['unit']) . '</p><p class="mu">Consider requesting additional stock.</p><a class="sm" href="requests.php?p=' . $r['product_id'] . '">Request stock</a></div>';
  if (!$low) echo '<div class="nt mu">No low-stock alerts.</div>';
  echo '</div></div><span class="role">' . $roles[$u['role']] . '</span><span>' . h($u['name']) . '</span><a class="out" href="logout.php">Sign out</a></header>'
    . '<div class="banner"><div class="wrap" style="padding:0"><h1>' . h($bn) . ' branch</h1><p>' . $sub . '</p></div></div><nav class="tabs">';
  foreach ($tabs as $t) echo '<a class="tab' . ($t[0] === $active ? ' on' : '') . '" href="' . $t[0] . '.php">' . $t[1] . '</a>';
  echo '</nav><div class="wrap" id="body">';
  if ($f = flash()) echo '<div class="msg g" role="status">' . h($f) . '</div>';
}
function bottom() { echo '</div></div><script src="assets/js/app.js"></script></body></html>'; }
