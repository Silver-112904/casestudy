<?php 
require 'db.php';

// Build WHERE clause with prepared statements
$where = "items.status = 'active'";
$params = [];
$types = "";

if(isset($_GET['type']) && $_GET['type'] != 'all' && $_GET['type'] != '') {
    $where .= " AND items.type = ?";
    $params[] = $_GET['type'];
    $types .= "s";
}
if(isset($_GET['rarity']) && $_GET['rarity'] != 'all' && $_GET['rarity'] != '') {
    $where .= " AND items.rarity = ?";
    $params[] = $_GET['rarity'];
    $types .= "s";
}
if(isset($_GET['category']) && $_GET['category'] != 'all' && $_GET['category'] != '') {
    $where .= " AND items.category_id = ?";
    $params[] = (int)$_GET['category'];
    $types .= "i";
}
if(isset($_GET['search']) && $_GET['search'] != '') {
    $search = "%{$_GET['search']}%";
    $where .= " AND (items.title LIKE ? OR items.description LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

$query = "SELECT items.*, users.username, users.rating_avg FROM items JOIN users ON items.user_id = users.id WHERE $where ORDER BY items.created_at DESC";

$stmt = $conn->prepare($query);
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result();

$categories = $conn->query("SELECT * FROM categories");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace | Mythic Market</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #0a0f1e 0%, #0c1222 100%); min-height: 100vh; color: #eef2ff; display: flex; }
        .sidebar { width: 280px; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(12px); border-right: 1px solid rgba(71, 85, 105, 0.4); padding: 28px 20px; display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; }
        .logo-area { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid rgba(71, 85, 105, 0.3); }
        .logo-icon { background: linear-gradient(135deg, #3b82f6, #06b6d4); width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .logo-text { font-size: 22px; font-weight: 700; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .nav-item { display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-radius: 14px; margin-bottom: 10px; color: #cbd5e1; transition: all 0.2s; cursor: pointer; text-decoration: none; }
        .nav-item i { width: 24px; font-size: 1.2rem; }
        .nav-item.active { background: rgba(59, 130, 246, 0.2); color: white; border-left: 3px solid #3b82f6; }
        .nav-item:hover:not(.active) { background: rgba(255, 255, 255, 0.05); color: white; }
        .user-section { margin-top: auto; padding-top: 30px; border-top: 1px solid rgba(71, 85, 105, 0.3); display: flex; align-items: center; gap: 12px; }
        .avatar { width: 44px; height: 44px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .main-content { margin-left: 280px; flex: 1; padding: 24px 32px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(8px); padding: 14px 24px; border-radius: 28px; margin-bottom: 32px; border: 1px solid rgba(71, 85, 105, 0.3); }
        .search-box { background: rgba(0, 0, 0, 0.3); padding: 8px 18px; border-radius: 40px; display: flex; align-items: center; gap: 10px; width: 350px; }
        .search-box input { background: transparent; border: none; outline: none; color: white; width: 100%; }
        .coin-badge-header { background: rgba(245, 158, 11, 0.2); padding: 8px 18px; border-radius: 40px; color: #fbbf24; font-weight: 600; }
        .back-button { display: inline-flex; align-items: center; gap: 8px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(71, 85, 105, 0.3); padding: 8px 20px; border-radius: 40px; color: #cbd5e1; text-decoration: none; font-size: 14px; margin-bottom: 24px; transition: all 0.2s; width: fit-content; }
        .back-button:hover { background: rgba(59, 130, 246, 0.2); border-color: #3b82f6; color: white; transform: translateX(-4px); }
        .filter-section { background: rgba(15, 23, 42, 0.4); border-radius: 24px; padding: 20px; margin-bottom: 32px; }
        .filter-form { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 150px; }
        .filter-group label { display: block; font-size: 12px; color: #94a3b8; margin-bottom: 6px; }
        .filter-group select, .filter-group input { background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(71, 85, 105, 0.4); padding: 10px 14px; border-radius: 12px; color: white; width: 100%; }
        .btn-filter { background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; padding: 10px 24px; border-radius: 40px; color: white; font-weight: 600; cursor: pointer; }
        .btn-filter:hover { transform: translateY(-2px); }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); border: 1px solid rgba(71, 85, 105, 0.3); border-radius: 24px; padding: 20px; transition: all 0.2s; text-decoration: none; color: inherit; display: block; }
        .glass-card:hover { transform: translateY(-4px); border-color: rgba(59, 130, 246, 0.5); }
        .card-image { width: 100%; height: 160px; background: rgba(59,130,246,0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; overflow: hidden; }
        .card-image img { width: 100%; height: 100%; object-fit: cover; }
        .card-image i { font-size: 48px; color: #3b82f6; opacity: 0.5; }
        .badge { padding: 4px 12px; border-radius: 40px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-sell { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .badge-swap { background: rgba(168, 85, 247, 0.2); color: #c084fc; }
        .badge-barter { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .price { color: #fbbf24; font-weight: 600; }
        .empty-state { text-align: center; padding: 60px; color: #94a3b8; }
        h1 { margin-bottom: 8px; }
        .subhead { color: #94a3b8; margin-bottom: 24px; }
        .search-button { background: #3b82f6; border: none; padding: 8px 20px; border-radius: 40px; color: white; cursor: pointer; margin-left: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-area"><div class="logo-icon"><i class="fas fa-landmark"></i></div><div class="logo-text">Mythic Market</div></div>
    <a href="index.php" class="nav-item"><i class="fas fa-home"></i> <span>Home</span></a>
    <a href="marketplace.php" class="nav-item active"><i class="fas fa-store"></i> <span>Marketplace</span></a>
    <?php if(isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="inventory.php" class="nav-item"><i class="fas fa-boxes"></i> <span>My Items</span></a>
        <a href="profile.php" class="nav-item"><i class="fas fa-user"></i> <span>Profile</span></a>
        <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="admin.php" class="nav-item"><i class="fas fa-shield-alt"></i> <span>Admin</span></a>
        <?php endif; ?>
        <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        <div class="user-section"><div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div><div><div style="font-weight: 600;"><?php echo htmlspecialchars($_SESSION['username']); ?></div><div style="font-size: 12px; color: #94a3b8;">Trader</div></div></div>
    <?php else: ?>
        <div style="margin-top: auto;"><a href="login.php" class="nav-item"><i class="fas fa-sign-in-alt"></i> <span>Login</span></a><a href="register.php" class="nav-item"><i class="fas fa-user-plus"></i> <span>Register</span></a></div>
    <?php endif; ?>
</div>

<div class="main-content">
    <div class="top-bar">
        <form action="marketplace.php" method="GET" style="display: flex; gap: 10px; align-items: center;">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn-filter" style="padding: 8px 20px;"><i class="fas fa-search"></i> Search</button>
        </form>
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="coin-badge-header"><i class="fas fa-coins"></i> <?php echo number_format($_SESSION['coins']); ?> MC</div>
        <?php endif; ?>
    </div>

    <a href="javascript:history.back()" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>

    <h1>🏪 Marketplace</h1>
    <p class="subhead">Browse, trade, and barter with the community</p>

    <div class="filter-section">
        <form method="GET" class="filter-form" id="filterForm">
            <div class="filter-group">
                <label>Type</label>
                <select name="type" onchange="this.form.submit()">
                    <option value="all">All Types</option>
                    <option value="sell" <?php echo ($_GET['type'] ?? '') == 'sell' ? 'selected' : ''; ?>>Sell</option>
                    <option value="swap" <?php echo ($_GET['type'] ?? '') == 'swap' ? 'selected' : ''; ?>>Swap</option>
                    <option value="barter" <?php echo ($_GET['type'] ?? '') == 'barter' ? 'selected' : ''; ?>>Barter</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Rarity</label>
                <select name="rarity" onchange="this.form.submit()">
                    <option value="all">All Rarities</option>
                    <option value="Common" <?php echo ($_GET['rarity'] ?? '') == 'Common' ? 'selected' : ''; ?>>Common</option>
                    <option value="Rare" <?php echo ($_GET['rarity'] ?? '') == 'Rare' ? 'selected' : ''; ?>>Rare</option>
                    <option value="Epic" <?php echo ($_GET['rarity'] ?? '') == 'Epic' ? 'selected' : ''; ?>>Epic</option>
                    <option value="Legendary" <?php echo ($_GET['rarity'] ?? '') == 'Legendary' ? 'selected' : ''; ?>>Legendary</option>
                    <option value="Custom" <?php echo ($_GET['rarity'] ?? '') == 'Custom' ? 'selected' : ''; ?>>Custom</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Category</label>
                <select name="category" onchange="this.form.submit()">
                    <option value="all">All Categories</option>
                    <?php 
                    $cats = $conn->query("SELECT * FROM categories"); 
                    while($cat = $cats->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <a href="marketplace.php" class="btn-filter" style="background: rgba(100,116,139,0.3); text-decoration: none; display: inline-block; text-align: center;">Clear</a>
        </form>
    </div>

    <div class="grid">
        <?php if($items->num_rows == 0): ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <i class="fas fa-box-open" style="font-size: 64px; opacity: 0.5; margin-bottom: 16px;"></i>
                <p>No items found matching your criteria.</p>
                <a href="marketplace.php" class="back-button" style="margin-top: 16px;">Clear filters</a>
            </div>
        <?php else: ?>
            <?php while($item = $items->fetch_assoc()): ?>
                <a href="trade_handler.php?initiate=<?php echo $item['id']; ?>" class="glass-card">
                    <div class="card-image">
                        <?php if($item['image'] && file_exists($item['image'])): ?>
                            <img src="<?php echo $item['image']; ?>">
                        <?php else: ?>
                            <i class="fas fa-gem"></i>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin: 12px 0;">
                        <span class="badge badge-<?php echo $item['type']; ?>"><?php echo ucfirst($item['type']); ?></span>
                        <span class="price"><?php echo $item['price'] ? $item['price'] . ' MC' : 'Offer only'; ?></span>
                    </div>
                    <div style="font-size: 13px; color: #94a3b8;">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['username']); ?>
                        <?php if($item['rating_avg'] > 0): ?> ⭐ <?php echo $item['rating_avg']; ?><?php endif; ?>
                    </div>
                    <div style="margin-top: 12px; font-size: 12px;">
                        🏷️ Rarity: <?php echo $item['rarity'] == 'Custom' ? ($item['rarity_custom'] ?: 'Custom') : $item['rarity']; ?>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>