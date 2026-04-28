<?php 
require 'db.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");

$user_id = $_SESSION['user_id'];

// Get incoming trade offers with proper validation
$offers = $conn->prepare("
    SELECT trades.*, items.title as item_title, items.id as item_id, users.username as from_username 
    FROM trades 
    JOIN items ON trades.item1_id = items.id 
    JOIN users ON trades.from_user = users.id 
    WHERE trades.to_user = ? AND trades.status = 'pending'
");
$offers->bind_param("i", $user_id);
$offers->execute();
$offers = $offers->get_result();

// Get counter offers
$counter_offers = $conn->prepare("
    SELECT offers.*, trades.item1_id, trades.to_user as original_to_user, items.title as item_title, users.username as from_username 
    FROM offers
    JOIN trades ON offers.trade_id = trades.id
    JOIN items ON trades.item1_id = items.id
    JOIN users ON offers.from_user = users.id
    WHERE offers.to_user = ? AND offers.status = 'pending'
");
$counter_offers->bind_param("i", $user_id);
$counter_offers->execute();
$counter_offers = $counter_offers->get_result();

// Get user's active listings
$listings = $conn->prepare("SELECT * FROM items WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC");
$listings->bind_param("i", $user_id);
$listings->execute();
$listings = $listings->get_result();

// Get stats
$total_listings_stmt = $conn->prepare("SELECT COUNT(*) as count FROM items WHERE user_id = ? AND status = 'active'");
$total_listings_stmt->bind_param("i", $user_id);
$total_listings_stmt->execute();
$total_listings = $total_listings_stmt->get_result()->fetch_assoc()['count'];

$total_trades_stmt = $conn->prepare("SELECT COUNT(*) as count FROM trades WHERE from_user = ? OR to_user = ?");
$total_trades_stmt->bind_param("ii", $user_id, $user_id);
$total_trades_stmt->execute();
$total_trades = $total_trades_stmt->get_result()->fetch_assoc()['count'];

$total_coins = $_SESSION['coins'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Mythic Market</title>
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
        .search-box button { background: transparent; border: none; color: #94a3b8; cursor: pointer; }
        .coin-badge-header { background: rgba(245, 158, 11, 0.2); padding: 8px 18px; border-radius: 40px; color: #fbbf24; font-weight: 600; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); border: 1px solid rgba(71, 85, 105, 0.3); border-radius: 24px; padding: 20px; }
        .stat-title { color: #94a3b8; font-size: 14px; margin-bottom: 12px; }
        .stat-number { font-size: 32px; font-weight: 700; }
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); border: 1px solid rgba(71, 85, 105, 0.3); border-radius: 24px; padding: 24px; }
        .glass-card h2 { font-size: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .offer-item, .listing-item { border-bottom: 1px solid rgba(71, 85, 105, 0.3); padding: 16px 0; }
        .offer-item:last-child, .listing-item:last-child { border-bottom: none; }
        .badge { padding: 4px 12px; border-radius: 40px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-sell { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .badge-swap { background: rgba(168, 85, 247, 0.2); color: #c084fc; }
        .badge-barter { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .btn-sm { background: rgba(255,255,255,0.08); border: none; padding: 6px 14px; border-radius: 40px; color: white; cursor: pointer; font-size: 12px; margin-right: 8px; text-decoration: none; display: inline-block; transition: all 0.2s; }
        .btn-sm:hover { background: #3b82f6; transform: translateY(-1px); }
        .btn-danger { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .btn-danger:hover { background: #ef4444; color: white; }
        .empty-state { text-align: center; padding: 40px; color: #94a3b8; }
        .alert { padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; }
        .success { background: rgba(34,197,94,0.2); color: #4ade80; }
        .error { background: rgba(239,68,68,0.2); color: #f87171; }
        a { text-decoration: none; }
        .counter-badge { background: rgba(245,158,11,0.2); color: #fbbf24; font-size: 11px; margin-left: 8px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-area">
        <div class="logo-icon"><i class="fas fa-landmark"></i></div>
        <div class="logo-text">Mythic Market</div>
    </div>
    <a href="index.php" class="nav-item"><i class="fas fa-home"></i> <span>Home</span></a>
    <a href="marketplace.php" class="nav-item"><i class="fas fa-store"></i> <span>Marketplace</span></a>
    <a href="dashboard.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
    <a href="inventory.php" class="nav-item"><i class="fas fa-boxes"></i> <span>My Items</span></a>
    <a href="profile.php" class="nav-item"><i class="fas fa-user"></i> <span>Profile</span></a>
    <?php if($_SESSION['role'] == 'admin'): ?>
        <a href="admin.php" class="nav-item"><i class="fas fa-shield-alt"></i> <span>Admin</span></a>
    <?php endif; ?>
    <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    <div class="user-section">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
        <div><div style="font-weight: 600;"><?php echo htmlspecialchars($_SESSION['username']); ?></div><div style="font-size: 12px; color: #94a3b8;">Trader</div></div>
    </div>
</div>

<div class="main-content">
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert success">
            <?php 
            $messages = [
                'accepted' => '✅ Trade accepted! Items marked as traded.',
                'declined' => '❌ Trade declined.',
                'counter_sent' => '🔄 Counter offer sent!',
                'counter_accepted' => '✅ Counter offer accepted! Trade completed.',
                'counter_declined' => '❌ Counter offer declined.',
                'offer_sent' => '📨 Trade offer sent!'
            ];
            echo $messages[$_GET['msg']] ?? 'Action completed successfully.';
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert error">
            <?php 
            $errors = [
                'invalid_trade' => '⚠️ Invalid trade request.',
                'insufficient_coins' => '⚠️ You don\'t have enough coins for this trade.',
                'trade_failed' => '⚠️ Trade failed. Please try again.',
                'counter_not_found' => '⚠️ Counter offer not found.'
            ];
            echo $errors[$_GET['error']] ?? 'An error occurred.';
            ?>
        </div>
    <?php endif; ?>

    <div class="top-bar">
        <form action="marketplace.php" method="GET" style="display: flex; gap: 10px; width: 100%; max-width: 400px;">
            <div class="search-box" style="flex: 1;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search marketplace..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn-sm" style="background: #3b82f6;">Search</button>
        </form>
        <div class="coin-badge-header"><i class="fas fa-coins"></i> <?php echo number_format($total_coins); ?> MC</div>
    </div>

    <h1 style="margin-bottom: 8px;">Dashboard</h1>
    <p style="color: #94a3b8; margin-bottom: 28px;">Manage your trades, offers, and listings</p>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-title"><i class="fas fa-box"></i> Active Listings</div><div class="stat-number"><?php echo $total_listings; ?></div></div>
        <div class="stat-card"><div class="stat-title"><i class="fas fa-handshake"></i> Total Trades</div><div class="stat-number"><?php echo $total_trades; ?></div></div>
        <div class="stat-card"><div class="stat-title"><i class="fas fa-coins"></i> Mythic Coins</div><div class="stat-number"><?php echo number_format($total_coins); ?></div></div>
        <div class="stat-card"><div class="stat-title"><i class="fas fa-star"></i> Rating</div><div class="stat-number"><?php echo $_SESSION['rating_avg'] ?: 'New'; ?></div></div>
    </div>

    <div class="dashboard-grid">
        <!-- Incoming Trade Offers -->
        <div class="glass-card">
            <h2><i class="fas fa-inbox"></i> Incoming Trade Offers</h2>
            <?php if($offers->num_rows == 0 && $counter_offers->num_rows == 0): ?>
                <div class="empty-state"><i class="fas fa-envelope-open-text" style="font-size: 48px; opacity: 0.5;"></i><p>No pending offers</p></div>
            <?php else: ?>
                <?php while($offer = $offers->fetch_assoc()): ?>
                    <div class="offer-item">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <strong><?php echo htmlspecialchars($offer['from_username']); ?></strong>
                                <span class="badge badge-<?php echo $offer['type']; ?>"><?php echo ucfirst($offer['type']); ?></span>
                                <p style="margin-top: 8px;">Wants: <strong><?php echo htmlspecialchars($offer['item_title']); ?></strong></p>
                                <?php if($offer['coin_diff'] > 0): ?>
                                    <p style="color: #fbbf24;">💰 + <?php echo $offer['coin_diff']; ?> MC</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top: 12px;">
                            <a href="trade_handler.php?accept=<?php echo $offer['id']; ?>" class="btn-sm" style="background: #10b981;" onclick="return confirm('Accept this trade offer?')">Accept</a>
                            <a href="trade_handler.php?decline=<?php echo $offer['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Decline this trade offer?')">Decline</a>
                            <a href="trade_handler.php?counter=<?php echo $offer['id']; ?>" class="btn-sm">Counter</a>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <?php while($counter = $counter_offers->fetch_assoc()): ?>
                    <div class="offer-item" style="border-left: 3px solid #fbbf24; padding-left: 12px;">
                        <div>
                            <strong><?php echo htmlspecialchars($counter['from_username']); ?></strong>
                            <span class="badge counter-badge">🔄 COUNTER</span>
                            <p style="margin-top: 8px;">For: <strong><?php echo htmlspecialchars($counter['item_title']); ?></strong></p>
                            <?php if($counter['offered_coins'] > 0): ?>
                                <p style="color: #fbbf24;">💰 + <?php echo $counter['offered_coins']; ?> MC</p>
                            <?php endif; ?>
                            <?php if($counter['message']): ?>
                                <p style="color: #94a3b8; font-size: 12px; margin-top: 5px;">💬 "<?php echo htmlspecialchars($counter['message']); ?>"</p>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 12px;">
                            <a href="trade_handler.php?accept_counter=<?php echo $counter['id']; ?>&trade_id=<?php echo $counter['trade_id']; ?>" class="btn-sm" style="background: #10b981;" onclick="return confirm('Accept this counter offer?')">Accept</a>
                            <a href="trade_handler.php?decline_counter=<?php echo $counter['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Decline this counter offer?')">Decline</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Your Active Listings -->
        <div class="glass-card">
            <h2><i class="fas fa-list-ul"></i> Your Active Listings</h2>
            <?php if($listings->num_rows == 0): ?>
                <div class="empty-state"><i class="fas fa-box-open" style="font-size: 48px; opacity: 0.5;"></i><p>No active listings</p><a href="inventory.php" class="btn-sm" style="margin-top: 12px; display: inline-block;">+ Create Item</a></div>
            <?php else: ?>
                <?php while($item = $listings->fetch_assoc()): ?>
                    <div class="listing-item">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div><strong><?php echo htmlspecialchars($item['title']); ?></strong><span class="badge badge-<?php echo $item['type']; ?>"><?php echo ucfirst($item['type']); ?></span><?php if($item['price']): ?><p style="color: #fbbf24; font-size: 14px;">💰 <?php echo $item['price']; ?> MC</p><?php endif; ?></div>
                            <a href="trade_handler.php?delete_item=<?php echo $item['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Remove this listing?')">Remove</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmAction(msg) { return confirm(msg); }
</script>
</body>
</html>