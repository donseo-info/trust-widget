<?php
require_once __DIR__ . '/config.php';
$db = Database::getInstance();
$hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
$db->execute("UPDATE users SET password = ? WHERE email = 'admin@localhost'", [$hash]);
echo "Done. Login: admin@localhost / admin123";
