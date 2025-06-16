<?php
require 'db_connect.php';
session_start();

// 處理登出
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// ver0.2 新增 teamdispute TABLE 欄位"Decision"顯示"判決結果：申述成立、不申述、惡意誣告"，若未成立顯示NULL。
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispute_id'], $_POST['decision'])) {
    $dispute_id = $_POST['dispute_id'];
    $decision = $_POST['decision'];

    echo "處理糾紛 ID: " . htmlspecialchars($dispute_id) . "<br>";
    echo "決策結果: " . htmlspecialchars($decision) . "<br>";
    // 建議先檢查 $dispute_id 和 $decision 是否有效
    $allowed_decisions = ['申述成立', '不申述', '惡意誣告'];
    if (!in_array($decision, $allowed_decisions)) {
        die('非法的判決結果');
    }

    $stmt = $pdo->prepare("UPDATE TeamDispute SET Decision = ? WHERE DisputeID = ?");
    $stmt->execute([$decision, $dispute_id]);

    header("Location: manage_disputes.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>糾紛管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- 頁首 -->
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">管理員: <?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
        </div>
    </nav>

    <!-- 主要內容 -->
    <div class="container mt-4">
        <div class="row">
            <!-- 側邊欄 -->
            <!-- <nav class="col-md-2 bg-light sidebar">
                <div class="sidebar-sticky d-flex flex-column" style="height: 100%;">
                    <ul class="nav flex-column flex-grow-1">
                        <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
                        <li class="nav-item"><a class="nav-link" href="create_competition.php">創建競賽</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin.php">競賽管理</a></li>
                        <li class="nav-item"><a class="nav-link active" href="manage_disputes.php">糾紛管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_blacklist.php">黑名單管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="post_announcement.php">發佈公告</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav> -->

            <!-- 動態內容 -->
            <main class="col-md-10 px-4">
                <!-- ver0.2 將糾紛管理分成兩部分，待處理糾紛和已處理糾紛顯示。 -->
                <h2>糾紛管理</h2>
                <table class="table table-striped">
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT DisputeID, ComplainantID, RespondentID, DisputeDetails, Decision FROM TeamDispute WHERE HandlerID = ?");
                        $stmt->execute([$_SESSION['user_id']]);

                        $processed = [];  // 已處理
                        $pending = [];    // 待處理

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            if (empty($row['Decision'])) {
                                $pending[] = $row;
                            } else {
                                $processed[] = $row;
                            }
                        }
                        ?>

                        <h3>待處理糾紛</h3>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>糾紛ID</th>
                                    <th>申訴人</th>
                                    <th>被申訴人</th>
                                    <th>糾紛內容</th>
                                    <th>判決結果</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['DisputeID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ComplainantID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['RespondentID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['DisputeDetails']); ?></td>
                                    <td>待處理</td>
                                    <td>
                                        <!-- ver0.2 實作"處理"按鈕使用 modal 。 -->
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#handleModal"
                                            data-disputeid="<?php echo htmlspecialchars($row['DisputeID']); ?>">處理</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <h3>已處理糾紛</h3>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>糾紛ID</th>
                                    <th>申訴人</th>
                                    <th>被申訴人</th>
                                    <th>糾紛內容</th>
                                    <th>判決結果</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($processed as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['DisputeID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ComplainantID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['RespondentID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['DisputeDetails']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Decision']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#handleModal"
                                            data-disputeid="<?php echo htmlspecialchars($row['DisputeID']); ?>">查看</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </tbody>
                </table>
                <a href="admin.php" class="btn btn-secondary">返回</a>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ver0.2 處理糾紛 Modal -->
<div class="modal fade" id="handleModal" tabindex="-1" aria-labelledby="handleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" id="handleForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="handleModalLabel">處理糾紛</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="dispute_id" id="dispute_id" value="">
          <!-- get value from data-disputeid -->
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                var handleModal = document.getElementById('handleModal');
                handleModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget; // 按鈕觸發的事件
                    var disputeId = button.getAttribute('data-disputeid'); // 獲取 data-disputeid 屬性
                    var disputeIdInput = document.getElementById('dispute_id');
                    disputeIdInput.value = disputeId; // 設置隱藏輸入框的值
                });
                });
            </script>
          <div class="mb-3">
            <label for="decision" class="form-label">判決結果</label>
            <select name="decision" id="decision" class="form-select" required>
              <option value="">請選擇</option>
              <option value="申述成立">申訴人理由充分，申述成立</option>
              <option value="不申述">申訴人理由不足，不申述</option>
              <option value="惡意誣告">申訴人惡意誣告</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
          <button type="submit" class="btn btn-primary">送出</button>
        </div>
      </div>
    </form>
  </div>
</div>
</body>
</html>