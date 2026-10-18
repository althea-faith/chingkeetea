<?php // Head admin: all users. Branch admin: staff of own branch. GET list, POST {a: create | toggle}
require __DIR__ . '/../inc/api.php'; guard([1, 2]); $u = me(); $head = $u['role'] == 1; $b = branch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post(); $i = input(); $a = $i['a'] ?? '';
  if ($a === 'create') {
    $name = trim($i['name'] ?? ''); $email = strtolower(trim($i['email'] ?? '')); $pw = $i['password'] ?? '';
    $role = $head ? (int)($i['role'] ?? 3) : 3; $br = $head ? (int)($i['branch'] ?? $b) : $b;
    $code = q('SELECT branch_code FROM branches WHERE branch_id = ?', [$br])->fetchColumn(); $dom = $code . '.' . ($role == 2 ? 'admin' : 'staff') . '.com';
    if (!in_array($role, [2, 3]) || !$code) fail('Choose a valid role and branch.');
    if ($name === '' || strlen($pw) < 8) fail('Enter a name and a password of at least 8 characters.');
    if (!preg_match('/^[a-z0-9.]+@' . preg_quote($dom, '/') . '$/', $email)) fail("Email must look like name@$dom");
    try { q('INSERT INTO users (full_name, email, password_hash, role_id, branch_id) VALUES (?,?,?,?,?)', [$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role, $br]); }
    catch (PDOException $e) { fail('That email already exists, or this branch already has an active branch admin.'); }
    out(['message' => "Account created for $name."]);
  }
  $t = q('SELECT * FROM users WHERE user_id = ? AND role_id > 1', [(int)($i['id'] ?? 0)])->fetch();
  if ($a !== 'toggle' || !$t || !($head || ($t['branch_id'] == $b && $t['role_id'] == 3))) fail('You are not allowed to do that.', 403);
  try { q('UPDATE users SET status = ? WHERE user_id = ?', [$t['status'] === 'active' ? 'inactive' : 'active', $t['user_id']]); }
  catch (PDOException $e) { fail('This branch already has an active branch admin.'); }
  out(['message' => 'Account updated.']);
}
out(['rows' => rows('SELECT u.user_id AS id, u.full_name AS name, u.email, u.role_id AS role, u.status, b.branch_name AS branch FROM users u LEFT JOIN branches b ON b.branch_id = u.branch_id'
    . ($head ? '' : ' WHERE u.branch_id = ' . $b) . ' ORDER BY u.role_id, b.branch_name, u.full_name'),
  'branches' => $head ? rows('SELECT branch_id AS id, branch_name AS name FROM branches') : []]);
