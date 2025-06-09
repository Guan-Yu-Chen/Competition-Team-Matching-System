<?php
require 'db_connect.php';
$cid = $_GET['cid'];
$stmt = $pdo->prepare("SELECT * FROM Competition WHERE CID = ?");
$stmt->execute([$cid]);
$competition = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>競賽詳情</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h2><?php echo htmlspecialchars($competition['Name']); ?></h2>
        <p><strong>主辦單位:</strong> <?php echo htmlspecialchars($competition['Organizing_Units']); ?></p>
        <p><strong>領域:</strong> <?php echo htmlspecialchars($competition['Field']); ?></p>
        <p><strong>報名截止:</strong> <?php echo htmlspecialchars($competition['Registration_Deadline']); ?></p>
        <p><strong>獎金:</strong> <?php echo htmlspecialchars($competition['Prize_Money'] ?? '無'); ?></p>
        <p><strong>資格要求:</strong> <?php echo htmlspecialchars($competition['Eligibility_Requirements'] ?? '無'); ?></p>
        <p><strong>所需人數:</strong> <?php echo htmlspecialchars($competition['Required_Number']); ?></p>
        <h3>所需技能</h3>
        <ul>
            <?php
            $stmt = $pdo->prepare("SELECT Skill FROM CompetitionRequireSkill WHERE Competition = ?");
            $stmt->execute([$cid]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<li>' . htmlspecialchars($row['Skill']) . '</li>';
            }
            ?>
        </ul>
        <a href="index.php" class="btn btn-secondary">返回</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>