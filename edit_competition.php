<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$cid = $_GET['cid'];
$stmt = $pdo->prepare("SELECT * FROM Competition WHERE CID = ?");
$stmt->execute([$cid]);
$competition = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $org_units = $_POST['org_units'];
    $field = $_POST['field'];
    $deadline = $_POST['deadline'];
    $prize = $_POST['prize'] ?: null;
    $eligibility = $_POST['eligibility'] ?: null;
    $required_num = $_POST['required_num'];
    $stmt = $pdo->prepare("UPDATE Competition SET Name = ?, Organizing_Units = ?, Field = ?, Registration_Deadline = ?, Prize_Money = ?, Eligibility_Requirements = ?, Required_Number = ? WHERE CID = ?");
    $stmt->execute([$name, $org_units, $field, $deadline, $prize, $eligibility, $required_num, $cid]);
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>編輯競賽</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h2>編輯競賽</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">競賽名稱</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($competition['Name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="org_units" class="form-label">主辦單位</label>
                <input type="text" class="form-control" id="org_units" name="org_units" value="<?php echo htmlspecialchars($competition['Organizing_Units']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="field" class="form-label">領域</label>
                <input type="text" class="form-control" id="field" name="field" value="<?php echo htmlspecialchars($competition['Field']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="deadline" class="form-label">報名截止日期</label>
                <input type="date" class="form-control" id="deadline" name="deadline" value="<?php echo htmlspecialchars($competition['Registration_Deadline']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="prize" class="form-label">獎金（可選）</label>
                <input type="number" step="0.01" class="form-control" id="prize" name="prize" value="<?php echo htmlspecialchars($competition['Prize_Money']); ?>">
            </div>
            <div class="mb-3">
                <label for="eligibility" class="form-label">資格要求（可選）</label>
                <textarea class="form-control" id="eligibility" name="eligibility"><?php echo htmlspecialchars($competition['Eligibility_Requirements']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="required_num" class="form-label">所需人數</label>
                <input type="number" class="form-control" id="required_num" name="required_num" value="<?php echo htmlspecialchars($competition['Required_Number']); ?>" required>
            </div>
            <button type="submit" class="btn btn-success">更新</button>
            <a href="admin.php" class="btn btn-secondary">返回</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>