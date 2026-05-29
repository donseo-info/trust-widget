<?php
/**
 * Schema installer — applies schema.sql to the DB configured in config.php.
 *
 * Strips CREATE DATABASE / USE lines so the schema lands in whatever
 * DB_NAME is set (handles trust_widget vs trust-widget naming).
 *
 * Run:  php db/install.php
 */
require_once dirname(__DIR__) . '/config.php';

$sql = file_get_contents(__DIR__ . '/schema.sql');
if ($sql === false) { fwrite(STDERR, "cannot read schema.sql\n"); exit(1); }

// Strip full-line comments (-- ...) so they don't get glued to statements
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
// Remove CREATE DATABASE ... ; and USE ...; statements
$sql = preg_replace('/CREATE DATABASE[^;]*;/i', '', $sql);
$sql = preg_replace('/USE\s+[^;]*;/i', '', $sql);

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Split on semicolons at line ends (schema has no procedures, so this is safe)
$statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]/', $sql)));

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($statements as $stmt) {
    if ($stmt === '') continue;
    $pdo->exec($stmt);
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

echo "Schema installed into DB '" . DB_NAME . "'.\n";
echo "Login: admin@localhost / admin123\n";
