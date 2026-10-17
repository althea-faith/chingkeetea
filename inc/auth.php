<?php
// Database, session, login and permission helpers.
define('LOW', 10); // low-stock alert threshold: 10 or below
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
function db() {
  static $p;
  if (!$p) { $c = require __DIR__ . '/../config.php';
    $p = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'],
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]); }
  return $p;
}
function q($sql, $a = []) { $s = db()->prepare($sql); $s->execute($a); return $s; }
function rows($sql, $a = []) { return q($sql, $a)->fetchAll(); }
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function dt($s) { return $s ? date('M j, Y g:i A', strtotime($s)) : ''; }
function me() { return $_SESSION['u'] ?? null; }
function csrf() { return $_SESSION['t'] ??= bin2hex(random_bytes(16)); }
function field() { return '<input type="hidden" name="t" value="' . csrf() . '">'; }
function chk() { if (!hash_equals(csrf(), $_POST['t'] ?? '')) { http_response_code(400); exit('Invalid form token. Go back and try again.'); } }
function flash($m = null) { if ($m !== null) { $_SESSION['f'] = $m; return ''; } $x = $_SESSION['f'] ?? ''; unset($_SESSION['f']); return $x; }
function go($page) { header("Location: $page"); exit; }
function pwok($pw, $u) { // supports the sample SHA-256 hashes and PHP password_hash()
  $x = $u['password_hash'];
  if (strlen($x) === 64 && ctype_xdigit($x)) return hash_equals($x, hash('sha256', $pw . $u['email']));
  return password_verify($pw, $x);
}
// Roles: 1 head admin, 2 branch admin, 3 staff. Always enforced on the server.
function need($roles = [1, 2, 3]) {
  if (!me()) go('index.php');
  if (!in_array(me()['role'], $roles)) { http_response_code(403); exit('Access denied for your role.'); }
}
function branch() { // branch the page works on: own branch, or the one the head admin picked
  $u = me();
  if ($u['role'] != 1) return (int)$u['branch'];
  if (isset($_GET['b'])) $_SESSION['b'] = (int)$_GET['b'];
  if (empty($_SESSION['b'])) $_SESSION['b'] = (int)q('SELECT MIN(branch_id) FROM branches')->fetchColumn();
  return $_SESSION['b'];
}
function lowitems($b) {
  return rows("SELECT p.product_id, p.product_name, p.unit, i.quantity FROM inventory i JOIN products p ON p.product_id = i.product_id
    WHERE i.branch_id = ? AND p.status = 'active' AND i.quantity <= ? ORDER BY i.quantity, p.product_name", [$b, LOW]);
}
function pill($c, $t) { return '<span class="pill ' . $c . '">' . h($t) . '</span>'; }
function rpill($s) { return pill(['pending' => 'low', 'requested' => 'blue', 'received' => 'ok', 'cancelled' => 'grey'][$s] ?? 'grey', ucfirst($s)); }
function spill($q) { return $q <= 0 ? pill('zero', 'Out of stock') : ($q <= LOW ? pill('low', 'Low stock ⚠') : pill('ok', 'In stock')); }
function items($b) { // product list for the searchable dropdown
  $o = [];
  foreach (rows("SELECT p.product_id id, p.product_name n, c.category_name c, p.unit u, i.quantity q FROM products p
    JOIN categories c ON c.category_id = p.category_id JOIN inventory i ON i.product_id = p.product_id AND i.branch_id = ?
    WHERE p.status = 'active' ORDER BY p.product_name", [$b]) as $r)
    $o[] = ['id' => (int)$r['id'], 'n' => $r['n'], 'c' => $r['c'], 'u' => $r['u'], 'q' => (int)$r['q'], 's' => $r['q'] . ' ' . $r['u']];
  return $o;
}
function combo($b, $sel = 0) {
  return '<div class="cb" data-items="' . h(json_encode(items($b))) . '" data-sel="' . (int)$sel . '"><input class="cbi" placeholder="Search product" autocomplete="off" aria-label="Product">'
    . '<input type="hidden" name="product_id"><ul class="cbl" hidden></ul></div>';
}
function table($head, $body) {
  return '<div class="tw"><table><thead><tr>' . implode('', array_map(fn($x) => "<th>$x</th>", $head)) . '</tr></thead><tbody>'
    . ($body ?: '<tr><td colspan="' . count($head) . '">Nothing to show yet.</td></tr>') . '</tbody></table></div>';
}
