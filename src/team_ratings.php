<?php
require 'db_connect.php';
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

// 取得目前選中的隊伍ID
$TID = $_GET['TID'] ?? null;
$page_mode = 'list'; // 這裡僅用於 sidebar 樣式

// 收到刪除請求，嘗試刪除評論
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_id'], $_POST['reviewer'], $_POST['reviewee'])) {
    try {
        $team = $_POST['team_id'];
        $rer = $_POST['reviewer'];
        $ree = $_POST['reviewee'];

        $stmt = $pdo->prepare("DELETE FROM TeamRatings WHERE Team = ? AND Reviewer = ? AND Reviewee = ?");
        $stmt->execute([$team, $rer, $ree]);
        
        if ($stmt->rowCount() > 0) {
            echo "<script>alert('刪除成功！'); window.location.href='team_ratings.php';</script>";
        } else {
            echo "<script>alert('輸入錯誤，未找到評論！'); history.back();</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); history.back();</script>";
    }
    //exit;
}

//被評論的部分
$stmt1 = $pdo->prepare("SELECT tr.Team AS TID, tr.Reviewer, tr.Reviewee, tr.Rating, tr.Comment, t.Team_Name, u1.Name AS Reviewer_Name, u2.Name AS Reviewee_Name
                       FROM TeamRatings tr
                       JOIN Team t ON tr.Team = t.TID
                       JOIN User u1 ON tr.Reviewer = u1.Account
                       JOIN User u2 ON tr.Reviewee = u2.Account
                       WHERE tr.Reviewee = ?");
$stmt1->execute([$_SESSION['user_id']]);
$ratings1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
//評論他人的部分
$stmt2 = $pdo->prepare("SELECT tr.Team AS TID, tr.Reviewer, tr.Reviewee, tr.Rating, tr.Comment, t.Team_Name, u1.Name AS Reviewer_Name, u2.Name AS Reviewee_Name
                       FROM TeamRatings tr
                       JOIN Team t ON tr.Team = t.TID
                       JOIN User u1 ON tr.Reviewer = u1.Account
                       JOIN User u2 ON tr.Reviewee = u2.Account
                       WHERE tr.Reviewer = ?");
$stmt2->execute([$_SESSION['user_id']]);
$ratings2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$user_id = $_SESSION['user_id'];
// 取得目前所有隊伍（我有參加且未離開）
$stmt = $pdo->prepare("SELECT t.TID, t.Team_Name, tmh.Join_Date
                       FROM TeamMembershipHistory tmh
                       JOIN Team t ON tmh.Team = t.TID
                       WHERE tmh.Member = ? AND tmh.Leave_Date IS NULL");
$stmt->execute([$user_id]);
$my_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得目前所有隊伍（只要隊名和TID，for sidebar）
$sidebar_teams = $my_teams;

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>隊友評價</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .navbar-brand { color: #8f94fb; }
        
        .sidebar-sticky { min-height: 100vh; }
        .sidebar .nav-link.active { font-weight: bold; color: #4e54c8 !important; }
        .sidebar .nav-link { cursor: pointer; }
        .sidebar .team-list { display: block; padding-left: 1.5em; }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">學生: <?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 bg-light sidebar">
                <div class="sidebar-sticky d-flex flex-column" style="height: 100%;">
                    <ul class="nav flex-column flex-grow-1">
                        <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
                        <li class="nav-item"><a class="nav-link" href="update_profile.php">個人檔案</a></li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_team.php">我的隊伍</a>
                            <ul class="team-list" id="teamList">
                                <?php foreach ($sidebar_teams as $team): ?>
                                    <li>
                                        <a class="nav-link<?php if ($TID == $team['TID']) echo ' active'; ?>" href="my_team.php?TID=<?php echo $team['TID']; ?>">
                                            <?php echo htmlspecialchars($team['Team_Name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="create_team.php">創建隊伍</a></li>
                        <li class="nav-item"><a class="nav-link" href="apply_team.php">申請入隊</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_invitations.php">管理訊息</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_history.php">組隊紀錄</a></li>
                        <li class="nav-item"><a class="nav-link active" href="team_ratings.php">查看評價</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>隊友評價</h2>
                    <h4 class="mt-5">獲得的評價</h4> <!-- null -> reviewee -->
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>隊伍名稱</th>
                            <th>評價者</th>
                            <th>評分</th>
                            <th>評論</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ratings1 as $rating1): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rating1['Team_Name']); ?></td>
                                <td><?php echo htmlspecialchars($rating1['Reviewer_Name']); ?></td>
                                <!--td><?php echo htmlspecialchars($rating1['Rating']); ?></td-->

                                <td><?php
                                $rating = $rating1['Rating'];
                                $maxrating = 5; // fullscore
                                echo '<div>';
                                for ($i = 1; $i <= $maxrating; $i++) {
                                    if ($i <= $rating) {
                                        echo '<span style="color: gold;">★</span>'; // rating
                                    } else {
                                        echo '<span style="color: gray;">☆</span>'; // 5-rating
                                    }
                                }
                                echo '</div>';
                                ?></td>
                                <td><?php echo htmlspecialchars($rating1['Comment'] ?: '無'); ?></td>
                                <td>無</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <h4 class="mt-5">你的評論</h4> <!-- null -> -->
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>隊伍名稱</th>
                            <th>被評價者</th>
                            <th>評分</th>
                            <th>評論</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ratings2 as $rating2): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rating2['Team_Name']); ?></td>
                                <td><?php echo htmlspecialchars($rating2['Reviewee_Name']); ?></td>

                                <td><?php
                                $rating = $rating2['Rating'];
                                $maxrating = 5; // fullscore
                                echo '<div>';
                                for ($i = 1; $i <= $maxrating; $i++) {
                                    if ($i <= $rating) {
                                        echo '<span style="color: gold;">★</span>'; // rating
                                    } else {
                                        echo '<span style="color: gray;">☆</span>'; // 5-rating
                                    }
                                }
                                echo '</div>';
                                ?></td>

                                <!--td><?php echo htmlspecialchars($rating2['Rating']); ?></td-->
                                <td><?php echo htmlspecialchars($rating2['Comment'] ?: '無'); ?></td>
                                <!--td><button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#delete" date-reviewee = "<?php echo htmlspecialchars($rating2['Reviewee_Name'])?>">刪除評論</button></td><-->
                                <td> <button class="btn btn-danger btn-sm delete-review-btn" 
                                    id = "deleteModal"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#delete"
                                    data-team-name="<?php echo htmlspecialchars($rating2['Team_Name']); ?>"
                                    data-reviewer-name="<?php echo htmlspecialchars($rating2['Reviewer_Name']); ?>"
                                    data-reviewee-name="<?php echo htmlspecialchars($rating2['Reviewee_Name']); ?>"

                                    data-team="<?php echo htmlspecialchars($rating2['TID']); ?>"
                                    data-reviewer="<?php echo htmlspecialchars($rating2['Reviewer']); ?>"
                                    data-reviewee="<?php echo htmlspecialchars($rating2['Reviewee']); ?>"
                                    
                                    data-rating="<?php echo htmlspecialchars($rating2['Rating']); ?>"
                                    data-comment="<?php echo htmlspecialchars($rating2['Comment']); ?>">刪除</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="student.php" class="btn btn-secondary">返回</a>
                
                <!--刪除要求參數+刪除葉面資訊-->
            <script>

                    document.querySelectorAll('.delete-review-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {  

                            var rating=btn.getAttribute('data-rating');
                            var comment=btn.getAttribute('data-comment');
                            //alert(team);
                            document.getElementById('modalTeamId').value = btn.getAttribute('data-team');
                            document.getElementById('modalReviewer').value = btn.getAttribute('data-reviewer');
                            document.getElementById('modalReviewee').value = btn.getAttribute('data-reviewee');

                            document.getElementById("gttn").innerText = btn.getAttribute('data-team-name');
                            document.getElementById("gtrr").innerText = btn.getAttribute('data-reviewer-name');
                            document.getElementById("gtre").innerText = btn.getAttribute('data-reviewee-name');
                            //document.getElementById("gtrt").innerText = rating;
                            document.getElementById("gtct").innerText = comment;
                            var starating = '';
                            for (var i = 1; i <= 5; i++) {
                                if (i <= rating) {
                                    starating += '<span style="color: gold;">★</span>';
                                } else {
                                    starating += '<span style="color: gray;">☆</span>';
                                }
                            }
                            document.getElementById("gtrt").innerHTML = starating;

                        });

                    });

            </script>
    <!--刪除葉面內容-->
    <div class="modal fade" id="delete" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">是否確認刪除評論</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <h5>您確定要刪除以下評論嗎？此操作無法復原。</h5>
                    <div>
                    <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>隊伍名稱</th>
                            <th>評價者</th>
                            <th>被評價者</th>
                            <th>評分</th>
                            <th>評論</th>
                        </tr>
                    </thead>
                    <tbody>
                            <tr>
                                <td id="gttn"></td>
                                <td id="gtrr"></td>
                                <td id="gtre"></td>
                                <td id="gtrt">d</td>
                                <td id="gtct">e</td>
                            </tr>
                    </tbody>
                    </table>
                    </div>

                        <!--刪除要求-->
                    <form id="deleteForm" method="POST">
                        <input type="hidden" name="team_id" id="modalTeamId">
                        <input type="hidden" name="reviewer" id="modalReviewer">
                        <input type="hidden" name="reviewee" id="modalReviewee">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-danger">確認刪除</button>
                    </form>
                        <!---->
    
                </div>
            </div>
        </div>
    </div>

            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>