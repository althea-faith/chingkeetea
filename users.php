<?php
require 'inc/layout.php'; need([1, 2]); $u = me(); $head = $u['role'] == 1; $b = branch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  chk(); $a = $_POST['a'] ?? '';
  if ($a === 'create') {
    $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
    $role = $head ? (int)($_POST['role'] ?? 3) : 3; $br = $head ? (int)($_POST['branch'] ?? $b) : $b;
    $code = q('SELECT branch_code FROM branches WHERE branch_id = ?', [$br])->fetchColumn(); $dom = $code . '.' . ($role == 2 ? 'admin' : 'staff') . '.com';
    if (!in_array($role, [2, 3]) || !$code) flash('Choose a valid role and branch.');
    elseif ($name === '' || strlen($pw) < 8) flash('Enter a name and a password of at least 8 characters.');
    elseif (!preg_match('/^[a-z0-9.]+@' . preg_quote($dom, '/') . '$/', $email)) flash("Email must look like name@$dom");
    else try {
      q('INSERT INTO users (full_name, email, password_hash, role_id, branch_id) VALUES (?,?,?,?,?)', [$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role, $br]); flash("Account created for $name.");
    } catch (PDOException $e) { flash('That email already exists, or this branch already has an active branch admin.'); }
  } elseif ($a === 'toggle') {
    $t = q('SELECT * FROM users WHERE user_id = ? AND role_id > 1', [(int)($_POST['id'] ?? 0)])->fetch();
    if ($t && ($head || ($t['branch_id'] == $b && $t['role_id'] == 3))) {
      try { q('UPDATE users SET status = ? WHERE user_id = ?', [$t['status'] === 'active' ? 'inactive' : 'active', $t['user_id']]); flash('Account updated.'); }
      catch (PDOException $e) { flash('This branch already has an active branch admin.'); }
    }
  }
  go('users.php');
}
$list = rows('SELECT u.*, r.role_name, b.branch_name FROM users u JOIN roles r ON r.role_id = u.role_id LEFT JOIN branches b ON b.branch_id = u.branch_id' . ($head ? '' : ' WHERE u.branch_id = ' . $b) . ' ORDER BY u.role_id, b.branch_name, u.full_name');
top('Users', 'users');
echo '<div class="panel"><h2>Add ' . ($head ? 'user' : 'staff') . '</h2><form method="post" class="fr">' . field() . '<input type="hidden" name="a" value="create">'
  . '<div><label>Full name</label><input name="name" required></div><div><label>Email</label><input name="email" type="email" required placeholder="name@branch.staff.com"></div><div><label>Password</label><input name="password" type="password" minlength="8" required></div>';
if ($head) {
  echo '<div><label>Role</label><select name="role"><option value="3">Staff</option><option value="2">Branch admin</option></select></div><div><label>Branch</label><select name="branch">';
  foreach (rows('SELECT branch_id, branch_name FROM branches') as $r) echo '<option value="' . $r['branch_id'] . '"' . ($r['branch_id'] == $b ? ' selected' : '') . '>' . h($r['branch_name']) . '</option>';
  echo '</select></div>';
}
echo '<div><button class="go" type="submit">Create account</button></div></form></div><div class="panel"><h2>' . ($head ? 'All users' : 'Branch team') . '</h2>';
$body = '';
foreach ($list as $r) {
  $can = $r['role_id'] > 1 && ($head || $r['role_id'] == 3);
  $body .= '<tr><td><b>' . h($r['full_name']) . '</b></td><td>' . h($r['email']) . '</td><td>' . ucwords(str_replace('_', ' ', $r['role_name'])) . '</td><td>' . h($r['branch_name'] ?? 'All branches') . '</td><td>' . pill($r['status'] === 'active' ? 'ok' : 'grey', ucfirst($r['status'])) . '</td><td>'
    . ($can ? '<form class="inl" method="post">' . field() . '<input type="hidden" name="a" value="toggle"><input type="hidden" name="id" value="' . $r['user_id'] . '"><button class="sm">' . ($r['status'] === 'active' ? 'Deactivate' : 'Activate') . '</button></form>' : '') . '</td></tr>';
}
echo table(['Name', 'Email', 'Role', 'Branch', 'Status', ''], $body) . '</div>'; bottom();
