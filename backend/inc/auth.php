<?php
// Database, session, login and branch helpers shared by every endpoint.
define('LOW', 10); // low-stock alert threshold: 10 or below
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
session_start();
function db() {
  static $p;
  if (!$p) { $c = require __DIR__ . '/../config.php';
    $p = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'],
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]); }
  return $p;
}
function q($sql, $a = []) { $s = db()->prepare($sql); $s->execute($a); return $s; }
function rows($sql, $a = []) { return q($sql, $a)->fetchAll(); }
function me() { return $_SESSION['u'] ?? null; }
function pwok($pw, $u) { // supports the sample SHA-256 hashes and PHP password_hash()
  $x = $u['password_hash'];
  if (strlen($x) === 64 && ctype_xdigit($x)) return hash_equals($x, hash('sha256', $pw . $u['email']));
  return password_verify($pw, $x);
}
// Branch the request works on: own branch, or the one the head admin picked (?b=ID).
function branch() {
  $u = me(); if ($u['role'] != 1) return (int)$u['branch'];
  if (isset($_GET['b']) && q('SELECT 1 FROM branches WHERE branch_id = ?', [(int)$_GET['b']])->fetch()) $_SESSION['b'] = (int)$_GET['b'];
  if (empty($_SESSION['b'])) $_SESSION['b'] = (int)q('SELECT MIN(branch_id) FROM branches')->fetchColumn();
  return $_SESSION['b'];
}
function lowitems($b) {
  return rows("SELECT p.product_id AS id, p.product_name AS name, p.unit, i.quantity FROM inventory i JOIN products p ON p.product_id = i.product_id
    WHERE i.branch_id = ? AND p.status = 'active' AND i.quantity <= ? ORDER BY i.quantity, p.product_name", [$b, LOW]);
}
