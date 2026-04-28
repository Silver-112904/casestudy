<?php require 'db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') header("Location: index.php");

// Handle admin actions
if(isset($_GET['ban_user'])) {
    $conn->query("UPDATE users SET role = 'banned' WHERE id = {$_GET['ban_user']}");
}
if(isset($_GET['delete_item'])) {
    $conn->query("DELETE FROM items WHERE id = {$_GET['delete_item']}");
}

$users = $conn->query("SELECT * FROM users");
$items = $conn->query("SELECT items.*, users.username FROM items JOIN users ON items.user_id = users.id");
$reports = $conn->query("SELECT reports.*, items.title, users.username FROM reports JOIN items ON reports.item_id = items.id JOIN users ON reports.reporter_id = users.id WHERE reports.status = 'pending'");
?>

<!DOCTYPE html>
<html>
<head><title>Admin Panel | Mythic Market</title><link rel="stylesheet" href="style.css"></head>
<body>
<nav class="navbar">...</nav>

<div class="container">
    <a href="javascript:history.back()" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>
    <h1>⚙️ Admin Panel</h1>
    
    <div class="grid">
        <div class="glass-card">
            <h2>👥 Manage Users</h2>
            <?php while($user = $users->fetch_assoc()): ?>
                <div style="padding: 8px 0;">
                    <?php echo htmlspecialchars($user['username']); ?> (<?php echo $user['role']; ?>)
                    <?php if($user['role'] != 'admin'): ?>
                        <a href="?ban_user=<?php echo $user['id']; ?>" style="color: #ef4444;">Ban</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="glass-card">
            <h2>📦 Manage Items</h2>
            <?php while($item = $items->fetch_assoc()): ?>
                <div style="padding: 8px 0;">
                    <?php echo htmlspecialchars($item['title']); ?> by <?php echo $item['username']; ?>
                    <a href="?delete_item=<?php echo $item['id']; ?>" style="color: #ef4444;">Delete</a>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="glass-card">
            <h2>⚠️ Pending Reports</h2>
            <?php while($report = $reports->fetch_assoc()): ?>
                <div style="padding: 8px 0;">
                    🚨 Item "<?php echo $report['title']; ?>" reported by <?php echo $report['username']; ?>
                    <p><?php echo $report['reason']; ?></p>
                    <a href="?delete_item=<?php echo $report['item_id']; ?>" class="btn">Remove Item</a>
                    <a href="?dismiss_report=<?php echo $report['id']; ?>" class="btn">Dismiss</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
</body>
</html>