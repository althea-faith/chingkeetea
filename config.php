<?php
// Database connection. XAMPP defaults: user "root", empty password.
date_default_timezone_set('Asia/Manila');
return [
  'host' => getenv('CKT_DB_HOST') ?: '127.0.0.1',
  'port' => getenv('CKT_DB_PORT') ?: 3307, // your phpMyAdmin shows 127.0.0.1:3307. Use 3306 if your MySQL uses the default.
  'name' => 'chingkeetea_ims',
  'user' => getenv('CKT_DB_USER') ?: 'root',
  'pass' => getenv('CKT_DB_PASS') ?: '',
];
