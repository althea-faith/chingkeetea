<?php
// Shared JSON helpers. Every endpoint in /api starts with: require __DIR__ . '/../inc/api.php';
require __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
set_exception_handler(function ($e) { http_response_code(500); echo json_encode(['error' => 'Server error: ' . $e->getMessage()]); });
function out($d = [], $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function fail($m, $c = 400) { out(['error' => $m], $c); }
function input() { static $i; return $i ??= (json_decode(file_get_contents('php://input'), true) ?: []); }
function guard($roles = [1, 2, 3]) { // 1 head admin, 2 branch admin, 3 staff
  if (!me()) fail('Please sign in.', 401);
  if (!in_array(me()['role'], $roles)) fail('Access denied for your role.', 403);
}
function post() { // POST only, and must come from the front end (blocks cross-site form posts)
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required.', 405);
  if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') fail('Bad request.', 400);
}
function movements($b, $type, $limit) {
  return rows("SELECT m.created_at, m.movement_type AS type, m.qty_change AS qty, m.reason, p.product_name AS product, p.unit, u.full_name AS by_name
    FROM stock_movements m JOIN products p ON p.product_id = m.product_id LEFT JOIN users u ON u.user_id = m.recorded_by
    WHERE m.branch_id = ?" . ($type ? ' AND m.movement_type = ?' : '') . ' ORDER BY m.created_at DESC, m.movement_id DESC LIMIT ' . (int)$limit, $type ? [$b, $type] : [$b]);
}
