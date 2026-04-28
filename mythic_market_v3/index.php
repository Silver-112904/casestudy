<?php require 'db.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mythic Market | Trade. Barter. Sell.</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0a0f1e 0%, #0c1222 100%);
            min-height: 100vh;
            color: #eef2ff;
            display: flex;
        }

        /* ========= SIDEBAR ========= */
        .sidebar {
            width: 280px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(71, 85, 105, 0.4);
            padding: 28px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(71, 85, 105, 0.3);
        }

        .logo-icon {
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .logo-text {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 10px;
            color: #cbd5e1;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
        }

        .nav-item i {
            width: 24px;
            font-size: 1.2rem;
        }

        .nav-item.active {
            background: rgba(59, 130, 246, 0.2);
            color: white;
            border-left: 3px solid #3b82f6;
        }

        .nav-item:hover:not(.active) {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .user-section {
            margin-top: auto;
            padding-top: 30px;
            border-top: 1px solid rgba(71, 85, 105, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* ========= MAIN CONTENT ========= */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 24px 32px;
        }

        /* top bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(8px);
            padding: 14px 24px;
            border-radius: 28px;
            margin-bottom: 32px;
            border: 1px solid rgba(71, 85, 105, 0.3);
        }

        .search-box {
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 18px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 300px;
        }

        .search-box input {
            background: transparent;
            border: none;
            outline: none;
            color: white;
            width: 100%;
        }

        .search-box input::placeholder {
            color: #94a3b8;
        }

        .auth-buttons {
            display: flex;
            gap: 12px;
        }

        .btn-login {
            background: transparent;
            border: 1px solid rgba(59, 130, 246, 0.5);
            padding: 8px 20px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-login:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: #3b82f6;
        }

        .btn-register {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            padding: 8px 20px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
            font-weight: 600;
        }

        .coin-badge-header {
            background: rgba(245, 158, 11, 0.2);
            padding: 8px 18px;
            border-radius: 40px;
            color: #fbbf24;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(6,182,212,0.05));
            border-radius: 32px;
            padding: 48px;
            margin-bottom: 48px;
            text-align: center;
            border: 1px solid rgba(71, 85, 105, 0.3);
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #fff, #94a3b8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero p {
            color: #94a3b8;
            font-size: 18px;
            margin-bottom: 32px;
        }

        .stats-banner {
            display: flex;
            justify-content: center;
            gap: 48px;
            margin-top: 32px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number-large {
            font-size: 32px;
            font-weight: 700;
            color: #3b82f6;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 14px;
        }

        /* Section headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-header h2 {
            font-size: 24px;
        }

        .section-header a {
            color: #3b82f6;
            text-decoration: none;
        }

        /* Card grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(71, 85, 105, 0.3);
            border-radius: 24px;
            padding: 20px;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .glass-card:hover {
            transform: translateY(-4px);
            border-color: rgba(59, 130, 246, 0.5);
        }

        .card-image-placeholder {
            width: 100%;
            height: 160px;
            background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(6,182,212,0.05));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .card-image-placeholder i {
            font-size: 48px;
            color: #3b82f6;
            opacity: 0.5;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-sell { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .badge-swap { background: rgba(168, 85, 247, 0.2); color: #c084fc; }
        .badge-barter { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }

        .price {
            color: #fbbf24;
            font-weight: 600;
        }

        .user-small {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            padding: 10px 28px;
            border-radius: 40px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        /* Category Pills */
        .category-pills {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .pill {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(71, 85, 105, 0.3);
            padding: 8px 20px;
            border-radius: 40px;
            text-decoration: none;
            color: #cbd5e1;
            transition: 0.2s;
        }

        .pill:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: #3b82f6;
            color: white;
        }

        hr {
            border-color: rgba(71, 85, 105, 0.2);
            margin: 32px 0;
        }

        .footer-text {
            text-align: center;
            color: #5b6e8c;
            font-size: 13px;
            padding: 24px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-area">
        <div class="logo-icon"><i class="fas fa-landmark"></i></div>
        <div class="logo-text">Mythic Market</div>
    </div>
    <a href="index.php" class="nav-item active">
        <i class="fas fa-home"></i> <span>Home</span>
    </a>
    <a href="marketplace.php" class="nav-item">
        <i class="fas fa-store"></i> <span>Marketplace</span>
    </a>
    <?php if(isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>
        <a href="inventory.php" class="nav-item">
            <i class="fas fa-boxes"></i> <span>My Items</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i> <span>Profile</span>
        </a>
        <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="admin.php" class="nav-item">
                <i class="fas fa-shield-alt"></i> <span>Admin</span>
            </a>
        <?php endif; ?>
        <a href="logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
        <div class="user-section">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
            <div>
                <div style="font-weight: 600;"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div style="font-size: 12px; color: #94a3b8;">Trader</div>
            </div>
        </div>
    <?php else: ?>
        <div style="margin-top: auto;">
            <a href="login.php" class="nav-item">
                <i class="fas fa-sign-in-alt"></i> <span>Login</span>
            </a>
            <a href="register.php" class="nav-item">
                <i class="fas fa-user-plus"></i> <span>Register</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="main-content">
    <div class="top-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search items..." id="globalSearch" onkeypress="if(event.key=='Enter') window.location='marketplace.php?search='+this.value">
        </div>
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="coin-badge-header">
                <i class="fas fa-coins"></i> <?php echo number_format($_SESSION['coins']); ?> MC
            </div>
        <?php else: ?>
            <div class="auth-buttons">
                <a href="login.php" class="btn-login">Login</a>
                <a href="register.php" class="btn-register">Register</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Hero Section -->
    <div class="hero">
        <h1>Welcome to Mythic Market</h1>
        <p>Trade, swap, and barter with Mythic Coins — zero fees, full control.</p>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <a href="register.php" class="btn-primary">Start Trading Now <i class="fas fa-arrow-right"></i></a>
        <?php else: ?>
            <a href="marketplace.php" class="btn-primary">Explore Marketplace <i class="fas fa-store"></i></a>
        <?php endif; ?>
        
        <div class="stats-banner">
            <?php
            $total_items = $conn->query("SELECT COUNT(*) as count FROM items WHERE status='active'")->fetch_assoc()['count'];
            $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
            $total_trades = $conn->query("SELECT COUNT(*) as count FROM trades WHERE status='completed'")->fetch_assoc()['count'];
            ?>
            <div class="stat-item">
                <div class="stat-number-large"><?php echo $total_items; ?>+</div>
                <div class="stat-label">Active Items</div>
            </div>
            <div class="stat-item">
                <div class="stat-number-large"><?php echo $total_users; ?>+</div>
                <div class="stat-label">Traders</div>
            </div>
            <div class="stat-item">
                <div class="stat-number-large"><?php echo $total_trades; ?>+</div>
                <div class="stat-label">Completed Trades</div>
            </div>
        </div>
    </div>

    <!-- Category Pills -->
    <div class="category-pills">
        <a href="marketplace.php" class="pill">All</a>
        <?php
        $cats = $conn->query("SELECT * FROM categories LIMIT 6");
        while($cat = $cats->fetch_assoc()):
        ?>
            <a href="marketplace.php?category=<?php echo $cat['id']; ?>" class="pill"><?php echo $cat['name']; ?></a>
        <?php endwhile; ?>
    </div>

    <!-- Recent Listings Section -->
    <div class="section-header">
        <h2><i class="fas fa-clock"></i> Recent Listings</h2>
        <a href="marketplace.php">View all <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="grid">
        <?php
        $recent = $conn->query("
            SELECT items.*, users.username, users.rating_avg 
            FROM items 
            JOIN users ON items.user_id = users.id 
            WHERE items.status = 'active' 
            ORDER BY items.created_at DESC 
            LIMIT 6
        ");
        
        if($recent->num_rows == 0):
        ?>
            <div class="glass-card" style="grid-column: 1/-1; text-align: center;">
                <i class="fas fa-box-open" style="font-size: 48px; opacity: 0.5; margin-bottom: 16px; display: block;"></i>
                <p>No listings yet. <a href="inventory.php">Be the first to list an item!</a></p>
            </div>
        <?php else: ?>
            <?php while($item = $recent->fetch_assoc()): ?>
                <a href="marketplace.php?item=<?php echo $item['id']; ?>" class="glass-card">
                    <div class="card-image-placeholder">
                        <?php if($item['image']): ?>
                            <img src="<?php echo $item['image']; ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 16px;">
                        <?php else: ?>
                            <i class="fas fa-gem"></i>
                        <?php endif; ?>
                    </div>
                    <div class="card-title"><?php echo htmlspecialchars($item['title']); ?></div>
                    <div class="card-meta">
                        <span class="badge badge-<?php echo $item['type']; ?>"><?php echo ucfirst($item['type']); ?></span>
                        <span class="price"><?php echo $item['price'] ? $item['price'] . ' MC' : 'Offer only'; ?></span>
                    </div>
                    <div class="user-small">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['username']); ?>
                        <?php if($item['rating_avg'] > 0): ?>
                             <?php echo $item['rating_avg']; ?>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Featured / Trending Section -->
    <div class="section-header">
        <h2><i class="fas fa-fire"></i> Trending Items</h2>
    </div>

    <div class="grid">
        <?php
        $trending = $conn->query("
            SELECT items.*, users.username, users.rating_avg 
            FROM items 
            JOIN users ON items.user_id = users.id 
            WHERE items.status = 'active' 
            ORDER BY items.id DESC 
            LIMIT 3
        ");
        
        while($item = $trending->fetch_assoc()):
        ?>
            <a href="marketplace.php?item=<?php echo $item['id']; ?>" class="glass-card">
                <div class="card-image-placeholder">
                    <i class="fas fa-crown" style="color: #fbbf24;"></i>
                </div>
                <div class="card-title"><?php echo htmlspecialchars($item['title']); ?></div>
                <div class="card-meta">
                    <span class="badge badge-<?php echo $item['type']; ?>"><?php echo ucfirst($item['type']); ?></span>
                    <span class="price"><?php echo $item['price'] ? $item['price'] . ' MC' : 'Offer only'; ?></span>
                </div>
                <div class="user-small">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['username']); ?>
                </div>
            </a>
        <?php endwhile; ?>
    </div>

    <hr>

    <div class="footer-text">
        <i class="fas fa-shield-alt"></i> Secure Escrow • Dispute Resolution • Zero Fees
    </div>
</div>

</body>
</html>