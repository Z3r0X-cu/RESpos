<?php
require_once __DIR__ . '/app.php';
function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dbFile = __DIR__ . '/../database/respos.sqlite';
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    return $pdo;
}
?>
