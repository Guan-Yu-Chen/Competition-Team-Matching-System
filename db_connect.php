<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=fundraising_platform", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    die("連線失敗: " . $e->getMessage());
}
?>