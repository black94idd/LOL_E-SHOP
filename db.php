<?php
session_start();

$host    = '127.0.0.1';
$db      = 'eshop_lol';
$user    = 'root';
$pass    = ''; // 依照你的 XAMPP 設定
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