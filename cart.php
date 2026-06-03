<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$userId = $_SESSION['user_id'];

// 處理所有的 POST 請求 (加入、修改數量、刪除、結帳)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($action === 'add' && $productId) {
        $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        if ($qty < 1) $qty = 1;

        $stmt = $pdo->prepare('
            INSERT INTO added_to (user_id, product_id, quantity) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ');
        $stmt->execute([$userId, $productId, $qty, $qty]);

        if (isset($_POST['buy_now'])) {
            header('Location: cart.php');
        } else {
            $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
            header('Location: ' . $referer);
        }
        exit;
    }

    if ($action === 'increase' && $productId) {
        $stmt = $pdo->prepare('UPDATE added_to SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);
        header('Location: cart.php');
        exit;
    }

    if ($action === 'decrease' && $productId) {
        $stmt = $pdo->prepare('SELECT quantity FROM added_to WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);
        $currentQty = $stmt->fetchColumn();

        if ($currentQty > 1) {
            $stmt = $pdo->prepare('UPDATE added_to SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ?');
            $stmt->execute([$userId, $productId]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM added_to WHERE user_id = ? AND product_id = ?');
            $stmt->execute([$userId, $productId]);
        }
        header('Location: cart.php');
        exit;
    }

    if ($action === 'remove' && $productId) {
        $stmt = $pdo->prepare('DELETE FROM added_to WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);
        header('Location: cart.php');
        exit;
    }

    if ($action === 'checkout') {
        $totalAmount = (int)$_POST['total_amount'];
        if ($totalAmount > 0) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO orders (user_id, total_amount) VALUES (?, ?)');
                $stmt->execute([$userId, $totalAmount]);
                
                $stmt = $pdo->prepare('DELETE FROM added_to WHERE user_id = ?');
                $stmt->execute([$userId]);
                
                $pdo->commit();
                echo "<script>alert('CHECKOUT SUCCESSFUL! 結帳成功！'); window.location.href='index.php';</script>";
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                die("結帳失敗：" . $e->getMessage());
            }
        }
    }
}

// 讀取購物車內容與商品資訊
$stmt = $pdo->prepare('
    SELECT p.id AS product_id, p.name, p.base_price, p.image_url, t.name AS team_name, a.quantity 
    FROM added_to a 
    JOIN products p ON a.product_id = p.id 
    JOIN teams t ON p.team_id = t.id
    WHERE a.user_id = ?
');
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$total = 0;
$cartCount = array_sum(array_column($cartItems, 'quantity'));
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHOPPING CART - LoL E-Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;800&family=Noto+Sans+TC:wght@400;700&display=swap');
        .font-tech { font-family: 'Orbitron', 'Noto Sans TC', sans-serif; }
    </style>
</head>
<body class="bg-[#0b0c10] text-[#rgba(255,255,255,0.9)] min-h-screen font-tech selection:bg-red-600 selection:text-white">

    <nav class="sticky top-0 w-full z-40 bg-[#0b0c10]/90 backdrop-blur-md border-b border-white/5 px-6 py-4 flex justify-between items-center">
        <a href="index.php" class="text-xl font-black tracking-tighter text-white hover:text-red-500 transition">
            LOL <span class="text-red-500">E-SHOP</span>
        </a>
        <div class="flex items-center space-x-6 text-xs font-bold tracking-widest">
            <span class="text-gray-400 hidden sm:inline">MEMBER // <span class="text-white"><?= htmlspecialchars($_SESSION['username']) ?></span></span>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="text-red-400 hover:text-red-300 transition border border-red-500/30 px-3 py-1.5 rounded bg-red-900/20">ADMIN PANEL</a>
            <?php endif; ?>
            <a href="auth.php?logout=1" class="text-gray-500 hover:text-red-500 transition border border-white/10 px-3 py-1.5 rounded bg-white/5">LOGOUT</a>
        </div>
    </nav>

    <main class="container mx-auto px-6 py-12 max-w-5xl">
        <div class="bg-[#1f2833]/30 p-8 rounded-sm border border-white/5 shadow-2xl backdrop-blur-sm">
            <h2 class="text-2xl font-black mb-8 text-white tracking-widest border-b border-white/10 pb-4">
                MY CART // <span class="text-gray-500 text-sm">購物車清單</span>
            </h2>
            
            <?php if (empty($cartItems)): ?>
                <div class="text-center py-16">
                    <p class="text-gray-500 tracking-widest font-bold mb-6">[ CART IS EMPTY ]</p>
                    <a href="index.php" class="border border-white/20 text-white hover:bg-white/10 px-6 py-3 tracking-widest text-xs font-bold transition rounded-sm uppercase">
                        RETURN TO SHOP
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left mb-8 border-collapse">
                        <thead>
                            <tr class="border-b border-white/10 text-gray-500 tracking-widest text-xs uppercase">
                                <th class="py-4 px-2 font-bold w-16">Image</th>
                                <th class="py-4 px-2 font-bold">Product</th>
                                <th class="py-4 px-2 font-bold">Price</th>
                                <th class="py-4 px-2 font-bold text-center">Qty</th>
                                <th class="py-4 px-2 font-bold text-right">Subtotal</th>
                                <th class="py-4 px-2 font-bold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cartItems as $item): 
                            $subtotal = $item['base_price'] * $item['quantity'];
                            $total += $subtotal;
                        ?>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition duration-300">
                            <td class="py-4 px-2">
                                <div class="w-12 h-12 bg-black/50 border border-white/5 rounded-sm flex items-center justify-center overflow-hidden">
                                    <img src="images/<?= htmlspecialchars($item['image_url']) ?>" alt="img" class="w-full h-full object-contain">
                                </div>
                            </td>
                            <td class="py-4 px-2">
                                <div class="text-[10px] text-red-500 font-bold tracking-widest mb-1"><?= htmlspecialchars($item['team_name']) ?></div>
                                <div class="text-sm font-bold text-white"><?= htmlspecialchars($item['name']) ?></div>
                            </td>
                            <td class="py-4 px-2 text-gray-300 text-sm">
                                NT$ <?= number_format($item['base_price']) ?>
                            </td>
                            <td class="py-4 px-2">
                                <div class="flex items-center justify-center space-x-1">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="decrease">
                                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                        <button type="submit" class="border border-white/20 text-white hover:bg-white/10 w-7 h-7 flex items-center justify-center font-bold transition rounded-sm text-xs">-</button>
                                    </form>
                                    <span class="w-8 text-center text-sm font-bold text-white"><?= $item['quantity'] ?></span>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="increase">
                                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                        <button type="submit" class="border border-white/20 text-white hover:bg-white/10 w-7 h-7 flex items-center justify-center font-bold transition rounded-sm text-xs">+</button>
                                    </form>
                                </div>
                            </td>
                            <td class="py-4 px-2 text-right text-white font-black">
                                NT$ <?= number_format($subtotal) ?>
                            </td>
                            <td class="py-4 px-2 text-right">
                                <form method="POST" onsubmit="return confirm('確定要移除此商品嗎？');">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                    <button type="submit" class="text-gray-500 hover:text-red-500 text-[10px] font-bold tracking-widest transition uppercase">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="flex flex-col md:flex-row justify-between items-center pt-6 border-t border-white/10">
                    <a href="index.php" class="text-gray-400 hover:text-white text-xs font-bold tracking-widest transition mb-6 md:mb-0 uppercase">
                        ← CONTINUE SHOPPING
                    </a>
                    <div class="flex items-center space-x-8">
                        <div class="text-right">
                            <span class="text-gray-500 text-xs tracking-widest block font-bold mb-1">TOTAL AMOUNT</span>
                            <span class="text-3xl font-black text-red-500 tracking-tighter">NT$ <?= number_format($total) ?></span>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="checkout">
                            <input type="hidden" name="total_amount" value="<?= $total ?>">
                            <button type="submit" class="bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-black px-10 py-4 text-sm tracking-widest rounded-sm transition-all duration-300 uppercase">
                                CHECKOUT
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>