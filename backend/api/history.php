<?php require __DIR__ . '/../inc/api.php'; guard(); out(['rows' => movements(branch(), null, 300)]);
