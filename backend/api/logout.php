<?php require __DIR__ . '/../inc/api.php'; post(); $_SESSION = []; session_destroy(); out(['ok' => true]);
