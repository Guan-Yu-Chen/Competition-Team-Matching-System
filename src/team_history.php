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

// 取得目前選中的隊伍ID
$TID = $_GET['TID'] ?? null;
$page_mode = 'list'; // 這裡僅用於 sidebar 樣式

// 處理 AJAX 請求
if (isset($_GET['team_info']) && isset($_GET['team_id'])) {
    $team_id = $_GET['team_id'];
    $type = $_GET['type'] ?? 'current';
    $join_date = $_GET['join_date'] ?? null;
    $leave_date = $_GET['leave_date'] ?? null;

    // 取得隊伍名稱
    $stmt = $pdo->prepare("SELECT Team_Name, Leader FROM Team WHERE TID = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$team) {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => '查無此隊伍'
        ]);
        exit;
    }

    // 取得隊伍要求技能
    $stmt = $pdo->prepare("SELECT Skill FROM TeamRequireSkill WHERE Team = ?");
    $stmt->execute([$team_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 取得隊伍成員
    if ($type === 'current') {
        // 目前成員（Leave_Date 為 NULL）
        $stmt = $pdo->prepare("SELECT m.Member, u.Name FROM TeamMembershipHistory m JOIN User u ON m.Member = u.Account WHERE m.Team = ? AND m.Leave_Date IS NULL");
        $stmt->execute([$team_id]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // 歷史成員（在該生加入與離開之間有同隊過的人）
        $stmt = $pdo->prepare(
            "SELECT DISTINCT m.Member, u.Name
            FROM TeamMembershipHistory m
            JOIN User u ON m.Member = u.Account
            WHERE m.Team = :team_id
            AND (
                (m.Join_Date <= :leave_date AND (m.Leave_Date IS NULL OR m.Leave_Date >= :join_date))
            )"
        );
        $stmt->execute([
            'team_id' => $team_id,
            'join_date' => $join_date,
            'leave_date' => $leave_date
        ]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 隊長放第一個
    $leader_id = $team['Leader'];
    $leader_name = '';
    foreach ($members as $k => $m) {
        if ($m['Member'] === $leader_id) {
            $leader_name = $m['Name'];
            unset($members[$k]);
            break;
        }
    }
    $members = array_values($members);

    header('Content-Type: application/json');
    echo json_encode([
        'team_name' => $team['Team_Name'],
        'leader' => ['id' => $leader_id, 'name' => $leader_name],
        'members' => $members,
        'skills' => $skills
    ]);
    exit;
}
// 處理 AJAX：評論成員
if (isset($_GET['ajax']) && $_GET['ajax'] === 'rate_members' && isset($_GET['team_id']) && isset($_GET['join_date']) && isset($_GET['leave_date'])) {
    $team_id = $_GET['team_id'];
    $join_date = $_GET['join_date'];
    $leave_date = $_GET['leave_date'];
    $current_user = $_SESSION['user_id'];

    // 取得同隊成員
    $stmt = $pdo->prepare(
        "SELECT m.Member, u.Name, t.Leader
        FROM TeamMembershipHistory m
        JOIN User u ON m.Member = u.Account
        JOIN Team t ON m.Team = t.TID
        WHERE m.Team = ?
        AND m.Join_Date <= ?
        AND (m.Leave_Date IS NULL OR m.Leave_Date >= ?)"
    );
    $stmt->execute([$team_id, $leave_date, $join_date]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 查詢每個成員的評論
    foreach ($members as &$m) {
        $stmt2 = $pdo->prepare("SELECT Rating, Comment FROM TeamRatings WHERE Reviewer = ? AND Reviewee = ? AND Team = ?");
        $stmt2->execute([$current_user, $m['Member'], $team_id]);
        $rating = $stmt2->fetch(PDO::FETCH_ASSOC);
        $m['Rating'] = $rating['Rating'] ?? null;
        $m['Comment'] = $rating['Comment'] ?? null;
    }
    unset($m);

    header('Content-Type: application/json');
    echo json_encode($members);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>隊伍歷史</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .navbar-brand { color: #8f94fb; }

        .star-yellow {
            color: #ffc107;
            font-size: 1.2em;
        }
        
        .sidebar-sticky { min-height: 100vh; }
        .sidebar .nav-link.active { font-weight: bold; color: #4e54c8 !important; }
        .sidebar .nav-link { cursor: pointer; }
        .sidebar .team-list { display: block; padding-left: 1.5em; }
        /* Modal 樣式 */
        .modal-bg {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.3);
            justify-content: center;
            align-items: center;
        }
        .modal-bg.active { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 2rem 2.5rem;
            min-width: 320px;
            max-width: 95vw;
            box-shadow: 0 8px 32px #4e54c822;
            position: relative;
        }
        .modal-close {
            position: absolute;
            right: 1.2rem;
            top: 1.2rem;
            font-size: 1.5rem;
            color: #888;
            cursor: pointer;
        }
        .modal-box h5 { margin-bottom: 1.2rem; }
        .modal-box ul { padding-left: 1.2em; }
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
                        <li class="nav-item"><a class="nav-link active" href="team_history.php">組隊紀錄</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_ratings.php">查看評價</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>隊伍歷史</h2>
                <!-- 過去的隊伍 -->
                <h4 class="mt-5">過去參與隊伍</h4>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>隊伍名稱</th>
                            <th>加入日期</th>
                            <th>離開日期</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 過去隊伍：Leave_Date 不為 NULL
                        $stmt = $pdo->prepare("SELECT t.Team_Name, t.TID, tmh.Join_Date, tmh.Leave_Date
                                                FROM Team AS t 
                                                JOIN (
                                                    SELECT Team, Join_Date, Leave_Date 
                                                    FROM TeamMembershipHistory 
                                                    WHERE Member = ? 
                                                        AND Leave_Date IS NOT NULL) AS tmh
                                                ON tmh.Team = t.TID");
                        $stmt->execute([$_SESSION['user_id']]);
                        $hasPast = false;
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $hasPast = true;
                            echo '
                            <tr>
                                <td>' . htmlspecialchars($row['Team_Name']) . '</td>
                                <td>' . htmlspecialchars($row['Join_Date']) . '</td>
                                <td>' . htmlspecialchars($row['Leave_Date']) . '</td>
                                <td>
                                    <button class="btn btn-primary btn-sm view-team-btn"
                                        data-team="' . htmlspecialchars($row['TID']) . '"
                                        data-type="past"
                                        data-join="' . htmlspecialchars($row['Join_Date']) . '"
                                        data-leave="' . htmlspecialchars($row['Leave_Date']) . '">查看</button>
                                    <button class="btn btn-success btn-sm rate-members-btn"
                                        data-team="' . htmlspecialchars($row['TID']) . '"
                                        data-join="' . htmlspecialchars($row['Join_Date']) . '"
                                        data-leave="' . htmlspecialchars($row['Leave_Date']) . '">
                                        評論成員
                                    </button>
                                </td>
                            </tr>';
                        }
                        if (!$hasPast) {
                            echo '<tr><td colspan="4" class="text-muted">尚未有過去隊伍紀錄</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <a href="student.php" class="btn btn-secondary">返回</a>
            </main>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-bg" id="teamModal">
        <div class="modal-box">
            <span class="modal-close" id="modalClose">&times;</span>
            <div id="modalContent">
                <!-- AJAX 內容 -->
            </div>
        </div>
    </div>
    <div class="modal-bg" id="rateMembersModal">
        <div class="modal-box">
            <span class="modal-close" id="rateMembersModalClose">&times;</span>
            <div id="rateMembersModalContent"></div>
        </div>
    </div>

    <script>
        // 重新載入評論成員 modal
        function reloadRateMembersModal(teamId, joinDate, leaveDate) {
            fetch(`team_history.php?ajax=rate_members&team_id=${encodeURIComponent(teamId)}&join_date=${encodeURIComponent(joinDate)}&leave_date=${encodeURIComponent(leaveDate)}`)
                .then(res => res.json())
                .then(members => {
                    let html = `<h5>評價成員：</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>成員名稱</th>
                                    <th>身分</th>
                                    <th>評分</th>
                                    <th>評論</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    if (members.length === 0) {
                        html += `<tr><td colspan="5" class="text-muted">沒有同隊成員</td></tr>`;
                    } else {
                        members.forEach(m => {
                            let stars = '無';
                            if (m.Rating !== null && m.Rating !== '') {
                                const rating = parseInt(m.Rating, 10);
                                stars = `<span class="star-yellow">` +
                                    '★'.repeat(rating) + '☆'.repeat(5 - rating) +
                                    `</span>`;
                            }
                            html += `<tr>
                                <td>${m.Name}</td>
                                <td>${m.Member === m.Leader ? '隊長' : '隊員'}</td>
                                <td>${stars}</td>
                                <td>${m.Comment !== null && m.Comment !== '' ? m.Comment : '無'}</td>
                                <td>
                                    <button class="btn btn-success btn-sm rate-edit-btn"
                                        data-uid="${m.Member}"
                                        data-name="${m.Name}"
                                        data-team="${teamId}"
                                        data-join="${joinDate}"
                                        data-leave="${leaveDate}">
                                        新增/編輯評論
                                    </button>
                                </td>
                            </tr>`;
                        });
                    }
                    html += `</tbody></table>`;
                    document.getElementById('rateMembersModalContent').innerHTML = html;
                    document.getElementById('rateMembersModal').classList.add('active');
                });
        }

        document.querySelectorAll('.view-team-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const teamId = btn.getAttribute('data-team');
                const type = btn.getAttribute('data-type');
                let url = 'team_history.php?team_info=1&team_id=' + encodeURIComponent(teamId) + '&type=' + type;
                if (type === 'past') {
                    url += '&join_date=' + encodeURIComponent(btn.getAttribute('data-join')) +
                        '&leave_date=' + encodeURIComponent(btn.getAttribute('data-leave'));
                }
                fetch(url)
                    .then(res => {
                        // 先檢查 response
                        if (!res.ok) throw new Error('HTTP error ' + res.status);
                        return res.text();
                    })
                    .then(text => {
                        try {
                            const data = JSON.parse(text);
                            if (data.error) {
                                document.getElementById('modalContent').innerHTML =
                                    `<div style="color:red;">${data.error}</div>`;
                                document.getElementById('teamModal').classList.add('active');
                                return;
                            }
                            let html = `<h5>隊伍名稱：${data.team_name}</h5>`;
                            html += `<div><strong>隊長：</strong>${data.leader.name}</div>`;
                            html += `<div class="mt-2"><strong>隊伍成員：</strong><ul>`;
                            html += `<li><span style="color:#4e54c8;font-weight:bold;">${data.leader.name} (隊長)</span></li>`;
                            data.members.forEach(m => {
                                html += `<li>${m.name}</li>`;
                            });
                            html += `</ul></div>`;
                            html += `<div class="mt-2"><strong>隊伍要求技能：</strong> ${data.skills.join(', ') || '無'}</div>`;
                            document.getElementById('modalContent').innerHTML = html;
                            document.getElementById('teamModal').classList.add('active');
                        } catch (e) {
                            // 顯示原始回傳內容與錯誤
                            document.getElementById('modalContent').innerHTML =
                                '<div style="color:red;">JSON 解析失敗：</div><pre style="max-height:300px;overflow:auto;background:#eee;">' +
                                text.replace(/</g, '&lt;') +
                                '</pre><div style="color:red;">錯誤訊息：' + e.message + '</div>';
                            document.getElementById('teamModal').classList.add('active');
                            console.error('JSON parse error:', e, text);
                        }
                    })
                    .catch(err => {
                        document.getElementById('modalContent').innerHTML = '載入失敗，請稍後再試。<br>' + err;
                        document.getElementById('teamModal').classList.add('active');
                        console.error('Fetch error:', err);
                    });
            });
        });
        document.getElementById('modalClose').onclick = function() {
            document.getElementById('teamModal').classList.remove('active');
        };
        document.getElementById('teamModal').onclick = function(e) {
            if (e.target === this) this.classList.remove('active');
        };
        document.querySelectorAll('.rate-members-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const teamId = btn.getAttribute('data-team');
                const joinDate = btn.getAttribute('data-join');
                const leaveDate = btn.getAttribute('data-leave');
                reloadRateMembersModal(teamId, joinDate, leaveDate);
            });
        });

        // 關閉 rateMembersModal
        document.getElementById('rateMembersModalClose').onclick = function() {
            document.getElementById('rateMembersModal').classList.remove('active');
        };
        document.getElementById('rateMembersModal').onclick = function(e) {
            if (e.target === this) this.classList.remove('active');
        };

        // 動態代理 新增/編輯評論 按鈕
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.rate-edit-btn');
            if (!btn) return;
            const uid = btn.getAttribute('data-uid');
            const uname = btn.getAttribute('data-name');
            const teamId = btn.getAttribute('data-team');
            const joinDate = btn.getAttribute('data-join');
            const leaveDate = btn.getAttribute('data-leave');
            document.getElementById('rateMembersModal').classList.remove('active');
            // 取得評論內容
            fetch(`my_team.php?ajax=edit-rating&uid=${encodeURIComponent(uid)}`)
                .then(res => res.json())
                .then(data => {
                    let html = `<h5>新增/編輯對 ${uname} 的評論：</h5>
                        <form id="editRatingForm">
                            <div class="mb-2">
                                <label>評分：</label>
                                <input type="number" name="rating" min="1" max="5" class="form-control" value="${data?.Rating || ''}">
                            </div>
                            <div class="mb-2">
                                <label>評論：</label>
                                <textarea name="comment" class="form-control">${data?.Comment || ''}</textarea>
                            </div>
                            <input type="hidden" name="Team" value="${teamId}">
                            <input type="hidden" name="uid" value="${uid}">
                            <button type="submit" class="btn btn-primary" id="submitRatingBtn" disabled>提交</button>
                            <button type="button" class="btn btn-danger" id="deleteRatingBtn">刪除</button>
                            <button type="button" class="btn btn-secondary" id="cancelEditRatingBtn">取消</button>
                        </form>`;
                    // 建立modal
                    let modal = document.createElement('div');
                    modal.className = 'modal-bg active';
                    modal.id = 'editRatingModal';
                    modal.innerHTML = `<div class="modal-box">
                        <span class="modal-close" id="editRatingModalClose">&times;</span>
                        <div>${html}</div>
                    </div>`;
                    document.body.appendChild(modal);

                    // 輸入檢查
                    const ratingInput = modal.querySelector('input[name="rating"]');
                    const commentInput = modal.querySelector('textarea[name="comment"]');
                    const submitBtn = modal.querySelector('#submitRatingBtn');
                    function checkInputs() {
                        if (ratingInput.value.trim() && commentInput.value.trim()) {
                            submitBtn.disabled = false;
                        } else {
                            submitBtn.disabled = true;
                        }
                    }
                    ratingInput.addEventListener('input', checkInputs);
                    commentInput.addEventListener('input', checkInputs);
                    checkInputs();

                    // 提交
                    modal.querySelector('#editRatingForm').onsubmit = function(e) {
                        e.preventDefault();
                        const formData = new FormData(this);
                        fetch('my_team.php?ajax=save-rating', {
                            method: 'POST',
                            body: formData
                        }).then(res => res.json()).then(r => {
                            if (r.success) {
                                document.body.removeChild(modal);
                                reloadRateMembersModal(teamId, joinDate, leaveDate);
                            }
                        });
                    };
                    // 刪除
                    modal.querySelector('#deleteRatingBtn').onclick = function() {
                        fetch('my_team.php?ajax=delete-rating', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'uid=' + encodeURIComponent(uid)
                        }).then(res => res.json()).then(r => {
                            if (r.success) {
                                document.body.removeChild(modal);
                                reloadRateMembersModal(teamId, joinDate, leaveDate);
                            }
                        });
                    };
                    // 關閉modal
                    function closeEditModal() {
                        document.body.removeChild(modal);
                        reloadRateMembersModal(teamId, joinDate, leaveDate);
                    }
                    modal.querySelector('#editRatingModalClose').onclick = closeEditModal;
                    modal.querySelector('#cancelEditRatingBtn').onclick = closeEditModal;
                    modal.onclick = function(e) {
                        if (e.target === modal) closeEditModal();
                    };
                });
        });
    </script>
    <script src