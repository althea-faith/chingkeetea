<?php // Who is signed in, the branch being viewed, and the low-stock notifications.
require __DIR__ . '/../inc/api.php'; guard(); $b = branch(); $u = me();
out(['user' => ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']],
  'branch' => ['id' => $b, 'name' => q('SELECT branch_name FROM branches WHERE branch_id = ?', [$b])->fetchColumn()],
  'branches' => $u['role'] == 1 ? rows('SELECT branch_id AS id, branch_name AS name FROM branches ORDER BY branch_id') : [],
  'low' => lowitems($b)]);
