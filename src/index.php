<?php
require 'db_connect.php';
session_start();

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $account = $_POST['account'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT Account, Password, Name FROM User WHERE Account = ?");
    $stmt->execute([$account]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['Password']) {
        $_SESSION['user_id'] = $user['Account'];
        $_SESSION['user_name'] = $user['Name'];
        $stmt = $pdo->prepare("SELECT AID FROM Administrator WHERE AID = ?");
        $stmt->execute([$account]);
        $_SESSION['role'] = $stmt->fetch() ? 'admin' : 'student';
    } else {
        $login_error = "帳號或密碼錯誤";
    }
}

// Register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $account = $_POST['account'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $stmt = $pdo->prepare("SELECT Account FROM User WHERE Account = ?");
    $stmt->execute([$account]);
    if ($stmt->fetch()) {
        $register_error = "帳號已存在";
    } else {
        $stmt = $pdo->prepare("INSERT INTO User (Account, Password, Name, Email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$account, $password, $name, $email]);
        $stmt = $pdo->prepare("INSERT INTO Student (SID) VALUES (?)");
        $stmt->execute([$account]);
        $register_success = "註冊成功，請登入";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>競賽組隊系統</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+TC:wght@700;900&family=Montserrat:wght@500;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100%;
            background: #fff;
        }

        .navbar-custom {
            position: relative;
            background: linear-gradient(120deg, #edeaff 60%, #f8f9fc 100%);
            border-bottom: 1.5px solid #e0e4f7;
            box-shadow: 0 2px 16px #8f94fb14;
            overflow: hidden;
            min-height: 160px;
            padding-top: 2.5rem;
            padding-bottom: 2.5rem;
            margin-top: 0 !important;
            top: 0;
            display: flex;
            align-items: center;
        }
        .navbar-bg-coral {
            position: absolute;
            left: 50%;
            top: 54%;
            transform: translate(-50%, -50%) scale(1.18);
            width: 420px;
            height: 220px;
            background: url('assets/img/main-logo.png') center/contain no-repeat;
            opacity: 0.10;
            z-index: 0;
            filter: blur(1.2px);
            pointer-events: none;
        }
        .navbar-custom .container {
            position: relative;
            z-index: 2;
        }
        .navbar-brand {
            font-family: 'Noto Serif TC', 'Noto Sans TC', serif;
            font-weight: 900;
            font-size: 2.1rem;
            color: #3c3163 !important;
            letter-spacing: 0.12em;
            line-height: 1.08;
            padding: 0;
        }
        .navbar-brand-slogan {
            display: block;
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            font-size: 1.22rem;
            font-weight: 600;
            letter-spacing: 0.07em;
            color: #4e54c8;
            margin-left: 2px;
            margin-top: 0.4em;
            line-height: 1.1;
            background: linear-gradient(90deg, #4e54c8 0%, #8f94fb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            text-shadow: 0 1px 8px #8f94fb22;
        }
        @media (max-width: 600px) {
            .navbar-brand { font-size: 1.2rem;}
            .navbar-bg-coral { width: 180px; height: 90px; }
            .navbar-brand-slogan { font-size: 0.85rem; }
            .navbar-custom { min-height: 95px; padding-top: 1.2rem; padding-bottom: 1.2rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-light navbar-custom">
        <div class="navbar-bg-coral"></div>
        <div class="container">
            <a class="navbar-brand" href="#">
                競賽組隊系統
                <span class="navbar-brand-slogan">Your Idea, Our Support, Success Ahead</span>
            </a>
            <div class="ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="navbar-text me-2">
                        歡迎，<?php echo htmlspecialchars($_SESSION['user_name']); ?>！
                    </span>
                    <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin.php' : 'student.php'; ?>" class="btn btn-primary me-2">我的儀表板</a>
                <?php else: ?>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#loginModal">登入</button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registerModal">註冊</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- 登入モーダル -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">登入</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($login_error)) echo "<div class='alert alert-danger'>$login_error</div>"; ?>
                    <form method="POST">
                        <input type="hidden" name="login" value="1">
                        <div class="mb-3">
                            <label for="login_account" class="form-label">帳號</label>
                            <input type="text" class="form-control" id="login_account" name="account" required>
                        </div>
                        <div class="mb-3">
                            <label for="login_password" class="form-label">密碼</label>
                            <input type="password" class="form-control" id="login_password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">登入</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 註冊モーダル -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">註冊</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($register_error)) echo "<div class='alert alert-danger'>$register_error</div>"; ?>
                    <?php if (isset($register_success)) echo "<div class='alert alert-success'>$register_success</div>"; ?>
                    <form method="POST">
                        <input type="hidden" name="register" value="1">
                        <div class="mb-3">
                            <label for="register_account" class="form-label">帳號</label>
                            <input type="text" class="form-control" id="register_account" name="account" required>
                        </div>
                        <div class="mb-3">
                            <label for="register_password" class="form-label">密碼</label>
                            <input type="password" class="form-control" id="register_password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="register_name" class="form-label">姓名</label>
                            <input type="text" class="form-control" id="register_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="register_email" class="form-label">電子郵件</label>
                            <input type="email" class="form-control" id="register_email" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-success">註冊</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<div style="height: 2.5rem;"></div>
                                        
    <!-- 主要內容 -->
    <div class="container my-4">
        <h2>競賽列表</h2>
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT CID, Name, Organizing_Units, Registration_Deadline FROM Competition");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">' . htmlspecialchars($row['Name']) . '</h5>
                            <p class="card-text">主辦單位: ' . htmlspecialchars($row['Organizing_Units']) . '</p>
                            <p class="card-text">報名截止: ' . htmlspecialchars($row['Registration_Deadline']) . '</p>
                            <a href="competition_details.php?cid=' . htmlspecialchars($row['CID']) . '" class="btn btn-info">查看詳情</a>
                        </div>
                    </div>
                </div>';
            }
            ?>
        </div>

        <h2 class="mt-5">最新公告</h2>
        <div id="announcementCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php
                $stmt = $pdo->query("SELECT Title, Content FROM Announcement");
                $first = true;
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $active = $first ? 'active' : '';
                    echo '
                    <div class="carousel-item ' . $active . '">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">' . htmlspecialchars($row['Title']) . '</h5>
                                <p class="card-text">' . htmlspecialchars($row['Content']) . '</p>
                            </div>
                        </div>
                    </div>';
                    $first = false;
                }
                ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#announcementCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#announcementCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </button>
        </div>
    </div>

    <footer class="bg-light text-center py-3">
        <p class="footer-title">版權 © 2025 競賽組隊系統</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
