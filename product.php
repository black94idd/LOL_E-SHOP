<?php
require 'db.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare('
    SELECT p.id, p.name, p.description, p.base_price, p.image_url, t.name AS team_name 
    FROM products p JOIN teams t ON p.team_id = t.id 
    WHERE p.id = ?
');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $stmtCount = $pdo->prepare('SELECT SUM(quantity) FROM added_to WHERE user_id = ?');
    $stmtCount->execute([$_SESSION['user_id']]);
    $cartCount = (int)$stmtCount->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - LoL E-Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;800&family=Noto+Sans+TC:wght@400;700&display=swap');
        .font-tech { font-family: 'Orbitron', 'Noto Sans TC', sans-serif; }
        input[type="number"]::-webkit-inner-spin-button, 
        input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="bg-[#0b0c10] text-[#rgba(255,255,255,0.9)] min-h-screen font-tech selection:bg-red-600 selection:text-white">

    <nav class="sticky top-0 w-full z-40 bg-[#0b0c10]/90 backdrop-blur-md border-b border-white/5 px-6 py-4 flex justify-between items-center">
        <a href="index.php" class="text-xl font-black tracking-tighter text-white hover:text-red-500 transition">
            LOL <span class="text-red-500">E-SHOP</span>
        </a>
        <div class="flex items-center space-x-6 text-xs font-bold tracking-widest">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="cart.php" class="relative text-gray-300 hover:text-white transition p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
                    </svg>
                    <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[9px] rounded-full h-4 w-4 flex items-center justify-center"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="auth.php?logout=1" class="text-gray-500 hover:text-red-500 border border-white/10 px-3 py-1.5 rounded">LOGOUT</a>
            <?php else: ?>
                <a href="auth.php" class="text-white hover:text-red-500 underline underline-offset-4">LOGIN</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container mx-auto px-6 py-16 max-w-5xl">
        <div class="flex items-center text-xs text-gray-500 mb-8 tracking-widest">
            <a href="index.php" class="hover:text-white transition">HOME</a>
            <span class="mx-2">/</span>
            <span class="text-red-500 uppercase"><?= htmlspecialchars($product['team_name']) ?></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div class="bg-[#1f2833]/20 border border-white/5 aspect-square flex items-center justify-center p-8 rounded-sm">
                <img src="images/<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-contain">
            </div>

            <div class="flex flex-col justify-center">
                <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight mb-4">
                    <?= htmlspecialchars($product['name']) ?>
                </h1>
                <div class="text-2xl font-extrabold text-red-500 mb-6">
                    NT$ <?= number_format($product['base_price']) ?>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed mb-10 border-b border-white/10 pb-10">
                    <?= htmlspecialchars($product['description']) ?>
                </p>

                <form action="cart.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    
                    <div class="mb-8">
                        <label class="block text-gray-400 text-xs font-bold tracking-widest mb-3">數量</label>
                        <div class="inline-flex items-center border border-white/20 rounded-sm bg-transparent">
                            <button type="button" onclick="let q=document.getElementById('qty'); if(q.value>1) q.value--;" class="px-4 py-2 text-white hover:bg-white/10 transition">-</button>
                            <input type="number" id="qty" name="quantity" value="1" min="1" class="w-16 text-center bg-transparent text-white font-bold focus:outline-none" readonly>
                            <button type="button" onclick="document.getElementById('qty').value++;" class="px-4 py-2 text-white hover:bg-white/10 transition">+</button>
                        </div>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit" name="add_cart" value="1" class="flex-1 border border-white/20 text-white hover:bg-[#1f2833] py-4 text-sm font-bold tracking-widest transition rounded-sm">
                            加入購物車
                        </button>
                        <button type="submit" name="buy_now" value="1" class="flex-1 bg-yellow-500 text-gray-900 hover:bg-yellow-400 py-4 text-sm font-bold tracking-widest transition rounded-sm">
                            立即結帳
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>