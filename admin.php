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
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>管理員儀表板</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .admin-header {
            font-size: 1.1rem;
            font-weight: 500;
            font-family: 'Noto Sans TC', 'Segoe UI', Arial, sans-serif;
            color: #665e85;
            letter-spacing: 0.02em;
            background: none;
            padding: 0.55rem 0;
        }
        .admin-header .admin-role {
            font-weight: 600;
            color: #4e54c8;
            margin-right: 0.3em;
        }
        .admin-header .admin-name {
            font-weight: 500;
            color: #3c3163;
        }
        .navbar {
            padding-top: 0.4rem;
            padding-bottom: 0.4rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="admin-header">
                <span class="admin-role">管理員：</span>
                <span class="admin-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </span>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 bg-light sidebar">
                <div class="sidebar-sticky d-flex flex-column" style="height: 100%;">
                    <ul class="nav flex-column flex-grow-1">
                        <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
                        <li class="nav-item"><a class="nav-link" href="create_competition.php">創建競賽</a></li>
                        <li class="nav-item"><a class="nav-link active" href="#competitions">競賽管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_disputes.php">糾紛管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_blacklist.php">黑名單管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="post_announcement.php">發佈公告</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_categories.php">管理分類</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <section id="competitions">
                    <h2>競賽管理</h2>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>名稱</th>
                                <th>主辦單位</th>
                                <th>截止日期</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT CID, Name, Organizing_Units, Registration_Deadline FROM Competition");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $is_pending = strpos($row['Organizing_Units'], '(待審核)') !== false;
                                echo '
                                <tr>
                                    <td>' . htmlspecialchars($row['Name']) . ($is_pending ? ' (待審核)' : '') . '</td>
                                    <td>' . htmlspecialchars($row['Organizing_Units']) . '</td>
                                    <td>' . htmlspecialchars($row['Registration_Deadline']) . '</td>
                                    <td>
                                        <a href="edit_competition.php?cid=' . htmlspecialchars($row['CID']) . '" class="btn btn-sm btn-primary">編輯</a>';
                                if ($is_pending) {
                                    echo '
                                        <form method="POST" action="approve_competition.php" style="display:inline;">
                                            <input type="hidden" name="cid" value="' . htmlspecialchars($row['CID']) . '">
                                            <button type="submit" class="btn btn-sm btn-success">批准</button>
                                        </form>';
                                }
                                echo '
                                    </td>
                                </tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </section>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
