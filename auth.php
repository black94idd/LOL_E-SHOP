<?php
require 'db.php';

$action = $_GET['action'] ?? 'login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 確保有接收到表單傳遞過來的帳號與密碼
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($action === 'register') {
        // 註冊邏輯 (預設寫入 role = 'user')
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT IGNORE INTO users (username, password, role) VALUES (?, ?, "user")');
        if ($stmt->execute([$username, $hash]) && $stmt->rowCount() > 0) {
            header('Location: auth.php?action=login');
            exit;
        } else {
            $error = '帳號已存在或輸入無效。';
        }
    } elseif ($action === 'login') {
        // 登入邏輯
        $stmt = $pdo->prepare('SELECT id, password, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role']; // 記錄管理員權限
            
            // 依照權限導向不同頁面
            if ($user['role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = '帳號或密碼錯誤。';
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?= $action === 'login' ? '登入' : '註冊' ?> - LoL E-Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;800&family=Noto+Sans+TC:wght@400;700&display=swap');
        .font-tech { font-family: 'Orbitron', 'Noto Sans TC', sans-serif; }
    </style>
</head>
<body class="bg-[#0b0c10] flex items-center justify-center min-h-screen font-tech">
    <div class="bg-[#1f2833]/50 p-8 rounded shadow-md w-96 text-white border border-white/10">
        <h2 class="text-2xl mb-4 font-bold tracking-widest"><?= $action === 'login' ? '會員登入' : '註冊帳號' ?></h2>
        <?php if ($error): ?><p class="text-red-500 mb-4 text-sm font-bold tracking-widest"><?= $error ?></p><?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-400 mb-1 text-xs tracking-widest">帳號</label>
                <input type="text" name="username" required class="w-full p-2 bg-[#0b0c10] rounded border border-white/20 focus:outline-none focus:border-red-500 transition text-white">
            </div>
            <div class="mb-6">
                <label class="block text-gray-400 mb-1 text-xs tracking-widest">密碼</label>
                <input type="password" name="password" required class="w-full p-2 bg-[#0b0c10] rounded border border-white/20 focus:outline-none focus:border-red-500 transition text-white">
            </div>
            <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold py-2 rounded transition tracking-widest">
                <?= $action === 'login' ? '登入' : '註冊' ?>
            </button>
        </form>
        
        <div class="mt-6 text-xs text-gray-400 text-center tracking-widest border-t border-white/10 pt-4">
            <?php if ($action === 'login'): ?>
                還沒有帳號？ <a href="?action=register" class="text-yellow-500 hover:text-yellow-400">立即註冊</a>
            <?php else: ?>
                已有帳號？ <a href="?action=login" class="text-yellow-500 hover:text-yellow-400">返回登入</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>