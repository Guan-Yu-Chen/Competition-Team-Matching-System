<?php
require 'db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cid'], $_POST['action'])) {
    $cid = $_POST['cid'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        // 批准：移除(待審核)
        $stmt = $pdo->prepare("UPDATE Competition SET Organizing_Units = REPLACE(Organizing_Units, ' (待審核)', '') WHERE CID = ?");
        $stmt->execute([$cid]);
    } elseif ($action === 'reject') {
        // ver0.2 新增回絕功能。
        // 回絕：設定 (待審核) 為 (已回絕)
        $stmt = $pdo->prepare("UPDATE Competition SET Organizing_Units = REPLACE(Organizing_Units, ' (待審核)', '(已回絕)') WHERE CID = ?");
        $stmt->execute([$cid]);
    }

    header("Location: admin.php");
    exit;
}
?>
