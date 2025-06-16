<?php
require 'db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$success = '';
$error = '';
$original = '';
$updated = '';


// 處理分類修改請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['original']) && isset($_POST['updated'])) {
    $original = trim($_POST['original']);
    $updated = trim($_POST['updated']);

    if ($original && $updated) {
        $stmt = $pdo->prepare("UPDATE Competition SET Field = ? WHERE Field = ?");
        $stmt->execute([$updated, $original]);
        $success = '分類「' . $original . '」已成功修改為「' . $updated . '」。';
    } else {
        $error = "請輸入正確的原分類與新分類名稱。";
    }
}

$stmt = $pdo->query("SELECT DISTINCT Field FROM Competition");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>編輯競賽分類</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-light bg-light">
    <div class="container">
        <span class="navbar-brand">管理員: <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
    </div>
</nav>
<div class="container mt-4">
    <h2>編輯競賽分類</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>現有分類</th>
                <th>修改為</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <form method="POST">
                    <td>
                        <input type="hidden" name="original" value="<?php echo htmlspecialchars($cat); ?>">
                        <?php echo htmlspecialchars($cat); ?>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="updated" placeholder="輸入新分類名稱" required>
                    </td>
                    <td>
                        <button type="submit" class="btn btn-primary btn-sm">修改</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="admin.php" class="btn btn-secondary">返回管理後台</a>
</div>
</body>
</html>
