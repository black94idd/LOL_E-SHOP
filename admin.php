<?php
require 'db.php';

// 權限驗證：未登入或不是 admin 則踢回首頁
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// 處理新增與刪除商品的 POST 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        $teamId = (int)$_POST['team_id'];
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $price = (int)$_POST['base_price'];
        $img = trim($_POST['image_url']);

        if ($name !== '' && $price > 0) {
            $stmt = $pdo->prepare('INSERT INTO products (team_id, name, description, base_price, image_url) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$teamId, $name, $desc, $price, $img]);
            header('Location: admin.php');
            exit;
        }
    }

    if ($action === 'delete_product') {
        $productId = (int)$_POST['product_id'];
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        header('Location: admin.php');
        exit;
    }
}

// 撈取所有商品資料供列表顯示
$stmt = $pdo->query('
    SELECT p.id, p.name, p.base_price, p.image_url, t.name AS team_name 
    FROM products p JOIN teams t ON p.team_id = t.id 
    ORDER BY p.team_id ASC, p.id DESC
');
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>後台管理 - LoL E-Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0b0c10] text-gray-100 min-h-screen p-8 font-sans">
    
    <div class="max-w-6xl mx-auto flex justify-between items-center mb-8 border-b border-white/10 pb-4">
        <h1 class="text-2xl font-bold text-red-500 tracking-widest">LoL E-Shop // 後台管理系統</h1>
        <div class="space-x-4 text-sm font-bold">
            <a href="index.php" class="text-gray-400 hover:text-white transition">返回前台首頁</a>
            <a href="auth.php?logout=1" class="bg-white/10 hover:bg-red-600 text-white px-4 py-2 rounded transition">登出</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
        
        <div class="bg-[#1f2833]/50 p-6 rounded border border-white/5 h-fit sticky top-8">
            <h2 class="text-xl font-bold mb-6 text-white border-b border-white/10 pb-2">新增商品</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_product">
                
                <div>
                    <label class="block text-xs text-gray-400 font-bold mb-1 tracking-widest">所屬戰隊</label>
                    <select name="team_id" class="w-full bg-[#0b0c10] border border-white/20 text-white p-2 rounded focus:border-red-500 focus:outline-none">
                        <option value="1">CFO (LCP)</option>
                        <option value="2">T1 (LCK)</option>
                        <option value="3">BLG (LPL)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs text-gray-400 font-bold mb-1 tracking-widest">商品名稱</label>
                    <input type="text" name="name" required class="w-full bg-[#0b0c10] border border-white/20 text-white p-2 rounded focus:border-red-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-xs text-gray-400 font-bold mb-1 tracking-widest">售價 (NT$)</label>
                    <input type="number" name="base_price" required min="1" class="w-full bg-[#0b0c10] border border-white/20 text-white p-2 rounded focus:border-red-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-xs text-gray-400 font-bold mb-1 tracking-widest">圖片檔名 (需含副檔名)</label>
                    <input type="text" name="image_url" required placeholder="例如: item.jpg" class="w-full bg-[#0b0c10] border border-white/20 text-white p-2 rounded focus:border-red-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-xs text-gray-400 font-bold mb-1 tracking-widest">商品描述</label>
                    <textarea name="description" rows="3" class="w-full bg-[#0b0c10] border border-white/20 text-white p-2 rounded focus:border-red-500 focus:outline-none"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded transition tracking-widest mt-4">
                    確認上架
                </button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-[#1f2833]/50 p-6 rounded border border-white/5">
            <h2 class="text-xl font-bold mb-6 text-white border-b border-white/10 pb-2">商品管理列表</h2>
            
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-gray-500 tracking-widest border-b border-white/10">
                        <th class="py-3 font-medium">戰隊</th>
                        <th class="py-3 font-medium">圖片</th>
                        <th class="py-3 font-medium">名稱</th>
                        <th class="py-3 font-medium">價格</th>
                        <th class="py-3 font-medium text-right">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition">
                            <td class="py-3 text-red-400 font-bold"><?= htmlspecialchars($p['team_name']) ?></td>
                            <td class="py-3">
                                <img src="images/<?= htmlspecialchars($p['image_url']) ?>" class="w-12 h-12 object-contain bg-black/50 rounded">
                            </td>
                            <td class="py-3 font-bold text-white"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="py-3 text-gray-300">NT$ <?= number_format($p['base_price']) ?></td>
                            <td class="py-3 text-right">
                                <form method="POST" onsubmit="return confirm('確定要下架並刪除此商品嗎？');">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="text-xs bg-red-900/50 hover:bg-red-600 text-white px-3 py-1.5 rounded transition border border-red-500/30">刪除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>