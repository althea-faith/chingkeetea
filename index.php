<?php
require 'inc/layout.php';
if (me()) go('dashboard.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  chk(); $e = strtolower(trim($_POST['email'] ?? ''));
  $u = q('SELECT * FROM users WHERE email = ?', [$e])->fetch();
  if ($u && $u['status'] === 'active' && pwok($_POST['password'] ?? '', $u)) {
    session_regenerate_id(true);
    $_SESSION['u'] = ['id' => (int)$u['user_id'], 'name' => $u['full_name'], 'role' => (int)$u['role_id'], 'branch' => $u['branch_id']];
    go('dashboard.php');
  }
  $err = 'Invalid email or password.';
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in · Chingkeetea IMS</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="assets/css/style.css"></head><body>
<section id="login"><div class="shell"><aside class="side"><div class="logo"><i class="leaf"></i>Chingkeetea</div>
<div><h2>Every cup is a new story</h2><p>Inventory management for all Chingkeetea branches.</p></div></aside>
<main class="main"><h1>Sign in</h1><p class="sub">Use your Chingkeetea email. Your branch and role open automatically.</p>
<form method="post" autocomplete="on"><?= field() ?>
<label for="u">Email</label><input id="u" name="email" type="email" required placeholder="carvero.althea@velez.admin.com" value="<?= h($_POST['email'] ?? '') ?>">
<label for="p">Password</label><input id="p" name="password" type="password" required placeholder="Enter your password">
<button class="go" type="submit">Sign in</button><div class="msg" role="alert"><?= h($err) ?></div></form></main></div></section></body></html>
