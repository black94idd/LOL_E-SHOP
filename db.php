<?php
session_start();
$host    = 'mysql-3f2e0ffe-kevin87945-d1ea.h.aivencloud.com';
$port    = '14933';
$db      = 'defaultdb';
$user    = 'avnadmin';
$pass = getenv('DB_PASS');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("資料庫連線失敗: " . $e->getMessage());
}
?>
