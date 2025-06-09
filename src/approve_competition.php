<?php
require 'db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = $_POST['cid'];
    $stmt = $pdo->prepare("UPDATE Competition SET Organizing_Units = REPLACE(Organizing_Units, ' (待審核)', '') WHERE CID = ?");
    $stmt->execute([$cid]);
    header("Location: admin.php");
    exit;
}
?>