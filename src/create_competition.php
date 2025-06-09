<?php
require 'db_connect.php';
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = uniqid('COMP');
    $name = $_POST['name'];
    $org_units = $_POST['org_units'] . ' (待審核)';
    $field = $_POST['field'];
    $deadline = $_POST['deadline'];
    $prize = $_POST['prize'] ?: null;
    $eligibility = $_POST['eligibility'] ?: null;
    $required_num = $_POST['required_num'];
    $stmt = $pdo->prepare("INSERT INTO Competition (CID, Name, Organizing_Units, Field, Registration_Deadline, Prize_Money, Eligibility_Requirements, Required_Number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$cid, $name, $org_units, $field, $deadline, $prize, $eligibility, $required_num]);
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>創建競賽</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">管理員: <?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 bg-light sidebar">
                <div class="sidebar-sticky d-flex flex-column" style="height: 100%;">
                    <ul class="nav flex-column flex-grow-1">
                        <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
                        <li class="nav-item"><a class="nav-link active" href="#">創建競賽</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin.php">競賽管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_disputes.php">糾紛管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_blacklist.php">黑名單管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="post_announcement.php">發佈公告</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_categories.php">管理分類</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>創建新競賽</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">競賽名稱</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="org_units" class="form-label">主辦單位</label>
                        <input type="text" class="form-control" id="org_units" name="org_units" required>
                    </div>
                    <div class="mb-3">
                        <label for="field" class="form-label">領域</label>
                        <input type="text" class="form-control" id="field" name="field" required>
                    </div>
                    <div class="mb-3">
                        <label for="deadline" class="form-label">報名截止日期</label>
                        <input type="date" class="form-control" id="deadline" name="deadline" required>
                    </div>
                    <div class="mb-3">
                        <label for="prize" class="form-label">獎金（可選）</label>
                        <input type="number" step="0.01" class="form-control" id="prize" name="prize">
                    </div>
                    <div class="mb-3">
                        <label for="eligibility" class="form-label">資格要求（可選）</label>
                        <textarea class="form-control" id="eligibility" name="eligibility"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="required_num" class="form-label">要求人數</label>
                        <input type="number" class="form-control" id="required_num" name="required_num" required>
                    </div>
                    <button type="submit" class="btn btn-success">創建</button>
                    <a href="admin.php" class="btn btn-secondary">返回</a>
                </form>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>