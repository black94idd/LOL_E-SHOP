<?php
require 'db.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// 1. 撈取商品資料與決定當前介面 (teamId)
if ($search !== '') {
    // [搜尋模式] 全域搜尋：不限制 team_id
    $stmt = $pdo->prepare('
        SELECT p.id, p.name, p.description, p.base_price, p.image_url, t.name AS team_name, p.team_id 
        FROM products p JOIN teams t ON p.team_id = t.id 
        WHERE p.name LIKE ? OR p.description LIKE ?
    ');
    $stmt->execute(["%$search%", "%$search%"]);
    $products = $stmt->fetchAll();

    // 如果有搜尋到商品，自動將介面 (teamId) 切換為第一筆商品的戰隊
    if (count($products) > 0) {
        $teamId = (int)$products[0]['team_id'];
    } else {
        // 若找不到，保留在原原本的戰隊介面
        $teamId = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 2;
    }
} else {
    // [一般模式] 依照網址的 team_id 撈取該戰隊商品 (預設為 2: T1)
    $teamId = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 2;
    $stmt = $pdo->prepare('
        SELECT p.id, p.name, p.description, p.base_price, p.image_url, t.name AS team_name, p.team_id 
        FROM products p JOIN teams t ON p.team_id = t.id 
        WHERE p.team_id = ?
    ');
    $stmt->execute([$teamId]);
    $products = $stmt->fetchAll();
}

// 2. 撈取當前登入使用者的購物車總數量
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmtCount = $pdo->prepare('SELECT SUM(quantity) FROM added_to WHERE user_id = ?');
    $stmtCount->execute([$userId]);
    $cartCount = (int)$stmtCount->fetchColumn();
}

// 3. 定義各戰隊的官方風格橫幅資訊
$banners = [
    1 => [
        'img' => 'banner_cfo.jpg',
        'sub' => 'PACIFIC CHAMPIONSHIP SERIES',
        'title' => 'CFO 2025 LCP LATEST GEAR',
    ],
    2 => [
        'img' => 'banner_t1.jpg',
        'sub' => 'CHAMPIONS ALIVE',
        'title' => 'T1 2025 OFFICIAL UNIFORM',
    ],
    3 => [
        'img' => 'banner_blg.webp',
        'sub' => 'LPL FIRST TO DEFY',
        'title' => 'BLG 2026 SPRING COLLECTION',
    ]
];
$currentBanner = $banners[$teamId] ?? $banners[2];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoL E-Shop | Official Style</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;800&family=Noto+Sans+TC:wght@400;700&display=swap');
        .font-tech { font-family: 'Orbitron', 'Noto Sans TC', sans-serif; }
    </style>
</head>
<body class="bg-[#0b0c10] text-[#rgba(255,255,255,0.9)] min-h-screen font-tech selection:bg-red-600 selection:text-white">

    <div class="fixed bottom-4 left-4 bg-red-600/90 text-white text-[10px] font-bold px-3 py-1.5 tracking-widest rounded uppercase z-50 backdrop-blur-sm border border-red-500">
        NON-OFFICIAL PROJECT / FOR ACADEMIC USE ONLY
    </div>

    <nav class="sticky top-0 w-full z-40 bg-[#0b0c10]/75 backdrop-blur-md border-b border-white/5 px-6 py-4 flex justify-between items-center transition-all duration-300">
        
        <div class="flex items-center space-x-12">
            <a href="index.php" class="text-xl font-black tracking-tighter text-white hover:text-red-500 transition">
                LOL <span class="text-red-500">E-SHOP</span>
            </a>
            <div class="hidden lg:flex space-x-8 text-xs font-bold tracking-widest">
                <a href="?team_id=2" class="relative py-1 transition <?= $teamId === 2 ? 'text-red-500 after:absolute after:bottom-0 after:left-0 after:w-full after:h-[2px] after:bg-red-500' : 'text-gray-400 hover:text-white' ?>">LCK (T1)</a>
                <a href="?team_id=3" class="relative py-1 transition <?= $teamId === 3 ? 'text-red-500 after:absolute after:bottom-0 after:left-0 after:w-full after:h-[2px] after:bg-red-500' : 'text-gray-400 hover:text-white' ?>">LPL (BLG)</a>
                <a href="?team_id=1" class="relative py-1 transition <?= $teamId === 1 ? 'text-red-500 after:absolute after:bottom-0 after:left-0 after:w-full after:h-[2px] after:bg-red-500' : 'text-gray-400 hover:text-white' ?>">LCP (CFO)</a>
            </div>
        </div>
        
        <div class="flex items-center space-x-4 md:space-x-6 text-xs font-bold tracking-widest">
            
            <form action="index.php" method="GET" class="hidden md:flex items-center">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="SEARCH..." 
                       class="bg-[#1f2833]/50 border border-white/20 text-white text-[10px] tracking-widest px-3 py-1.5 focus:outline-none focus:border-red-500 w-32 xl:w-48 transition-all rounded-l-sm placeholder-gray-500">
                <button type="submit" class="bg-white/10 hover:bg-red-600 text-white border border-l-0 border-white/20 px-3 py-1.5 rounded-r-sm transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </button>
            </form>

            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="text-gray-400 hidden xl:inline">MEMBER // <span class="text-white"><?= htmlspecialchars($_SESSION['username']) ?></span></span>
                
                <a href="cart.php" class="relative text-gray-300 hover:text-white transition p-1" title="VIEW CART">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[9px] font-black rounded-full h-4 w-4 flex items-center justify-center animate-pulse">
                            <?= $cartCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="admin.php" class="text-red-400 hover:text-red-300 transition border border-red-500/30 px-3 py-1.5 rounded bg-red-900/20">ADMIN PANEL</a>
<?php endif; ?>
<a href="auth.php?logout=1" class="text-gray-500 hover:text-red-500 transition border border-white/10 px-3 py-1.5 rounded bg-white/5">LOGOUT</a>
            <?php else: ?>
                <a href="auth.php" class="text-white hover:text-red-500 transition underline underline-offset-4">LOGIN / REGISTER</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="relative w-full h-[50vh] md:h-[65vh] bg-black overflow-hidden group border-b border-white/5">
        <img src="images/<?= htmlspecialchars($currentBanner['img']) ?>" 
             alt="<?= htmlspecialchars($currentBanner['title']) ?>" 
             class="w-full h-full object-contain opacity-40 scale-105 group-hover:scale-100 transition-transform duration-1000 ease-out">
        <div class="absolute inset-0 bg-gradient-to-t from-[#0b0c10] via-transparent to-[#0b0c10]/50"></div>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-4">
            <span class="text-red-500 text-xs md:text-sm font-black tracking-[0.4em] mb-3 uppercase animate-fade-in">
                <?= htmlspecialchars($currentBanner['sub']) ?>
            </span>
            <h2 class="text-3xl md:text-6xl font-black text-white tracking-tighter max-w-4xl uppercase drop-shadow-2xl">
                <?= htmlspecialchars($currentBanner['title']) ?>
            </h2>
            <div class="w-12 h-[3px] bg-red-500 mt-6"></div>
        </div>
    </div>

    <main class="container mx-auto px-6 py-16">
        
        <div class="flex justify-between items-end mb-12 border-b border-white/5 pb-4">
            <h3 class="text-lg font-bold tracking-widest text-white uppercase">
                EXCLUSIVE GEARS // <span class="text-gray-500 text-sm font-medium">
                    <?= $search !== '' ? 'SEARCH RESULTS' : 'SHOWING ALL ITEMS' ?>
                </span>
            </h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
            <?php if (empty($products)): ?>
                <div class="col-span-full text-center py-20 text-gray-500 tracking-widest font-bold">
                    [ NO PRODUCTS FOUND MATCHING "<?= htmlspecialchars($search) ?>" ]
                </div>
            <?php else: ?>
                <?php foreach ($products as $index => $product): ?>
                    <a href="product.php?id=<?= $product['id'] ?>" class="group flex flex-col bg-transparent overflow-hidden cursor-pointer block">
                        <div class="relative aspect-[4/5] w-full overflow-hidden bg-[#1f2833]/20 border border-white/5 rounded-sm mb-4">
                            <span class="absolute top-3 left-3 bg-red-600 text-white text-[9px] font-black px-2 py-1 tracking-widest uppercase z-10">
                                <?= $index === 0 ? 'PRE-ORDER' : 'NEW' ?>
                            </span>
                            <img src="images/<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500 ease-out">
                            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>
                        
                        <div class="flex flex-col flex-grow">
                            <span class="text-red-500 text-[10px] font-bold tracking-widest uppercase mb-1">
                                <?= htmlspecialchars($product['team_name']) ?> // OFFICIAL
                            </span>
                            <h4 class="text-white text-base font-bold tracking-tight mb-1 group-hover:text-red-400 transition-colors duration-300">
                                <?= htmlspecialchars($product['name']) ?>
                            </h4>
                            <p class="text-gray-400 text-xs font-light line-clamp-2 mb-4 leading-relaxed">
                                <?= htmlspecialchars($product['description']) ?>
                            </p>
                            
                            <div class="flex justify-between items-center mt-auto pt-2 border-t border-white/5">
                                <span class="text-lg font-extrabold text-white">
                                    NT$ <?= number_format($product['base_price']) ?>
                                </span>
                                <span class="text-gray-500 group-hover:text-white text-[10px] font-bold tracking-widest transition-colors uppercase">
                                    VIEW DETAILS
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="w-full border-t border-white/5 py-8 text-center text-xs text-gray-600 tracking-widest">
        &copy; 2026 LOL E-SHOP ACADEMIC PROJECT. ALL RIGHTS RESERVED.
    </footer>

</body>
</html>