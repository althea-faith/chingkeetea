<?php require __DIR__ . '/../inc/api.php'; post(); $i = input();
$u = q('SELECT * FROM users WHERE email = ?', [strtolower(trim($i['email'] ?? ''))])->fetch();
if (!$u || $u['status'] !== 'active' || !pwok($i['password'] ?? '', $u)) fail('Invalid email or password.', 401);
session_regenerate_id(true);
$_SESSION['u'] = ['id' => (int)$u['user_id'], 'name' => $u['full_name'], 'role' => (int)$u['role_id'], 'branch' => $u['branch_id']];
out(['ok' => true]);
